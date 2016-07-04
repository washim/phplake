<?php
namespace AppBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class DomainExistValidator extends ConstraintValidator
{
    public function __construct(EntityManager $em, TokenStorageInterface $session)
    {
        $this->em = $em;
        $this->session = $session;
    }
    public function validate($value, Constraint $constraint)
    {
        $em = $this->em;
        $username = $this->session->getToken()->getUser()->getUsername();
        $value = 'dev-' . $value . '-' . $username . '.phplake.com';
        $sites = $em->getRepository('AppBundle:Sites')->findOneByDomain($value);
        if ($sites) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('%string%', $value)
                ->addViolation();
        }
    }
}