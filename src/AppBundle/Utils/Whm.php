<?php
namespace AppBundle\Utils;

class Whm
{
    private $whmusername;
    private $hash;
    private $host;
    private $dbhost;
    
    public function __construct()
    {
        $this->whmusername = 'root';
        $this->hash = '881257cf833c814734f39eef33936ea794b2cd97a974c2c245c247c82ca97693a1afa19a9be8860ca525a121c3253bf63110b685d72ab1f38f1da786278bda2151e8524cbf4affe19ca3aa10a42023c0b7780bd213f43481111dc0fac1bb7d61ab6417dec32a18acb6f3087c3bfe0dbbfbffed040ec9cfcfd1695390bb83e2f0fbbd916c21962c5008b9361cc31193bcc3f932a925d72c63f115ed7c7240061e8eaffc600fa63ae73746675343890d1ad0bfb2be1859b7be3d689982a0b0756b27a1d7ed3349a87d887ab229a14a7984feef146d73d3ba6fc5eb2423e29352cdf7d8606ca94c7fa71a419d4fbc37482a81850240f672b4e98126938102aafd99a34ea122a8aa43054ffa2694d832dd9113e3fe7119058effb7214bd886ccc266f103372c625a4d6db6100786bef566182906949889a648b500cb1e8d974759b2e797aaafc2778c80aa580db00fc0ce69b06259cf33f84960a04448856232718ff57d962d2aad5caa52300c20c91eeead8b418e6fe6a15c4bbae7e030f665a60f4efb2fa6f270970a14b85b54a432a84586b66e1f99ce84bf0d60159e7c4b587363ba0cd224db82d46a246d6608bb101850aae95476dda9f6718fb97983ea0d4554888cf7deb2b2a2b333eae0f497597a';
        $this->host = 'https://server.tmwgroups.com:2087/json-api/';
    }
    
    public function perform($cmd, $postdata)
    {
        foreach ($postdata as $key => $value) {
            $params[] = $key . '=' . $value;
        }
        $query = $this->host . $cmd . '?' . implode('&', $params);
        return $this->whmrequest($query);
    }
    
    public function getwhmuser($user)
    {
        $cmd = 'listaccts';
        $postdata = array(
            'api.version' => 1,
            'search' => $user,
            'searchtype' => 'user',
        );
        foreach ($postdata as $key => $value) {
            $params[] = $key . '=' . $value;
        }
        $query = $this->host . $cmd . '?' . implode('&', $params);
        $response = $this->whmrequest($query);
        if (array_key_exists('data', $response)) {
            return reset($response->data->acct);
        }
        else {
            return 206;
        }
    }
    
    public function createdb($user, $db, $password, $action = '')
    {
        /**
         * Create DB for Dev Environment
         */
        $createdb = $this->perform('cpanel',
            array(
                'cpanel_jsonapi_user' => $user,
                'cpanel_jsonapi_apiversion' => '2',
                'cpanel_jsonapi_module' => 'MysqlFE',
                'cpanel_jsonapi_func' => 'createdb',
                'db' => $db
            )
        );
        if ($createdb->cpanelresult->event->result == 1) {
            if ($action == 'create_user') {
                /**
                 * Create DB User for recently created account
                 */
                $createdbuser = $this->perform('cpanel',
                    array(
                        'cpanel_jsonapi_user' => $user,
                        'cpanel_jsonapi_apiversion' => '2',
                        'cpanel_jsonapi_module' => 'MysqlFE',
                        'cpanel_jsonapi_func' => 'createdbuser',
                        'dbuser' => $user . '_phplake',
                        'password' => $password
                    )
                );
                
                if ($createdbuser->cpanelresult->event->result == 1) {
                    /**
                     * Set DB User to Database
                     */
                    $setdbuserprivileges = $this->perform('cpanel',
                        array(
                            'cpanel_jsonapi_user' => $user,
                            'cpanel_jsonapi_apiversion' => '2',
                            'cpanel_jsonapi_module' => 'MysqlFE',
                            'cpanel_jsonapi_func' => 'setdbuserprivileges',
                            'db' => $db,
                            'dbuser' => $user . '_phplake',
                            'privileges' => 'ALL PRIVILEGES'
                        )
                    );
                    if ($setdbuserprivileges->cpanelresult->event->result == 1) {
                        return 200;
                    }
                    else {
                        return 205; //Writing DB user privileges
                    }
                }
                else {
                    return 204; //DB user creation failed
                }
            }
            else {
                /**
                 * Set DB User to Database without creating new user
                 */
                $setdbuserprivileges = $this->perform('cpanel',
                    array(
                        'cpanel_jsonapi_user' => $user,
                        'cpanel_jsonapi_apiversion' => '2',
                        'cpanel_jsonapi_module' => 'MysqlFE',
                        'cpanel_jsonapi_func' => 'setdbuserprivileges',
                        'db' => $db,
                        'dbuser' => $user . '_phplake',
                        'privileges' => 'ALL PRIVILEGES'
                    )
                );
                if ($setdbuserprivileges->cpanelresult->event->result == 1) {
                    return 200;
                }
                else {
                    return 205; //Writing DB user privileges
                }
            }
        }
        else {
            return 203; //Dev Environment DB creation failed
        }
    }
    
