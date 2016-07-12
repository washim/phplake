<?php
namespace AppBundle\Entity;

use Symfony\Bridge\Doctrine\Security\User\UserLoaderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Doctrine\ORM\EntityRepository;

class UserRepository extends EntityRepository implements UserLoaderInterface
{
    public function loadUserByUsername($username)
    {
        return $this->createQueryBuilder('u')
            ->where('u.username = :username OR u.email = :email AND u.isActive = :isActive')
            ->setParameter('username', $username)
            ->setParameter('email', $username)
            ->setParameter('isActive', true)
            ->getQuery()
            ->getOneOrNullResult();
    }
}