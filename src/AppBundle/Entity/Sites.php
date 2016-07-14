<?php
namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity
 * @ORM\Table(name="sites")
 */
class Sites
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
    private $pid;
    
    /**
	 * @ORM\Column(type="string", length=255, unique=true)
	 */
    private $domain;
    
    /**
	 * @ORM\Column(type="string", length=255)
	 */
    private $subdomain;
    
    /**
	 * @ORM\Column(type="string", length=64)
	 */
    private $environment;
    
    /**
	 * @ORM\Column(type="string", length=255)
	 */
    private $db;
    
    /**
	 * @ORM\Column(type="string", length=255)
	 */
    private $dbuser;
    
    /**
	 * @ORM\Column(type="string", length=255)
	 */
    private $dbpass;
    
    /**
	 * @ORM\Column(type="datetime")
	 */
	private $createdAt;
	
	/**
     * @ORM\ManyToOne(targetEntity="Projects")
	 * @ORM\JoinColumn(name="pid", referencedColumnName="id")
     */
	private $project;
	
	public function __construct()
	{
	    $this->environment = 'dev';
	    $this->createdAt = new \DateTime('now');
	}

    public function getId()
    {
        return $this->id;
    }

    public function setPid($pid)
    {
        $this->pid = $pid;

        return $this;
    }

    public function getPid()
    {
        return $this->pid;
    }

    public function setDomain($domain)
    {
        $this->domain = $domain;

        return $this;
    }

    public function getDomain()
    {
        return $this->domain;
    }
    
    public function setSubdomain($subdomain)
    {
        $this->subdomain = $subdomain;

        return $this;
    }
    
    public function getSubdomain()
    {
        return $this->subdomain;
    }

    public function setEnvironment($environment)
    {
        $this->environment = $environment;

        return $this;
    }

    public function getEnvironment()
    {
        return $this->environment;
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

    public function setProject(\AppBundle\Entity\Projects $project = null)
    {
        $this->project = $project;

        return $this;
    }

    public function getProject()
    {
        return $this->project;
    }
    
    public function setDb($db)
    {
        $this->db = $db;
        
        return $this;
    }
    
    public function getDb()
    {
        return $this->db;
    }
    
    public function setDbuser($dbuser)
    {
        $this->dbuser = $dbuser;
        
        return $this;
    }
    
    public function getDbuser()
    {
        return $this->dbuser;
    }
    
    public function setDbpass($dbpass)
    {
        $this->dbpass = $dbpass;
        
        return $this;
    }
    
    public function getDbpass()
    {
        return $this->dbpass;
    }
}
