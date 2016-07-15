<?php
namespace AppBundle\Form\Model;
use Symfony\Component\Security\Core\Validator\Constraints as SecurityAssert;
use Symfony\Component\Validator\Constraints as Assert;

class ChangePassword
{
    /**
     * @SecurityAssert\UserPassword(
     *     message = "Wrong value for your current password"
     * )
     */
    protected $oldPassword;
    protected $changetarget;
     
    /**
     * @Assert\NotBlank()
     * @Assert\Length(min=6, max=15, minMessage="Password should by at least {{ limit }} chars long")
     */
    protected $plainPassword;
    protected $password;
    
    public function getOldPassword()
    {
        return $this->oldPassword;
    }
    
    public function setOldPassword($oldPassword)
    {
        $this->oldPassword = $oldPassword;
        
        return $oldPassword;
    }
    
    public function getPlainPassword()
    {
        return $this->plainPassword;
    }

    public function setPlainPassword($plainPassword)
    {
        $this->plainPassword = $plainPassword;
        
        return $this;
    }
    
    public function getPassword()
    {
        return $this->password;
    }
    
    public function setPassword($password)
    {
        $this->password = $password;

        return $this;
    }
    
    public function setChangetarget($changetarget)
    {
        $this->changetarget = $changetarget;
        
        return $this;
    }
    
    public function getChangetarget()
    {
        return $this->changetarget;
    }
}