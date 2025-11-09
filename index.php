<?php 

declare(strict_types=1);

spl_autoload_register(function ($className) {
    // Try loading from src/ directory
    $filePath = __DIR__ . '/src/' . $className . '.php';
    if (file_exists($filePath)) {
        require_once $filePath;
        return;
    }
    
    // Try loading from src/enums/ directory
    $enumPath = __DIR__ . '/src/enums/' . $className . '.php';
    if (file_exists($enumPath)) {
        require_once $enumPath;
        return;
    }
    
    // Class not found
    http_response_code(500);
    echo json_encode(['error' => "Internal Server Error: Class file for {$className} not found."]);
    exit();
});

set_error_handler(['ErrorHandler', 'handleError']);
set_exception_handler(['ErrorHandler', 'handleException']);

// CORS Headers - Allow requests from Angular app
header("Access-Control-Allow-Origin: http://localhost:4200");
header("Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Max-Age: 3600");
header("Content-Type: application/json; charset=UTF-8");

// Handle preflight OPTIONS request
if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(200);
    exit();
}

$uri = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
$uriSegments = explode("/", trim($uri, "/"));

$resource = $uriSegments[0] ?? null;

// Check if resource exists
if (!in_array($resource, ['users', 'vacations'])) {
    http_response_code(404);
    echo json_encode(['error' => '404 Not Found']);
    exit();
} 

$dbHost = getenv('DB_HOST') ?: 'mysql';
$dbName = getenv('DB_DATABASE') ?: 'my_database';
$dbUser = getenv('DB_USERNAME') ?: 'appuser';
$dbPass = getenv('DB_PASSWORD') ?: 'apppass';

$database = new Database($dbHost, $dbName, $dbUser, $dbPass);

// Route to appropriate controller
if ($resource === 'users') {
    $usersGateway = new UsersGateway($database);
    $controller = new UserController($usersGateway);
} elseif ($resource === 'vacations') {
    $vacationGateway = new VacationGateway($database);
    $controller = new VacationController($vacationGateway);
}

// Pass the method name/action and any additional ID
$action = $uriSegments[1] ?? null;
$id = $uriSegments[2] ?? null;

$controller->processRequest($_SERVER["REQUEST_METHOD"], $action, $id);





?>