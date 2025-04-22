<?php

namespace App\Repository;

use App\Entity\RefreshTokens;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\User;

/**
 * @extends ServiceEntityRepository<RefreshTokens>
 */
class RefreshTokensRepository extends ServiceEntityRepository
{

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RefreshTokens::class);
    }

    public function insertRefreshToken(String $rt, User $user)
    {
        $entityManager = $this->getEntityManager();
        $refreshToken = new RefreshTokens();
        $model = $refreshToken->createForUserWithTtl($rt,$user,8*3600);
        $entityManager->persist($model);
        $entityManager->flush();
    }
}