<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Planes de Estudio</title>
    <link rel="stylesheet" href="/public/assets/css/styles.css">
</head>
<body>
    <?php include __DIR__ . '/../layout.php'; ?>
    
    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Planes de Estudio</h2>
            <button type="button" class="btn btn-primary" id="btnNuevoPlan">
                <i class="fas fa-plus"></i> Nuevo Plan
            </button>
        </div>
        
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Carrera</th>
                                <th>Año</th>
                                <th>Clave</th>
                                <th>Descripción</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($planes)): ?>
                                <tr>
                                    <td colspan="7" class="text-center">No hay planes registrados</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($planes as $plan): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($plan['id']) ?></td>
                                        <td><?= htmlspecialchars($plan['carrera_nombre']) ?> (<?= htmlspecialchars($plan['carrera_clave']) ?>)</td>
                                        <td><?= htmlspecialchars($plan['anio']) ?></td>
                                        <td><?= htmlspecialchars($plan['clave']) ?></td>
                                        <td><?= htmlspecialchars($plan['descripcion'] ?? '') ?></td>
                                        <td>
                                            <span class="badge badge-<?= $plan['activo'] ? 'success' : 'secondary' ?>">
                                                <?= $plan['activo'] ? 'Activo' : 'Inactivo' ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-warning btn-edit" data-id="<?= $plan['id'] ?>">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-sm btn-danger btn-delete" data-id="<?= $plan['id'] ?>">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal Crear/Editar Plan -->
    <div class="modal fade" id="modalPlan" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalPlanLabel">Nuevo Plan de Estudio</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <form id="formPlan">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                    <input type="hidden" name="id" id="plan_id">
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="carrera_id">Carrera *</label>
                            <select class="form-control" id="carrera_id" name="carrera_id" required>
                                <option value="">-- Seleccione --</option>
                                <?php foreach ($carreras as $carrera): ?>
                                    <option value="<?= $carrera['id'] ?>"><?= htmlspecialchars($carrera['nombre']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="anio">Año *</label>
                            <input type="number" class="form-control" id="anio" name="anio" required min="2000" max="2100">
                        </div>
                        <div class="form-group">
                            <label for="clave">Clave *</label>
                            <input type="text" class="form-control" id="clave" name="clave" required maxlength="50">
                        </div>
                        <div class="form-group">
                            <label for="descripcion">Descripción</label>
                            <textarea class="form-control" id="descripcion" name="descripcion" rows="3"></textarea>
                        </div>
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="activo" name="activo" checked>
                            <label class="form-check-label" for="activo">Activo</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Guardar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        $(document).ready(function() {
            // Nuevo plan
            $('#btnNuevoPlan').click(function() {
                $('#formPlan')[0].reset();
                $('#plan_id').val('');
                $('#modalPlanLabel').text('Nuevo Plan de Estudio');
                $('#modalPlan').modal('show');
            });
            
            // Editar plan
            $('.btn-edit').click(function() {
                const id = $(this).data('id');
                $.get('/planes/get?id=' + id, function(data) {
                    $('#plan_id').val(data.id);
                    $('#carrera_id').val(data.carrera_id);
                    $('#anio').val(data.anio);
                    $('#clave').val(data.clave);
                    $('#descripcion').val(data.descripcion);
                    $('#activo').prop('checked', data.activo == 1);
                    $('#modalPlanLabel').text('Editar Plan de Estudio');
                    $('#modalPlan').modal('show');
                });
            });
            
            // Guardar plan
            $('#formPlan').submit(function(e) {
                e.preventDefault();
                const id = $('#plan_id').val();
                const url = id ? '/planes/update' : '/planes/store';
                $.post(url, $(this).serialize(), function(response) {
                    alert(response.message || 'Operación exitosa');
                    location.reload();
                }).fail(function(xhr) {
                    const res = xhr.responseJSON;
                    alert(res?.error || 'Error en la operación');
                });
            });
            
            // Eliminar plan
            $('.btn-delete').click(function() {
                if (!confirm('¿Está seguro de eliminar este plan?')) return;
                const id = $(this).data('id');
                $.post('/planes/delete', {
                    id: id,
                    csrf_token: '<?= $_SESSION['csrf_token'] ?? '' ?>'
                }, function(response) {
                    alert(response.message || 'Plan eliminado');
                    location.reload();
                }).fail(function(xhr) {
                    const res = xhr.responseJSON;
                    alert(res?.error || 'Error al eliminar');
                });
            });
        });
    </script>
</body>
</html>
