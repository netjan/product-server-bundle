<?php

namespace NetJan\ProductServerBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\Query\Expr;
use Doctrine\Persistence\ManagerRegistry;
use NetJan\ProductServerBundle\Entity\Product;
use NetJan\ProductServerBundle\Exception as BundleException;
use NetJan\ProductServerBundle\Filter\ProductFilter;
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

    /**
     * @return Product[] Returns an array of Product objects
     */
    public function list(ProductFilter $filter): array
    {
        $qb = $this->createQueryBuilder('p');

        if (null === $filter->stock) {
            $qb->andWhere(new Expr\Andx(
                'p.amount > :amount'
            ));
            $qb->setParameter('amount', 5);
        } elseif (true === $filter->stock) {
            $qb->andWhere(new Expr\Andx(
                'p.amount > :amount'
            ));
            $qb->setParameter('amount', 0);
        } else { // false === $filter->stock
            $qb->andWhere(new Expr\Andx(
                'p.amount = :amount'
            ));
            $qb->setParameter('amount', 0);
        }

        return $qb->getQuery()->getResult();
    }

    public function save(Product $product)
    {
        try {
            $this->_em->persist($product);
            $this->_em->flush();
        } catch (ORMException $e) {
            $this->logger->error($e->getMessage());
            throw new BundleException\ORMException($e);
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            throw new BundleException\RepositoryException($e);
        }
    }

    public function remove(Product $product)
    {
        try {
            $this->_em->remove($product);
            $this->_em->flush();
        } catch (ORMException $e) {
            $this->logger->error($e->getMessage());
            throw new BundleException\ORMException($e);
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            throw new BundleException\RepositoryException($e);
        }
    }
}
