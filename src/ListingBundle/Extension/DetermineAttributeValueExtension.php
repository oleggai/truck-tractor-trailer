<?php

namespace ListingBundle\Extension;


use Doctrine\ORM\EntityManager;
use ListingBundle\Entity\Listing;
use ListingBundle\Service\AttributeService;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;

class DetermineAttributeValueExtension extends \Twig_Extension
{

    private $attributeService;
    private $twig;
    private $em;
    private $tokenStorage;

    private $user;

    /**
     * DetermineAttributeValueExtension constructor.
     * @param AttributeService $attributeService
     * @param \Twig_Environment $twig
     * @param EntityManager $em
     * @param TokenStorage $tokenStorage
     */
    public function __construct(
        AttributeService $attributeService,
        \Twig_Environment $twig,
        EntityManager $em,
        TokenStorage $tokenStorage
    ) {
        $this->attributeService = $attributeService;
        $this->twig = $twig;
        $this->em = $em;
        $this->tokenStorage = $tokenStorage;

        $user = $this->tokenStorage->getToken() ? $this->tokenStorage->getToken()->getUser() : null;

        $this->user = is_object($user) ? $user : null;


    }

    /**
     * @return array
     */
    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction('determineValueForOptions', array($this, 'determineValueForOptions')),
            new \Twig_SimpleFunction('determineValueForShowingOptions',
                array($this, 'determineValueForShowingOptions')),

            new \Twig_SimpleFunction('determineValueForBool', array($this, 'determineValueForBool')),
            new \Twig_SimpleFunction('determineValueForNotBool', array($this, 'determineValueForNotBool')),
            new \Twig_SimpleFunction('determineValueForDictionary', array($this, 'determineValueForDictionary')),
            new \Twig_SimpleFunction('setAttributeToModal', array($this, 'setAttributeToModal')),
        );
    }

    /**
     * Need for set listing data into modal window for to edit listing data
     * @param Listing\Attribute\Value|null $value
     * @return Listing\Attribute\Value|string
     */
    public function setAttributeToModal(Listing\Attribute\Value $value = null)
    {
        if ($value) {
            switch ($value->getType()) {
                case Listing\Attribute\Value::TYPE_BOOLEAN:
                    $value = $value->getValue() ? 'checked' : '';
                    break;

                default:
                    $value = $value->getValue();
            }

            return $value;
        }
    }


    /**
     * @param Listing $listing
     * @param $attributeName
     * @return int|string
     */
    public function determineValueForOptions(Listing $listing, $attributeName)
    {
        $valueEntity = $this->attributeService->get($listing, $attributeName);

        $value = '';

        if ($valueEntity) {
            if ($valueEntity->getValue()) {
                $value = 1;
            }
        }

        return $value;
    }

    /**
     * @param Listing $listing
     * @param $attributeName
     * @return string
     */
    public function determineValueForShowingOptions(Listing $listing, $attributeName)
    {
        $valueEntity = $this->attributeService->get($listing, $attributeName);

        $value = '';

        if ($valueEntity) {
            if ($valueEntity->getValue()) {
                $value = $valueEntity->getAttribute()->getLabel().'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
            }
        }

        return $value;
    }

    /**
     * @param Listing $listing
     * @param $attributeName
     * @return string
     */
    public function determineValueForBool(Listing $listing, $attributeName)
    {
        $valueEntity = $this->attributeService->get($listing, $attributeName);

        if ($valueEntity) {
            $value = $valueEntity->getValue() ? 'Yes' : 'No';
        } else {
            $value = 'N/A';
        }

        return $value;
    }

    /**
     * @param Listing $listing
     * @param $attributeName
     * @return string
     */
    public function determineValueForNotBool(Listing $listing, $attributeName)
    {
        $valueEntity = $this->attributeService->get($listing, $attributeName);
        $na = 'N/A';
        if ($valueEntity) {
            $value = $valueEntity->getValue();
            if(!$value) {
                $value = $na;
            }
        } else {
            $value = $na;
        }

        return $value;
    }

    /**
     * @param Listing $listing
     * @param $attributeName
     * @return string
     */
    public function determineValueForDictionary(Listing $listing, $attributeName)
    {
        $value = $this->determineValueForNotBool($listing, $attributeName);
        $dictionary = [];
        $busConditions = Listing\Attribute::BUS_CONDITIONS;

        switch ($attributeName) {
            case Listing\Attribute::FUEL_TYPE:
                $dictionary = Listing\Attribute::FUEL_TYPES;
                break;
            case Listing\Attribute::BUS_FUEL_TYPE:
                $dictionary = Listing\Attribute::FUEL_TYPES;
                break;
            case Listing\Attribute::BUS_INTERIOR_CONDITION:
                $dictionary = $busConditions;
                break;
            case Listing\Attribute::BUS_EXTERIOR_CONDITION:
                $dictionary = $busConditions;
            default:

        }

        if (key_exists($value, $dictionary)) {
            $value = $dictionary[$value];
        }

        return $value;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'determine_value_extension';
    }
}