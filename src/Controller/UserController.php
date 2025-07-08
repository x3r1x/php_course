<?php
declare(strict_types=1);

namespace App\Controller;

use App\Connection\Database;
use App\Model\User;
use App\Model\UserTable;
use DateTime;
use Exception;
use InvalidArgumentException;
use JetBrains\PhpStorm\NoReturn;
use RuntimeException;

require_once __DIR__ . '/../Model/User.php';
require_once __DIR__ . '/../Model/UserTable.php';
require_once __DIR__ . '/../Connection/Database.php';

class UserController
{
    private const USER_REQUIRED_FIELDS = [
        'first_name',
        'last_name',
        'gender',
        'birth_date',
        'email'
    ];

    function __construct()
    {
    }

    function index(): void
    {
        require_once __DIR__ . '/../View/register_form.php';
    }

    #[NoReturn] function registerUser(): void
    {
        $userData = self::getInputInformation();
        self::validateRequiredFields($userData);
        $validatedUserParams = self::normalizeUserData($userData);

        $user = new User(
            null,
            firstName: $validatedUserParams['first_name'],
            lastName: $validatedUserParams['last_name'],
            middleName: empty($validatedUserParams['middle_name']) ? $validatedUserParams['middle_name'] : null,
            gender: $validatedUserParams['gender'],
            birthDate: $validatedUserParams['birth_date'],
            email: $validatedUserParams['email'],
            phone: $validatedUserParams['phone'] !== "" ? $validatedUserParams['phone'] : null,
            avatarPath: $validatedUserParams['avatar_path'] !== "" ? $validatedUserParams['avatar_path'] : null
        );

        $userTable = new UserTable(Database::connectDatabase());
        $userId = $userTable->saveUserToDatabase($user);
        header("Location: /user/" . $userId);
        exit();
    }

    function showUser(int $userId): void
    {
        $userTable = new UserTable(Database::connectDatabase());
        $userData = $userTable->findUserInDatabase($userId);

        if (!$userData) {
            http_response_code(404);
            echo 'User not found';
            return;
        }

        $user = new User(
            id: (int)$userData['id'],
            firstName: $userData['first_name'],
            lastName: $userData['last_name'],
            middleName: $userData['middle_name'] !== "" ? $userData['middle_name'] : null,
            gender: $userData['gender'],
            birthDate: $userData['birth_date'],
            email: $userData['email'],
            phone: $userData['phone'] !== "" ? $userData['phone'] : null,
            avatarPath: $userData['avatar_path'] !== "" ? $userData['avatar_path'] : null
        );

        require_once __DIR__ . '/../View/user_page.php';
    }

    private function getInputInformation(): array
    {
        return [
            'first_name' => $_POST['first_name'] ?? '',
            'last_name' => $_POST['last_name'] ?? '',
            'middle_name' => $_POST['middle_name'] ?? '',
            'gender' => $_POST['gender'] ?? '',
            'birth_date' => $_POST['birth_date'] ?? '',
            'email' => $_POST['email'] ?? '',
            'phone' => $_POST['phone'] ?? '',
            'avatar_path' => self::getPath()
        ];
    }

    private function getPath(): ?string
    {
        if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
            return null;
        }

        $uploadsDir = __DIR__ . '/../../assets/uploads/';
        $filename = uniqid() . '_' . basename($_FILES['avatar']['name']);
        $destination = $uploadsDir . $filename;

        if (!move_uploaded_file($_FILES['avatar']['tmp_name'], $destination)) {
            throw new RuntimeException('Save failed');
        }

        return '/uploads/' . $filename;
    }

    private function validateRequiredFields(array $userParams): void
    {
        $missingFields = array_filter(self::USER_REQUIRED_FIELDS, fn($field) => empty($userParams[$field]));

        if ($missingFields) {
            throw new InvalidArgumentException(
                'Required fields are not specified: ' . implode(', ', $missingFields)
            );
        }
    }

    private function normalizeUserData(array $userParams): array
    {
        return [
            'first_name' => trim($userParams['first_name']),
            'last_name' => trim($userParams['last_name']),
            'middle_name' => isset($userParams['middle_name']) ? trim($userParams['middle_name']) : '',
            'gender' => $userParams['gender'],
            'birth_date' => $this->validateBirthDate($userParams['birth_date']),
            'email' => strtolower(trim($userParams['email'])),
            'phone' => isset($userParams['phone']) ? $this->validatePhone($userParams['phone']) : '',
            'avatar_path' => $userParams['avatar_path'] ?? ''
        ];
    }

    private function validateBirthDate($birthDate): string
    {
        if ($birthDate instanceof DateTime) {
            return $birthDate->format('Y-m-d H:i:s');
        }

        try {
            return (new DateTime($birthDate))->format('Y-m-d H:i:s');
        } catch (Exception $e) {
            throw new InvalidArgumentException('Invalid birth date: ' . $e->getMessage());
        }
    }

    private function validatePhone(string $phone): string
    {
        return preg_replace('/[^0-9+]/', '', $phone);
    }
}