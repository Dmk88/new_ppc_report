<?php

namespace App\Api;

use Google_Client;
use Google_Service_Analytics;
use Google_Service_Drive;

class GoogleClient
{
    public $client;
    
    static private $_ins = null;
    
    static public function get_instance($params = [])
    {
        if (self::$_ins instanceof self) {
            return self::$_ins;
        }
        
        return self::$_ins = new self($params);
    }
    
    public function __construct($params = [])
    {
        $default = [
            'APPLICATION_NAME'   => 'Google API',
            'CREDENTIALS_PATH'   => app_path() . '/ApiSources/google-drive.json',
            'CLIENT_SECRET_PATH' => app_path() . '/ApiSources/client_secret.json',
            'SCOPES'             => array(
                Google_Service_Drive::DRIVE,
                Google_Service_Drive::DRIVE_APPDATA,
                Google_Service_Drive::DRIVE_FILE,
                Google_Service_Drive::DRIVE_METADATA,
                Google_Service_Drive::DRIVE_METADATA_READONLY,
                Google_Service_Drive::DRIVE_PHOTOS_READONLY,
                Google_Service_Drive::DRIVE_READONLY,
                Google_Service_Drive::DRIVE_SCRIPTS,
                Google_Service_Analytics::ANALYTICS_READONLY,
                
            ),
        ];
        $params  = array_merge($default, $params);
        define('APPLICATION_NAME', $params['APPLICATION_NAME']);
        define('CREDENTIALS_PATH', $params['CREDENTIALS_PATH']);
        define('CLIENT_SECRET_PATH', $params['CLIENT_SECRET_PATH']);
        define('SCOPES', implode(' ', $params['SCOPES']));
        
        $this->client = $this->getClient();
    }
    
    protected function expandHomeDirectory($path)
    {
        $homeDirectory = getenv('HOME');
        if (empty($homeDirectory)) {
            $homeDirectory = getenv('HOMEDRIVE') . getenv('HOMEPATH');
        }
        
        return str_replace('~', realpath($homeDirectory), $path);
    }
    
    protected function getClient()
    {
        $client = new Google_Client();
        $client->setApplicationName(APPLICATION_NAME);
        $client->setScopes(SCOPES);
        $client->setAuthConfig(CLIENT_SECRET_PATH);
        $client->setAccessType('offline');
        
        // Load previously authorized credentials from a file.
        $credentialsPath = $this->expandHomeDirectory(CREDENTIALS_PATH);
        if (file_exists($credentialsPath)) {
            $accessToken = json_decode(file_get_contents($credentialsPath), true);
        } else {
            // Request authorization from the user.
            $authUrl = $client->createAuthUrl();
            printf("Open the following link in your browser:\n%s\n", $authUrl);
            print 'Enter verification code: ';
            $authCode = trim(fgets(STDIN));
            
            // Exchange authorization code for an access token.
            $accessToken = $client->fetchAccessTokenWithAuthCode($authCode);
            
            // Store the credentials to disk.
            if (!file_exists(dirname($credentialsPath))) {
                mkdir(dirname($credentialsPath), 0700, true);
            }
            file_put_contents($credentialsPath, json_encode($accessToken));
            printf("Credentials saved to %s\n", $credentialsPath);
        }
        $client->setAccessToken($accessToken);
        
        // Refresh the token if it's expired.
        if ($client->isAccessTokenExpired()) {
            $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
            file_put_contents($credentialsPath, json_encode($client->getAccessToken()));
        }
        
        return $client;
    }
}