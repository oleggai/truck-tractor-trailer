<?php

namespace ListingBundle\Service;

use ListingBundle\Entity\Listing;
use ListingBundle\Entity\Vehicle\CabinType;
use ListingBundle\Entity\Vehicle\Type;
use ListingBundle\Service\Traits\BaseFilterTrait;

class TractorFilterService extends BaseFilter
{
    use BaseFilterTrait;

    protected $type = Type::TRACTOR;

    public function initQuery()
    {
        parent::initQuery();
        $this->query
            ->join('main.tractorItems', 'items')
            ->leftJoin('main.cabinType', 'cabin_type');
    }

    public function setEcmAvailable($val)
    {
        $this->query
            ->andWhere('main.ecm = :main_ecm')
            ->setParameter('main_ecm', $val);
    }

    public function setCabinType($cabinType)
    {
        $this->query
            ->andWhere('main.cabinType = :cabinType')
            ->setParameter('cabinType', $cabinType);
    }

    public function getCabinTypes()
    {
        $qb = $this->em->createQueryBuilder();
        $types = clone $this->query->select('cabin_type.id');
        $types->andWhere('cabin_type.id IS NOT NULL');
        $types = $types->groupBy('cabin_type.id')->getQuery()->execute();

        $types = array_map(function ($type) {
            return $type['id'];
        }, $types);

        if (count($types) > 0) {
            $qb->select('type')
                ->from(CabinType::class, 'type')
                ->where($qb->expr()->in('type.id', $types))
                ->orderBy('type.name', 'ASC');

            return $qb->getQuery()->execute();
        } else {
            return $this->em->getRepository(CabinType::class)->findAll();
        }
    }

    public function isListingMatchFilter(Listing $listing, $filter)
    {
        /** @var Listing\Tractor $listing */

        if(!($listing instanceof Listing\Tractor)){
            return false;
        }

        if (!parent::isListingMatchFilterBase($listing, $filter)) {
            return false;
        }

        if (isset($filter['ecm'])) {
            $ecm = $listing->getECM();
            if ($ecm != $filter['ecm']) {
                return false;
            }
        }

        if (isset($filter['cabinType'])) {
            $cabinType = $listing->getCabinType();
            /** @var CabinType $cabinType */
            if ($cabinType && $cabinType->getId() != $filter['cabinType']) {
                return false;
            }
        }

        if (isset($filter['mileageMin']) || isset($filter['mileageMax'])) {
            $mileage = $listing->getItems()[0]->getMileage();
            if (isset($filter['mileageMin'])) {
                if (!($mileage >= $filter['mileageMin'])) {
                    return false;
                }
            }

            if (isset($filter['mileageMax'])) {
                if (!($mileage <= $filter['mileageMax'])) {
                    return false;
                }
            }
        }

        if (isset($filter['horsePowerMin']) || isset($filter['horsePowerMax'])) {
            $horsePower = $listing->getHorsePower();
            if (isset($filter['horsePowerMin'])) {
                if (!($horsePower >= $filter['horsePowerMin'])) {
                    return false;
                }
            }

            if (isset($filter['horsePowerMax'])) {
                if (!($horsePower <= $filter['horsePowerMax'])) {
                    return false;
                }
            }
        }

        return true;
    }
}