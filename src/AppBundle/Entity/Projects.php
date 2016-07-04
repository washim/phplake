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
	 * @ORM\Column(type="text")
	 */
    private $targetUrl;
    
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
	    $this->createdAt = new \DateTime('now');
	}

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set uid
     *
     * @param integer $uid
     *
     * @return Projects
     */
    public function setUid($uid)
    {
        $this->uid = $uid;

        return $this;
    }

    /**
     * Get uid
     *
     * @return integer
     */
    public function getUid()
    {
        return $this->uid;
    }

    /**
     * Set name
     *
     * @param string $name
     *
     * @return Projects
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
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
     * Set createdAt
     *
     * @param \DateTime $createdAt
     *
     * @return Projects
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Get createdAt
     *
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Set owner
     *
     * @param \AppBundle\Entity\Users $owner
     *
     * @return Projects
     */
    public function setOwner(\AppBundle\Entity\Users $owner = null)
    {
        $this->owner = $owner;

        return $this;
    }

    /**
     * Get owner
     *
     * @return \AppBundle\Entity\Users
     */
    public function getOwner()
    {
        return $this->owner;
    }

    /**
     * Set category
     *
     * @param string $category
     *
     * @return Projects
     */
    public function setCategory($category)
    {
        $this->category = $category;

        return $this;
    }

    /**
     * Get category
     *
     * @return string
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * Add site
     *
     * @param \AppBundle\Entity\Sites $site
     *
     * @return Projects
     */
    public function addSite(\AppBundle\Entity\Sites $site)
    {
        $this->sites[] = $site;

        return $this;
    }

    /**
     * Remove site
     *
     * @param \AppBundle\Entity\Sites $site
     */
    public function removeSite(\AppBundle\Entity\Sites $site)
    {
        $this->sites->removeElement($site);
    }

    /**
     * Get sites
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getSites()
    {
        return $this->sites;
    }
    
    /**
     * Set targetUrl
     *
     * @param string $targetUrl
     *
     * @return Sites
     */
    public function setTargetUrl($targetUrl)
    {
        $this->targetUrl = $targetUrl;

        return $this;
    }

    /**
     * Get targetUrl
     *
     * @return string
     */
    public function getTargetUrl()
    {
        return $this->targetUrl;
    }
}
