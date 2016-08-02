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
use AppBundle\Form\ProjectsType;

/**
 * @Route("/dashboard")
 */
class DashboardController extends Controller
{
    /**
     * @Route("/", name="dashboard")
     */
    public function indexAction(Request $request)
    {
		$debug = 'off';
		$site = new Sites();
        $project = new Projects();
        $project->setOwner($this->getUser());
        
        $form = $this->createForm(ProjectsType::class, $project);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
			$subscription = $this->getUser()->getProjects()->matching(Criteria::create()->where(Criteria::expr()->eq("subscription", 'free')))->first();
            $domain    = 'dev-' . $project->getName() . '-' . $this->getUser()->getUsername() . '.phplake.com';
            $subdomain = 'dev-' . $project->getName() . '-' . $this->getUser()->getUsername();
            $db        = $this->getUser()->getUsername() . '_' . $project->getName() . '_dev';
            $dbpass    = bin2hex(random_bytes(6));
            $pass      = bin2hex(random_bytes(6));
            $user      = $this->get('app.whm')->getwhmuser($this->getUser()->getUsername());
            if ($user == "success") {
                if ($subscription == false) {
					$response = $this->get('app.whm')->update_cpanel_account($this->getUser()->getUsername(), $domain, $subdomain, $db, $project->getTargetUrl(), $project->getCategory(), $debug);
                    if ($response == 'success') {
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
                else {
                    $this->addFlash(
                        'error',
                        'You have reached your limit of free project. To create a new project, delete an unused free project or choose project subscription.'
                    );
                    $session = new Session();
					$session->set('domaininfo', 'new|' . $project->getName() . '|' . $domain . '|' . $subdomain . '|' . $db . '|' . $project->getTargetUrl() . '|' . $project->getCategory());
                    return $this->redirectToRoute('upgrade');
                }
            }
            else {
                $idepass   = bin2hex(random_bytes(6));
				$response = $this->get('app.whm')->create_cpanel_account($this->getUser()->getUsername(), $pass, $this->getUser()->getEmail(), $domain, $subdomain, $db, $dbpass, $project->getTargetUrl(), $project->getCategory(), $idepass, $debug);
                if ($response == 'success') {
                    $idemail = \Swift_Message::newInstance()
                        ->setSubject('Online IDE Phplake')
                        ->setFrom(['support@phplake.com' => 'Phplake Support'])
                        ->setTo($this->getUser()->getEmail())
                        ->setBody(
                            $this->renderView('Emails/ide.html.twig', [
                                'user' => $this->getUser(),
                                'idepass' => $idepass
                            ])
                        );
                    $this->get('mailer')->send($idemail);
                    
                    $dbmail = \Swift_Message::newInstance()
                        ->setSubject('Dev/Stage DB Credential Phplake')
                        ->setFrom(['support@phplake.com' => 'Phplake Support'])
                        ->setTo($this->getUser()->getEmail())
                        ->setBody(
                            $this->renderView('Emails/db.html.twig', [
                                'user' => $this->getUser(),
								'db' => $db,
                                'dbpass' => $dbpass
                            ])
                        );
                    $this->get('mailer')->send($dbmail);
                    
                    $this->addFlash(
                        'success',
                        'Project created with default Dev environment.'
                    );
                }
                else {
                    $this->addFlash(
                        'error',
                        'Project creation failed.'
                    );
                }
            }
            
            $em = $this->getDoctrine()->getManager();
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
            
			if ($debug == 'off')
            return $this->redirectToRoute('dashboard');
        }
        return $this->render('default/dashboard.html.twig', [
            'form' => $form->createView(),
        ]);
    }
    
    /**
     * @Route("/myprojects", name="myprojects")
     */
    public function myprojectsAction(Request $request)
    {
        $projects = $this->getUser()->getProjects();
        return $this->render('default/myprojects.html.twig',['projects' => $projects]);
    }
    
    /**
     * @Route("/myproject/{id}", name="myproject_details")
     */
    public function myprojectdetailsAction(Request $request, Projects $project)
    {
        $authorizationChecker = $this->get('security.authorization_checker');
        if (false === $authorizationChecker->isGranted('VIEW', $project)) {
            throw new AccessDeniedException();
        }
        $dev = $project->getSites()->matching(
            Criteria::create()
            ->where(Criteria::expr()->eq("environment", 'dev')))
            ->first();
            
        $stage = $project->getSites()->matching(
            Criteria::create()
            ->where(Criteria::expr()->eq("environment", 'stage')))
            ->first();
            
        $prod = $project->getSites()->matching(
            Criteria::create()
            ->where(Criteria::expr()->eq("environment", 'prod')))
            ->first();
            
        return $this->render('default/myproject_details.html.twig', [
            'project' => $project,
            'dev' => $dev,
            'stage' => $stage,
            'prod' => $prod
        ]);
    }
    
    /**
     * @Route("/myproject/{id}/delete", name="myproject_delete")
	 * @Method("POST")
     */
    public function myprojectdeleteAction(Request $request, Projects $project)
    {
		$authorizationChecker = $this->get('security.authorization_checker');
        if (false === $authorizationChecker->isGranted('VIEW', $project)) {
            throw new AccessDeniedException();
        }
        
        if ($project->getId() && $request->request->get('agree_perform_action')) {
            foreach ($project->getSites() as $site) {
				$this->get('app.whm')->deletesite($this->getUser()->getUsername(), $site->getDomain(), $site->getSubdomain() . '.' . $this->getUser()->getIde(), $site->getDb());
                // Deleting the ACL
                $aclProvider = $this->get('security.acl.provider');
                $objectIdentity = ObjectIdentity::fromDomainObject($site);
                $aclProvider->deleteAcl($objectIdentity);
            }
            
            // Deleting the ACL
            $aclProvider = $this->get('security.acl.provider');
            $objectIdentity = ObjectIdentity::fromDomainObject($project);
            $aclProvider->deleteAcl($objectIdentity);
            
            $em = $this->getDoctrine()->getManager();
    		$em->remove($project);
    		$em->flush();
    		
    		$this->addFlash(
    			'success',
    			'Project deleted with all environment successfully.'
    		);
        }
        else {
            $this->addFlash(
    			'error',
    			'Project which you looking for does not exist.'
    		);
        }
		
        return $this->redirectToRoute('myprojects');
    }
    
    /**
     * @Route("/env/{id}/delete", name="env_delete")
	 * @Method("POST")
     */
    public function envdeleteAction(Request $request, Sites $site)
    {
        $authorizationChecker = $this->get('security.authorization_checker');
        if (false === $authorizationChecker->isGranted('VIEW', $site)) {
            throw new AccessDeniedException();
        }
        
        if ($site->getId() && $request->request->get('agree_perform_action')) {
			$this->get('app.whm')->deletesite($this->getUser()->getUsername(), $site->getDomain(), $site->getSubdomain() . '.' . $this->getUser()->getIde(), $site->getDb());
            // Deleting the ACL
            $aclProvider = $this->get('security.acl.provider');
            $objectIdentity = ObjectIdentity::fromDomainObject($site);
            $aclProvider->deleteAcl($objectIdentity);
            
            $em = $this->getDoctrine()->getManager();
    		$em->remove($site);
    		$em->flush();
    		
    		$this->addFlash(
    			'success',
    			'Environment deleted successfully.'
    		);
        }
        else {
            $this->addFlash(
    			'error',
    			'Site which you trying to delete does not exist.'
    		);
        }
		
        return $this->redirectToRoute('myprojects');
    }
    
    /**
     * @Route("/myproject/{id}/createstage", name="myproject_create_stage")
     */
    public function myprojectcreatestageAction(Request $request, Projects $project)
    {
        $authorizationChecker = $this->get('security.authorization_checker');
        if (false === $authorizationChecker->isGranted('VIEW', $project)) {
            throw new AccessDeniedException();
        }
		
		if ($project->getSubscription() == 'free') {
			$session = new Session();
			$session->set('domaininfo', 'clone|myproject_create_stage|' . $project->getId());
			return $this->redirectToRoute('upgrade');
		}
		
        $domain    = 'stage-' . $project->getName() . '-' . $this->getUser()->getUsername() . '.phplake.com';
        $subdomain = 'stage-' . $project->getName() . '-' . $this->getUser()->getUsername();
        $db        = $this->getUser()->getUsername() . '_' . $project->getName() . '_stage';
        
        $sites = $project->getSites();
        $criteria = Criteria::create()
            ->where(Criteria::expr()->eq("domain", $domain))
        ;
        $site = $sites->matching($criteria)->first();
        
        if ($site === false) {
			$response = $this->get('app.whm')->siteclone($this->getUser()->getUsername(), $domain, $subdomain, str_replace('stage', 'dev', $domain) , $db, $project->getTargetUrl(), $project->getCategory());
            if ($response == 'success') {
                $this->addFlash(
                    'success',
                    'Stage environment created successfully.'
                );
            }
            else {
                $this->addFlash(
                    'error',
                    'Stage environment creation failed.'
                );
            }
        }
        else {
            $this->addFlash(
                'error',
                'Stage environment already exist in your account.'
            );
            
            return $this->redirectToRoute('myproject_details', ['id' => $project->getId()]);
        }
        
        $site = new Sites();
        $site->setDomain($domain);
        $site->setSubdomain($subdomain);
        $site->setDb($db);
        $site->setDbuser($this->getUser()->getUsername() . '_phplake');
        $site->setEnvironment('stage');
        $site->setProject($project);
        $project->addSite($site);
        
        $em = $this->getDoctrine()->getManager();
        $em->persist($project);
        $em->flush();
        
        // creating the ACL
        $aclProvider = $this->get('security.acl.provider');
        $objectIdentity = ObjectIdentity::fromDomainObject($site);
        $acl = $aclProvider->createAcl($objectIdentity);

        // retrieving the security identity of the currently logged-in user
        $tokenStorage = $this->get('security.token_storage');
        $user = $tokenStorage->getToken()->getUser();
        $securityIdentity = UserSecurityIdentity::fromAccount($user);

        // grant owner access
        $acl->insertObjectAce($securityIdentity, MaskBuilder::MASK_OWNER);
        $aclProvider->updateAcl($acl);
        
        return $this->redirectToRoute('myproject_details', ['id' => $project->getId()]);
    }
    
    /**
     * @Route("/myproject/{id}/createprod", name="myproject_create_prod")
     */
    public function myprojectcreateprodAction(Request $request, Projects $project)
    {
        $authorizationChecker = $this->get('security.authorization_checker');
        if (false === $authorizationChecker->isGranted('VIEW', $project)) {
            throw new AccessDeniedException();
        }
		
		if ($project->getSubscription() == 'free') {
			$session = new Session();
			$session->set('domaininfo', 'clone|myproject_create_prod|' . $project->getId());
			return $this->redirectToRoute('upgrade');
		}
        
        $domain    = 'prod-' . $project->getName() . '-' . $this->getUser()->getUsername() . '.phplake.com';
        $subdomain = 'prod-' . $project->getName() . '-' . $this->getUser()->getUsername();
        $db        = $this->getUser()->getUsername() . '_' . $project->getName() . '_prod';
        
        $sites = $project->getSites();
        $criteria = Criteria::create()
            ->where(Criteria::expr()->eq("domain", $domain))
        ;
        $site = $sites->matching($criteria)->first();
        
        if ($site === false) {
            $response = $this->get('app.whm')->siteclone($this->getUser()->getUsername(), $domain, $subdomain, str_replace('prod', 'stage', $domain) , $db, $project->getTargetUrl(), $project->getCategory());
            if ($response == 'success') {
                $this->addFlash(
                    'success',
                    'Production environment created successfully.'
                );
            }
            else {
                $this->addFlash(
                    'error',
                    'Stage environment creation failed.'
                );
            }
        }
        else {
            $this->addFlash(
                'error',
                'Production environment already exist in your account.'
            );
            
            return $this->redirectToRoute('myproject_details', ['id' => $project->getId()]);
        }
        
        $site = new Sites();
        $site->setDomain($domain);
        $site->setSubdomain($subdomain);
        $site->setDb($db);
        $site->setDbuser($this->getUser()->getUsername() . '_phplake');
        $site->setEnvironment('prod');
        $site->setProject($project);
        $project->addSite($site);
        
        $em = $this->getDoctrine()->getManager();
        $em->persist($project);
        $em->flush();
        
        // creating the ACL
        $aclProvider = $this->get('security.acl.provider');
        $objectIdentity = ObjectIdentity::fromDomainObject($site);
        $acl = $aclProvider->createAcl($objectIdentity);

        // retrieving the security identity of the currently logged-in user
        $tokenStorage = $this->get('security.token_storage');
        $user = $tokenStorage->getToken()->getUser();
        $securityIdentity = UserSecurityIdentity::fromAccount($user);

        // grant owner access
        $acl->insertObjectAce($securityIdentity, MaskBuilder::MASK_OWNER);
        $aclProvider->updateAcl($acl);
        
        return $this->redirectToRoute('myproject_details', ['id' => $project->getId()]);
    }
	
	/**
     * @Route("/keygen", name="keygen")
	 * @Method("POST")
     */
	public function keygen(Request $request)
	{
		if ($request->request->get('agree_perform_action') && count($this->getUser()->getProjects()) > 0) {
			$keygen = $this->get('app.whm')->perform('cpanel', array(
				'cpanel_jsonapi_user' => $this->getUser()->getUsername(),
				'cpanel_jsonapi_apiversion' => '2',
				'cpanel_jsonapi_module' => 'SSH',
				'cpanel_jsonapi_func' => 'genkey',
				'bits' => 1024,
				'name' => 'id_rsa',
				'type' => 'rsa'
			));
			if (!isset($keygen->cpanelresult->error)) {
				$keyfetch = $this->get('app.whm')->perform('cpanel', array(
					'cpanel_jsonapi_user' => $this->getUser()->getUsername(),
					'cpanel_jsonapi_apiversion' => '2',
					'cpanel_jsonapi_module' => 'SSH',
					'cpanel_jsonapi_func' => 'fetchkey',
					'name' => 'id_rsa',
					'pub' => 1
				));
				if (!isset($keyfetch->cpanelresult->error)) {
					$user = $this->getUser();
					$user->setSshkey($keyfetch->cpanelresult->data[0]->key);
					$em = $this->getDoctrine()->getManager();
					$em->persist($user);
					$em->flush();
					
					$this->addFlash(
						'success',
						'SSH Key succesfully generated.'
					);
					
					return $this->redirectToRoute('myaccount');
				}
			}
		}
		else {
			$this->addFlash(
				'error',
				'Atleast one project is mandatory to create SSH key.'
			);

			return $this->redirectToRoute('myaccount');
		}
	}
	
	/**
     * @Route("/upgrade", name="upgrade")
     */
    public function upgradeAction(Request $request)
    {     
        return $this->render('default/upgrade.html.twig');
    }
}