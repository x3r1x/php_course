<?php
declare(strict_types=1);

namespace App\Controller;

use App\Controller\PhotoController;
use App\Model\User;
use App\Model\UserTable;
use DateTime;
use Exception;
use InvalidArgumentException;
use JetBrains\PhpStorm\NoReturn;
use PDO;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class UserController extends AbstractController
{
    private PhotoController $photoController;
    private UserTable $userTable;
    private const USER_REQUIRED_FIELDS = [
        'first_name',
        'last_name',
        'gender',
        'birth_date',
        'email'
    ];

    function __construct(private readonly PDO $dbConnection)
    {
        $this->photoController = new PhotoController();
        $this->userTable = new UserTable($this->dbConnection);
    }

    function goToRegister(): Response
    {
        return $this->redirectToRoute('register');
    }

    function index(): Response
    {
        return $this->render('view/register_form.html.twig');
    }

    #[NoReturn] function registerUser(): Response
    {
        $userData = self::getInputInformation();
        self::validateRequiredFields($userData);
        $validatedUserParams = self::normalizeUserData($userData);
        $validatedUserParams['id'] = null;

        $user = $this->userTable->convertArrayToUser($validatedUserParams);
        $userId = $this->userTable->saveUserToDatabase($user);
        return $this->redirectToRoute('user_page', ['userId' => $userId]);
    }

    #[NoReturn] function showUser(int $userId): Response
    {
        $user = $this->userTable->findUserInDatabase($userId);

        if (is_null($user)) {
            http_response_code(404);
            echo 'User not found';
            return $this->render('view/register_form.html.twig');
        }

        return $this->render('view/user_page.html.twig', ['user' => $user]);
    }

    #[NoReturn] function deleteUser(int $userId): Response
    {
        if ($this->userTable->findUserInDatabase($userId) === null) {
            throw new Exception("No such user found!");
        }

        $this->userTable->deleteUserFromDatabase($userId);
        return $this->redirectToRoute('register');
    }

    function editUser(int $userId, Request $request): Response
    {
        $user = $this->userTable->findUserInDatabase($userId);

        try {
            if ($request->isMethod('GET')) {
                return $this->render('view/edit_form.html.twig', ['user' => $user, 'error' => null]);
            } elseif ($request->isMethod('POST')) {
                $this->photoController->updateAvatar($user);
                unset($_POST['avatar']);
                unset($_POST['remove_avatar']);
                $this->updateOtherFields($user, $_POST);
                $this->userTable->updateUserInDatabase($user);
                return $this->redirectToRoute('user_page', ['userId' => $userId]);
            }
        } catch (RuntimeException $e) {
            $error = $e->getMessage();

            return $this->render('view/edit_user.html.twig', ['user' => $user, 'error' => $error]);
        }

        return $this->redirectToRoute('view/register_form.html.twig');
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
