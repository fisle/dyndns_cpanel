<?php

namespace DNSUpdater;

class Dyndns {

    /*
     * Define basic settings
     */
    private $cpanelConfig = array(
        'url'        => 'https://your-cpanel.com:2083', // cPanel address
        'user'       => 'cpanel-username',                      // cPanel username
        'pass'       => 'cpanel-password',                 // cPanel password
        'basedomain' => 'basedomain.com',                  // Base domain of dyndns record
        'dynamic'    => 'sub.basedomain.com'              // Complete domain name for dynamic address
    );

    /*
     * These are the username and password that
     * updating script users, aka authentication
     */
    private $auth = array(
        'user'  => 'youruser',
        'token' => 'yourtoken'
    );

    /*
     * Authenticate user, if success;
     * update the DNS entry
     */
    public function __construct() {
        if (!isset($_SERVER['PHP_AUTH_USER']) && !isset($_SERVER['PHP_AUTH_PW'])){
            header('WWW-Authenticate: Basic realm="please check yoself"');
            header('HTTP/1.0 401 Unauthorized');
            echo 'No.';
            die();
        }
        $user = filter_var($_SERVER['PHP_AUTH_USER'], FILTER_SANITIZE_STRING);  // Rule number 1:
        $token = filter_var($_SERVER['PHP_AUTH_PW'],   FILTER_SANITIZE_STRING);    // Every input is hostile
        // Development values
        #$user = $this->auth['user'];
        #$token = $this->auth['token'];

        $auth = $this->authenticate($user, $token);
        if ($auth) {
            if($_SERVER['REMOTE_ADDR'] === '127.0.0.1') {
                $this->curl = \curl_init();
                $curlOpts = array(CURLOPT_RETURNTRANSFER => true);
                curl_setopt_array($this->curl, $curlOpts);
                curl_setopt($this->curl, CURLOPT_URL, 'http://icanhazip.com');
                $result = curl_exec($this->curl);
                $ipAddr = $result;
            } else {
                $ipAddr = $_SERVER['REMOTE_ADDR'];
            }
            $result = $this->updateEntry($ipAddr);
        } else {
            die('Authentication failed');
        }
    }

    /*
     * Function to authenticate the user
     * Very simple function, because we only have
     * one user defined that can access.
     *
     * @param string $user
     * @param string $token
     * @return bool
     */
    protected function authenticate($user, $token) {
        if ($user !== $this->auth['user'] && $token !== $this->auth['token']) {
            return false;
        } else {
            return true;
        }
    }

    /*
     * Main logic here.
     * Get the current record,
     * update only if it is different
     * than our current IP
     * @param string $ipAddr - New IP address
     * @return string $result - Result from cPanel, should match $ipAddr
     */
    protected function updateEntry($ipAddr) {

        // Define options to send to cPanel
        $params = array(
            'cpanel_jsonapi_module' => 'ZoneEdit',
            'cpanel_jsonapi_func'   => 'fetchzone_records',
            'domain'                => $this->cpanelConfig['basedomain'],
            'customonly'            => 1
        );
        // Call the API
        $result = $this->callCpanel($params);

        // Get the results and pick only the matching entry
        foreach ($result as $line) {
            if ($line['type'] == 'A') {
                if (strstr($line['name'], $this->cpanelConfig['dynamic'])) {
                    $record = $line;
                }
            }
        }

        // If the current record is the current IP,
        // we don't have to update anything
        if ($record['address'] !== $ipAddr) {

            // Define options to send to cPanel
            $updateParams = array(
                'cpanel_jsonapi_module' => 'ZoneEdit',
                'cpanel_jsonapi_func'   => 'edit_zone_record',
                'domain'                => $this->cpanelConfig['basedomain'],
                'Line'                  => $record['Line'],
                'type'                  => $record['type'],
                'address'               => $ipAddr
            );
            // Call the API
            $result = $this->callCpanel($updateParams);
        } else {
            die();
        }
        return $result;
    }

    /*
     * Call the cPanel API using cURL
     * Add Headers with Authentication data.
     * @param array $params 
     * @return array $result
     */
    protected function callCpanel($params) {
        $this->curl = \curl_init();
        $curlOpts = array(
            CURLOPT_HTTPHEADER => array( 'Authorization: Basic ' . base64_encode($this->cpanelConfig['user'] . ':' . $this->cpanelConfig['pass'])),
            CURLOPT_SSL_VERIFYPEER => false, // Self signed / expired certs
            CURLOPT_SSL_VERIFYHOST => false, // Certs not matching the hostname
            CURLOPT_RETURNTRANSFER => true,  // Return contents
        );
        curl_setopt_array($this->curl, $curlOpts);
        curl_setopt($this->curl, CURLOPT_URL, $this->cpanelConfig['url'] . '/json-api/cpanel?' . http_build_query($params));

        // Get the result and close connection
        $result = curl_exec($this->curl);
        curl_close($this->curl);

        // Decode results into array
        $jsonResult = json_decode($result, true);
        $result = $jsonResult['cpanelresult']['data'];
        return $result;
    }
}

$data = new Dyndns;
