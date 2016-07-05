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
        $this->projects = new ArrayCollection();
        $this->isActive = true;
        $this->uroles = 'ROLE_USER';
        $this->subscription = 'free';
        $this->createdAt = new \DateTime('now');
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
     * Set username
     *
     * @param string $username
     *
     * @return Users
     */
    public function setUsername($username)
    {
        $this->username = $username;

        return $this;
    }

    /**
     * Set password
     *
     * @param string $password
     *
     * @return Users
     */
    public function setPassword($password)
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Set email
     *
     * @param string $email
     *
     * @return Users
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Get email
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Set isActive
     *
     * @param boolean $isActive
     *
     * @return Users
     */
    public function setIsActive($isActive)
    {
        $this->isActive = $isActive;

        return $this;
    }

    /**
     * Get isActive
     *
     * @return boolean
     */
    public function getIsActive()
    {
        return $this->isActive;
    }

    /**
     * Set uroles
     *
     * @param string $uroles
     *
     * @return Users
     */
    public function setUroles($uroles)
    {
        $this->uroles = $uroles;

        return $this;
    }

    /**
     * Get uroles
     *
     * @return string
     */
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
    
    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     *
     * @return Sites
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
}
