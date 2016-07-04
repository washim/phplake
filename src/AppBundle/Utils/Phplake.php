<?php
namespace AppBundle\Utils;

class Phplake
{
    private $host;
    
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