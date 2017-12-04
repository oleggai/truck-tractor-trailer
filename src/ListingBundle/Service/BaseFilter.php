<?php

namespace ListingBundle\Service;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use ListingBundle\Entity\Listing;
use ListingBundle\Entity\Vehicle\Brand;
use ListingBundle\Entity\Vehicle\Type;
use SystemVariablesBundle\Service\VariableService;

abstract class BaseFilter implements FilterInterface
{
    const SIMPLE_WHERE = 1;

    const MULTIPLE_WHERE = 2;

    const sortType = [
        ['items.price', 'DESC'],
        ['items.price', 'ASC'],
        ['main.createdAt', 'DESC'],
        ['main.createdAt', 'ASC'],
        //        ['distance', 'ASC'],
        //        ['distance', 'DESC']
    ];

    protected $em;
    protected $qb;
    /** @var QueryBuilder */
    protected $query;
    protected $listing;
    protected $filters = [];
    protected $variableService;
    protected $countListings = false;
    protected $attributeService;

    protected $type;

    public function __construct(
        EntityManager $em,
        VariableService $variableService,
        $listing,
        AttributeService $attributeService
    ) {
        $this->em = $em;
        $this->variableService = $variableService;
        $this->listing = $listing;
        $this->attributeService = $attributeService;
        $this->initQuery();
    }

    public function initQuery()
    {
        $this->filters = [];
        $this->query = $this->em->createQueryBuilder()->from($this->listing, 'main')
            ->join('main.brand', 'brand')
            ->join('main.model', 'model')
            ->join('main.address', 'address')
            ->andWhere('main.approvedByUser = 1')
            ->andWhere('main.approvedByAdmin = 1');
    }

    //--checkbox--start
    public function setFleetMaintained($val)
    {
        $this->query
            ->andWhere('main.fleetMaintained = :main_fleetMaintained')
            ->setParameter('main_fleetMaintained', $val);
    }
    //--checkbox--end


    //--slider--start
    public function setPriceInterval($min, $max)
    {
        $this->query
            ->andWhere('items.price BETWEEN :price_min AND :price_max')
            ->setParameter('price_min', (int)$min)
            ->setParameter('price_max', (int)$max);
    }

    public function setYearInterval($min, $max)
    {
        $this->query
            ->andWhere('main.year BETWEEN :year_min AND :year_max')
            ->setParameter('year_min', $min)
            ->setParameter('year_max', $max);
    }

    public function setMileageInterval($min, $max)
    {
        $this->query
            ->andWhere('items.mileage BETWEEN :mileage_min AND :mileage_max')
            ->setParameter('mileage_min', $min)
            ->setParameter('mileage_max', $max);
    }

    //--slider--end

    public function setSearchQuery($query)
    {
        foreach (explode(' ', $query) as $index => $word) {
            $word = trim($word);
            $param = 'word' . $index;
            if ($word) {
                $this->query
                    ->andWhere("main.name LIKE :{$param} OR main.description LIKE :{$param}")
                    ->setParameter($param, '%' . $word . '%');
            }
        }
    }

    public function setAvailability($availability)
    {
        if (in_array($availability, [
            Listing::STATUS_FOR_SALE,
            Listing::STATUS_UNDER_CONTRACT,
            Listing::STATUS_SOLD,
        ])) {
            $this->query
                ->andWhere('main.status = :status')
                ->setParameter('status', $availability);
        }
    }

    public function setPlace($place)
    {
        $milesToKm = 1.60934;
        $this->query
            ->andWhere('GEO_DISTANCE(address.latitude, address.longitude, :latitude, :longitude) <= :distance')
            ->setParameter('latitude', $place['latitude'])
            ->setParameter('longitude', $place['longitude'])
            ->setParameter('distance', intval(!empty($place['distance']) ? $place['distance'] : 1) * $milesToKm);
    }

    //--multiple-select--start
    public function setBrand($brandId)
    {
        $this->addWhere('brand', $brandId);
    }

    protected function addWhere($referenceTableName, $parameter)
    {
        if (!array_key_exists($referenceTableName, $this->filters)) {
            $this->filters[$referenceTableName] = $this::SIMPLE_WHERE;
        }

        if ($this->filters[$referenceTableName] == $this::SIMPLE_WHERE) {
            $nameParameter = $referenceTableName . '_id';
            $this->query
                ->andWhere($referenceTableName . '.id = :' . $nameParameter)
                ->setParameter($nameParameter, $parameter);
        } else {
            throw new \Exception('You can use only one type of "Where" condition for one relations!');
        }
    }
    //--multiple-select--end


    //--get-initial-values--start

    public function setBrands(array $brandIds)
    {
        $this->addWhereMultipleParameters('brand', $brandIds);
    }

    protected function addWhereMultipleParameters($referenceTableName, array $parameters)
    {
        $qb = $this->em->createQueryBuilder();
        if (!array_key_exists($referenceTableName, $this->filters)) {
            $this->filters[$referenceTableName] = $this::MULTIPLE_WHERE;
            $this->query->andWhere($qb->expr()->in($referenceTableName . '.id', $parameters));
        } elseif ($this->filters[$referenceTableName] != $this::MULTIPLE_WHERE) {
            throw new \Exception('You can use only one type of "Where" condition for one relations!');
        } else {
            throw new \Exception('You can use "MultipleWhere" condition once for one relations!');
        }
    }

