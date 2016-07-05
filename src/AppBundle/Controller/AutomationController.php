<?php
namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

class AutomationController extends Controller
{
    private $whmusername;
    private $hash;
    private $host;
    
    public function __construct()
    {
        $this->whmusername = "root";
        $this->hash = "881257cf833c814734f39eef33936ea794b2cd97a974c2c245c247c82ca97693a1afa19a9be8860ca525a121c3253bf63110b685d72ab1f38f1da786278bda2151e8524cbf4affe19ca3aa10a42023c0b7780bd213f43481111dc0fac1bb7d61ab6417dec32a18acb6f3087c3bfe0dbbfbffed040ec9cfcfd1695390bb83e2f0fbbd916c21962c5008b9361cc31193bcc3f932a925d72c63f115ed7c7240061e8eaffc600fa63ae73746675343890d1ad0bfb2be1859b7be3d689982a0b0756b27a1d7ed3349a87d887ab229a14a7984feef146d73d3ba6fc5eb2423e29352cdf7d8606ca94c7fa71a419d4fbc37482a81850240f672b4e98126938102aafd99a34ea122a8aa43054ffa2694d832dd9113e3fe7119058effb7214bd886ccc266f103372c625a4d6db6100786bef566182906949889a648b500cb1e8d974759b2e797aaafc2778c80aa580db00fc0ce69b06259cf33f84960a04448856232718ff57d962d2aad5caa52300c20c91eeead8b418e6fe6a15c4bbae7e030f665a60f4efb2fa6f270970a14b85b54a432a84586b66e1f99ce84bf0d60159e7c4b587363ba0cd224db82d46a246d6608bb101850aae95476dda9f6718fb97983ea0d4554888cf7deb2b2a2b333eae0f497597a";
        $this->host = 'https://server.tmwgroups.com:2087/json-api/';
    }
    
    /**
     * @Route("/deploy", name="api_deploy")
     */
    public function deployAction(Request $request)
    {
        $res = $this->get('app.whm')->getwhmuser($this->getUser()->getUsername());
        print_r($res);
        return new Response();
    }
}