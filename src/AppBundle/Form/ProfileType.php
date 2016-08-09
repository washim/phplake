<?php
namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\CountryType;

class ProfileType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', TextType::class, ['label' => 'Full Name'])
            ->add('mobile', TextType::class, ['label' => 'Mobile No', 'attr' => ['placeholder' => 'Enter valid mobile number']])
            ->add('street', TextType::class, ['label' => 'Street', 'attr' => ['placeholder' => 'Enter street address']])
            ->add('city', TextType::class, ['label' => 'City', 'attr' => ['placeholder' => 'Enter City']])
            ->add('state', TextType::class, ['label' => 'State', 'attr' => ['placeholder' => 'Enter State']])
            ->add('country', CountryType::class, ['label' => 'Country', 'attr' => ['placeholder' => 'Choose Country']])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => 'AppBundle\Form\Model\Profile',
        ]);
    }
}