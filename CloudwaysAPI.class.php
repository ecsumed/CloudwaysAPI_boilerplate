<?php
class CloudwaysAPIClient {

    const API_URL = "https://api.cloudways.com/api/v1";

    var $auth_key;
    var $auth_email;

    var $accessToken;

    function CloudwaysAPIClient($key, $email) {
        $this->auth_key = $key;
        $this->auth_email = $email;

        $this->prepare_access_token();
    }
   
    function get_servers() { 
        $response = $this->request('GET', '/server');

        if ($response->status === true) {
            return $response->servers;
        }
        return false;
    }

    function get_cron_list($server_id, $app_id) { 
        $data = ['server_id' => $server_id,
                 'app_id' => $app_id
                ];

        $qry_str = "?";
        foreach ($data as $name => $value) {
            $qry_str .= urlencode($name) . '=' . urlencode($value) . '&';
        }

        return $this->request('GET', '/app/manage/cronList'. $qry_str);
    }

    function service_varnish($server_id, $action) { 
        $actions = ['enable', 'disable', 'purge'];

        if (in_array($action, $actions)) {
            $data = ['server_id' => $server_id,
                     'action' => $action 
                    ];

            return $this->request('POST', '/service/varnish', $data);
        } 
        return false;
    }

    function prepare_access_token() { 
        $data = ['email' => $this->auth_email,
                 'api_key' => $this->auth_key
                ];
        $response = $this->request('POST', '/oauth/access_token', $data);

        $this->accessToken = $response->access_token;
    }

    function request($method, $url, $post = []) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL, self::API_URL . $url);
           
        do {
            if ($this->accessToken) {
               curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $this->accessToken]);
            }
     
            //Set Post Parameters
            $encoded = '';
            if (count($post)) {
                foreach ($post as $name => $value) {
                    $encoded .= urlencode($name) . '=' . urlencode($value) . '&';
                }
                $encoded = substr($encoded, 0, strlen($encoded) - 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $encoded);
                curl_setopt($ch, CURLOPT_POST, 1);
            }
            $output = curl_exec($ch);
            $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            
            # ACCESS TOKEN HAS EXPIRED, so regenerate and retry
            if ($httpcode == '401') {
                $this->prepare_access_token();    
            }
        } while ($httpcode == '401'); 

        if ($httpcode != '200') {
            die('An error occurred code: ' . $httpcode . ' output: ' . substr($output, 0, 10000));
        }
        curl_close($ch);
        return json_decode($output);
        }
    } 
?>
