<?php
namespace AppBundle\Utils;

class Phplake
{
    private $host;
    private $msg;
    
    public function __construct()
    {
        $this->host = 'http://phplake.com/api';
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
    
    public function command($args) {
        $status = 1;
        if (count($args) > 1) {
            $command = $this->get('kernel')->getRootDir() . '/phplakecodebase ' . implode(' ', $args);
            exec($command . ' 2>&1', $output, $status);
        }
        return $status;
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