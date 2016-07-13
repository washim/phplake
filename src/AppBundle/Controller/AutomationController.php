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
        $response = '';
        switch($request->query->get('action')) {
            case 'install':
                $user      = $request->query->get('user');
                $dest      = $request->query->get('destination');
                $source    = $request->query->get('source');
                $arr       = explode('/', $source);
                $filename  = $arr[count($arr) - 1];
                $tmpfolder = $request->query->get('tmpfolder');
                $workspace = '/home/'.$user;
                $command = "php ".__DIR__."/phplakecodebase $user $dest $source $filename $tmpfolder $workspace";
                exec($command . ' 2>&1', $output, $status);
                if ($status == 0 && !empty($request->query->get('project'))) {
                    $project = $request->query->get('project');
                    $ide     = "http://ide-" . $user . ".phplake.com";
                    $idepass = $request->query->get('idepass');
                    $query   = "$ide/components/install/setup.php?path=$dest&username=$user&password=$idepass&project_name=$project&project_path=$project&timezone=Asia/Kolkata";
                    $curl    = curl_init();
                    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
                    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
                    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
                    curl_setopt($curl, CURLOPT_URL, $query);
                    $output = curl_exec($curl);
                    $output = json_decode($output);
                    curl_close($curl);
                }
                $result = array(
                    'status' => $status,
                    'output' => $output
                );
                $response = json_encode($result);
                break;
        }
        return new Response($response);
    }
}