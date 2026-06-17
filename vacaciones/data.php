<?php
/*
  data.php — almacén compartido para Control de Compras.
  GET  -> devuelve el contenido de data.json (o {} si no existe).
  POST -> guarda en data.json el JSON recibido en el cuerpo.
  Coloca este archivo en la MISMA carpeta que el index.html.
  No necesita base de datos: usa un fichero data.json en el servidor.
*/

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$FILE = __DIR__ . '/data.json';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'GET') {
    if (is_file($FILE)) {
        readfile($FILE);
    } else {
        echo '{}';
    }
    exit;
}

if ($method === 'POST') {
    $raw = file_get_contents('php://input');

    // Límite de tamaño defensivo (2 MB).
    if (strlen($raw) > 2 * 1024 * 1024) {
        http_response_code(413);
        echo json_encode(['ok' => false, 'error' => 'demasiado grande']);
        exit;
    }

    // Validar que es JSON correcto antes de guardar.
    $data = json_decode($raw, true);
    if ($raw === '' || $data === null) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'json no válido']);
        exit;
    }

    // Escritura atómica con bloqueo para evitar corrupción con varios usuarios.
    $tmp = $FILE . '.tmp';
    if (file_put_contents($tmp, $raw, LOCK_EX) === false || !rename($tmp, $FILE)) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'error' => 'no se pudo guardar']);
        exit;
    }

    echo json_encode(['ok' => true]);
    exit;
}

http_response_code(405);
echo json_encode(['ok' => false, 'error' => 'método no permitido']);
