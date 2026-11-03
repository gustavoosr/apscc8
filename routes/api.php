<?php
declare(strict_types=1);

use App\Controllers\{AuthController, UserController, OrganizationController, SectorController,
    WaterConsumptionController, EnergyConsumptionController, WasteController, WasteTypeController,
    WasteDestinationController, ObjectiveController, GoalController, IndicatorController,
    DashboardController, ReportController};
use App\Middleware\{AuthMiddleware, CsrfMiddleware, RoleMiddleware};

/** @var \App\Core\Router $router */
$A = AuthMiddleware::class;
$C = CsrfMiddleware::class;
$ADMIN = RoleMiddleware::class . ':ADMINISTRADOR';
$MANAGER = RoleMiddleware::class . ':GESTOR';

$router->group('/api/v1', [], function ($router) use ($A, $C, $ADMIN, $MANAGER) {

    // ---- Autenticação
    $router->post('/auth/login', [AuthController::class, 'login']);
    $router->post('/auth/password/forgot', [AuthController::class, 'forgotPassword']);
    $router->post('/auth/logout', [AuthController::class, 'logout'], [$A, $C]);
    $router->get('/auth/me', [AuthController::class, 'me'], [$A]);

    // Todas as demais rotas exigem autenticação e CSRF em métodos de escrita.
    $router->group('', [$A, $C], function ($router) use ($ADMIN, $MANAGER) {

        $router->resource('/users', UserController::class, [$ADMIN]);

        // Organizações: leitura/edição por perfil; criar e excluir só ADMINISTRADOR.
        $router->get('/organizations', [OrganizationController::class, 'index']);
        $router->get('/organizations/{id}', [OrganizationController::class, 'show']);
        $router->post('/organizations', [OrganizationController::class, 'store'], [$ADMIN]);
        $router->put('/organizations/{id}', [OrganizationController::class, 'update'], [$MANAGER]);
        $router->patch('/organizations/{id}', [OrganizationController::class, 'update'], [$MANAGER]);
        $router->delete('/organizations/{id}', [OrganizationController::class, 'destroy'], [$ADMIN]);

        $router->resource('/sectors', SectorController::class);

        $router->get('/water-consumption/compare', [WaterConsumptionController::class, 'compare']);
        $router->resource('/water-consumption', WaterConsumptionController::class);
        $router->get('/energy-consumption/compare', [EnergyConsumptionController::class, 'compare']);
        $router->resource('/energy-consumption', EnergyConsumptionController::class);

        $router->get('/waste/summary', [WasteController::class, 'summary']);
        $router->resource('/waste', WasteController::class);

        // Tipos/categorias de resíduos: leitura geral, escrita GESTOR+.
        $router->get('/waste-types/categories', [WasteTypeController::class, 'categories']);
        $router->post('/waste-types/categories', [WasteTypeController::class, 'storeCategory'], [$MANAGER]);
        $router->resource('/waste-types', WasteTypeController::class, [], [$MANAGER]);

        $router->get('/waste-destinations', [WasteDestinationController::class, 'index']);
        $router->get('/waste-destinations/{id}', [WasteDestinationController::class, 'show']);
        $router->post('/waste-destinations', [WasteDestinationController::class, 'store'], [$ADMIN]);
        $router->put('/waste-destinations/{id}', [WasteDestinationController::class, 'update'], [$ADMIN]);
        $router->patch('/waste-destinations/{id}', [WasteDestinationController::class, 'update'], [$ADMIN]);

        $router->resource('/environmental-objectives', ObjectiveController::class);
        $router->get('/environmental-goals/{id}/progress', [GoalController::class, 'progress']);
        $router->resource('/environmental-goals', GoalController::class);

        $router->get('/indicators/water', [IndicatorController::class, 'water']);
        $router->get('/indicators/energy', [IndicatorController::class, 'energy']);
        $router->get('/indicators/waste', [IndicatorController::class, 'waste']);
        $router->get('/indicators/goals', [IndicatorController::class, 'goals']);

        $router->get('/dashboard/summary', [DashboardController::class, 'summary']);
        $router->get('/dashboard/charts', [DashboardController::class, 'charts']);

        $router->get('/reports/consumption', [ReportController::class, 'consumption'], [$MANAGER]);
        $router->get('/reports/waste', [ReportController::class, 'waste'], [$MANAGER]);
        $router->get('/reports/goals', [ReportController::class, 'goals'], [$MANAGER]);
    });
});
