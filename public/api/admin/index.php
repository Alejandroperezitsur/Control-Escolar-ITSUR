<?php
http_response_code(410);
header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    'success' => false,
    'error' => 'API legacy eliminada en esta ruta. Usa rutas modernas bajo app.php.'
]);
exit;
