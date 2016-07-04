<?php
namespace AppBundle\Validator\Constraints;
use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class LettersCheck extends Constraint
{
    public $message = 'Only lowercase letters are allowed.';
    public function validatedBy() {
        return 'LettersCheckValidator';
    }
}