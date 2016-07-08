<?php
namespace AppBundle\Utils;

class Phplake
{
    private $host;
    private $msg;
    
    public function __construct()
    {
        $this->host = 'http://api.phplake.com/api.live.php';
    }
    
    public function perform($postdata, $host = '')
    {
        empty($host) ? false : $this->host = $host;
        foreach ($postdata as $key => $value) {
            $params[] = $key . '=' . $value;
        }
        $query = $this->host . '?' . implode('&', $params);
        return $this->phplakerequest($query);
    }
    
    public function geterror($errorno)
    {
        switch ($errorno) {
            case 201:
                $this->msg = 'Account creation failed. Please try after some time.';
                break;
            case 202:
                $this->msg = 'Dev environment creation failed for project. Please try after some time.';
                break;
            case 203:
                $this->msg = 'Dev environment Database creation failed for project. Please try after some time.';
                break;
            case 204:
                $this->msg = 'Dev environment Database user creation failed for project. Please try after some time.';
                break;
            case 205:
                $this->msg = 'Dev environment Database privileges failed for project. Please try after some time.';
                break;
        }
        return $this->msg;
    }
    
    public function envdelete($domain, $ide)
    {
        // Codiad deleting project
        $this->perform(
            array(
                'anonymous' => 'yes',
                'action' => 'delete',
                'project_path' => $domain
            ),
            'http://'.$ide.'/components/project/controller.php'
        );
    }
    
    public function buildsourceupdate($user, $source, $docroot, $category, $domain, $ide)
    {
        $devpull = $this->perform(
            array(
                'action' => 'install',
                'user' => $user,
                'source' => $source,
                'destination' => $docroot,
                'tmpfolder' => $category
            )
        );
        if ($devpull->status == 0) {
            $addprojincodiad = $this->perform(
                array(
                    'anonymous' => 'yes',
                    'action' => 'create',
                    'project_name' => $domain,
                    'project_path' => $domain
                ),
                'http://'.$ide.'/components/project/controller.php'
            );
            return $addprojincodiad;
        }
    }
    
    public function buildsourcecreate($user, $source, $docroot, $category, $domain)
    {
        //Install Codiad for recently created user
        $codiad = $this->perform(
            array(
                'action' => 'install',
                'user' => $user,
                'source' => 'https://github.com/washim/Codiad/archive/ide.tar.gz',
                'destination' => '/home/' . $user . '/public_html',
                'tmpfolder' => 'Codiad-ide',
                'project' => $domain
            )
        );
        if ($codiad->status == 0) {
            //Pull Dev environment source code from url
            $devpull = $this->perform(
                array(
                    'action' => 'install',
                    'user' => $user,
                    'source' => $source,
                    'destination' => $docroot,
                    'tmpfolder' => $category
                )
            );
            return $devpull;
        }
    }
    
    private function phplakerequest($query)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_URL, $query);
        $result = curl_exec($curl);
        curl_close($curl);
        return json_decode($result);
    }
}