<?php
declare(strict_types=1);

namespace App\Validators;

use App\Core\ValidationException;

/**
 * Validador de entrada. Regras separadas por '|':
 * required, nullable, string, email, int, numeric, bool, date, month,
 * min:N, max:N (tamanho para string, valor para números), in:a,b,c
 */
final class Validator
{
    public static function validate(array $data, array $rules, bool $partial = false): array
    {
        $errors = [];
        $out = [];
        foreach ($rules as $field => $ruleStr) {
            $rs = explode('|', $ruleStr);
            $present = array_key_exists($field, $data);
            if (!$present && $partial) {
                continue;
            }
            $value = $data[$field] ?? null;
            if (is_string($value)) {
                $value = trim($value);
            }
            $empty = $value === null || $value === '';
            if ($empty) {
                if (in_array('required', $rs, true)) {
                    $errors[] = ['field' => $field, 'message' => 'Campo obrigatório.'];
                } elseif ($present) {
                    $out[$field] = null;
                }
                continue;
            }
            try {
                $out[$field] = self::check($field, $value, $rs);
            } catch (\InvalidArgumentException $e) {
                $errors[] = ['field' => $field, 'message' => $e->getMessage()];
            }
        }
        if ($errors) {
            throw new ValidationException($errors);
        }
        return $out;
    }

    private static function check(string $field, mixed $v, array $rs): mixed
    {
        $min = $max = null;
        $in = null;
        $type = 'string';
        foreach ($rs as $r) {
            if (str_starts_with($r, 'min:')) { $min = (float) substr($r, 4); }
            elseif (str_starts_with($r, 'max:')) { $max = (float) substr($r, 4); }
            elseif (str_starts_with($r, 'in:')) { $in = explode(',', substr($r, 3)); }
            elseif (in_array($r, ['string', 'email', 'int', 'numeric', 'bool', 'date', 'month'], true)) { $type = $r; }
        }

        switch ($type) {
            case 'int':
                if (!is_int($v) && !(is_string($v) && preg_match('/^-?\d+$/', $v))) {
                    throw new \InvalidArgumentException('Deve ser um número inteiro.');
                }
                $v = (int) $v;
                break;
            case 'numeric':
                if (is_string($v)) { $v = str_replace(',', '.', $v); }
                if (!is_numeric($v) || is_nan((float) $v) || is_infinite((float) $v)) {
                    throw new \InvalidArgumentException('Deve ser um número.');
                }
                $v = (float) $v;
                break;
            case 'bool':
                $b = filter_var($v, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                if ($b === null) { throw new \InvalidArgumentException('Deve ser verdadeiro ou falso.'); }
                return $b ? 1 : 0;
            case 'date':
                $v = self::date((string) $v, 'Y-m-d', 'Data inválida (use AAAA-MM-DD).');
                break;
            case 'month':
                $s = (string) $v;
                if (preg_match('/^\d{4}-\d{2}$/', $s)) { $s .= '-01'; }
                $v = self::date($s, 'Y-m-d', 'Mês inválido (use AAAA-MM).');
                $v = substr($v, 0, 8) . '01';
                break;
            case 'email':
                if (!is_string($v) || !filter_var($v, FILTER_VALIDATE_EMAIL)) {
                    throw new \InvalidArgumentException('E-mail inválido.');
                }
                $v = mb_strtolower($v);
                break;
            default:
                if (!is_scalar($v)) { throw new \InvalidArgumentException('Valor inválido.'); }
                $v = (string) $v;
        }

        if ($in !== null && !in_array((string) $v, $in, true)) {
            throw new \InvalidArgumentException('Valor inválido. Permitidos: ' . implode(', ', $in) . '.');
        }
        if (is_string($v)) {
            $len = mb_strlen($v);
            if ($min !== null && $len < $min) { throw new \InvalidArgumentException("Mínimo de {$min} caracteres."); }
            if ($max !== null && $len > $max) { throw new \InvalidArgumentException("Máximo de {$max} caracteres."); }
        } elseif (is_int($v) || is_float($v)) {
            if ($min !== null && $v < $min) { throw new \InvalidArgumentException("Valor mínimo: {$min}."); }
            if ($max !== null && $v > $max) { throw new \InvalidArgumentException("Valor máximo: {$max}."); }
        }
        return $v;
    }

    private static function date(string $s, string $fmt, string $msg): string
    {
        $d = \DateTimeImmutable::createFromFormat('!' . $fmt, $s);
        if (!$d || $d->format($fmt) !== $s) {
            throw new \InvalidArgumentException($msg);
        }
        return $s;
    }
}
