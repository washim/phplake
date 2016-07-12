<?php
namespace AppBundle\Form\Model;
use Symfony\Component\Validator\Constraints as Assert;

class ForgotPassword
{
    /**
     * @Assert\NotBlank()
     * @Assert\Email()
     */
    protected $email;
    
    public function getEmail()
    {
        return $this->email;
    }
    
    public function setEmail($email)
    {
        $this->email = $email;
        
        return $this;
    }
}