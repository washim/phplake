<?php
namespace AppBundle\Controller;

use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;
use Symfony\Component\Security\Acl\Permission\MaskBuilder;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Doctrine\Common\Collections\Criteria;

use Symfony\Component\HttpFoundation\Session\Session;

class PaymentController extends Controller
{
    /**
     * @Route("/dashboard/upgrade", name="upgrade")
     */
    public function upgradeAction(Request $request)
    {
        return $this->render('default/upgrade.html.twig');
    }
    
    /**
     * @Route("/payment/success/{amount}", name="paymentsuccess")
     */
    public function paymentsuccess(Request $request, $amount)
    {
        $session = new Session();
        list($name, $domain, $subdomain, $db, $file, $dir) = explode('|', $session->get('domaininfo'), 6);
        $response = $this->get('app.whm')->update_cpanel_account($this->getUser()->getUsername(), $domain, $subdomain, $db, $file, $dir, 'off');
        if ($response == 'success') {
            $em = $this->getDoctrine()->getManager();
            $project = new Projects();
            $project->setName($name);
            $project->setCategory($dir);
            $project->setTargetUrl($file);
            $amount == 675 ? $project->setSubscription('subscribed_monthly') : $project->setSubscription('subscribed_yearly');
            $project->setOwner($this->getUser());
            
            $site = new Sites();
            $site->setDomain($domain);
            $site->setSubdomain($subdomain);
            $site->setDb($db);
            $site->setDbuser($this->getUser()->getUsername() . '_phplake');
            $site->setProject($project);

            $project->addSite($site);
            $em->persist($project);
            $em->flush();
            
            // creating the ACL
            $aclProvider = $this->get('security.acl.provider');
            $objectIdentity = ObjectIdentity::fromDomainObject($project, $site);
            $acl = $aclProvider->createAcl($objectIdentity);

            // retrieving the security identity of the currently logged-in user
            $tokenStorage = $this->get('security.token_storage');
            $user = $tokenStorage->getToken()->getUser();
            $securityIdentity = UserSecurityIdentity::fromAccount($user);

            // grant owner access
            $acl->insertObjectAce($securityIdentity, MaskBuilder::MASK_OWNER);
            $aclProvider->updateAcl($acl);
            
            $this->addFlash(
                'success',
                'Project created successfully with default dev environment.'
            );
        }
        else {
            $this->addFlash(
                'error',
                $response
            );
        }
        $session->remove('domaininfo');
        return $this->redirectToRoute('myprojects');
    }
    
    /**
     * @Route("/payment/canceled", name="paymentcanceled")
     */
    public function paymentcancelAction(Request $request)
    {
        $this->addFlash(
            'error',
            'Project payment canceled.'
        );
        
        return $this->redirectToRoute('myprojects');
    }
    
    /**
     * @Route("/payment/failed", name="paymentfailed")
     */
    public function paymentfailedAction(Request $request)
    {
        $this->addFlash(
            'error',
            'Project payment failed.'
        );
        
        return $this->redirectToRoute('myprojects');
    }
}