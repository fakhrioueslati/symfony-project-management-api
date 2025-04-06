<?php

namespace App\Repository;

use App\Entity\Project;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Project>
 */
class ProjectRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Project::class);
    }

    public function findByIdAndOwnerOrAssignedUser(int $id,$user)
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.roleAssignments', 'ra')
            ->leftJoin('ra.user', 'u')
            ->where('p.id = :id')
            ->andWhere('p.owner = :user OR u.id = :user')
            ->setParameter('id', $id)
            ->setParameter('user', $user->getId())
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findAllAssignedProjects($user)
{
    return $this->createQueryBuilder('p')
        ->leftJoin('p.roleAssignments', 'ra')
        ->leftJoin('ra.user', 'u')
        ->where('u.id = :user') 
        ->setParameter('user', $user->getId())
        ->getQuery()
        ->getResult();  
}

    

    //    /**
    //     * @return Project[] Returns an array of Project objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('p.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Project
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
