<?php

namespace App\Repository;

use App\Entity\Comment;
use App\Entity\Text;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;
use App\Repository\TextRepository;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * @extends ServiceEntityRepository<Comment>
 */
class CommentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry,
                    private TextRepository $textRepository,
                    private Security $security,
                    )
    {
        parent::__construct($registry, Comment::class);
    }

    public function getComments(Text $text): array | null {
        // return $this->security->getUser();

        return $this->getEntityManager()->createQuery("select c from App\Entity\Comment c where c.delete_date is null and c.text = :text order by c.create_date asc")->setParameter('text',$text)
        ->getResult();
    }

    public function insertComment(String $id, String $commentText, int $timeZone): Comment | null {
        if(!($text = $this->textRepository->findOneBy(['id' => $id, 'delete_date' => null]))) return null;
        $entityManager = $this->getEntityManager();
        $comment = new Comment();
        $comment->setId(Uuid::v4()->toString());
        $comment->setCreateDate((new \DateTime())->modify("$timeZone hour"));
        $comment->setCreatedBy($this->security->getUser());
        $comment->setComment($commentText);
        $comment->setText($text);
        $entityManager->persist($comment);
        $entityManager->flush();
        return $comment;
    }

    //    /**
    //     * @return Comment[] Returns an array of Comment objects
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

    //    public function findOneBySomeField($value): ?Comment
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
