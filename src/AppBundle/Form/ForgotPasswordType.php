<?php
namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Gregwar\CaptchaBundle\Type\CaptchaType;

class ForgotPasswordType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('email', EmailType::class)
            ->add('captcha', CaptchaType::class, [
                'width' => 200,
                'height' => 50,
                'length' => 6,
                'quality' => 90,
                'distortion' => true,
                'background_color' => [255, 255, 255],
                'max_front_lines' => 0,
                'max_behind_lines' => 0,
                'label' => 'Plaese enter below captcha in box'
            ]
        );
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'AppBundle\Form\Model\ForgotPassword',
        ));
    }
}