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

use AppBundle\Entity\Projects;
use AppBundle\Entity\Sites;

class PayUMoneyController extends Controller
{
    /**
     * @Route("/dashboard/PayUMoney", name="PayUMoney")
     */
    public function PayUMoneyAction(Request $request)
    {
        $key = $this->container->getParameter('payumoney_key');
        $salt = $this->container->getParameter('payumoney_salt');
        $hash_string = '';
        $hash = '';
        $productinfo = '';
        $txnid = substr(hash('sha256', mt_rand() . microtime()), 0, 20);
        if ($request->request->get('txnid')) {
            $txnid = $request->request->get('txnid');
            if ($request->request->get('amount') == 6710) {
                $productinfo = 'subscribed_yearly';
            }
            elseif($request->request->get('amount') == 675) {
                $productinfo = 'subscribed_monthly';
            }
            $hashSequence = "key|txnid|amount|productinfo|firstname|email|udf1|udf2|udf3|udf4|udf5|udf6|udf7|udf8|udf9|udf10";
            $hashVarsSeq = explode('|', $hashSequence);
            $hash_string = '';
            foreach($hashVarsSeq as $hash_var) {
                if($hash_var == 'productinfo')
                $hash_string .= $productinfo;
                else
                $hash_string .= $request->request->get($hash_var) ? $request->request->get($hash_var) : '';
                $hash_string .= '|';
            }
            $hash_string .= $salt;
            $hash = strtolower(hash('sha512', $hash_string));
        }
        return $this->render('default/PayUMoney.html.twig', [
            'key' => $key,
            'salt' => $salt,
            'hash' => $hash,
            'productinfo' => $productinfo,
            'action' => $this->container->getParameter('payumoney_endurl'),
            'txnid' => $txnid,
            'surl' => $request->getSchemeAndHttpHost() . $this->generateUrl('PayUMoneySuccess'),
            'furl' => $request->getSchemeAndHttpHost() . $this->generateUrl('PayUMoneyFailed'),
            'curl' => $request->getSchemeAndHttpHost() . $this->generateUrl('PayUMoneyCanceled')
        ]);
    }
    
    /**
     * @Route("/PayUMoney/success", name="PayUMoneySuccess")
     * @Method("POST")
     */
    public function PayUmoneySuccessAction(Request $request)
    {
        if ($request->request->get('status') == 'success') {
            $session = new Session();
            $arr = explode('|', $session->get('domaininfo'));
            if($arr[0] == 'new') {
                list($type, $name, $domain, $subdomain, $db, $file, $dir) = explode('|', $session->get('domaininfo'), 7);
                $response = $this->get('app.whm')->update_cpanel_account($this->getUser()->getUsername(), $domain, $subdomain, $db, $file, $dir, 'off');
                if ($response == 'success') {
                    $date = new \DateTime('now');
                    $request->request->get('productinfo') == 'subscribed_monthly' ? $date->modify('+30 days') : $date->modify('+1 year');
                    $em = $this->getDoctrine()->getManager();
                    $project = new Projects();
                    $project->setName($name);
                    $project->setCategory($dir);
                    $project->setTargetUrl($file);
                    $project->setSubscription($request->request->get('productinfo'));
                    $project->setDuedate($date->format('d-m-Y'));
                    $project->setPrice($request->request->get('amount'));
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
            }
            elseif($arr[0] == 'clone'){
                list($type, $name, $id) = explode('|', $session->get('domaininfo'), 3);
                $date = new \DateTime('now');
                $request->request->get('productinfo') == 'subscribed_monthly' ? $date->modify('+30 days') : $date->modify('+1 year');
                $project = $this->getDoctrine()->getRepository('AppBundle:Projects')->find($id);
                $project->setSubscription($request->request->get('productinfo'));
                $project->setDuedate($date->format('d-m-Y'));
                $project->setPrice($request->request->get('amount'));
                $em = $this->getDoctrine()->getManager();
                $em->persist($project);
                $em->flush();
                return $this->redirectToRoute($name, ['id' => $id]);
            }
        }
        else {
            $this->addFlash(
                'error',
                $request->request->get('error_Message')
            );
        }
        $session->remove('domaininfo');
        return $this->redirectToRoute('myprojects');
    }
    
    /**
     * @Route("/PayUMoney/canceled", name="PayUMoneyCanceled")
     */
    public function PayUmoneyCanceledAction(Request $request)
    {
        $this->addFlash(
            'error',
            'Project payment canceled.'
        );
        
        return $this->redirectToRoute('myprojects');
    }
    
    /**
     * @Route("/payment/failed", name="PayUMoneyFailed")
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