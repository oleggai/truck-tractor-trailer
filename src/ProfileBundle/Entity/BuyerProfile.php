<?php

namespace ProfileBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="user_profile_buyer")
 * @ORM\HasLifecycleCallbacks
 * @ORM\Entity(repositoryClass="ProfileBundle\Repository\BuyerProfileRepository")
 */
class BuyerProfile
{
    /**
     * @ORM\Id
     * @ORM\Column(name="id", type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(name="confirmed_at", type="datetime", nullable=true)
     */
    protected $confirmedAt;
    /**
     * @var boolean
     * @ORM\Column(name="valid_cdl", type="boolean", nullable=true)
     */
    private $validCdl = true;
    /**
     * @var string
     * @ORM\Column(name="years_driving", type="string", nullable=true)
     */
    private $yearsDriving;
    /**
     * @var boolean
     * @ORM\Column(name="owner_or_operator", type="boolean", nullable=true)
     */
    private $ownerOrOperator;

    /**
     * @var User
     * @ORM\OneToOne(targetEntity="ProfileBundle\Entity\User", mappedBy="buyerProfile")
     */
    private $user;

    /**
     * Get validCdl
     *
     * @return boolean
     */
    public function getValidCdl()
    {
        return $this->validCdl;
    }

    /**
     * @return bool
     */
    public function isValidCdl()
    {
        return $this->validCdl;
    }

    /**
     * @param bool $validCdl
     */
    public function setValidCdl($validCdl)
    {
        $this->validCdl = $validCdl;
    }

    /**
     * @return string
     */
    public function getYearsDriving()
    {
        return $this->yearsDriving;
    }

    /**
     * @param string $yearsDriving
     */
    public function setYearsDriving($yearsDriving)
    {
        $this->yearsDriving = $yearsDriving;
    }

    /**
     * @return bool
     */
    public function isOwnerOrOperator()
    {
        return $this->ownerOrOperator;
    }

    /**
     * @param bool $ownerOrOperator
     */
    public function setOwnerOrOperator($ownerOrOperator)
    {
        $this->ownerOrOperator = $ownerOrOperator;
    }

    /**
     * @return mixed
     */
    public function getConfirmedAt()
    {
        return $this->confirmedAt;
    }

    /**
     * @param mixed $confirmedAt
     */
    public function setConfirmedAt($confirmedAt)
    {
        $this->confirmedAt = $confirmedAt;
    }

    /**
     * Get ownerOrOperator
     *
     * @return boolean
     */
    public function getOwnerOrOperator()
    {
        return $this->ownerOrOperator;
    }

    /**
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param User $user
     */
    public function setUser($user)
    {
        $this->user = $user;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }
}