    public function getPriceRange()
    {
        $main = clone $this->query->select('items.price');
        $prices = $main->getQuery()->execute();
        $min = 0;
        $max = 200000;
        if (count($prices) > 0) {
            $min = min($prices)['price'] ?: $min;
            $max = max($prices)['price'] ?: $max;
        }

        return ['min' => $min, 'max' => $max];
    }

    public function getYearRange()
    {
        $main = clone $this->query->select('main.year');
        $years = $main->getQuery()->execute();
        $date = new \DateTime();
        $min = 1900;
        $max = $date->format('Y') + 1;
        if (count($years) > 0) {
            $min = min($years)['year'] ?: $min;
            $max = max($years)['year'] ?: $max;
        }

        return ['min' => $min, 'max' => $max];
    }

    //--get-initial-values--end

    public function getMileageRange()
    {
        $main = clone $this->query->select('items.mileage');
        $mileages = $main->getQuery()->execute();
        $min = 0;
        $max = 12000;
        if (count($mileages) > 0) {
            $min = min($mileages)['mileage'] ?: $min;
            $max = max($mileages)['mileage'] ?: $max;
        }

        return ['min' => $min, 'max' => $max];
    }

    public function getBrands()
    {
        $qb = $this->em->createQueryBuilder();
        $brands = clone $this->query->select('brand.id');
        $brands = $brands->groupBy('brand.id')->getQuery()->execute();

        $brands = array_map(function ($brand) {
            return $brand['id'];
        }, $brands);

        if (count($brands) > 0) {
            $qb->select('brand')
                ->from(Brand::class, 'brand')
                ->where($qb->expr()->in('brand.id', $brands))
                ->orderBy('brand.name', 'ASC');

            return $qb->getQuery()->execute();
        } else {
            $type = $this->em->getRepository(Type::class)->findOneBy(['id' => $this->type]);

            return $type->getBrands()->toArray();
        }
    }

    public function setSortType($sortType)
    {
        if (array_key_exists($sortType, $this::sortType)) {
            $currentSort = $this::sortType[$sortType];
            $this->query->orderBy($currentSort[0], $currentSort[1]);
        }
    }

    public function getActiveFilters()
    {
        return $this->filters;
    }

    public function getQuery()
    {
        return $this->query;
    }

    public function getListingsCount()
    {
        $main = clone $this->query->select('count(DISTINCT main.id)');

        return $main->getQuery()->getSingleScalarResult();
    }

    public function getPageResult($pageNumber, $getQueryBuilder = false)
    {
        $listingsPerPage = $this->variableService->get('count_listings_per_page');
        $main = clone $this->query->select('main');
        $main->groupBy('main.id')
            ->setFirstResult($listingsPerPage * $pageNumber - $listingsPerPage)
            ->setMaxResults($listingsPerPage);

        return $getQueryBuilder ? $main : $main->getQuery()->execute();
    }

    public function getResult()
    {
        $main = clone $this->query->select('main');

        return $main->getQuery()->execute();
    }


    /**
     * @param QueryBuilder $queryBuilder
     * @param $limit
     * @param string $orderBy
     *
     * @return QueryBuilder
     */
    public function setLimitAndOrderBy(QueryBuilder $queryBuilder, $limit, $orderBy = 'updatedAt')
    {

        return $queryBuilder->setFirstResult(0)
            ->setMaxResults($limit)
            ->orderBy("main.$orderBy", 'DESC');
    }

    public function isListingMatchFilterBase(Listing $listing, $filter)
    {
        if (isset($filter['searchQuery']) && strlen(trim($filter['searchQuery'])) > 0) {
            $match = false;

            foreach (explode(' ', $filter['searchQuery']) as $word) {
                $word = trim($word);
                if ($word) {
                    if (strpos($listing->getName(), $word) || strpos($listing->getDescription(), $word)) {
                        $match = true;
                        break;
                    }
                }
            }
            if (!$match) {
                return false;
            }
        }

        if (isset($filter['fleetMaintained'])) {
            $fleetMaintained = $listing->getFleetMaintained();
            if ($fleetMaintained != $filter['fleetMaintained']) {
                return false;
            }
        }

        if (isset($filter['priceMin']) || isset($filter['priceMax'])) {
            $price = $listing->getPrice();
            if (isset($filter['priceMin'])) {
                if (!($price >= $filter['priceMin'])) {
                    return false;
                }
            }

            if (isset($filter['priceMax'])) {
                if (!($price <= $filter['priceMax'])) {
                    return false;
                }
            }
        }

        if (isset($filter['yearMin']) || isset($filter['yearMax'])) {
            $year = $listing->getYear();
            if (isset($filter['yearMin'])) {
                if (!($year >= $filter['yearMin'])) {
                    return false;
                }
            }

            if (isset($filter['yearMax'])) {
                if (!($year <= $filter['yearMax'])) {
                    return false;
                }
            }
        }

        if (isset($filter['brands']) && is_array($filter['brands'])) {
            $brand = $listing->getBrand();
            /** @var Brand $brand */
            if (!in_array($brand->getId(), $filter['brands'])) {
                return false;
            }
        }

//        $place = $listing->getAddress();
//        if ($place != $filter['place']) {
//
////      [place] => Array
////        (
////            [latitude] => 41.7457732
////            [longitude] => -71.42696230000001
////            [place] => 1230 U.S. 1, Warwick, RI, United States
////            [distance] => 10
////        )
//
//            return false;
//        }

        return true;
    }
}