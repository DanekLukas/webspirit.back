<?php

namespace App\Repository;

use App\Entity\Category;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * @extends ServiceEntityRepository<Category>
 */
class CategoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry,
                               private Security $security,)
    {
        parent::__construct($registry, Category::class);
    }

    public function getCategory(String $id): Category | null {
        // return $this->security->getUser();
        return $this->findOneBy(['id' => $id]);
    }

    public function getCategories(): array | null {
        // return $this->security->getUser();

        return $this->getEntityManager()->createQuery("select c from App\Entity\Category c where c.delete_date is null")
        ->getResult();
    }

    public function getFilteredCategories(): array | null {
        // return $this->security->getUser();

        $categories = [];

        $texts = $this->getEntityManager()->createQuery("select t from App\Entity\Text t where t.delete_date is null order by t.create_date desc")->setFirstResult(2)
        ->getResult();

        foreach($texts as $text) {
            $category = $text->getCategory();
            $id = $category->getId();
            if(!array_key_exists($id, $categories))
                $categories[$id] = $category;
        }
        $res = array_values($categories);
        return count($res) === 0 ? null : $res;
    }

    public function insertCategory(String $name, String $name_en, int $timeZone): Category | null {
        if($this->findOneBy(['name' => $name])) return null;
        $entityManager = $this->getEntityManager();
        $category = new Category();
        $category->setId(Uuid::v4()->toString());
        $category->setCreateDate((new \DateTime())->modify("$timeZone hour"));
        $category->setCreatedBy($this->security->getUser());
        $category->setName($name);
        $category->setNameEn($name_en);
        $entityManager->persist($category);
        $entityManager->flush();
        return $category;
    }

    public function updateCategory(String $id, String $name, String $name_en, int $timeZone): Category | null {
        $category = $this->find($id);
        if(!$category || $category->getDeleteDate() !== null /*|| $category->getCreatedBy() !== $this->security->getUser() || $this->findOneBy(['name' => $name])*/) return null;
        $entityManager = $this->getEntityManager();
        $category->setLastUpdate((new \DateTime())->modify("$timeZone hour"));
        $category->setUpdatedBy($this->security->getUser());
        $category->setName($name);
        $category->setNameEn($name_en);
        $entityManager->persist($category);
        $entityManager->flush();
        return $category;
    }

//    /**
//     * @return Category[] Returns an array of Category objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('c')
//            ->andWhere('c.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('c.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Category
//    {
//        return $this->createQueryBuilder('c')
//            ->andWhere('c.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
