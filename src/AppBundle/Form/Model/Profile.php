<?php
namespace AppBundle\Form\Model;
use Symfony\Component\Validator\Constraints as Assert;

class Profile
{
    /**
     * @Assert\NotBlank()
     */
    protected $name;
    
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    public function getName()
    {
        return $this->name;
    }
}