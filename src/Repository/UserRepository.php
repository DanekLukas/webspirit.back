<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;
use Symfony\Bundle\SecurityBundle\Security;

const roles = ['ROLE_USER', 'ROLE_ADMIN'];

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository
{

    public function __construct(ManagerRegistry $registry,
    private Security $security,
    )
    {
        parent::__construct($registry, User::class);
    }

    public function getUser(String $id): ?User {
        return $this->findOneBy(['id' => $id]);
    }

    public function getOwn(): ?User {
        return $this->security->getUser();
    }

    public function activateAccount(string $firstName, string $lastName, int $age, string $password, int $timeZone): ?User {
        $name = $this->security->getUser()->getUserIdentifier();
        $user = $this->findOneBy(['name' => $name]);
        if($user->getActive() !== 0) return $user;
        $user->setActive(1);
        $user->setFirstName($firstName);
        $user->setLastName($lastName);
        $user->setPassword($password);
        $user->setBirth((new \DateTime())->modify("-$age year"));
        $user->setLastUpdate((new \DateTime())->modify("$timeZone hour"));
        $user->setUpdatedBy($this->security->getUser());
        $entityManager = $this->getEntityManager();
        $entityManager->persist($user);
        $entityManager->flush();
        return $user;
    }

    public function changePassword(String $password, int $timeZone) {
        $name = $this->security->getUser()->getUserIdentifier();
        $user = $this->findOneBy(['name' => $name]);
        $user->setPassword($password);
        $user->setLastUpdate((new \DateTime())->modify("$timeZone hour"));
        $user->setUpdatedBy($this->security->getUser());
        $entityManager = $this->getEntityManager();
        $entityManager->persist($user);
        $entityManager->flush();
        return $user;
    }

    public function getUserByEmail(String $email): ?User {
        return $this->findOneBy(['email' => $email]);
    }

    static function envel(String $item): String {
        return '"'.$item.'"';
    }

    static function isIn(String $item): String {
        $toBeUsed = [];
        foreach(roles as $role)
            if (str_contains($item, $role))
                array_push($toBeUsed, UserRepository::envel($role));
        
        if(count($toBeUsed) === 0)
            array_push($toBeUsed, UserRepository::envel(roles[0]));
        
        return '['.implode(",", $toBeUsed).']';
    }

    public function insertUser(String $name, String $email, String $role, int $timeZone): User | null {
        if($this->findOneBy(['name' => $name])) return null;
        $entityManager = $this->getEntityManager();
        $user = new User();
        $user->setCreateDate((new \DateTime())->modify("$timeZone hour"));
        $user->setCreatedBy($this->security->getUser());
        $user->setName($name);
        $user->setId(Uuid::v4()->toString());
        $user->setEmail($email);
        $user->setActive(0);
        $user->setRole(UserRepository::isIn($role));
        $entityManager->persist($user);
        $entityManager->flush();
        return $user;
    }

    public function updateUser(String $id, String $name, String $password, String $role, int $timeZone): User | null {
        $user = $this->find($id);
        if($this->findOneBy(['name' => $name])) return null;
        $entityManager = $this->getEntityManager();
        $user->setLastUpdate((new \DateTime())->modify("$timeZone hour"));
        $user->setUpdatedBy($this->security->getUser());
        $user->setName($name);
        $user->setPassword($password);
        $user->setRole(UserRepository::isIn($role));
        $entityManager->persist($user);
        $entityManager->flush();
        return $user;
    }

//    /**
//     * @return User[] Returns an array of User objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('u')
//            ->andWhere('u.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('u.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?User
//    {
//        return $this->createQueryBuilder('u')
//            ->andWhere('u.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
