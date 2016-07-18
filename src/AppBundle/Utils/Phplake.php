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
    
    public function command($path, $args) {
        $command = $path . '/phplakecodebase ' . implode(' ', $args);
        $response = shell_exec($command);
        return trim($response);
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