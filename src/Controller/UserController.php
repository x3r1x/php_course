<?php
declare(strict_types=1);

namespace App\Controller;

use App\Connection\Database;
use App\Controller\PhotoController;
use App\Model\User;
use App\Model\UserTable;
use DateTime;
use Exception;
use InvalidArgumentException;
use JetBrains\PhpStorm\NoReturn;
use RuntimeException;

class UserController
{
    private PhotoController $photoController;
    private const USER_REQUIRED_FIELDS = [
        'first_name',
        'last_name',
        'gender',
        'birth_date',
        'email'
    ];

    function __construct(private UserTable $userTable)
    {
        $this->photoController = new PhotoController();
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
        $validatedUserParams['id'] = null;

        $user = $this->userTable->convertArrayToUser($validatedUserParams);

        $userTable = new UserTable(Database::connectDatabase());
        $userId = $userTable->saveUserToDatabase($user);
        header("Location: /user/" . $userId);
        exit();
    }

    #[NoReturn] function showUser(int $userId): void
    {
        $userData = $this->userTable->findUserInDatabase($userId);

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

    #[NoReturn] function deleteUser(int $userId): void
    {
        $this->userTable->deleteUserFromDatabase($userId);
        header('Location: /register');
        exit;
    }

    function showEditForm(int $userId): void
    {
        $userParams = $this->userTable->findUserInDatabase($userId);
        $user = $this->userTable->convertArrayToUser($userParams);
        include __DIR__ . "/../View/edit_form.php";
    }

    function editUser(int $userId, array $inputsData): void
    {
        $userParams = $this->userTable->findUserInDatabase($userId);
        $user = $this->userTable->convertArrayToUser($userParams);

        try {
            $this->photoController->updateAvatar($user);
            unset($inputsData['avatar']);
            unset($inputsData['remove_avatar']);
            $this->updateOtherFields($user, $inputsData);
            $this->userTable->updateUserInDatabase($user);
            header('Location: /user/' . $userId);
        } catch (RuntimeException $e) {
            $error = $e->getMessage();
            include __DIR__ . '/../View/edit_form.php';
        }

        exit();
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
            'avatar_path' => $this->photoController->getAvatarPath()
        ];
    }

    private function updateOtherFields(User $user, array $inputsData): void
    {
        foreach ($inputsData as $field => $fieldData) {
            $setter = 'set' . str_replace('_', '', ucwords($field));

            $user->{$setter}($fieldData);
        }
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