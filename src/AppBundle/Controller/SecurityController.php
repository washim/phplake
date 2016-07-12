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
            
            $message = \Swift_Message::newInstance()
                    ->setSubject('Activate your Phplake account')
                    ->setFrom(['support@phplake.com' => 'Phplake Support'])
                    ->setTo($user->getEmail())
                    ->setBody(
                        $this->renderView('Emails/activateaccount.html.twig', [
                            'name' => $user->getUsername(),
                            'powerkey' => sha1($user->getEmail()),
                            'key' => base64_encode($user->getEmail())
                        ])
                    );
            $this->get('mailer')->send($message);
            
            $this->addFlash(
                'success',
                'An activation link sent to your email account.'
            );
            
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
    
    /**
     * @Route("/forgotpass", name="forgotpass")
     */
    public function forgotpassAction(Request $request)
    {
        $user = new Users();
        $form = $this->createForm(UsersType::class, $user);
        $form->remove('username');
        $form->remove('plainPassword');
        if ($request->getMethod() == 'POST') {
            $email = $request->request->get('users')['email'];
            $fuser = $this->getDoctrine()
                ->getRepository('AppBundle:Users')
                ->findOneByEmail($email);
            if (!$fuser) {
                $this->addFlash(
                    'error',
                    'No account found with this email.'
                );
            }
            else {
                $message = \Swift_Message::newInstance()
                    ->setSubject('Reset your Phplake password')
                    ->setFrom(['support@phplake.com' => 'Phplake Support'])
                    ->setTo($email)
                    ->setBody(
                        $this->renderView('Emails/forgot.html.twig', [
                            'name' => $fuser->getUsername(),
                            'powerkey' => sha1($email),
                            'key' => base64_encode($email)
                        ])
                    );
                $this->get('mailer')->send($message);
                
                $this->addFlash(
                    'success',
                    'Further instruction sent to email.'
                );
            }
            
            return $this->redirectToRoute('forgotpass');
        }
        
        return $this->render('forgotpass.html.twig', [
            'form' => $form->createView()
        ]);
    }
    
    /**
     * @Route("/resetpassword/{powerkey}/{key}", name="resetpassword")
     */
    function resetpasswordAction(Request $request, $powerkey, $key)
    {
        
        $user = new Users();
        $form = $this->createForm(UsersType::class, $user);
        $form->remove('username');
        $form->remove('email');
        if (sha1(base64_decode($key)) == $powerkey) {
            if ($request->getMethod() == 'POST') {
                $fuser = $this->getDoctrine()
                    ->getRepository('AppBundle:Users')
                    ->findOneByEmail(base64_decode($key));
                    
                if (!$fuser) {
                    $this->addFlash(
                        'error',
                        'Unable to change password due to invalid reset link.'
                    );
                }
                else {
                    $first = $request->request->get('users')['plainPassword']['first'];
                    $second = $request->request->get('users')['plainPassword']['second'];
                    if ($first !== $second) {
                        $this->addFlash(
                            'error',
                            'Repeat password not matched with new password.'
                        );
                    }
                    else {
                        $password = $this->get('security.password_encoder')->encodePassword($fuser, $second);
                        $fuser->setPassword($password);
                        
                        $em = $this->getDoctrine()->getManager();
                        $em->persist($fuser);
                        $em->flush();
                        
                        $this->addFlash(
                            'success',
                            'Password updated successfully.'
                        );
                        
                        return $this->redirectToRoute('login');
                    }
                }
            }
        }
        else {
            $this->addFlash(
                'error',
                'Reset password link invalid or expired.'
            );
            
            return $this->redirectToRoute('forgotpass');
        }
        
        return $this->render('resetpass.html.twig', [
            'form' => $form->createView()
        ]);
    }
    
    /**
     * @Route("/activateaccount/{powerkey}/{key}", name="activateaccount")
     */
    function activateaccountAction(Request $request, $powerkey, $key)
    {
        if (sha1(base64_decode($key)) == $powerkey) {
            $fuser = $this->getDoctrine()
                ->getRepository('AppBundle:Users')
                ->findOneByEmail(base64_decode($key));
            
            if (!$fuser) {
                $this->addFlash(
                    'error',
                    'Unable to activate your account due to invalid activation link.'
                );
            }
            else {
                $fuser->setIsActive(true);
                
                $em = $this->getDoctrine()->getManager();
                $em->persist($fuser);
                $em->flush();
                
                $this->addFlash(
                    'success',
                    'Account activated successfully.'
                );
                
                return $this->redirectToRoute('login');
            }
        }
        else {
            $this->addFlash(
                'error',
                'Reset password link invalid or expired.'
            );
            
            return $this->redirectToRoute('login');
        }
        
        return null;
    }
}