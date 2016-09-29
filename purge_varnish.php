<?php 

include 'CloudwaysAPI.class.php';

$api_key = '';
$email = '';

$cw_api = new CloudwaysAPIClient($api_key, $email);

$servers = $cw_api->get_servers();

foreach($servers as $server) {

    foreach($server->apps as $app){
        if ($app->id == '159495') {
            var_dump($cw_api->get_cron_list($server->id, $app->id));
        }
    }
}

?>

