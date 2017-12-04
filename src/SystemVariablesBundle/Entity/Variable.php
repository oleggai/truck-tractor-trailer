<?php

namespace SystemVariablesBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Variable
 *
 * @ORM\Table(name="variable")
 * @ORM\Entity(repositoryClass="SystemVariablesBundle\Repository\VariableRepository")
 */
class Variable
{

    const sellNegotiationsInfo = 'sellNegotiationsInfo';
    const buyNegotiationsInfo = 'buyNegotiationsInfo';
    const sellDealsInfo = 'sellDealsInfo';
    const buyDealsInfo = 'buyDealsInfo';
    const watchedItemsInfo = 'watchedItemsInfo';
    const wishListInfo = 'wishListInfo';
    const requestsInfo = 'requestsInfo';
    const questionsInfo = 'questionsInfo';
    const inventoryInfo = 'inventoryInfo';
    const analyticsInfo = 'analyticsInfo';
    const sellDealsAcceptedOfferInfo = 'sellDealsAcceptedOfferInfo';
    const buyDealsAcceptedOfferInfo = 'buyDealsAcceptedOfferInfo';
    const foundInfo = 'foundInfo';
    const numberOfBuyersInfo = 'numberOfBuyersInfo';
    const searchCriteriaInfo = 'searchCriteriaInfo';

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=55, unique=true)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="value", type="text")
     */
    private $value;

    /**
     * @var string
     *
     * @ORM\Column(name="unit", type="string", length=55)
     */
    private $unit;


    /**
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set name
     *
     * @param string $name
     *
     * @return Variable
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get value
     *
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Set value
     *
     * @param string $value
     *
     * @return Variable
     */
    public function setValue($value)
    {
        $this->value = $value;

        return $this;
    }

    /**
     * Get unit
     *
     * @return string
     */
    public function getUnit()
    {
        return $this->unit;
    }

    /**
     * Set unit
     *
     * @param string $unit
     *
     * @return Variable
     */
    public function setUnit($unit)
    {
        $this->unit = $unit;

        return $this;
    }
}

