<?php

namespace ListingBundle\Command;

use ListingBundle\Entity\Listing;
use ListingBundle\Entity\Vehicle\EngineMake;
use ListingBundle\Entity\Vehicle\EngineModel;
use ListingBundle\Entity\Vehicle\Type;
use ListingBundle\Service\Vehicle\TypeService;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class FixCommand extends ContainerAwareCommand
{

    const ENGINE_MAKE = 'engine_make';
    const ENGINE_MODEL = 'engine_model';

    const BUS_ENGINE_MAKE = 'bus_engine_make';
    const BUS_ENGINE_MODEL = 'bus_engine_model';

    protected function configure()
    {
        $this->setName('ttt:listing:fix');
        $this->setDescription('Runs listing database fixes');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->fixStatus();
        $this->fixBusFuelType();
        $this->fixBusConditions(Listing\Attribute::BUS_EXTERIOR_CONDITION);
        $this->fixBusConditions(Listing\Attribute::BUS_INTERIOR_CONDITION);
        $this->fixAttributeValueListingCopy();
        $this->fixEngineMakeModels();
    }

    protected function fixStatus()
    {
        $em             = $this->getContainer()->get('doctrine.orm.entity_manager');
        $repository     = $em->getRepository(Listing::class);
        $listingService = $this->getContainer()->get('ttt.listing_service');
        $listings       = $repository->findAll();
        foreach ($listings as $listing) {
            /** @var Listing $listing */
            switch ($listing->getStatus()) {
                // Old STATUS_ACTIVE
                case 1:
                    $listing->setStatus(Listing::STATUS_FOR_SALE);
                    $listing->setApprovedByUser(true);
                    $em->persist($listing);
                    break;
                // Old STATUS_NOT_ACTIVE
                case 2:
                    $listing->setStatus(Listing::STATUS_FOR_SALE);
                    $listing->setApprovedByUser(false);
                    $em->persist($listing);
                    break;
            }
            if ($listingService->isUnderContract($listing)) {
                $listing->setStatus(Listing::STATUS_UNDER_CONTRACT);
                $em->persist($listing);
            }
            if ($listingService->isSold($listing)) {
                $listing->setStatus(Listing::STATUS_SOLD);
                $em->persist($listing);
            }
        }
        $em->flush();
    }

    protected function fixBusFuelType()
    {
        $em               = $this->getContainer()->get('doctrine.orm.entity_manager');
        $attributeService = $this->getContainer()->get('ttt.listing.attribute');
        $query            = $em->createQueryBuilder()
                               ->select('value')
                               ->from(Listing\Attribute\Value\StringValue::class, 'value')
                               ->join('value.attribute', 'attribute')
                               ->andWhere('attribute.entityType = :entityType')
                               ->andWhere('attribute.name = :name')
                               ->setParameters([
                                   'entityType' => Listing\Bus::class,
                                   'name'       => Listing\Attribute::BUS_FUEL_TYPE,
                               ]);
        $values           = $query->getQuery()->execute();
        foreach ($values as $value) {
            /** @var Listing\Attribute\Value $value */
            $em->remove($value);
            if (is_numeric($value->getValue())) {
                $newValue = $attributeService->createValue($value->getValue(), $value->getAttribute());
                $newValue->setListing($value->getListing());
                $em->persist($newValue);
            }
        }
        $em->flush();
    }

    protected function fixBusConditions($condition)
    {
        $em     = $this->getContainer()->get('doctrine.orm.entity_manager');
        $query  = $em->createQueryBuilder()
                     ->select('value')
                     ->from(Listing\Attribute\Value\IntegerValue::class, 'value')
                     ->join('value.attribute', 'attribute')
                     ->andWhere('attribute.entityType = :entityType')
                     ->andWhere('attribute.name = :name')
                     ->andWhere('value.value NOT IN (:values)')
                     ->setParameters([
                         'entityType' => Listing\Bus::class,
                         'name'       => $condition,
                         'values'     => array_keys(Listing\Attribute::BUS_CONDITIONS),
                     ]);
        $values = $query->getQuery()->execute();
        foreach ($values as $value) {
            $em->remove($value);
        }
        $em->flush();
    }

    /**
     *
     */
    protected function fixEngineMakeModels()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $listingIterator = $em->createQueryBuilder()
            ->select('listing')
            ->from(Listing::class, 'listing')
            ->getQuery()
            ->iterate();

        foreach ($listingIterator as $listingRow) {
            $listing = $listingRow[0];

            $this->createEngineMakeModels($listing);
        }

        $this->deleteEngineMakeModelValues();
        $this->deleteEngineMakeModelAttributes();
    }

    /**
     * @param Listing $listing
     */
    private function createEngineMakeModels(Listing $listing)
    {

        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $attributeService = $this->getContainer()->get('ttt.listing.attribute');
        $vehicleTypeService = $this->getContainer()->get('ttt.listing.vehicle.type');

        $type = $vehicleTypeService->getListingTypeName($listing);
        $engineMakeValue = null;
        $engineModelValue = null;

        switch($type) {
            case TypeService::TRACTOR:

                $engineMakeValue  = $attributeService->get($listing, self::ENGINE_MAKE);
                $engineModelValue = $attributeService->get($listing, self::ENGINE_MODEL);

                break;
            case TypeService::BUS:

                $engineMakeValue  = $attributeService->get($listing, self::BUS_ENGINE_MAKE);
                $engineModelValue = $attributeService->get($listing, self::BUS_ENGINE_MODEL);

                break;
        }

        $type = $em->getRepository(Type::class)->findOneBy(['name' => $type]);

        $engineMakeValue  = $engineMakeValue ? $engineMakeValue->getValue() : '';
        $engineModelValue = $engineModelValue ? $engineModelValue->getValue() : '';

        $engineMakeValue = ucfirst(strtolower($engineMakeValue));
        $engineModelValue = ucfirst(strtolower($engineModelValue));

        if($engineMakeValue) {
            $engineMake = $em->getRepository(EngineMake::class)->findOneBy(['name' => $engineMakeValue]);

            if(empty($engineMake)) {
                $engineMake = new EngineMake();
                $engineMake->setName($engineMakeValue);
                $engineMake->addType($type);
                $em->persist($engineMake);
                $em->flush($engineMake);
            }

            if($engineModelValue) {
                $engineModel = $em->getRepository(EngineModel::class)->findOneBy([
                    'name' => $engineModelValue,
                    'engineMake' => $engineMake
                ]);

                if(empty($engineModel)) {
                    $engineModel = new EngineModel();
                    $engineModel->setName($engineModelValue);
                    $engineModel->setEngineMake($engineMake);
                    $engineModel->setType($type);
                    $em->persist($engineModel);
                    $em->flush($engineModel);
                }
            }
        }
    }

    /**
     *
     */
    private function deleteEngineMakeModelValues()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $valueIterator = $em->createQueryBuilder()
            ->select('value, attribute, listing')
            ->from(Listing\Attribute\Value\StringValue::class, 'value')
            ->join('value.attribute', 'attribute')
            ->leftJoin('value.listing', 'listing')
            ->where('attribute.name IN (:attributeNames)')
            ->setParameter('attributeNames', [
                self::ENGINE_MAKE, self::ENGINE_MODEL,
                self::BUS_ENGINE_MAKE, self::BUS_ENGINE_MODEL
            ])
            ->getQuery()
            ->iterate();
        $batchSize = 20;
        $i = 0;
        while (($valueRow = $valueIterator->next()) !== false) {
            $value = $valueRow[0];

            $em->remove($value);
            if (($i % $batchSize) === 0) {
                $em->flush();
                $em->clear();
            }
            ++$i;
        }
        $em->flush();
    }

    private function deleteEngineMakeModelAttributes()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $em->createQueryBuilder()
            ->delete(Listing\Attribute::class, 'attribute')
            ->where('attribute.name IN (:attributeNames)')
            ->setParameter('attributeNames', [
                self::ENGINE_MAKE, self::ENGINE_MODEL,
                self::BUS_ENGINE_MAKE, self::BUS_ENGINE_MODEL
            ])
            ->getQuery()
            ->execute();
    }

    protected function fixAttributeValueListingCopy()
    {
        $em      = $this->getContainer()->get('doctrine.orm.entity_manager');
        $classes = $em->getClassMetadata(Listing\Attribute\Value::class)->subClasses;
        foreach ($classes as $class) {
            $repository = $em->getRepository($class);
            $values     = $repository->createQueryBuilder('value')
                                     ->where('value.listingCopy IS NULL')
                                     ->getQuery()
                                     ->execute();
            foreach ($values as $value) {
                /** @var Listing\Attribute\Value $value */
                if ( ! $value->getListing()) {
                    $em->remove($value);
                } else {
                    $value->setListing($value->getListing());
                    $em->persist($value);
                }
            }
        }
        $em->flush();
    }

}