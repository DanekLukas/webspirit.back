<?php

namespace App\Repository;

use App\Entity\Text;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;
use App\Repository\CategoryRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * @extends ServiceEntityRepository<Text>
 */
class TextRepository extends ServiceEntityRepository
{
    const path = __DIR__.'/../../public/img/';

    public function __construct(ManagerRegistry $registry,
        private CategoryRepository $categoryRepository,
        private UserRepository $userRepository,
        private Security $security,
        )
    {
        parent::__construct($registry, Text::class);
    }
    public function getText(String $id): Text | null {
        // return $this->security->getUser();
        return $this->findOneBy(['id' => $id]);
    }

    public function getTexts(String | null $id): array | null {
        // return $this->security->getUser();
        if($id === null) {
            return $this->getEntityManager()->createQuery("select t from App\Entity\Text t where t.delete_date is null order by t.create_date desc")->setMaxResults(20)->getResult();
        }
        $category = $this->categoryRepository->findOneBy(['id' => $id]);
        if(!$category) return null;
        return $this->getEntityManager()->createQuery("select t from App\Entity\Text t where t.delete_date is null and t.category = :category")->setParameter('category', $category)
        ->getResult();
    }

    public function getFilteredTexts(String $category): array | null {
        $texts = $this->getEntityManager()->createQuery("select t from App\Entity\Text t where t.delete_date is null order by t.create_date desc")->setFirstResult(20)
        ->getResult();

        $res = [];

        foreach($texts as $text) {
            if($text->getCategory()->getName() === $category)
                $res[] = $text;
        }

        return count($res) === 0 ? null : $res;
    }

    public function getEditTexts(): array | null {
        return $this->getEntityManager()->createQuery("select t from App\Entity\Text t where t.delete_date is null and t.created_by = :me order by t.create_date desc")->setParameter('me', $this->security->getUser() )
        ->getResult();
    }

    public function insertText(String $title, String $inText, String $author, String $source, String $category_id, int $timeZone): Text | null {
        $category = $this->categoryRepository->findOneBy(['id' => $category_id]);
        if(!$category) return null;
        $entityManager = $this->getEntityManager();
        $text = new Text();
        $text->setId(Uuid::v4()->toString());
        $text->setCreateDate((new \DateTime())->modify("$timeZone hour"));
        $text->setCreatedBy($this->security->getUser());
        $text->setTitle($title);
        $text->setText($inText);
        $text->setAuthor($author);
        $text->setSource($source);
        $text->setCategory($category);
        $entityManager->persist($text);
        $entityManager->flush();
        return $text;
    }   

    public function updateText(String $id, String $title, String $inText, String $author, String $source, String $category_id, int $timeZone): Text | null {
        $text  = $this->find($id);
        if(!$text || $text->getDeleteDate() !== null || $text->getCreatedBy() !== $this->security->getUser()) return null;
        $category = $this->categoryRepository->findOneBy(['id' => $category_id]);
        if(!$category) return null;
        $entityManager = $this->getEntityManager();
        $text->setLastUpdate((new \DateTime())->modify("$timeZone hour"));
        $text->setUpdatedBy($this->security->getUser());
        $text->setTitle($title);
        $text->setText($inText);
        $text->setAuthor($author);
        $text->setSource($source);
        $text->setCategory($category);
        $entityManager->persist($text);
        $entityManager->flush();
        return $text;
    }   

    private static function removeExt(string $name): string
    {
        return substr($name,0,strrpos($name,'.'));
    }

    //    /**
    //     * @return Text[] Returns an array of Text objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('t')
    //            ->andWhere('t.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('t.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Text
    //    {
    //        return $this->createQueryBuilder('t')
    //            ->andWhere('t.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
