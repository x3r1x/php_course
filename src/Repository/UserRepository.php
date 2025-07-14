<?php
declare(strict_types=1);

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;

class UserRepository extends ServiceEntityRepository {
    private EntityManagerInterface $entityManager;

    public function __construct(ManagerRegistry $managerRegistry)
    {
        parent::__construct($managerRegistry, User::class);
        $this->entityManager = $this->getEntityManager();
    }

    public function storeData(User $userData): int
    {
        $this->entityManager->persist($userData);
        $this->entityManager->flush();
        return $userData->getId();
    }

    public function findUserById(int $userId): ?User
    {
        return $this->find($userId);
    }

    public function deleteUserById(int $userId): void
    {
        $user = $this->findUserById($userId);
        $this->entityManager->remove($user);
        $this->entityManager->flush();
    }

    public function getAllUsers(): array
    {
        return $this->findBy([]);
    }
}
