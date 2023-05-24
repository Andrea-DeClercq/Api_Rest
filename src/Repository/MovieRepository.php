<?php

namespace App\Repository;

use App\Entity\Movie;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Movie>
 *
 * @method Movie|null find($id, $lockMode = null, $lockVersion = null)
 * @method Movie|null findOneBy(array $criteria, array $orderBy = null)
 * @method Movie[]    findAll()
 * @method Movie[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MovieRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Movie::class);
    }

    public function save(Movie $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Movie $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findAllWithPagination(int $page,int $limit)
    {
        $qb = $this->createQueryBuilder('m')
            ->setFirstResult(($page - 1)* $limit)
            ->setMaxResults($limit);

            $query = $qb->getQuery();
            $query->setFetchMode(Movie::class, 'author', ClassMetadata::FETCH_EAGER);
            return $query->getResult();
    }

    public function findByIdForAuthor(int $id)
    {
        $qb = $this->createQueryBuilder('m')
            ->andWhere('m.id = :id')
            ->setParameter('id', $id);
        $query = $qb->getQuery();
        $query->setFetchMode(Movie::class, 'author', ClassMetadata::FETCH_EAGER);
        return $query->getResult();
    }
}
