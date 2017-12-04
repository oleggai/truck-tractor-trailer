<?php

namespace ListingBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use FileUploadBundle\Entity\FileEntityInterface;
use FileUploadBundle\Entity\ListingFile;
use InspectionBundle\Entity\Inspection;
use ListingBundle\Entity\Listing\Attribute\Value;
use ListingBundle\Entity\Listing\Item;
use ListingBundle\Entity\Vehicle\Brand;
use ListingBundle\Entity\Vehicle\Condition;
use ListingBundle\Entity\Vehicle\Model;
use LocationBundle\Entity\ContactAddress;
use LocationBundle\Entity\ListingAddress;
use MediaBundle\Entity\ImageFile;
use MediaBundle\Entity\ImageURL;
use MediaBundle\Entity\MediaItem;
use ProfileBundle\Entity\CreatedUpdatedFieldset;
use ProfileBundle\Entity\User;
use WebsiteBundle\Entity\Traits\IsExpired;

/**
 * @ORM\Entity(repositoryClass="ListingBundle\Repository\ListingRepository")
 * @ORM\Table(name="listing")
 * @ORM\HasLifecycleCallbacks
 * @ORM\InheritanceType(value="JOINED")
 * @ORM\DiscriminatorColumn(name="type", type="string")
 * @ORM\DiscriminatorMap({
 *     "trailer" = "ListingBundle\Entity\Listing\Trailer",
 *     "tractor" = "ListingBundle\Entity\Listing\Tractor",
 *     "bus"     = "ListingBundle\Entity\Listing\Bus",
 * })
 */
abstract class Listing implements FileEntityInterface
{

    const STATUS_NOT_AVAILABLE = 3;

    const STATUS_SOLD = 4;

    const STATUS_FOR_SALE = 5;

    const STATUS_UNDER_CONTRACT = 6;

    use CreatedUpdatedFieldset;
    use IsExpired;
    /**
     * @ORM\Id
     * @ORM\Column(name="id", type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(name="name", type="text", nullable=false)
     */
    protected $name;

    /**
     * @var Brand
     * @ORM\ManyToOne(targetEntity="ListingBundle\Entity\Vehicle\Brand", inversedBy="listings", cascade={"persist"})
     * @ORM\JoinColumn(name="brand_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     *
     */
    protected $brand;

    /**
     * @var Model
     * @ORM\ManyToOne(targetEntity="ListingBundle\Entity\Vehicle\Model", inversedBy="listings", cascade={"persist"})
     * @ORM\JoinColumn(name="model_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     */
    protected $model;

