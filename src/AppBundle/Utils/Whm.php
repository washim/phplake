<?php
namespace AppBundle\Utils;

class Whm
{
    private $whmusername;
    private $hash;
    private $host;
    
    public function __construct()
    {
        $this->whmusername = 'root';
        $this->hash = file_get_contents('/home/phplake/.accesshash');
        $this->host   = 'https://server.tmwgroups.com:2087/json-api/';
    }
    
    public function perform($cmd, $postdata)
    {
        foreach ($postdata as $key => $value) {
            $params[] = $key . '=' . $value;
        }
        $query = $this->host . $cmd . '?' . implode('&', $params);
        return $this->whmrequest($query);
    }
    
    public function configureide($url, $postdata, $rawpostdata = '')
    {
        foreach ($postdata as $key => $value) {
            $params[] = $key . '=' . $value;
        }
        $query = $url . '?' . implode('&', $params);
        if (empty($rawpostdata))
            return $this->whmrequest($query);
        else
            return $this->whmrequest($query, $rawpostdata);
    }
    
    public function getwhmuser($user)
    {
        $postdata = array(
            'api.version' => 1,
            'search' => $user,
            'searchtype' => 'user',
        );
        foreach ($postdata as $key => $value) {
            $params[] = $key . '=' . $value;
        }
        $query = $this->host . 'listaccts?' . implode('&', $params);
        $response = $this->whmrequest($query);
        if (array_key_exists('data', $response)) 
        return 'success';
        else 
        return 'User does not exist';
    }
    
    public function putfiles($server, $user, $pass, $files)
    {
        $conn = ftp_connect($server);
        $login = ftp_login($conn, $user, $pass);
        foreach ($files as $remote_file => $file) {
            if(ftp_put($conn, $remote_file, $file, FTP_BINARY))
                $status = 'success';
            else {
                break;
                $status = 'failed';
            }
        }
        ftp_close($conn);
        return $status;
    }
    
