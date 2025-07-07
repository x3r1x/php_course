<?php
declare(strict_types = 1);

use App\Connection\Database;

require_once __DIR__ . '/src/Domain/User/UserController.php';
require_once __DIR__ . '/src/Connection/Database.php';

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$dbConnection = Database::connectDatabase();
$userController = new UserController($dbConnection);

try {
    switch ($path) {
        case '/':
            header("Location: /register");
            exit;
        case '/register':
            $userController -> index();
            break;
        case '/register/save':
            $userController -> registerUser();
        case (bool)preg_match('#^/user/(\d+)$#', $path, $matches):
            $userId = (int)$matches[1];
            $userController->showUser($userId);
            break;
        default:
            http_response_code(404);
            echo "404 Not Found brr brr patapim";
    }
} catch (Exception $exception) {
    http_response_code(404);
    error_log("Error: " . $exception->getMessage());
    echo "Server error";
}