<?php
namespace AppBundle\Form\Model;
use Symfony\Component\Validator\Constraints as Assert;

class ResetPassword
{
    /**
     * @Assert\NotBlank()
     * @Assert\Length(min=6, max=15, minMessage="Password should by at least {{ limit }} chars long")
     */
    protected $plainPassword;
    protected $password;
    
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
}