    public function create_cpanel_account($user, $pass, $email, $domain, $subdomain , $db, $dbpass, $filename, $dir, $idepass, $debug)
    {
        $createacct = $this->perform('createacct', 
            array(
                'api.version'  => 1,
                'username'     => $user,
                'password'     => $pass,
                'contactemail' => $email,
                'domain'       => 'ide-' . $user . '.phplake.com',
                'plan'         => 'default',
                'owner'        => 'tmwgroups'
            )
        );
        $debug == 'on' ? dump($createacct) : null;
        if ($createacct->metadata->result == 1) {
            $addaddondomain = $this->perform('cpanel',
                array(
                    'cpanel_jsonapi_user' => $user,
                    'cpanel_jsonapi_apiversion' => '2',
                    'cpanel_jsonapi_module' => 'AddonDomain',
                    'cpanel_jsonapi_func' => 'addaddondomain',
                    'dir' => '/home/' . $user . '/public_html/workspace/' . $domain,
                    'newdomain' => $domain,
                    'subdomain' => $subdomain
                )
            );
            $debug == 'on' ? dump($addaddondomain) : null;
            if (!isset($addaddondomain->cpanelresult->error)) {
                $createdbuser = $this->perform('cpanel',
                    array(
                        'cpanel_jsonapi_user' => $user,
                        'cpanel_jsonapi_apiversion' => '2',
                        'cpanel_jsonapi_module' => 'MysqlFE',
                        'cpanel_jsonapi_func' => 'createdbuser',
                        'dbuser' => $user . '_phplake',
                        'password' => $dbpass
                    )
                );
                $debug == 'on' ? dump($createdbuser) : null;
                if (!isset($createdbuser->cpanelresult->error)) {
                    $createdb = $this->perform('cpanel',
                        array(
                            'cpanel_jsonapi_user' => $user,
                            'cpanel_jsonapi_apiversion' => '2',
                            'cpanel_jsonapi_module' => 'MysqlFE',
                            'cpanel_jsonapi_func' => 'createdb',
                            'db' => $db
                        )
                    );
                    $debug == 'on' ? dump($createdb) : null;
                    if (!isset($createdb->cpanelresult->error)) {
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
                        $debug == 'on' ? dump($setdbuserprivileges) : null;
                        if (!isset($setdbuserprivileges->cpanelresult->error)) {
                            $ftp = $this->putfiles("ftp.ide-$user.phplake.com", $user, $pass, array(
                                "ide.tar.gz" => "/home/phplake/public_html/files/ide.tar.gz",
                                $filename => "/home/phplake/public_html/files/$filename"
                            ));
                            $debug == 'on' ? dump($ftp) : null;
                            if ($ftp == 'success') {
                                $extract = $this->perform('cpanel',
                                    array(
                                        'cpanel_jsonapi_user' => $user,
                                        'cpanel_jsonapi_apiversion' => '2',
                                        'cpanel_jsonapi_module' => 'Fileman',
                                        'cpanel_jsonapi_func' => 'fileop',
                                        'op' => 'extract',
                                        'sourcefiles' => "ide.tar.gz,$filename",
                                        'doubledecode' => 1
                                    )
                                );
                                $debug == 'on' ? dump($extract) : null;
                                if (!isset($extract->cpanelresult->error)) {
                                    $removedocroot = $this->perform('cpanel',
                                        array(
                                            'cpanel_jsonapi_user' => $user,
                                            'cpanel_jsonapi_apiversion' => '2',
                                            'cpanel_jsonapi_module' => 'Fileman',
                                            'cpanel_jsonapi_func' => 'fileop',
                                            'op' => 'unlink',
                                            'sourcefiles' => "/home/$user/public_html",
                                            'doubledecode' => 1
                                        )
                                    );
                                    $debug == 'on' ? dump($removedocroot) : null;
                                    if (!isset($removedocroot->cpanelresult->error)) {
                                        $installide = $this->perform('cpanel',
                                            array(
                                                'cpanel_jsonapi_user' => $user,
                                                'cpanel_jsonapi_apiversion' => '2',
                                                'cpanel_jsonapi_module' => 'Fileman',
                                                'cpanel_jsonapi_func' => 'fileop',
                                                'op' => 'move',
                                                'sourcefiles' => "/home/$user/Codiad-ide",
                                                'destfiles' => "/home/$user/public_html",
                                                'doubledecode' => 1
                                            )
                                        );
                                        $debug == 'on' ? dump($installide) : null;
                                        if (!isset($installide->cpanelresult->error)) {
                                            $installapps = $this->perform('cpanel',
                                                array(
                                                    'cpanel_jsonapi_user' => $user,
                                                    'cpanel_jsonapi_apiversion' => '2',
                                                    'cpanel_jsonapi_module' => 'Fileman',
                                                    'cpanel_jsonapi_func' => 'fileop',
                                                    'op' => 'move',
                                                    'sourcefiles' => "/home/$user/$dir",
                                                    'destfiles' => "/home/$user/public_html/workspace/$domain",
                                                    'doubledecode' => 1
                                                )
                                            );
                                            $debug == 'on' ? dump($installapps) : null;
                                            if (!isset($installapps->cpanelresult->error)) {
                                                $this->perform('cpanel',
                                                    array(
                                                        'cpanel_jsonapi_user' => $user,
                                                        'cpanel_jsonapi_apiversion' => '2',
                                                        'cpanel_jsonapi_module' => 'Fileman',
                                                        'cpanel_jsonapi_func' => 'fileop',
                                                        'op' => 'unlink',
                                                        'sourcefiles' => "/home/$user/ide.tar.gz,/home/$user/$filename",
                                                        'doubledecode' => 1
                                                    )
                                                );
                                                $this->configureide("http://ide-$user.phplake.com/components/install/setup.php", array(
                                                    'path' => "/home/$user/public_html",
                                                    'base_url' => "ide-$user.phplake.com",
                                                    'username' => $user,
                                                    'password' => $idepass,
                                                    'project_name' => $domain,
                                                    'project_path' => $domain,
                                                    'timezone' => 'Asia/Kolkata'
                                                ));
                                                return 'success';
                                            }
                                            else {
                                                return $installapps->cpanelresult->error;
                                            }
                                        }
                                        else{
                                            return $installide->cpanelresult->error;
                                        }
                                    }
                                    else {
                                        return $removedocroot->cpanelresult->error;
                                    }
                                }
                                else {
                                    return $extract->cpanelresult->error;
                                }
                            }
                            else {
                                return 'Uploading source failed';
                            }
                        }
                        else {
                            return $setdbuserprivileges->cpanelresult->error;
                        }
                    }
                    else {
                        return $createdb->cpanelresult->error;
                    }
                }
                else {
                    return $createdbuser->cpanelresult->error;
                }
            }
            else {
                return $addaddondomain->cpanelresult->error;
            }
        }
        else {
            return 'Account creation failed';
        }
    }
    
