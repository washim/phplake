<?php
namespace AppBundle\Form\Model;
use Symfony\Component\Validator\Constraints as Assert;

class Profile
{
    /**
     * @Assert\NotBlank()
     */
    private $name;
    
    /**
	 * @Assert\NotBlank()
     */
	private $mobile;
	
	/**
	 * @Assert\NotBlank()
     */
	private $street;
	
	/**
	 * @Assert\NotBlank()
     */
	private $city;
	
	/**
	 * @Assert\NotBlank()
     */
	private $state;
	
	private $country;
    
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    public function getName()
    {
        return $this->name;
    }
    
    public function setMobile($mobile)
    {
        $this->mobile = $mobile;

        return $this;
    }

    public function getMobile()
    {
        return $this->mobile;
    }

    public function setStreet($street)
    {
        $this->street = $street;

        return $this;
    }

    public function getStreet()
    {
        return $this->street;
    }

    public function setCity($city)
    {
        $this->city = $city;

        return $this;
    }

    public function getCity()
    {
        return $this->city;
    }

    public function setState($state)
    {
        $this->state = $state;

        return $this;
    }

    public function getState()
    {
        return $this->state;
    }

    public function setCountry($country)
    {
        $this->country = $country;

        return $this;
    }

    public function getCountry()
    {
        return $this->country;
    }
}