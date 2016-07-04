<?php
namespace AppBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class LettersCheckValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint)
    {
        if (ctype_lower($value) === false) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('%string%', $value)
                ->addViolation();
        }
    }
}