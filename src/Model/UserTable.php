<?php
declare(strict_types=1);

namespace App\Model;

use InvalidArgumentException;
use PDO;
use PDOException;
use RuntimeException;

class UserTable
{
    function __construct(private PDO $dbConnection)
    {
    }

    function saveUserToDatabase(user $user): int
    {
        $sqlPrompt = "INSERT INTO `user` 
        (
         `first_name`, 
         `last_name`, 
         `middle_name`, 
         `gender`, 
         `birth_date`, 
         `email`, 
         `phone`, 
         `avatar_path`
        )
        VALUES 
            (
             :first_name, 
             :last_name, 
             :middle_name, 
             :gender, 
             :birth_date, 
             :email, 
             :phone, 
             :avatar_path
             )";

        try {
            $preparedPrompt = $this->dbConnection->prepare($sqlPrompt);
            $preparedPrompt->execute($user->convertInfoToArray());
            return (int)$this->dbConnection->lastInsertId();
        } catch (PDOException $e) {
            if (str_contains($e->getMessage(), 'Duplicate entry')) {

                if (str_contains($e->getMessage(), 'email_idx')) {
                    throw new InvalidArgumentException('Пользователь с таким email уже существует');
                }
                if (str_contains($e->getMessage(), 'phone_idx')) {
                    throw new InvalidArgumentException('Пользователь с таким телефоном уже существует');
                }
            }
            throw $e;
        }
    }

    function findUserInDatabase(int $userId): ?User
    {
        $sqlPrompt = "SELECT 
            `user_id`,
            `first_name`, 
            `last_name`, 
            `middle_name`, 
            `gender`, 
            `birth_date`, 
            `email`, 
            `phone`, 
            `avatar_path`
        FROM `user`
        WHERE `user_id` = :user_id";

        $preparedPrompt = $this->dbConnection->prepare($sqlPrompt);
        $preparedPrompt->execute([':user_id' => $userId]);
        $userData = $preparedPrompt->fetch(PDO::FETCH_ASSOC);

        if ($userData) {
            $userData['id'] = $userId;
            return $this->convertArrayToUser($userData);
        }

        return null;
    }

    function updateUserInDatabase(User $user): void
    {
        $sqlPrompt = "UPDATE user 
                SET
                first_name = :first_name,
                last_name = :last_name,
                middle_name = :middle_name,
                gender = :gender,
                birth_date = :birth_date,
                email = :email,
                phone = :phone,
                avatar_path = :avatar_path
                WHERE user_id = :user_id";

        try {
            $preparedPrompt = $this->dbConnection->prepare($sqlPrompt);
            $updatingUserData = $user->convertInfoToArray();
            $updatingUserData['user_id'] = $user->getId();
            $preparedPrompt->execute($updatingUserData);
        } catch (PDOException $e) {
            if (str_contains($e->getMessage(), 'Duplicate entry')) {

                if (str_contains($e->getMessage(), 'email_idx')) {
                    throw new InvalidArgumentException(' Email id already exists');
                }

                if (str_contains($e->getMessage(), 'phone_idx')) {
                    throw new InvalidArgumentException('Phone id already exists');
                }
            }

            throw new RuntimeException("Failed to update user: " . $e->getMessage());
        }
    }

    function deleteUserFromDatabase(int $userId): void
    {
        $sqlPrompt = "DELETE FROM `user` WHERE `user_id` = :user_id";
        $preparedPrompt = $this->dbConnection->prepare($sqlPrompt);
        $preparedPrompt->execute([':user_id' => $userId]);
    }

    function convertArrayToUser(array $userInfo): User
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