    /**
     * @ORM\Column(name="year", nullable=true)
     */
    protected $year;
    /**
     * @ORM\ManyToOne(targetEntity="ListingBundle\Entity\Vehicle\Condition", inversedBy="listings", cascade={"persist"})
     * @ORM\JoinColumn(name="condition_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     */
    protected $condition;
	/**
	 * @ORM\OneToMany(targetEntity="ListingBundle\Entity\Listing\Item", mappedBy="listing", cascade={"persist"})
	 */
    protected $items;
	/**
	 * @ORM\OneToMany(targetEntity="ListingBundle\Entity\Listing\Item\TractorItem", mappedBy="listingCopy", cascade={"persist"})
	 */
	protected $tractorItems;
	/**
	 * @ORM\OneToMany(targetEntity="ListingBundle\Entity\Listing\Item\TrailerItem", mappedBy="listingCopy", cascade={"persist"})
	 */
	protected $trailerItems;
	/**
	 * @ORM\OneToMany(targetEntity="ListingBundle\Entity\Listing\Item\BusItem", mappedBy="listingCopy", cascade={"persist"})
	 */
	protected $busItems;
    /**
     * @ORM\OneToMany(targetEntity="ListingBundle\Entity\ListingView", mappedBy="listing", cascade={"persist"})
     */
	protected $views;
    /**
     * @ORM\ManyToOne(targetEntity="LocationBundle\Entity\ListingAddress", cascade={"persist"})
     * @ORM\JoinColumn(name="address_id", referencedColumnName="id", onDelete="CASCADE", nullable=true)
     * @var ListingAddress
     */
    protected $address;
    /**
     * @ORM\ManyToOne(targetEntity="LocationBundle\Entity\ContactAddress", cascade={"persist"})
     * @ORM\JoinColumn(name="contact_address_id", referencedColumnName="id", onDelete="CASCADE", nullable=true)
     * @var ContactAddress
     */
    protected $contactAddress;
    /**
     * @ORM\Column(name="contact_phone", type="string", nullable=true)
     */
    protected $contactPhone;
    /**
     * @ORM\Column(name="contact_email", type="string", nullable=true)
     */
    protected $contactEmail;
    /**
     * @ORM\Column(name="description", type="text", nullable=false)
     */
    protected $description;
    /**
     * @ORM\Column(name="allow_offers", type="boolean", nullable=true)
     */
    protected $allowOffers;
    /**
     * @ORM\Column(name="allow_partial_offers", type="boolean", nullable=true)
     */
    protected $allowPartialOffers;
    /**
     * @ORM\Column(name="fleet_maintained", type="boolean", nullable=true)
     */
    protected $fleetMaintained;
    /**
     * @ORM\ManyToOne(targetEntity="ProfileBundle\Entity\User", inversedBy="listings")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=false)
     */
    protected $user;
    /**
     * @ORM\Column(name="approved_by_user", type="boolean", nullable=true)
     */
    protected $approvedByUser = false;
    /**
     * @ORM\Column(name="approved_by_admin", type="boolean", nullable=true)
     */
    protected $approvedByAdmin = true;
    /**
     * @ORM\OneToOne(targetEntity="InspectionBundle\Entity\Inspection", mappedBy="listing", cascade={"persist"})
     * @var Inspection
     */
    protected $inspection;
    /**
     * @ORM\OneToMany(targetEntity="FileUploadBundle\Entity\ListingFile", mappedBy="entity", cascade={"persist"})
     */
    protected $files;
    /**
     * @ORM\OneToMany(targetEntity="ListingBundle\Entity\Listing\Attribute\Value", mappedBy="listing", cascade={"persist"})
     */
    protected $attributeValues;
    /**
     * @ORM\OneToMany(targetEntity="ListingBundle\Entity\Listing\Attribute\Value\BooleanValue", mappedBy="listingCopy", cascade={"persist"})
     */
    protected $booleanAttributeValues;
    /**
     * @ORM\OneToMany(targetEntity="ListingBundle\Entity\Listing\Attribute\Value\IntegerValue", mappedBy="listingCopy", cascade={"persist"})
     */
    protected $integerAttributeValues;
    /**
     * @ORM\OneToMany(targetEntity="ListingBundle\Entity\Listing\Attribute\Value\FloatValue", mappedBy="listingCopy", cascade={"persist"})
     */
    protected $floatAttributeValues;
    /**
     * @ORM\OneToMany(targetEntity="ListingBundle\Entity\Listing\Attribute\Value\StringValue", mappedBy="listingCopy", cascade={"persist"})
     */
    protected $stringAttributeValues;
    /**
     * @ORM\OneToMany(targetEntity="ListingBundle\Entity\WatchList", mappedBy="listing", cascade={"persist"})
     */
    protected $watchListItems;
    /**
     * @ORM\Column(name="status", type="integer", options={"default": 5})
     */
    protected $status = self::STATUS_FOR_SALE;

    /**
     * @ORM\Column(name="source_url", type="text", nullable=true)
     */
    protected $sourceUrl;
    /**
     * @ORM\OneToMany(targetEntity="MediaBundle\Entity\MediaItem", mappedBy="listing", cascade={"persist"})
     * @ORM\OrderBy({"weight" = "ASC"})
     */
    protected $mediaItems;

    public function __construct()
    {
        $this->items           = new ArrayCollection();
        $this->views           = new ArrayCollection();
        $this->files           = new ArrayCollection();
        $this->attributeValues = new ArrayCollection();
        $this->watchListItems  = new ArrayCollection();
        $this->mediaItems      = new ArrayCollection();
    }

    public function getId()
    {
        return $this->id;
    }

    public function setBrand(Brand $brand = null)
    {
        $this->brand = $brand;
    }

    public function getBrand()
    {
        return $this->brand;
    }

    public function setModel(Model $model = null)
    {
        $this->model = $model;
    }

    public function getModel()
    {
        return $this->model;
    }

    public function setYear($year)
    {
        $this->year = $year;
    }

    public function getYear()
    {
        return $this->year;
    }

    public function setCondition(Condition $condition = null)
    {
        $this->condition = $condition;
    }

    public function getCondition()
    {
        return $this->condition;
    }

    public function addItem(Item $item)
    {
        $this->items->add($item);
        $item->setListing($this);
    }

    public function removeItem(Item $item)
    {
        $this->items->removeElement($item);
    }

    public function getItems()
    {
        return $this->items;
    }

    public function getPrice()
    {
        /**
         * @var $this ->items[0] Item
         */
        if (!empty($this->items) && isset($this->items[0])) {
            return $this->items[0]->getPrice();
        }
        return 0;
    }

    public function setAddress(ListingAddress $address)
    {
        $this->address = $address;
    }

    public function getAddress()
    {
        return $this->address;
    }

    public function setContactAddress(ContactAddress $contactAddress)
    {
        $this->contactAddress = $contactAddress;
    }

