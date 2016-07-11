<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;
use Symfony\Component\Security\Acl\Permission\MaskBuilder;

use AppBundle\Form\UsersType;
use AppBundle\Entity\Users;
use AppBundle\Form\ProfileType;
use AppBundle\Form\ChangePasswordType;

class SecurityController extends Controller
{
    /**
     * @Route("/login", name="login")
     */
    public function loginAction(Request $request)
    {
        $authenticationUtils = $this->get('security.authentication_utils');
    
        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
    
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();
    
        return $this->render(
            'login.html.twig',
            array(
                // last username entered by the user
                'last_username' => $lastUsername,
                'error'         => $error,
            )
        );
    }
    
    /**
     * @Route("/register", name="register")
     */
    public function registerAction(Request $request)
    {
        $user = new Users();
        $form = $this->createForm(UsersType::class, $user);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $password = $this->get('security.password_encoder')->encodePassword($user, $user->getPlainPassword());
            $user->setPassword($password);
            
            $em = $this->getDoctrine()->getManager();
            $em->persist($user);
            $em->flush();
            
            // creating the ACL
            $aclProvider = $this->get('security.acl.provider');
            $objectIdentity = ObjectIdentity::fromDomainObject($user);
            $acl = $aclProvider->createAcl($objectIdentity);

            // retrieving the security identity of the currently logged-in user
            $securityIdentity = UserSecurityIdentity::fromAccount($user);

            // grant owner access
            $acl->insertObjectAce($securityIdentity, MaskBuilder::MASK_OWNER);
            $aclProvider->updateAcl($acl);
            
            return $this->redirectToRoute('login');
        }
        return $this->render('register.html.twig', [
            'form' => $form->createView()
        ]);
    }
    
    /**
     * @Route("/logout", name="logout")
     */
    public function logoutAction(Request $request)
    {
        
    }
    
    /**
     * @Route("/myaccount", name="myaccount")
     */
    public function myaccountAction(Request $request)
    {
        $user = $this->getUser();
        $passform = $this->createForm(ProfileType::class, $user);
        $passform->handleRequest($request);
        if ($passform->isSubmitted() && $passform->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($user);
            $em->flush();
            
            $this->addFlash(
                'success',
                'Account metadata updated successfully.'
            );
            
            return $this->redirectToRoute('myaccount');
        }
        
        $form = $this->createForm(ChangePasswordType::class, $user);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if($this->get('security.password_encoder')->isPasswordValid($user, $request->request->get('change_password')['oldPassword']) === false){
                $this->addFlash(
                    'error',
                    'Current password not matched.' . $request->request->get('change_password')['oldPassword']
                );
                
                return $this->redirectToRoute('myaccount');
            }
            $password = $this->get('security.password_encoder')->encodePassword($user, $user->getPlainPassword());
            $user->setPassword($password);
            
            $em = $this->getDoctrine()->getManager();
            $em->persist($user);
            $em->flush();
            
            $this->addFlash(
                'success',
                'Password updated successfully.'
            );
            
            return $this->redirectToRoute('myaccount');
        }
        
        return $this->render('default/profile.html.twig', ['user' => $this->getUser(), 'form' => $form->createView(), 'passform' => $passform->createView()]);
    }
}