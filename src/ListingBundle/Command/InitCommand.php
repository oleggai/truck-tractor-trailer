<?php

namespace ListingBundle\Command;

use ListingBundle\Entity\Listing\Attribute;
use ListingBundle\Entity\Vehicle\Brand;
use ListingBundle\Entity\Vehicle\BusType;
use ListingBundle\Entity\Vehicle\Condition;
use ListingBundle\Entity\Vehicle\EngineMake;
use ListingBundle\Entity\Vehicle\EngineModel;
use ListingBundle\Entity\Vehicle\Model;
use ListingBundle\Entity\Vehicle\TrailerType;
use ListingBundle\Entity\Vehicle\TractorType;
use ListingBundle\Entity\Vehicle\CabinType;
use ListingBundle\Entity\Vehicle\Type;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

class InitCommand extends ContainerAwareCommand
{

    protected function configure()
    {
        $this->setName('ttt:listing:init');
        $this->setDescription('Create default listing-related data: attributes, trailer types if they do not exist.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->initAttributes();
        $this->initTrailerTypes();
        $this->initTractorTypes();
        $this->initBusTypes();
        $this->initTractorCabinTypes();
        $this->initVehicles();
        //$this->initEngineMakeModels();
        $this->initVehicleConditions();
    }

    protected function initAttributes()
    {
        $entityManager = $this->getContainer()->get('doctrine.orm.entity_manager');
        $attributeRepository = $entityManager->getRepository('ListingBundle:Listing\Attribute');
        $config = $this->getContainer()->get('kernel')->locateResource('@ListingBundle/Resources/data/attributes.yml');
        foreach (Yaml::parse(file_get_contents($config)) as $entity) {
            foreach ($entity['fieldsets'] as $fieldset) {
                $weight = 0;
                foreach ($fieldset['fields'] as $fieldName => $field) {
                    $attribute = $attributeRepository->findOneBy([
                        'name' => $fieldName,
                        'entityType' => $entity['entityType']
                    ]);
                    if (empty($attribute)) {
                        $attribute = new Attribute();
                        $attribute->setName($fieldName);
                        $attribute->setEntityType($entity['entityType']);
                    }
                    $attribute->setLabel($field['label']);
                    $attribute->setFieldType($field['fieldType']);
                    $attribute->setFieldset($fieldset['name']);
                    $attribute->setWeight($weight);
                    $entityManager->persist($attribute);
                    $weight++;
                }
            }
        }
        $entityManager->flush();
    }

    protected function initTrailerTypes()
    {
        $entityManager = $this->getContainer()->get('doctrine.orm.entity_manager');
        $trailerTypeRepository = $entityManager->getRepository('ListingBundle:Vehicle\TrailerType');
        $config = $this->getContainer()->get('kernel')->locateResource('@ListingBundle/Resources/data/trailer_types.yml');
        foreach (Yaml::parse(file_get_contents($config))['trailerTypes'] as $name) {
            $trailerType = $trailerTypeRepository->findOneBy(['name' => $name]);
            if (empty($trailerType)) {
                $trailerType = new TrailerType();
                $trailerType->setName($name);
                $entityManager->persist($trailerType);
            }
        }
        $entityManager->flush();
    }

    protected function initTractorTypes()
	{
		$entityManager = $this->getContainer()->get('doctrine.orm.entity_manager');
		$tractorTypeRepository = $entityManager->getRepository('ListingBundle:Vehicle\TractorType');
		$config = $this->getContainer()->get('kernel')->locateResource('@ListingBundle/Resources/data/tractor_types.yml');
		foreach (Yaml::parse(file_get_contents($config))['tractorTypes'] as $name) {
			$tractorType = $tractorTypeRepository->findOneBy(['name' => $name]);
			if (empty($tractorType)) {
				$tractorType = new TractorType();
				$tractorType->setName($name);
				$entityManager->persist($tractorType);
			}
		}
		$entityManager->flush();
	}

    /**
     *
     */
    protected function initBusTypes()
    {
        $entityManager = $this->getContainer()->get('doctrine.orm.entity_manager');
        $busTypeRepository = $entityManager->getRepository('ListingBundle:Vehicle\BusType');
        $config = $this->getContainer()->get('kernel')->locateResource('@ListingBundle/Resources/data/bus_types.yml');
        foreach (Yaml::parse(file_get_contents($config))['busTypes'] as $name) {
            $busType = $busTypeRepository->findOneBy(['name' => $name]);
            if (empty($busType)) {
                $busType = new BusType();
                $busType->setName($name);
                $entityManager->persist($busType);
            }
        }
        $entityManager->flush();
    }

	protected function initTractorCabinTypes() {
		$entityManager = $this->getContainer()->get('doctrine.orm.entity_manager');
		$tractorCabinTypeRepository = $entityManager->getRepository('ListingBundle:Vehicle\CabinType');
		$config = $this->getContainer()->get('kernel')->locateResource('@ListingBundle/Resources/data/tractor_cabin_types.yml');

		foreach (Yaml::parse(file_get_contents($config))['cabinTypes'] as $name) {
			$tractorType = $tractorCabinTypeRepository->findOneBy(['name' => $name]);
			if (empty($tractorType)) {
				$cabinType = new CabinType();
				$cabinType->setName($name);
				$entityManager->persist($cabinType);
			}
		}
		$entityManager->flush();
	}

    protected function initVehicles()
    {
        $entityManager = $this->getContainer()->get('doctrine.orm.entity_manager');
        $vehicleTypeRepository = $entityManager->getRepository('ListingBundle:Vehicle\Type');
        $vehicleBrandRepository = $entityManager->getRepository('ListingBundle:Vehicle\Brand');
        $vehicleModelRepository = $entityManager->getRepository('ListingBundle:Vehicle\Model');
        $config = $this->getContainer()->get('kernel')->locateResource('@ListingBundle/Resources/data/vehicle_types_brands_models.yml');
        foreach (Yaml::parse(file_get_contents($config)) as $typeName => $type) {
            $vehicleType = $vehicleTypeRepository->findOneBy(['name' => $typeName]);
            if (empty($vehicleType)) {
                $vehicleType = new Type();
                $vehicleType->setName($typeName);
            }
            $vehicleType->setLabel($type['label']);
            $entityManager->persist($vehicleType);
            $entityManager->flush($vehicleType);
            foreach ($type['brands'] as $brand) {
                $vehicleBrand = $vehicleBrandRepository->findOneBy(['name' => $brand['name']]);
                if (empty($vehicleBrand)) {
                    $vehicleBrand = new Brand();
                    $vehicleBrand->setName($brand['name']);
                }
                if (!$vehicleBrand->getTypes()->contains($vehicleType)) {
                    $vehicleBrand->addType($vehicleType);
                }
                $entityManager->persist($vehicleBrand);
                $entityManager->flush($vehicleBrand);
                if (is_array($brand['models'])) {
                    foreach ($brand['models'] as $model) {
                        $vehicleModel = $vehicleModelRepository->findOneBy([
                            'name' => $model,
                            'brand' => $vehicleBrand,
                            'type' => $vehicleType
                        ]);
                        if (empty($vehicleModel)) {
                            $vehicleModel = new Model();
                            $vehicleModel->setName($model);
                            $vehicleModel->setBrand($vehicleBrand);
                            $vehicleModel->setType($vehicleType);
                            $entityManager->persist($vehicleModel);
                        }
                    }
                }
            }
        }
        $entityManager->flush();
    }

    /**
     * @deprecated
     */
    protected function initEngineMakeModels()
    {
        $entityManager = $this->getContainer()->get('doctrine.orm.entity_manager');
        $vehicleTypeRepository = $entityManager->getRepository(Type::class);
        $vehicleEngineMakeRepository = $entityManager->getRepository(EngineMake::class);
        $vehicleEngineModelRepository = $entityManager->getRepository(EngineModel::class);
        $config = $this->getContainer()->get('kernel')->locateResource('@ListingBundle/Resources/data/vehicle_engine_make_model.yml');
        foreach (Yaml::parse(file_get_contents($config)) as $typeName => $type) {
            $vehicleType = $vehicleTypeRepository->findOneBy(['name' => $typeName]);
            if (empty($vehicleType)) {
                $vehicleType = new Type();
                $vehicleType->setName($typeName);
            }
            $vehicleType->setLabel($type['label']);
            $entityManager->persist($vehicleType);
            $entityManager->flush($vehicleType);
            foreach ($type['engine_makes'] as $engineMake) {
                $engineMakeName = ucfirst(strtolower($engineMake['name']));
                $vehicleEngineMake = $vehicleEngineMakeRepository->findOneBy(['name' => $engineMakeName]);
                if (empty($vehicleEngineMake)) {
                    $vehicleEngineMake = new EngineMake();
                    $vehicleEngineMake->setName($engineMakeName);
                }
                if (!$vehicleEngineMake->getTypes()->contains($vehicleType)) {
                    $vehicleEngineMake->addType($vehicleType);
                }
                $entityManager->persist($vehicleEngineMake);
                $entityManager->flush($vehicleEngineMake);
                if (is_array($engineMake['engine_models'])) {
                    foreach ($engineMake['engine_models'] as $engineModel) {
                        $engineModelName = ucfirst(strtolower($engineModel));
                        $vehicleEngineModel = $vehicleEngineModelRepository->findOneBy([
                            'name' => $engineModelName,
                            'engineMake' => $vehicleEngineMake,
                            'type' => $vehicleType
                        ]);
                        if (empty($vehicleEngineModel)) {
                            $vehicleEngineModel = new EngineModel();
                            $vehicleEngineModel->setName($engineModelName);
                            $vehicleEngineModel->setEngineMake($vehicleEngineMake);
                            $vehicleEngineModel->setType($vehicleType);
                            $entityManager->persist($vehicleEngineModel);
                        }
                    }
                }
            }
        }
        $entityManager->flush();
    }

    protected function initVehicleConditions()
    {
        $entityManager = $this->getContainer()->get('doctrine.orm.entity_manager');
        $vehicleConditionRepository = $entityManager->getRepository('ListingBundle:Vehicle\Condition');
        $config = $this->getContainer()->get('kernel')->locateResource('@ListingBundle/Resources/data/conditions.yml');
        foreach (Yaml::parse(file_get_contents($config)) as $condition) {
            $vehicleCondition = $vehicleConditionRepository->findOneBy(['name' => $condition]);
            if (empty($vehicleCondition)) {
                $vehicleCondition = new Condition();
                $vehicleCondition->setName($condition);
                $entityManager->persist($vehicleCondition);
            }
        }
        $entityManager->flush();
    }

}