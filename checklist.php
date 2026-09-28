<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

ini_set('display_errors', '0');
error_reporting(E_ALL);

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

$host = 'sdb-86.hosting.stackcp.net';
$db   = 'dectectores-353130306663';
$user = 'detectores';
$pass = 'Pymex@705';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    $pdo->exec("CREATE TABLE IF NOT EXISTS user_checklist (
        id VARCHAR(64) NOT NULL,
        task_id VARCHAR(64) NOT NULL,
        completed TINYINT(1) NOT NULL DEFAULT 0,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id, task_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error de conexión MySQL: ' . $e->getMessage()]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $stmt = $pdo->query("SELECT task_id, completed FROM user_checklist WHERE id = 'main_board'");
        $rows = $stmt->fetchAll();
        $tasks = [];
        foreach ($rows as $row) {
            $tasks[$row['task_id']] = (bool)$row['completed'];
        }
        echo json_encode(['success' => true, 'tasks' => $tasks]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Error en tabla SQL: ' . $e->getMessage()]);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input) || !isset($input['tasks']) || !is_array($input['tasks'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'JSON inválido. Se esperaba {tasks: {...}}']);
        exit;
    }
    try {
        $stmt = $pdo->prepare("INSERT INTO user_checklist (id, task_id, completed) VALUES ('main_board', :task_id, :completed) ON DUPLICATE KEY UPDATE completed = :completed_dup");
        foreach ($input['tasks'] as $taskId => $isCompleted) {
            $comp = $isCompleted ? 1 : 0;
            $stmt->execute([
                'task_id' => (string)$taskId,
                'completed' => $comp,
                'completed_dup' => $comp,
            ]);
        }
        echo json_encode(['success' => true, 'message' => 'Guardado en MySQL']);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Error al guardar en MySQL: ' . $e->getMessage()]);
    }
    exit;
}

http_response_code(405);
echo json_encode(['success' => false, 'error' => 'Método no permitido']);