    public function update_cpanel_account($user, $domain, $subdomain , $db, $filename, $dir, $debug)
    {
        $pass = bin2hex(random_bytes(6));
        $addaddondomain = $this->perform('cpanel',
            array(
                'cpanel_jsonapi_user' => $user,
                'cpanel_jsonapi_apiversion' => '2',
                'cpanel_jsonapi_module' => 'AddonDomain',
                'cpanel_jsonapi_func' => 'addaddondomain',
                'dir' => '/home/' . $user . '/public_html/workspace/' . $domain,
                'newdomain' => $domain,
                'subdomain' => $subdomain
            )
        );
        $debug == 'on' ? dump($addaddondomain) : null;
        if (!isset($addaddondomain->cpanelresult->error)) {
            $createdb = $this->perform('cpanel',
                array(
                    'cpanel_jsonapi_user' => $user,
                    'cpanel_jsonapi_apiversion' => '2',
                    'cpanel_jsonapi_module' => 'MysqlFE',
                    'cpanel_jsonapi_func' => 'createdb',
                    'db' => $db
                )
            );
            $debug == 'on' ? dump($createdb) : null;
            if (!isset($createdb->cpanelresult->error)) {
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
                $debug == 'on' ? dump($setdbuserprivileges) : null;
                if (!isset($setdbuserprivileges->cpanelresult->error)) {
                    $cpchangepass = $this->perform('passwd',
                        array(
                            'api.version'  => 1,
                            'user' => $user,
                            'password' => $pass
                        )
                    );
                    $debug == 'on' ? dump($cpchangepass) : null;
                    if ($cpchangepass->metadata->result == 1) {
                        $ftp = $this->putfiles("ftp.ide-$user.phplake.com", $user, $pass, array(
                            $filename => "/home/phplake/public_html/files/$filename"
                        ));
                        $debug == 'on' ? dump($ftp) : null;
                        if ($ftp == 'success') {
                            $extract = $this->perform('cpanel',
                                array(
                                    'cpanel_jsonapi_user' => $user,
                                    'cpanel_jsonapi_apiversion' => '2',
                                    'cpanel_jsonapi_module' => 'Fileman',
                                    'cpanel_jsonapi_func' => 'fileop',
                                    'op' => 'extract',
                                    'sourcefiles' => $filename,
                                    'doubledecode' => 1
                                )
                            );
                            $debug == 'on' ? dump($extract) : null;
                            if (!isset($extract->cpanelresult->error)) {
                                $removedocroot = $this->perform('cpanel',
                                    array(
                                        'cpanel_jsonapi_user' => $user,
                                        'cpanel_jsonapi_apiversion' => '2',
                                        'cpanel_jsonapi_module' => 'Fileman',
                                        'cpanel_jsonapi_func' => 'fileop',
                                        'op' => 'unlink',
                                        'sourcefiles' => "/home/$user/public_html/workspace/$domain",
                                        'doubledecode' => 1
                                    )
                                );
                                $debug == 'on' ? dump($removedocroot) : null;
                                if (!isset($removedocroot->cpanelresult->error)) {
                                    $installapps = $this->perform('cpanel',
                                        array(
                                            'cpanel_jsonapi_user' => $user,
                                            'cpanel_jsonapi_apiversion' => '2',
                                            'cpanel_jsonapi_module' => 'Fileman',
                                            'cpanel_jsonapi_func' => 'fileop',
                                            'op' => 'move',
                                            'sourcefiles' => "/home/$user/$dir",
                                            'destfiles' => "/home/$user/public_html/workspace/$domain",
                                            'doubledecode' => 1
                                        )
                                    );
                                    $debug == 'on' ? dump($installapps) : null;
                                    if (!isset($installapps->cpanelresult->error)) {
                                        $this->perform('cpanel',
                                            array(
                                                'cpanel_jsonapi_user' => $user,
                                                'cpanel_jsonapi_apiversion' => '2',
                                                'cpanel_jsonapi_module' => 'Fileman',
                                                'cpanel_jsonapi_func' => 'fileop',
                                                'op' => 'unlink',
                                                'sourcefiles' => "/home/$user/$filename",
                                                'doubledecode' => 1
                                            )
                                        );
                                        $this->configureide("http://ide-$user.phplake.com/components/project/controller.php", array(
                                            'action' => 'create',
                                            'project_name' => $domain,
                                            'project_path' => $domain,
                                            'key' => 'phplake786'
                                        ));
                                        return 'success';
                                    }
                                    else {
                                        return $installapps->cpanelresult->error;
                                    }
                                }
                                else {
                                    return $removedocroot->cpanelresult->error;
                                }
                            }
                            else {
                                return $extract->cpanelresult->error;
                            }
                        }
                        else {
                            return 'Uploading source failed';
                        }
                    }
                    else {
                        return $cpchangepass->cpanelresult->error;
                    }
                }
                else {
                    return $setdbuserprivileges->cpanelresult->error;
                }
            }
            else {
                return $createdb->cpanelresult->error;
            }
        }
        else {
            return $addaddondomain->cpanelresult->error;
        }
    }
    
