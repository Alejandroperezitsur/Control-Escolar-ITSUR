<?php
http_response_code(410);
header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    'success' => false,
    'error' => 'API legacy /api/profesores eliminada. Usa rutas modernas bajo /public/app.php.'
]);
exit;
