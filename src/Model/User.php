<?php

namespace App\Model;

class User
{
    function __construct(
        private ?int    $id,
        private string  $firstName,
        private string  $lastName,
        private ?string $middleName,
        private string  $gender,
        private string  $birthDate,
        private string  $email,
        private ?string $phone,
        private ?string $avatarPath
    )
    {
    }

    function getId(): ?int
    {
        return $this->id;
    }

    function getFirstName(): string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): void
    {
        $this->firstName = $firstName;
    }

    function getLastName(): string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): void
    {
        $this->lastName = $lastName;
    }

    function getMiddleName(): ?string
    {
        return $this->middleName;
    }

    public function setMiddleName(?string $middleName): void
    {
        $this->middleName = $middleName;
    }

    function getGender(): string
    {
        return $this->gender;
    }

    public function setGender(string $gender): void
    {
        $this->gender = $gender;
    }

    function getBirthDate(): string
    {
        return $this->birthDate;
    }

    public function setBirthDate(string $birthDate): void
    {
        $this->birthDate = $birthDate;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): void
    {
        $this->phone = $phone;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    function getAvatarPath(): ?string
    {
        return $this->avatarPath;
    }

    function setAvatarPath(?string $newAvatarPath): void
    {
        $this->avatarPath = $newAvatarPath;
    }

    function convertInfoToArray(): array
    {
        return [
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
            'middle_name' => $this->middleName,
            'gender' => $this->gender,
            'birth_date' => $this->birthDate,
            'email' => $this->email,
            'phone' => $this->phone,
            'avatar_path' => $this->avatarPath
        ];
    }
}