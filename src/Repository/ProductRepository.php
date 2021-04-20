<?php

namespace NetJan\ProductServerBundle\Repository;

use NetJan\ProductServerBundle\Entity\Product;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\Expr as Expr;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

/**
 * @method Product|null find($id, $lockMode = null, $lockVersion = null)
 * @method Product|null findOneBy(array $criteria, array $orderBy = null)
 * @method Product[]    findAll()
 * @method Product[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProductRepository extends ServiceEntityRepository implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
    }

    // /**
    //  * @return Product[] Returns an array of Product objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('p.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Product
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
    public function getList(?array $filters = [])
    {
        return $this->queryList($filters)->getQuery()->getResult();
    }

    public function queryList(?array $filters = [])
    {
        $qb = $this->createQueryBuilder('p');
        // dump($filters);

        if (!isset($filters['stock']) || null === $filters['stock']) {
            $qb->andWhere(new Expr\Andx(
                'p.amount > :amount'
            ));
            $qb->setParameter('amount', 5);
        } elseif ($filters['stock']) {
            $qb->andWhere(new Expr\Andx(
                'p.amount > :amount'
            ));
            $qb->setParameter('amount', 0);
        } else { // $filters['stock'] == false
            $qb->andWhere(new Expr\Andx(
                'p.amount = :amount'
            ));
            $qb->setParameter('amount', 0);
        }

        return $qb;
    }

    public function save(Product $product) {
        $result = [
            'error' => false,
            'messages' => [],
        ];

        try {
            $this->_em->persist($product);
            $this->_em->flush();
        } catch (\Exception $e) {
            $result['error'] = true;
            $result['messages'][] = 'Data saving error!';
            $this->logger->error($e->getMessage());
        }

        return $result;
    }

    public function remove(Product $product)
    {
        $result = [
            'error' => false,
            'messages' => [],
        ];

        try {
            $this->_em->remove($product);
            $this->_em->flush();
        } catch (\Exception $e) {
            $result['error'] = true;
            $result['messages'][] = 'Data saving error!';
            $this->logger->error($e->getMessage());
        }

        return $result;
    }
}
