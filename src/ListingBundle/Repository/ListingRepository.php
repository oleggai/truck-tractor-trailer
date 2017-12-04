<?php

namespace ListingBundle\Repository;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping;
use Doctrine\ORM\Tools\Pagination\Paginator;
use ListingBundle\Entity\Listing;
use ProfileBundle\Entity\User;
use WebsiteBundle\Repository\ExpiredRepositoryInterface;

class ListingRepository extends EntityRepository implements ExpiredRepositoryInterface
{

    private $qb;
    private $em;

    /**
     * ListingRepository constructor.
     * @param EntityManager $em
     * @param Mapping\ClassMetadata $class
     */
    public function __construct(EntityManager $em, Mapping\ClassMetadata $class)
    {
        parent::__construct($em, $class);
        $this->em = $em;
        $this->qb = $this->em->createQueryBuilder();
    }

    public function getListingByIdAndUser($id, $user)
    {
        $query = $this->createQueryBuilder('listing')
            ->where('listing.id = :id')
            ->andWhere('listing.user = :user')
            ->setParameter('id', $id)
            ->setParameter('user', $user);

        return $query->getQuery()->getOneOrNullResult();
    }

    public function getRecentlyAddedListings($countItems)
    {
        $this->qb->select('l')
            ->from(Listing::class, 'l')
            ->andWhere('l.approvedByAdmin = 1')
            ->andWhere('l.approvedByUser = 1')
            ->orderBy('l.createdAt', 'DESC')
            ->setMaxResults($countItems);

        return $this->qb->getQuery()->execute();
    }

    public function findAllUserListings(User $user, $limit = 0, $page = 1)
    {
        return $this->findLimitUserListings($user, $limit, $page);
    }

    public function findLimitUserListings(User $user, $limit = 4, $page = 1, $attribute = '', $sort = '')
    {
        $query = $this->createQueryBuilder('listing')
            ->setFirstResult($limit * ($page - 1))
            ->where('listing.user = :user')
            ->setParameter('user', $user);

        if ($limit > 0) {
            $query->setMaxResults($limit);
        }

        if($attribute) {
            switch ($attribute) {
                case 'name':
                    $query->addOrderBy('listing.name', $sort);
                    break;
                case 'price':
                    $query->leftJoin('listing.items', 'items')
                        ->addOrderBy('items.price', $sort);
                    break;
                case 'createdAt':
                    $query->addOrderBy('listing.createdAt', $sort);
                    break;
                case 'views':
                    $query->leftJoin('listing.views', 'views')
                        ->addSelect('count(views.id) as HIDDEN count_views')
                        ->addGroupBy('listing.id')
                        ->addOrderBy('count_views', $sort);
                    break;
                case 'status':
                    $query->addSelect('
                    CASE WHEN listing.status = :status_active THEN 0 
                        WHEN listing.status = :status_under_contract THEN 1 
                        WHEN listing.status = :status_sold THEN 2 
                        ELSE 3 as HIDDEN sortCondition')
                        ->setParameter('status_active', 5)
                        ->setParameter('status_under_contract', 6)
                        ->setParameter('status_sold', 4)
                        ->addOrderBy('sortCondition', $sort);
                    break;
            }
        } else {
            $query->addOrderBy('listing.createdAt', 'DESC');
        }

        $paginator = new Paginator($query);
        return $paginator;
    }

    /**
     * @param User $user
     * @return int
     */
    public function countUserListings(User $user)
    {
        $qb = $this->createQueryBuilder('listing')
            ->where('listing.user = :user')
            ->setParameter('user', $user)
            ->select('count(listing.id)');

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @param array $filterParams
     * @param $user User
     * @return mixed
     */
    public function filter(array $filterParams, $user)
    {

        $this->qb->select('l, s')
            ->from(Listing::class, 'l')
            ->join('l.status', 's')
            ->where('l.user = :user')
            ->setParameter('user', $user);

        /**
         * Filter Name
         */
        if ($filterParams['name'] !== null) {
            $this->qb->andWhere('l.name LIKE :name')
                ->setParameter('name', '%' . $filterParams['name'] . '%');
        }

        /**
         * Filter Manufacturer
         */
        if ($filterParams['manufacturer'] !== null) {
            $this->qb->andWhere('l.manufacturer LIKE :manufacturer')
                ->setParameter('manufacturer', '%' . $filterParams['manufacturer'] . '%');
        }

        /**
         * Filter Description
         */
        if ($filterParams['description'] !== null) {
            $this->qb->andWhere('l.description LIKE :description')
                ->setParameter('description', '%' . $filterParams['description'] . '%');
        }
        /**
         * Filter Model
         */
        if ($filterParams['model'] !== null) {
            $this->qb->andWhere('l.model LIKE :model')
                ->setParameter('model', '%' . $filterParams['model'] . '%');
        }

        /**
         * Filter Status
         */
        if ($filterParams['status'] !== null) {
            $this->qb->andWhere('l.status = :status')
                ->setParameter('status', $filterParams['status']);
        }

        $this->qb->orderBy('s.id', 'ASC');

        return $this->qb->getQuery()->execute();
    }

    public function getExpiredElements(\DateTime $expiryDate)
    {
        $query = $this->qb->select('l')
            ->from(Listing::class, 'l')
            ->where('l.createdAt < :expireDate')
            ->andWhere($this->qb->expr()->eq('l.isExpired', ':isExpired'))
            ->setParameter('expireDate', $expiryDate)
            ->setParameter('isExpired', false);

        return $query->getQuery()->execute();
    }
}