    public function deletesite($user, $domain, $subdomain, $db)
    {
        $deladdondomain = $this->perform('cpanel',
            array(
                'cpanel_jsonapi_user' => $user,
                'cpanel_jsonapi_apiversion' => '2',
                'cpanel_jsonapi_module' => 'AddonDomain',
                'cpanel_jsonapi_func' => 'deladdondomain',
                'domain' => $domain,
                'subdomain' => $subdomain
            )
        );
        if (!isset($deladdondomain->cpanelresult->error)) {
            $deletedb = $this->perform('cpanel',
                array(
                    'cpanel_jsonapi_user' => $user,
                    'cpanel_jsonapi_apiversion' => '2',
                    'cpanel_jsonapi_module' => 'MysqlFE',
                    'cpanel_jsonapi_func' => 'deletedb',
                    'db' => $db
                )
            );
            if (!isset($deletedb->cpanelresult->error)) {
                $this->perform('cpanel',
                    array(
                        'cpanel_jsonapi_user' => $user,
                        'cpanel_jsonapi_apiversion' => '2',
                        'cpanel_jsonapi_module' => 'Fileman',
                        'cpanel_jsonapi_func' => 'fileop',
                        'op' => 'unlink',
                        'sourcefiles' => "/home/$2/public_html/workspace/$domain",
                        'doubledecode' => 1
                    )
                );
                $this->configureide("http://ide-$user.phplake.com/components/project/controller.php", array(
                    'action' => 'delete',
                    'project_path' => $domain,
                    'key' => 'phplake786'
                ));
                return 'success';
            }
            else {
                return $deletedb->cpanelresult->error;
            }
        }
        else {
            return $deladdondomain->cpanelresult->error;
        }
    }
    
