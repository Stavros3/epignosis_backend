<?php 

declare(strict_types=1);

spl_autoload_register(function ($className) {
    $filePath = __DIR__ . '/src/' . $className . '.php';
    if (file_exists($filePath)) {
        require_once $filePath;
    } else {
        http_response_code(500);
        echo "Internal Server Error: Class file for {$className} not found.";
        exit();
    }
});

set_error_handler(['ErrorHandler', 'handleError']);
set_exception_handler(['ErrorHandler', 'handleException']);

header("Content-Type: application/json; charset=UTF-8");

$uriSegments = explode("/", $_SERVER["REQUEST_URI"]);
 // print_r($uriSegments);

if ($uriSegments[1] != 'users') {
    http_response_code(404);
    echo "404 Not Found";
    exit();
} 

$dbHost = getenv('DB_HOST') ?: 'mysql';
$dbName = getenv('DB_DATABASE') ?: 'my_database';
$dbUser = getenv('DB_USERNAME') ?: 'appuser';
$dbPass = getenv('DB_PASSWORD') ?: 'apppass';

$database = new Database($dbHost, $dbName, $dbUser, $dbPass);
//$pdo = $database->getConnection();

$usersGateway = new UsersGateway($database);
$controller = new UserController($usersGateway);

$controller->processRequest($_SERVER["REQUEST_METHOD"], $uriSegments[2] ?? null);





?>