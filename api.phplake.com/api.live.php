<?php
switch($_GET['action']) {
    case 'install':
        $user      = $_GET['user'];
        $dest      = $_GET['destination'];
        $source    = $_GET['source'];
        $arr       = explode('/', $source);
        $filename  = $arr[count($arr) - 1];
        $tmpfolder = $_GET['tmpfolder'];
        $workspace = '/home/'.$user;
        $command = "php /home/phplake/phplakecodebase $user $dest $source $filename $tmpfolder $workspace";
        exec($command . ' 2>&1', $output, $status);
        if (isset($_GET['project']) && $status == 0) {
            $project = $_GET['project'];
            $ide     = "http://ide-" . $user . ".phplake.com";
            $idepass = $_GET['idepass'];
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
        print json_encode($result);
        break;
    case 'test':
    	$cmd = '/usr/local/bin/drush status';
    	exec($cmd .' 2>&1', $output, $status);
    	print_r($output);
    break;
}
?>