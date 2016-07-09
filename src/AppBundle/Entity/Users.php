<?php
namespace AppBundle\Entity;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\UserInterface;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @ORM\Table(name="users")
 * @ORM\Entity(repositoryClass="AppBundle\Entity\UserRepository")
 * @UniqueEntity(fields="email", message="Email already taken")
 * @UniqueEntity(fields="username", message="Username already taken")
 */
class Users implements UserInterface, \Serializable
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255, unique=true)
     * @Assert\NotBlank()
     * @Assert\Regex(pattern="/^\S{6,}$/", match=true, message="Minimum 6 characters long with no whitespace")
     * @Assert\Length(max=8, maxMessage="Maximum {{ limit }} characters long with no whitespace")
     */
    private $username;
    
    /**
     * @Assert\NotBlank()
     * @Assert\Length(max=4096)
     */
    private $plainPassword;

    /**
     * @ORM\Column(type="string", length=64)
     */
    private $password;
    
    /**
     * @ORM\Column(type="string", length=64)
     */
    private $cpanelpass;
    
    /**
     * @ORM\Column(type="string", length=64)
     */
    private $idepass;
    
    /**
     * @ORM\OneToMany(targetEntity="Projects", mappedBy="owner")
     */
	private $projects;

    /**
     * @ORM\Column(type="string", length=255, unique=true)
     * @Assert\NotBlank()
     * @Assert\Email()
     */
    private $email;
    
    /**
     * @ORM\Column(type="string", length=64)
     */
    private $subscription;
    
    /**
     * @ORM\Column(type="string", length=64)
     */
    private $uroles;

    /**
     * @ORM\Column(name="is_active", type="boolean")
     */
    private $isActive;
    
    /**
	 * @ORM\Column(type="datetime")
	 */
	private $createdAt;
    
    private $avatar;
    
    private $ide;

    public function __construct()
    {
        $this->projects     = new ArrayCollection();
        $this->isActive     = true;
        $this->uroles       = 'ROLE_USER';
        $this->subscription = 'free';
        $this->createdAt    = new \DateTime('now');
        $this->cpanelpass   = bin2hex(random_bytes(6));
        $this->idepass      = bin2hex(random_bytes(6));
    }

    public function getUsername()
    {
        return $this->username;
    }

    public function getSalt()
    {
        // you *may* need a real salt depending on your encoder
        // see section on salt below
        return null;
    }

    public function getPassword()
    {
        return $this->password;
    }

    public function getRoles()
    {
        return array($this->uroles);
    }

    public function eraseCredentials()
    {
    }

    /** @see \Serializable::serialize() */
    public function serialize()
    {
        return serialize(array(
            $this->id,
            $this->username,
            $this->password,
            // see section on salt below
            // $this->salt,
        ));
    }

    /** @see \Serializable::unserialize() */
    public function unserialize($serialized)
    {
        list (
            $this->id,
            $this->username,
            $this->password,
            // see section on salt below
            // $this->salt
        ) = unserialize($serialized);
    }
 
    public function getId()
    {
        return $this->id;
    }

    public function setUsername($username)
    {
        $this->username = $username;

        return $this;
    }

    public function setPassword($password)
    {
        $this->password = $password;

        return $this;
    }

    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    public function getEmail()
    {
        return $this->email;
    }

    public function setIsActive($isActive)
    {
        $this->isActive = $isActive;

        return $this;
    }

    public function getIsActive()
    {
        return $this->isActive;
    }

    public function setUroles($uroles)
    {
        $this->uroles = $uroles;

        return $this;
    }

    public function getUroles()
    {
        return $this->uroles;
    }
    
    public function getPlainPassword()
    {
        return $this->plainPassword;
    }

    public function setPlainPassword($password)
    {
        $this->plainPassword = $password;
    }
    
    public function getAvatar()
    {
        $this->avatar = "https://www.gravatar.com/avatar/" . md5( strtolower( trim( $this->email ) ) ) . "?d=" . urlencode( 'https://www.gravatar.com/avatar/00000000000000000000000000000000' ) . "&s=40";
        return $this->avatar;
    }
    
    public function getIde() {
        $this->ide = 'ide-' . $this->username . '.phplake.com' ;
        
        return $this->ide;
    }
    
    public function getProjects()
    {
        return $this->projects;
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
    
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    public function setCpanelpass($cpanelpass)
    {
        $this->cpanelpass = $cpanelpass;

        return $this;
    }

    public function getCpanelpass()
    {
        return $this->cpanelpass;
    }

    public function addProject(\AppBundle\Entity\Projects $project)
    {
        $this->projects[] = $project;

        return $this;
    }

    public function removeProject(\AppBundle\Entity\Projects $project)
    {
        $this->projects->removeElement($project);
    }

    public function setIdepass($idepass)
    {
        $this->idepass = $idepass;

        return $this;
    }

    public function getIdepass()
    {
        return $this->idepass;
    }
}