    public function siteclone($user, $domain, $subdomain, $sourcedomain , $db, $dbuser, $filename, $dir)
    {
        $addaddondomain = $this->perform('cpanel',
            array(
                'cpanel_jsonapi_user' => $user,
                'cpanel_jsonapi_apiversion' => '2',
                'cpanel_jsonapi_module' => 'AddonDomain',
                'cpanel_jsonapi_func' => 'addaddondomain',
                'dir' => '/home/' . $user . '/public_html/workspace/' . $domain,
                'newdomain' => $domain,
                'subdomain' => $subdomain
            )
        );
        if (!isset($addaddondomain->cpanelresult->error)) {
            $createdb = $this->perform('cpanel',
                array(
                    'cpanel_jsonapi_user' => $user,
                    'cpanel_jsonapi_apiversion' => '2',
                    'cpanel_jsonapi_module' => 'MysqlFE',
                    'cpanel_jsonapi_func' => 'createdb',
                    'db' => $db
                )
            );
            if (!isset($createdb->cpanelresult->error)) {
                $checkdbuser = $this->perform('cpanel',
                    array(
                        'cpanel_jsonapi_user' => $user,
                        'cpanel_jsonapi_apiversion' => '2',
                        'cpanel_jsonapi_module' => 'MysqlFE',
                        'cpanel_jsonapi_func' => 'dbuserexists',
                        'dbuser' => $dbuser
                    )
                );
                if ($checkdbuser->cpanelresult->data[0] == 0) {
                    $this->perform('cpanel',
                        array(
                            'cpanel_jsonapi_user' => $user,
                            'cpanel_jsonapi_apiversion' => '2',
                            'cpanel_jsonapi_module' => 'MysqlFE',
                            'cpanel_jsonapi_func' => 'createdbuser',
                            'dbuser' => $dbuser,
                            'password' => $dbpass
                        )
                    );
                }
                $setdbuserprivileges = $this->perform('cpanel',
                    array(
                        'cpanel_jsonapi_user' => $user,
                        'cpanel_jsonapi_apiversion' => '2',
                        'cpanel_jsonapi_module' => 'MysqlFE',
                        'cpanel_jsonapi_func' => 'setdbuserprivileges',
                        'db' => $db,
                        'dbuser' => $dbuser,
                        'privileges' => 'ALL PRIVILEGES'
                    )
                );
                if (!isset($setdbuserprivileges->cpanelresult->error)) {
                    $removedocroot = $this->perform('cpanel',
                        array(
                            'cpanel_jsonapi_user' => $user,
                            'cpanel_jsonapi_apiversion' => '2',
                            'cpanel_jsonapi_module' => 'Fileman',
                            'cpanel_jsonapi_func' => 'fileop',
                            'op' => 'unlink',
                            'sourcefiles' => "/home/$user/public_html/workspace/$domain",
                            'doubledecode' => 1
                        )
                    );
                    if (!isset($removedocroot->cpanelresult->error)) {
                        $clone = $this->perform('cpanel',
                            array(
                                'cpanel_jsonapi_user' => $user,
                                'cpanel_jsonapi_apiversion' => '2',
                                'cpanel_jsonapi_module' => 'Fileman',
                                'cpanel_jsonapi_func' => 'fileop',
                                'op' => 'copy',
                                'sourcefiles' => "/home/$user/public_html/workspace/$sourcedomain/*",
                                'destfiles' => "/home/$user/public_html/workspace/$domain",
                                'doubledecode' => 1
                            )
                        );
                        if (!isset($clone->cpanelresult->error)) {
                            $this->configureide("http://ide-$user.phplake.com/components/project/controller.php", array(
                                'action' => 'create',
                                'project_name' => $domain,
                                'project_path' => $domain,
                                'key' => 'phplake786'
                            ));
                            return 'success';
                        }
                        else {
                            return $clone->cpanelresult->error;
                        }
                    }
                    else {
                        return $removedocroot->cpanelresult->error;
                    }
                }
                else {
                    return $setdbuserprivileges->cpanelresult->error;
                }
            }
            else {
                return $createdb->cpanelresult->error;
            }
        }
        else {
            return $addaddondomain->cpanelresult->error;
        }
    }
    
    /**
     * WHM Common universal Curl request
     */
    private function whmrequest($query, $post = '')
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        if (!empty($post)) { // This if logic is the adjustment of IDE username password change or any curl post request and not related with WHM api call
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $post);
        }
        else {
            $header[0] = "Authorization: WHM $this->whmusername:" . preg_replace("'(\r|\n)'", "", $this->hash);
            curl_setopt($curl,CURLOPT_HTTPHEADER, $header);
        }
        curl_setopt($curl, CURLOPT_URL, $query);
        $result = curl_exec($curl);
        curl_close($curl);
        return json_decode($result);
    }
}