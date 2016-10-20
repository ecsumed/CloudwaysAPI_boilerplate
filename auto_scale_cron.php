<?php 
include 'CloudwaysAPI.class.php';

$api_key = '';
$email = '';

$CHECK_AVG = '1'; # 1, 5, or 15 minutes
$MAX_THRESHOLD = '.9'; # load threshold to reach before upgrading .9 = 90% 
$MIN_THRESHOLD = '.1'; # load threshold to reach before downgrading .1 = 10% 

$LOADS = array(
            '1'  => 0,
            '5' => 1,
            '15' => 2,
         );

$load = sys_getloadavg()[$LOADS[$CHECK_AVG]];

function my_log($msg) {
    echo $msg.PHP_EOL;
    file_put_contents('/var/log/scale_server.txt',
                      $msg.PHP_EOL,
                      FILE_APPEND | LOCK_EX); 
}

function num_cpus() {
  $numCpus = 1;
  if (is_file('/proc/cpuinfo')) {
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

function new_instance($cur_instance, $instances, $direction = 1) {
    // direction = 1 for next (larger instance)
    // direction = -1 for previous (smaller instance)
    $cur_index = array_search($cur_instance, $instances);
    $length = count($instances);

    if (array_key_exists($cur_index + $direction, $instances)) {
        return $instances[$cur_index + $direction];
    }
    else {
        return false;
    }
};

$cw_api = new CloudwaysAPIClient($api_key, $email);

$servers = $cw_api->get_servers();

foreach($servers as $server) {
    if ($server->server_fqdn == gethostname()) {
        $available_instances = $cw_api->get_server_sizes()->{$server->cloud};

        if(real_load() > $MAX_THRESHOLD) {
            my_log(date("Y-m-d H:i:s"));
            my_log("Server: ".$server->label);
            my_log("Cloud Provider: ".$server->cloud);
            my_log("Available server sizes: ".implode(',', $available_instances));
            my_log("Current size: ".$server->instance_type);
            my_log("Cores: ".num_cpus());
            my_log("Load: ".$load);
            my_log("Real Load: ".real_load());
    
            // Upscale to new size if it exists
            $scale_to = new_instance($server->instance_type, $available_instances);
            if ($scale_to) {
                my_log("Will upscale to ".$scale_to);  
                $cw_api->scale_server($server->id, $scale_to);
            }
            else {
                my_log("Current instance is the largest available size");
            }
        }
        if(real_load() < $MIN_THRESHOLD) {
            my_log(date("Y-m-d H:i:s"));
            my_log("Server: ".$server->label);
            my_log("Cloud Provider: ".$server->cloud);
            my_log("Available server sizes: ".implode(',', $available_instances));
            my_log("Current size: ".$server->instance_type);
            my_log("Cores: ".num_cpus());
            my_log("Load: ".$load);
            my_log("Real Load: ".real_load());
    
            // Upscale to new size if it exists
            $scale_to = new_instance($server->instance_type, $available_instances, -1);
            if ($scale_to) {
                my_log("Will downscale to ".$scale_to);  
                $cw_api->scale_server($server->id, $scale_to);
            }
            else {
                my_log("Current instance is the smallest available size");
            }
        }
    }
}
?>
