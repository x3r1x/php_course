<?php

namespace App\Model;

use InvalidArgumentException;
use PDO;
use PDOException;

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

    function findUserInDatabase(int $userId): ?array
    {
        $sqlPrompt = "SELECT 
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
        $user = $preparedPrompt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            return $user;
        }

        return null;
    }
}