    public function getContactAddress()
    {
        return $this->contactAddress;
    }

    public function setContactPhone($contactPhone)
    {
        $this->contactPhone = $contactPhone;
    }

    public function getContactPhone()
    {
        return $this->contactPhone;
    }

    public function setContactEmail($contactEmail)
    {
        $this->contactEmail = $contactEmail;
    }

    public function getContactEmail()
    {
        return $this->contactEmail;
    }

    public function setDescription($description)
    {
        $this->description = $description;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function setAllowOffers($allowOffers)
    {
        $this->allowOffers = $allowOffers;
        if (!$allowOffers) {
            $this->setAllowPartialOffers(false);
        }
    }

    public function getAllowOffers()
    {
        return $this->allowOffers;
    }

    public function setAllowPartialOffers($allowPartialOffers)
    {
        $this->allowPartialOffers = $allowPartialOffers;
    }

    public function getAllowPartialOffers()
    {
        return $this->allowOffers && $this->allowPartialOffers;
    }

    public function setFleetMaintained($fleetMaintained)
    {
        $this->fleetMaintained = $fleetMaintained;
    }

    public function getFleetMaintained()
    {
        return $this->fleetMaintained;
    }

    public function setUser(User $user)
    {
        $this->user = $user;
    }

    /**
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    public function setApprovedByUser($approvedByUser)
    {
        $this->approvedByUser = $approvedByUser;
    }

    public function getApprovedByUser()
    {
        return $this->approvedByUser;
    }

    public function setApprovedByAdmin($approvedByAdmin)
    {
        $this->approvedByAdmin = $approvedByAdmin;
    }

    public function getApprovedByAdmin()
    {
        return $this->approvedByAdmin;
    }

    public function getApproved()
    {
        return $this->getApprovedByAdmin() && $this->getApprovedByUser();
    }

    public function addFile(ListingFile $file)
    {
        $this->files->add($file);
    }

    public function removeFile(ListingFile $file)
    {
        $this->files->removeElement($file);
    }

    public function getFiles()
    {
        return $this->files;
    }

    public function addAttributeValue(Value $attributeValue)
    {
        $this->attributeValues->add($attributeValue);
        $attributeValue->setListing($this);
    }

    public function removeAttributeValue(Value $attributeValue)
    {
        $this->attributeValues->removeElement($attributeValue);
    }

    public function getAttributeValues()
    {
        return $this->attributeValues;
    }

    public function addWatchListItem(WatchList $item)
    {
        $this->watchListItems->add($item);
    }

    public function removeWatchListItem(WatchList $item)
    {
        $this->watchListItems->removeElement($item);
    }

    public function getWatchListItems()
    {
        return $this->watchListItems;
    }

    public function setSourceUrl($sourceUrl)
    {
        $this->sourceUrl = $sourceUrl;
    }

    public function getSourceUrl()
    {
        return $this->sourceUrl;
    }

    public function getName()
    {
        if (empty($this->name) && $this->id) {
            return $this->year . ' ' . $this->brand->getName() . ' ' . $this->model->getName();
        }
        return $this->name;
    }

    public function getEntityType()
    {
        return get_class($this);
    }


    /**
     * Set name
     *
     * @param string $name
     *
     * @return Listing
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getStatus()
    {
        return $this->status;
    }

    public function getViews()
    {
        return $this->views;
    }

    /**
     * @return mixed
     */
    public function getStatusName()
    {
        switch ($this->status) {
            case self::STATUS_FOR_SALE:
                return 'For sale';
            case self::STATUS_UNDER_CONTRACT:
                return 'Under contract';
            case self::STATUS_NOT_AVAILABLE:
                return 'Not available';
            case self::STATUS_SOLD:
                return 'Sold';
            default:
                return 'Unknown status';
        }
    }

    /**
     * @param mixed $status
     */
    public function setStatus($status)
    {
        $this->status = $status;
    }

    public function setInspection(Inspection $inspection = null)
    {
        $this->inspection = $inspection;
    }

    public function getInspection()
    {
        return $this->inspection;
    }

    public function __toString()
    {
        return $this->getName();
    }

    public function setMediaItems(array $items)
    {
        $this->mediaItems = $items;
    }

    public function addMediaItem(MediaItem $item)
    {
        $this->mediaItems->add($item);
    }

    public function removeMediaItem(MediaItem $item)
    {
        $this->mediaItems->removeElement($item);
    }

    public function getMediaItems()
    {
        return $this->mediaItems;
    }

    public function getCoverImage()
    {
        foreach ($this->getMediaItems() as $item) {
            if ($item instanceof ImageFile || $item instanceof ImageURL) {
                return $item;
            }
        }

        return null;
    }

}