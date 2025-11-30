<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Aulas</title>
    <link rel="stylesheet" href="/public/assets/css/styles.css">
</head>
<body>
    <?php include __DIR__ . '/../layout.php'; ?>
    
    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Gestión de Aulas</h2>
            <button type="button" class="btn btn-primary" id="btnNuevaAula">
                <i class="fas fa-plus"></i> Nueva Aula
            </button>
        </div>
        
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Clave</th>
                                <th>Capacidad</th>
                                <th>Tipo</th>
                                <th>Ubicación</th>
                                <th>Recursos</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($aulas)): ?>
                                <tr>
                                    <td colspan="8" class="text-center">No hay aulas registradas</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($aulas as $aula): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($aula['id']) ?></td>
                                        <td><?= htmlspecialchars($aula['clave']) ?></td>
                                        <td><?= htmlspecialchars($aula['capacidad']) ?></td>
                                        <td><?= htmlspecialchars($aula['tipo']) ?></td>
                                        <td><?= htmlspecialchars($aula['ubicacion'] ?? '') ?></td>
                                        <td><?= htmlspecialchars(substr($aula['recursos_json'] ?? '', 0, 50)) ?></td>
                                        <td>
                                            <span class="badge badge-<?= $aula['activo'] ? 'success' : 'secondary' ?>">
                                                <?= $aula['activo'] ? 'Activa' : 'Inactiva' ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-warning btn-edit" data-id="<?= $aula['id'] ?>">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-sm btn-danger btn-delete" data-id="<?= $aula['id'] ?>">
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
    
    <!-- Modal Crear/Editar Aula -->
    <div class="modal fade" id="modalAula" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalAulaLabel">Nueva Aula</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <form id="formAula">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                    <input type="hidden" name="id" id="aula_id">
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="clave">Clave *</label>
                            <input type="text" class="form-control" id="clave" name="clave" required maxlength="20">
                        </div>
                        <div class="form-group">
                            <label for="capacidad">Capacidad *</label>
                            <input type="number" class="form-control" id="capacidad" name="capacidad" required min="1" max="200">
                        </div>
                        <div class="form-group">
                            <label for="tipo">Tipo *</label>
                            <select class="form-control" id="tipo" name="tipo" required>
                                <option value="aula">Aula</option>
                                <option value="laboratorio">Laboratorio</option>
                                <option value="taller">Taller</option>
                                <option value="auditorio">Auditorio</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="ubicacion">Ubicación</label>
                            <input type="text" class="form-control" id="ubicacion" name="ubicacion" maxlength="100">
                        </div>
                        <div class="form-group">
                            <label for="recursos">Recursos (separados por coma)</label>
                            <input type="text" class="form-control" id="recursos" name="recursos" placeholder="Ej: Proyector, Computadoras, Pizarrón digital">
                        </div>
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="activo" name="activo" checked>
                            <label class="form-check-label" for="activo">Activa</label>
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
            // Nueva aula
            $('#btnNuevaAula').click(function() {
                $('#formAula')[0].reset();
                $('#aula_id').val('');
                $('#modalAulaLabel').text('Nueva Aula');
                $('#modalAula').modal('show');
            });
            
            // Editar aula
            $('.btn-edit').click(function() {
                const id = $(this).data('id');
                $.get('/aulas/get?id=' + id, function(data) {
                    $('#aula_id').val(data.id);
                    $('#clave').val(data.clave);
                    $('#capacidad').val(data.capacidad);
                    $('#tipo').val(data.tipo);
                    $('#ubicacion').val(data.ubicacion);
                    $('#recursos').val(data.recursos_json);
                    $('#activo').prop('checked', data.activo == 1);
                    $('#modalAulaLabel').text('Editar Aula');
                    $('#modalAula').modal('show');
                });
            });
            
            // Guardar aula
            $('#formAula').submit(function(e) {
                e.preventDefault();
                const id = $('#aula_id').val();
                const url = id ? '/aulas/update' : '/aulas/store';
                $.post(url, $(this).serialize(), function(response) {
                    alert(response.message || 'Operación exitosa');
                    location.reload();
                }).fail(function(xhr) {
                    const res = xhr.responseJSON;
                    alert(res?.error || 'Error en la operación');
                });
            });
            
            // Eliminar aula
            $('.btn-delete').click(function() {
                if (!confirm('¿Está seguro de eliminar esta aula?')) return;
                const id = $(this).data('id');
                $.post('/aulas/delete', {
                    id: id,
                    csrf_token: '<?= $_SESSION['csrf_token'] ?? '' ?>'
                }, function(response) {
                    alert(response.message || 'Aula eliminada');
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
