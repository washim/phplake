<?php
namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

class AutomationController extends Controller
{
    /**
     * @Route("/api", name="api")
     */
    public function apiAction(Request $request)
    {
        return new Response('');
    }
}