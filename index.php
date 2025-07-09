<?php
declare(strict_types = 1);

require_once __DIR__ . '/vendor/autoload.php';

use App\Connection\Database;
use App\Controller\UserController;
use App\Model\UserTable;

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
        case (bool)preg_match('#^/user/(\d+)$#', $path, $params):
            $userId = (int)$params[1];
            $userController->showUser($userId);
            break;
        case (bool)preg_match('#^/user/(\d+)/delete$#', $path, $params):
            $userId = (int)$params[1];
            $userController->deleteUser($userId);
            break;
        case (bool)preg_match('#^/user/(\d+)/edit$#', $path, $params):
            $userId = (int)$params[1];

            if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                $userController->showEditForm($userId);
            } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $userController->editUser($userId, $_POST);
            }
            break;
        default:
            http_response_code(404);
            echo "404 Not Found";
    }
} catch (Exception $exception) {
    http_response_code(404);
    error_log("Error: " . $exception->getMessage());
    echo "Server error";
    die();
}