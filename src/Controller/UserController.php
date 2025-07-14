<?php
declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use DateTime;
use DateTimeImmutable;
use Exception;
use InvalidArgumentException;
use JetBrains\PhpStorm\NoReturn;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class UserController extends AbstractController
{
    private PhotoController $photoController;
    private const USER_REQUIRED_FIELDS = [
        'first_name',
        'last_name',
        'gender',
        'birth_date',
        'email'
    ];

    public function __construct(private readonly UserRepository $userRepository)
    {
        $this->photoController = new PhotoController();
    }

    public function goToRegister(): Response
    {
        return $this->redirectToRoute('list_of_users');
    }

    public function index(): Response
    {
        return $this->render('view/register_form.html.twig');
    }

    #[NoReturn] public function registerUser(): Response
    {
        $userData = self::getInputInformation();
        self::validateRequiredFields($userData);
        $validatedUserParams = self::normalizeUserData($userData);
        $validatedUserParams['id'] = null;

        $user = $this->convertArrayToUser($validatedUserParams);
        $userId = $this->userRepository->storeData($user);
        return $this->redirectToRoute('user_page', ['userId' => $userId]);
    }

    #[NoReturn] public function showUser(int $userId): Response
    {
        $user = $this->userRepository->findUserById($userId);

        if (is_null($user)) {
            http_response_code(404);
            echo 'User not found';
            return $this->render('view/register_form.html.twig');
        }

        return $this->render('view/user_page.html.twig', ['user' => $user]);
    }

    #[NoReturn] public function deleteUser(int $userId): Response
    {
        if ($this->userRepository->findUserById($userId) === null) {
            throw new Exception("No such user found!");
        }

        $this->userRepository->deleteUserById($userId);
        return $this->redirectToRoute('list_of_users');
    }

    public function editUser(int $userId, Request $request): Response
    {
        $user = $this->userRepository->findUserById($userId);

        try {
            if ($request->isMethod('GET')) {
                return $this->render('view/edit_form.html.twig', ['user' => $user, 'error' => null]);
            } elseif ($request->isMethod('POST')) {
                $this->photoController->updateAvatar($user);
                unset($_POST['avatar']);
                unset($_POST['remove_avatar']);
                $this->updateOtherFields($user, $_POST);
                $this->userRepository->storeData($user);
                return $this->redirectToRoute('user_page', ['userId' => $userId]);
            }
        } catch (RuntimeException $e) {
            $error = $e->getMessage();

            return $this->render('view/edit_user.html.twig', ['user' => $user, 'error' => $error]);
        }

        return $this->redirectToRoute('view/register_form.html.twig');
    }

    public function listOfUsers(): Response
    {
        try {
            $users = $this->userRepository->getAllUsers();
            return $this->render('view/list_of_users.html.twig', ['users' => $users]);
        } catch (Exception $e) {
            return new Response('Error: ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
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

            if ($field === 'birth_date') {
                $fieldData = $this->validateBirthDate($fieldData);
            }

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

    private function validateBirthDate($birthDate): DateTimeImmutable
    {
        if ($birthDate instanceof DateTimeImmutable) {
            return $birthDate;
        }

        if ($birthDate instanceof DateTime) {
            return DateTimeImmutable::createFromMutable($birthDate);
        }

        try {
            $date = DateTimeImmutable::createFromFormat('Y-m-d', (string)$birthDate);

            if ($date === false) {
                throw new InvalidArgumentException('Invalid date format');
            }

            return $date->setTime(0, 0, 0);

        } catch (Exception $e) {
            throw new InvalidArgumentException('Invalid birth date: ' . $e->getMessage());
        }
    }

    private function validatePhone(string $phone): string
    {
        return preg_replace('/[^0-9+]/', '', $phone);
    }

    private function convertArrayToUser(array $userInfo): User
    {
        return new User(
            (int)$userInfo['id'],
            $userInfo['first_name'],
            $userInfo['last_name'],
            !empty($userInfo['middle_name']) ? $userInfo['middle_name'] : null,
            $userInfo['gender'],
            $userInfo['birth_date'],
            $userInfo['email'],
            !empty($userInfo['phone']) ? $userInfo['phone'] : null,
            !empty($userInfo['avatar_path']) ? $userInfo['avatar_path'] : null
        );
    }
}
