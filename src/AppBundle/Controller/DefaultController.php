<?php

namespace AppBundle\Controller;

use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;
use Symfony\Component\Security\Acl\Permission\MaskBuilder;
use Doctrine\Common\Collections\Criteria;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Entity\Projects;
use AppBundle\Entity\Sites;
use AppBundle\Form\ProjectsType;

class DefaultController extends Controller
{
    /**
     * @Route("/", name="homepage")
     */
    public function indexAction(Request $request)
    {
        $site = new Sites();
        $project = new Projects();
        $project->setOwner($this->getUser());
        
        $form = $this->createForm(ProjectsType::class, $project);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $domain    = 'dev-' . $project->getName() . '-' . $this->getUser()->getUsername() . '.phplake.com';
            $totproj   = count($this->getUser()->getProjects());
            $docroot   = '/home/' . $this->getUser()->getUsername() . '/public_html/workspace/' . $domain;
            $subdomain = 'dev-' . $project->getName() . '-' . $this->getUser()->getUsername();
            $db        = implode('_', array(substr($this->getUser()->getUsername(), 0, 8), $project->getName(), 'dev'));
            $dbpass    = 'phplake786';
            $pass      = 'merriment786';
            if ($totproj > 0 || $this->get('app.whm')->getwhmuser($this->getUser()->getUsername()) !== 206 ) {
                if ($this->getUser()->getSubscription() == 'paid') {
                    $response = $this->get('app.whm')->updatecp(
                        $this->getUser()->getUsername(),
                        $docroot,
                        $domain,
                        $subdomain,
                        $db,
                        $dbpass,
                        $project->getTargetUrl(),
                        $project->getCategory()
                    );
                    if ($response == 200) {
                        // Pull Dev environment source code from url
                        $buildsource = $this->get('app.phplake')->buildsourceupdate(
                            $this->getUser()->getUsername(),
                            $project->getTargetUrl(),
                            $docroot,
                            $project->getCategory(),
                            $domain,
                            $this->getUser()->getIde()
                        );
                        if (!empty($buildsource->status)) {
                            $this->addFlash(
                                'success',
                                'Project created successfully with default dev environment.'
                            );
                        }
                        else {
                            $this->addFlash(
                                'error',
                                'Dev Environment source code build failed.'
                            );
                        }
                    }
                    else {
                        $this->addFlash(
                            'error',
                            $this->get('app.phplake')->geterror($response)
                        );
                    }
                }
                else {
                    $this->addFlash(
                        'error',
                        'You have reached your limit of project. To create a new project, delete an unused project or upgrade your account.'
                    );
                    
                    return $this->redirectToRoute('homepage');
                }
            }
            else {
                $response = $this->get('app.whm')->createcp(
                    $this->getUser()->getUsername(),
                    $pass,
                    $this->getUser()->getEmail(),
                    $domain,
                    $docroot,
                    $subdomain,
                    $db,
                    $dbpass,
                    $project->getTargetUrl(),
                    $project->getCategory()
                );
                if ($response == 200) {
                    $buildsourcecreate = $this->get('app.phplake')->buildsourcecreate(
                        $this->getUser()->getUsername(),
                        $project->getTargetUrl(),
                        $docroot,
                        $project->getCategory(),
                        $domain
                    );
                    if ($buildsourcecreate->status == 0) {
                        $this->addFlash(
                            'success',
                            'Project created with default dev environment.'
                        );
                    }
                    else {
                        $this->addFlash(
                            'error',
                            'Dev Environment source code build failed.'
                        );
                    }
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
            $site->setStorage(serialize(array(
                'db' => $db,
                'dbuser' => $this->getUser()->getUsername() . '_phplake',
                'dbpass' => $dbpass
            )));
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
            
            return $this->redirectToRoute('homepage');
        }
        return $this->render('default/index.html.twig', [
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
     */
    public function myprojectdeleteAction(Request $request, Projects $project)
    {
        $authorizationChecker = $this->get('security.authorization_checker');
        if (false === $authorizationChecker->isGranted('VIEW', $project)) {
            throw new AccessDeniedException();
        }
        
        foreach ($project->getSites() as $site) {
            // Env Delete
            $this->get('app.whm')->envdelete(
                $this->getUser()->getUsername(),
                $site->getDomain(),
                $site->getSubdomain() . '.' . $this->getUser()->getIde(),
                unserialize($site->getStorage())['db']
            );
            // Codiad deleting project
            $this->get('app.phplake')->envdelete(
                $site->getDomain(),
                $this->getUser()->getIde()
            );
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
		
        return $this->redirectToRoute('myprojects');
    }
    
    /**
     * @Route("/env/{id}/delete", name="env_delete")
     */
    public function envdeleteAction(Request $request, Sites $site)
    {
        $authorizationChecker = $this->get('security.authorization_checker');
        if (false === $authorizationChecker->isGranted('VIEW', $site)) {
            throw new AccessDeniedException();
        }
        
        // Env Delete
        $this->get('app.whm')->envdelete(
            $this->getUser()->getUsername(),
            $site->getDomain(),
            $site->getSubdomain() . '.' . $this->getUser()->getIde(),
            unserialize($site->getStorage())['db']
        );
        // Codiad deleting project
        $this->get('app.phplake')->envdelete(
            $site->getDomain(),
            $this->getUser()->getIde()
        );
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
		
        return $this->redirectToRoute('myprojects');
    }
    
    /**
     * @Route("/myproject/{id}/createstage", name="myproject_create_stage")
     */
    public function myprojectcreatestageAction(Request $request, Projects $project)
    {
        $domain = 'stage-' . $project->getName() . '-' . $this->getUser()->getUsername() . '.phplake.com';
        $docroot   = '/home/' . $this->getUser()->getUsername() . '/public_html/workspace/' . $domain;
        $subdomain = 'stage-' . $project->getName() . '-' . $this->getUser()->getUsername();
        $db        = $this->getUser()->getUsername() . '_' . $project->getName() . '_stage';
        $dbpass    = 'phplake786';
        $pass      = 'merriment786';
        
        $sites = $project->getSites();
        $criteria = Criteria::create()
            ->where(Criteria::expr()->eq("domain", $domain))
        ;
        $site = $sites->matching($criteria)->first();
        
        if ($site === false) {
            $response = $this->get('app.whm')->updatecp(
                $this->getUser()->getUsername(),
                $docroot,
                $domain,
                $subdomain,
                $db,
                $dbpass,
                $project->getTargetUrl(),
                $project->getCategory()
            );
            if ($response == 200) {
                // Pull Dev environment source code from url
                $buildsource = $this->get('app.phplake')->buildsourceupdate(
                    $this->getUser()->getUsername(),
                    $project->getTargetUrl(),
                    $docroot,
                    $project->getCategory(),
                    $domain,
                    $this->getUser()->getIde()
                );
                if (!empty($buildsource->status)) {
                    $this->addFlash(
                        'success',
                        'Stage environment created successfully.'
                    );
                }
                else {
                    $this->addFlash(
                        'error',
                        'Stage Environment source code build failed.'
                    );
                }
            }
            else {
                $this->addFlash(
                    'error',
                    $this->get('app.phplake')->geterror($response)
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
        $site->setStorage(serialize(array(
            'db' => $db,
            'dbuser' => $this->getUser()->getUsername() . '_phplake',
            'dbpass' => $dbpass
        )));
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
        $domain = 'prod-' . $project->getName() . '-' . $this->getUser()->getUsername() . '.phplake.com';
        $docroot   = '/home/' . $this->getUser()->getUsername() . '/public_html/workspace/' . $domain;
        $subdomain = 'prod-' . $project->getName() . '-' . $this->getUser()->getUsername();
        $db        = $this->getUser()->getUsername() . '_' . $project->getName() . '_prod';
        $dbpass    = 'phplake786';
        $pass      = 'merriment786';
        
        $sites = $project->getSites();
        $criteria = Criteria::create()
            ->where(Criteria::expr()->eq("domain", $domain))
        ;
        $site = $sites->matching($criteria)->first();
        
        if ($site === false) {
            $response = $this->get('app.whm')->updatecp(
                $this->getUser()->getUsername(),
                $docroot,
                $domain,
                $subdomain,
                $db,
                $dbpass,
                $project->getTargetUrl(),
                $project->getCategory()
            );
            if ($response == 200) {
                // Pull Dev environment source code from url
                $buildsource = $this->get('app.phplake')->buildsourceupdate(
                    $this->getUser()->getUsername(),
                    $project->getTargetUrl(),
                    $docroot,
                    $project->getCategory(),
                    $domain,
                    $this->getUser()->getIde()
                );
                if (!empty($buildsource->status)) {
                    $this->addFlash(
                        'success',
                        'Production environment created successfully.'
                    );
                }
                else {
                    $this->addFlash(
                        'error',
                        'Production Environment source code build failed.'
                    );
                }
            }
            else {
                $this->addFlash(
                    'error',
                    $this->get('app.phplake')->geterror($response)
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
        $site->setStorage(serialize(array(
            'db' => $db,
            'dbuser' => $this->getUser()->getUsername() . '_phplake',
            'dbpass' => $dbpass
        )));
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
}
