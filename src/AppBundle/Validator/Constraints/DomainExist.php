<?php
namespace AppBundle\Validator\Constraints;
use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class DomainExist extends Constraint
{
    public $message = 'The site "%string%" already exist.';
    public function validatedBy() {
        return 'DomainExistValidator';
    }
}