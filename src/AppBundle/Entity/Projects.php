<?php
namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use AppBundle\Validator\Constraints as AcmeAssert;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @ORM\Entity
 * @ORM\Table(name="projects")
 */
class Projects
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;
    
    /**
     * @ORM\Column(type="integer")
     */
    private $uid;
    
    /**
	 * @ORM\Column(type="string", length=255)
     * @Assert\NotBlank()
     * @AcmeAssert\DomainExist
     * @AcmeAssert\LettersCheck
	 */
    private $name;
    
    /**
	 * @ORM\Column(type="string", length=255)
	 */
    private $category;
    
    /**
	 * @ORM\Column(type="string", length=64)
	 */
    private $targetUrl;
	
	/**
     * @ORM\Column(type="string", length=64)
     */
    private $subscription;
	
	/**
     * @ORM\Column(type="decimal", scale=2)
     */
	private $price;
	
	/**
     * @ORM\Column(type="string", length=64)
     */
	private $duedate;
    
    /**
	 * @ORM\Column(type="datetime")
	 */
	private $createdAt;
	
	/**
     * @ORM\ManyToOne(targetEntity="Users", inversedBy="projects")
	 * @ORM\JoinColumn(name="uid", referencedColumnName="id")
     */
	private $owner;
	
	/**
     * @ORM\OneToMany(targetEntity="Sites", mappedBy="project", cascade={"persist","remove"})
     */
	private $sites;
	
	public function __construct()
	{
	    $this->sites = new ArrayCollection();
		$this->subscription = 'free';
		$this->price = 0.00;
		$this->duedate = 'NA';
	    $this->createdAt = new \DateTime('now');
	}

    public function getId()
    {
        return $this->id;
    }

    public function setUid($uid)
    {
        $this->uid = $uid;

        return $this;
    }

    public function getUid()
    {
        return $this->uid;
    }

    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    public function setOwner(\AppBundle\Entity\Users $owner = null)
    {
        $this->owner = $owner;

        return $this;
    }

    public function getOwner()
    {
        return $this->owner;
    }

    public function setCategory($category)
    {
        $this->category = $category;

        return $this;
    }

    public function getCategory()
    {
        return $this->category;
    }

    public function addSite(\AppBundle\Entity\Sites $site)
    {
        $this->sites[] = $site;

        return $this;
    }

    public function removeSite(\AppBundle\Entity\Sites $site)
    {
        $this->sites->removeElement($site);
    }

    public function getSites()
    {
        return $this->sites;
    }
    
    public function setTargetUrl($targetUrl)
    {
        $this->targetUrl = $targetUrl;

        return $this;
    }
	
    public function getTargetUrl()
    {
        return $this->targetUrl;
    }
	
	public function setSubscription($subscription)
    {
        $this->subscription = $subscription;
        return $this;
    }
    
    public function getSubscription()
    {
        return $this->subscription;
    }

    public function setDuedate($duedate)
    {
        $this->duedate = $duedate;

        return $this;
    }

    public function getDuedate()
    {
        return $this->duedate;
    }

    public function setPrice($price)
    {
        $this->price = $price;

        return $this;
    }
	
    public function getPrice()
    {
        return $this->price;
    }
}
