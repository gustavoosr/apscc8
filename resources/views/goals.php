<ul class="nav nav-pills mb-3" role="tablist">
  <li class="nav-item"><button class="nav-link active" data-bs-toggle="pill" data-bs-target="#tabGoals"><i class="fa-solid fa-bullseye me-1"></i>Metas</button></li>
  <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#tabObjectives"><i class="fa-solid fa-flag me-1"></i>Objetivos</button></li>
</ul>
<div class="alert alert-light border small">
  <i class="fa-solid fa-circle-info text-success me-1"></i>
  O progresso respeita a direção da meta: em <strong>reduções</strong> avança quanto menor o valor atual; em <strong>aumentos</strong>, quanto maior. Crie primeiro um objetivo e depois as metas associadas.
</div>
<div class="tab-content">
  <div class="tab-pane fade show active" id="tabGoals"><div id="goalsRoot"></div></div>
  <div class="tab-pane fade" id="tabObjectives"><div id="objectivesRoot"></div></div>
</div>
<script>document.addEventListener('DOMContentLoaded', () => Pages.goals());</script>