    /**
     * Cpanel Create account
     */
    public function createcp($user, $pass, $email, $domain, $docroot, $subdomain, $db, $dbpass, $source, $category)
    {
        //Create WHM IDE User Account
        $createacct = $this->perform('createacct', 
            array(
                'api.version' => 1,
                'username' => $user,
                'password' => $pass,
                'contactemail' => $email,
                'domain' => 'ide-' . $user . '.phplake.com',
                'plan' => 'free',
                'hasshell' => 0,
                'owner' => 'tmwgroups'
            )
        );
        if ($createacct->metadata->result == 1) {
            //Create Dev Environment
            $addaddondomain = $this->perform('cpanel',
                array(
                    'cpanel_jsonapi_user' => $user,
                    'cpanel_jsonapi_apiversion' => '2',
                    'cpanel_jsonapi_module' => 'AddonDomain',
                    'cpanel_jsonapi_func' => 'addaddondomain',
                    'dir' => $docroot,
                    'newdomain' => $domain,
                    'subdomain' => $subdomain
                )
            );
            if (empty($addaddondomain->cpanelresult->error)) {
                //Create Database for Dev environment
                $createdb = $this->createdb($user, $db, $dbpass, 'create_user');
                return $createdb;
            }
            else {
                return 202; //Dev environment Addondomain creation failed
            }
        }
        else {
            return 201; //WHM User account creation failed
        }
    }
    
    /**
     * Cpanel Create account
     */
    public function updatecp($user, $domain, $docroot, $subdomain, $db, $dbpass, $source, $category)
    {
        $addaddondomain = $this->perform('cpanel',
            array(
                'cpanel_jsonapi_user' => $user,
                'cpanel_jsonapi_apiversion' => '2',
                'cpanel_jsonapi_module' => 'AddonDomain',
                'cpanel_jsonapi_func' => 'addaddondomain',
                'dir' => $docroot,
                'newdomain' => $domain,
                'subdomain' => $subdomain
            )
        );
        if (empty($addaddondomain->cpanelresult->error)) {
            // Create Database for Dev environment
            $createdb = $this->createdb($user, $db, $dbpass);
            return $createdb;
        }
        else {
            return 202; //Dev environment Addondomain creation failed
        }
    }
    
    /**
     * Cpanel Delete addon domain
     */
    public function envdelete($user, $domain, $subdomain, $docroot, $db)
    {
        $this->perform('cpanel',
            array(
                'cpanel_jsonapi_user' => $user,
                'cpanel_jsonapi_apiversion' => '2',
                'cpanel_jsonapi_module' => 'AddonDomain',
                'cpanel_jsonapi_func' => 'deladdondomain',
                'domain' => $domain,
                'subdomain' => $subdomain
            )
        );
        $this->perform('cpanel',
            array(
                'cpanel_jsonapi_user' => $user,
                'cpanel_jsonapi_apiversion' => '2',
                'cpanel_jsonapi_module' => 'MysqlFE',
                'cpanel_jsonapi_func' => 'deletedb',
                'db' => $db
            )
        );
        $this->perform('cpanel',
            array(
                'cpanel_jsonapi_user' => $user,
                'cpanel_jsonapi_apiversion' => '2',
                'cpanel_jsonapi_module' => 'Fileman',
                'cpanel_jsonapi_func' => 'fileop',
                'op' => 'unlink',
                'sourcefiles' => $docroot,
                'doubledecode' => 1
            )
        );
    }
    
    /**
     * WHM Common universal Curl request
     */
    private function whmrequest($query)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $header[0] = "Authorization: WHM $this->whmusername:" . preg_replace("'(\r|\n)'", "", $this->hash);
        curl_setopt($curl,CURLOPT_HTTPHEADER, $header);
        curl_setopt($curl, CURLOPT_URL, $query);
        $result = curl_exec($curl);
        curl_close($curl);
        return json_decode($result);
    }
}