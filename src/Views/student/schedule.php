<?php
$base = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/');
ob_start();
?>
<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="mb-1">Mi Horario</h2>
        <p class="text-muted small mb-0">Horario del semestre actual: <strong><?= htmlspecialchars($currentCycle ?? 'N/A') ?></strong></p>
    </div>
    <a href="<?php echo $base; ?>/app.php?r=/dashboard" class="btn btn-sm btn-outline-secondary">Volver</a>
  </div>

  <?php if (empty($horarios)): ?>
    <div class="alert alert-info">
      <i class="fa-solid fa-info-circle me-2"></i>
      No tienes inscripciones en el semestre actual o no se han asignado horarios a tus grupos.
    </div>
  <?php else: ?>
    <div class="row">
      <div class="col-12">
        <div class="card shadow-sm border-0">
          <div class="card-body">
            <!-- Tabs por día de la semana -->
            <ul class="nav nav-tabs mb-3" id="scheduleTabs" role="tablist">
              <?php 
              $dias = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
              $firstActive = true;
              foreach ($dias as $dia): 
                if (isset($scheduleByDay[$dia])):
              ?>
                <li class="nav-item" role="presentation">
                  <button class="nav-link <?= $firstActive ? 'active' : '' ?>" 
                          id="tab-<?= strtolower($dia) ?>" 
                          data-bs-toggle="tab" 
                          data-bs-target="#content-<?= strtolower($dia) ?>" 
                          type="button" 
                          role="tab">
                    <?= $dia ?>
                  </button>
                </li>
              <?php 
                  $firstActive = false;
                endif;
              endforeach; 
              ?>
            </ul>

            <!-- Contenido de tabs -->
            <div class="tab-content" id="scheduleTabContent">
              <?php 
              $firstActive = true;
              foreach ($dias as $dia): 
                if (isset($scheduleByDay[$dia])):
              ?>
                <div class="tab-pane fade <?= $firstActive ? 'show active' : '' ?>" 
                     id="content-<?= strtolower($dia) ?>" 
                     role="tabpanel">
                  <div class="table-responsive">
                    <table class="table table-hover align-middle">
                      <thead class="table-light">
                        <tr>
                          <th class="text-center" style="width: 150px;">Horario</th>
                          <th>Materia</th>
                          <th>Grupo</th>
                          <th class="text-center">Aula</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php foreach ($scheduleByDay[$dia] as $h): ?>
                          <tr>
                            <td class="text-center">
                              <span class="badge bg-primary-subtle text-primary">
                                <?= date('H:i', strtotime($h['hora_inicio'] ?? '00:00')) ?> - 
                                <?= date('H:i', strtotime($h['hora_fin'] ?? '00:00')) ?>
                              </span>
                            </td>
                            <td>
                              <strong><?= htmlspecialchars($h['materia'] ?? 'Sin materia') ?></strong>
                            </td>
                            <td>
                              <span class="badge bg-secondary-subtle text-secondary">
                                <?= htmlspecialchars($h['grupo'] ?? 'Sin grupo') ?>
                              </span>
                            </td>
                            <td class="text-center">
                              <?php if (!empty($h['aula'])): ?>
                                <span class="badge bg-success-subtle text-success">
                                  <i class="fa-solid fa-door-open me-1"></i>
                                  <?= htmlspecialchars($h['aula']) ?>
                                </span>
                              <?php else: ?>
                                <span class="text-muted small">Por asignar</span>
                              <?php endif; ?>
                            </td>
                          </tr>
                        <?php endforeach; ?>
                      </tbody>
                    </table>
                  </div>
                </div>
              <?php 
                  $firstActive = false;
                endif;
              endforeach; 
              ?>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Vista compacta alternativa: todas las clases en una sola tabla -->
    <div class="row mt-4">
      <div class="col-12">
        <div class="card shadow-sm border-0">
          <div class="card-header bg-white">
            <h5 class="mb-0"><i class="fa-solid fa-calendar-week me-2"></i>Vista Semanal Completa</h5>
          </div>
          <div class="card-body">
            <div class="table-responsive">
              <table class="table table-sm table-striped align-middle">
                <thead class="table-light">
                  <tr>
                    <th>Día</th>
                    <th>Horario</th>
                    <th>Materia</th>
                    <th>Grupo</th>
                    <th class="text-center">Aula</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($horarios as $h): ?>
                    <tr>
                      <td><strong><?= htmlspecialchars(ucfirst($h['dia_semana'] ?? 'Sin día')) ?></strong></td>
                      <td class="small">
                        <?= date('H:i', strtotime($h['hora_inicio'] ?? '00:00')) ?> - 
                        <?= date('H:i', strtotime($h['hora_fin'] ?? '00:00')) ?>
                      </td>
                      <td><?= htmlspecialchars($h['materia'] ?? 'Sin materia') ?></td>
                      <td>
                        <span class="badge bg-secondary-subtle text-secondary">
                          <?= htmlspecialchars($h['grupo'] ?? 'Sin grupo') ?>
                        </span>
                      </td>
                      <td class="text-center">
                        <?php if (!empty($h['aula'])): ?>
                          <span class="badge bg-success-subtle text-success">
                            <?= htmlspecialchars($h['aula']) ?>
                          </span>
                        <?php else: ?>
                          <span class="text-muted small">-</span>
                        <?php endif; ?>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
?>
