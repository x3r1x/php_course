<?php

class User {
    function __construct(
        private int $id,
        private string  $firstName,
        private string  $lastName,
        private ?string $middleName,
        private string $gender,
        private string $birthDate,
        private string  $email,
        private ?string $phone,
        private ?string $avatarPath
    ) {}

    function getId(): int
    {
        return $this->id;
    }

    function getFirstName(): string
    {
        return $this->firstName;
    }

    function getLastName(): string
    {
        return $this->lastName;
    }

    function getMiddleName(): ?string
    {
        return $this->middleName;
    }

    function getGender(): string
    {
        return $this->gender;
    }

    function getBirthDate(): string
    {
        return $this->birthDate;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }
    public function getEmail(): string
    {
        return $this->email;
    }

    function getAvatarPath(): ?string
    {
        return $this->avatarPath;
    }
}