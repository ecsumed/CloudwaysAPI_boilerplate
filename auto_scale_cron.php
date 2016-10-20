<?php 

include 'CloudwaysAPI.class.php';

$api_key = '0DPXMhpyXU6tBZ338pbxayMU4dWo7C';
$email = 'fahad.saleh@cloudways.com';

$CHECK_AVG = '5'; # 1, 5, or 15 minutes
$THRESHOLD = '.8'; # load threshold .8 = 80% 

$LOADS = array(
        '1'  => 0,
        '5' => 1,
        '15' => 2,
         );

$load = sys_getloadavg()[$LOADS[$CHECK_AVG]];

function num_cpus() {
  $numCpus = 1;
  if (is_file('/proc/cpuinfo'))
  {
    $cpuinfo = file_get_contents('/proc/cpuinfo');
    preg_match_all('/^processor/m', $cpuinfo, $matches);
    $numCpus = count($matches[0]);
  }
  return $numCpus;
}

function real_load() {
    global $load;
    return $load / num_cpus();
};

$cw_api = new CloudwaysAPIClient($api_key, $email);

$servers = $cw_api->get_servers();

foreach($servers as $server) {
    if ($server->server_fqdn == gethostname()) {
        echo "Server: ".$server->label.PHP_EOL;
        echo "Cores: ".num_cpus().PHP_EOL;
        echo "Load: ".$load.PHP_EOL;
        echo "Real Load: ".real_load().PHP_EOL;
    
        if(real_load() > $THRESHOLD) {
            # Upscale
            echo "Upscale".PHP_EOL;
        }
    }
}

?>

