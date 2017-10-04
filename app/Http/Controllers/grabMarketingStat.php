<?php

namespace App\Http\Controllers;


use Google;
use Google\AdsApi\AdWords\AdWordsServices;
use Google\AdsApi\AdWords\AdWordsSession;
use Google\AdsApi\AdWords\AdWordsSessionBuilder;
use Google\AdsApi\AdWords\v201708\cm\CampaignService;
use Google\AdsApi\AdWords\v201708\cm\OrderBy;
use Google\AdsApi\AdWords\v201708\cm\Paging;
use Google\AdsApi\AdWords\v201708\cm\Selector;
use Google\AdsApi\AdWords\v201708\cm\SortOrder;
use Google\AdsApi\Common\OAuth2TokenBuilder;
use Google_Client;
use Google_Service_Drive;
use Google_Service_Sheets;
use App\Api\GoogleClient;
use Happyr\LinkedIn;




class grabMarketingStat extends Controller
{
    const PAGE_LIMIT = 500;

    public function getClient()
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

    public static function runExample(
        AdWordsServices $adWordsServices,
        AdWordsSession $session
    )
    {
        $campaignService = $adWordsServices->get($session, CampaignService::class);
        // Create selector.
        $selector = new Selector();
        $selector->setFields(['Id', 'Name']);
        $selector->setOrdering([new OrderBy('Name', SortOrder::ASCENDING)]);
        $selector->setPaging(new Paging(0, self::PAGE_LIMIT));
        $totalNumEntries = 0;
        do {
            // Make the get request.
            $page = $campaignService->get($selector);
            // Display results.
            if ($page->getEntries() !== null) {
                $totalNumEntries = $page->getTotalNumEntries();
                foreach ($page->getEntries() as $campaign) {
                    printf("Campaign with ID %d and name '%s' was found.\n", $campaign->getId(), $campaign->getName());
                }
            }
            // Advance the paging index.
            $selector->getPaging()->setStartIndex($selector->getPaging()->getStartIndex() + self::PAGE_LIMIT);
        } while ($selector->getPaging()->getStartIndex() < $totalNumEntries);
        printf("Number of results found: %d\n", $totalNumEntries);
    }

    public static function main()
    {
        // Generate a refreshable OAuth2 credential for authentication.
        $oAuth2Credential = (new OAuth2TokenBuilder())->fromFile()->build();
        // Construct an API session configured from a properties file and the OAuth2
        // credentials above.
        $session = (new AdWordsSessionBuilder())->fromFile()->withOAuth2Credential($oAuth2Credential)->build();
        self::runExample(new AdWordsServices(), $session);
    }

    public function grab()
    {
        ini_set('display_errors', 1);
        error_reporting(E_ALL);
        define('APPLICATION_NAME', 'Google API');
        define('CREDENTIALS_PATH', app_path() . '/Api/google-drive.json');
        define('CLIENT_SECRET_PATH', app_path() . '/Api/client_secret.json');
        define('SCOPES', implode(' ', array(
            Google_Service_Drive::DRIVE,
            Google_Service_Drive::DRIVE_APPDATA,
            Google_Service_Drive::DRIVE_FILE,
            Google_Service_Drive::DRIVE_METADATA,
            Google_Service_Drive::DRIVE_METADATA_READONLY,
            Google_Service_Drive::DRIVE_PHOTOS_READONLY,
            Google_Service_Drive::DRIVE_READONLY,
            Google_Service_Drive::DRIVE_SCRIPTS,
        )));

        // Generate a refreshable OAuth2 credential for authentication.
        $oAuth2Credential = (new OAuth2TokenBuilder())->fromFile(app_path() . $_ENV['ADSAPI'])->build();
        // Construct an API session configured from a properties file and the OAuth2
        // credentials above.
        // dd($oAuth2Credential);

        $session = (new AdWordsSessionBuilder())->fromFile(app_path() . $_ENV['ADSAPI'])->withOAuth2Credential($oAuth2Credential)->build();

        $adWordsServices = new AdWordsServices();

        echo "<pre>";
        var_dump($adWordsServices);
        var_dump($session);
        dd($adWordsServices);

        $campaignService = $adWordsServices->get($session, CampaignService::class);
        var_dump($campaignService);
        dd($campaignService);
        // Create selector.
        $selector = new Selector();
        $selector->setFields(['Id', 'Name']);
        $selector->setOrdering([new OrderBy('Name', SortOrder::ASCENDING)]);
        $selector->setPaging(new Paging(0, self::PAGE_LIMIT));
        $totalNumEntries = 0;
        do {
            // Make the get request.
            $page = $campaignService->get($selector);
            // Display results.
            if ($page->getEntries() !== null) {
                $totalNumEntries = $page->getTotalNumEntries();
                foreach ($page->getEntries() as $campaign) {
                    printf("Campaign with ID %d and name '%s' was found.\n", $campaign->getId(), $campaign->getName());
                }
            }
            // Advance the paging index.
            $selector->getPaging()->setStartIndex($selector->getPaging()->getStartIndex() + self::PAGE_LIMIT);
        } while ($selector->getPaging()->getStartIndex() < $totalNumEntries);
        printf("Number of results found: %d\n", $totalNumEntries);


//         $client = $this->getClient();
//         $this->main();
//         dd('ddd');131-205-3573
    }

    public function grabLinkedin()
    {
        $linkedIn=new \Happyr\LinkedIn\LinkedIn('78ruqha4he57aa', 'Ai3zGFNAV6cpYKxO');
        dd($linkedIn);
        if ($linkedIn->isAuthenticated()) {
            //we know that the user is authenticated now. Start query the API
            $user=$linkedIn->get('v1/people/~:(firstName,lastName)');
            echo "Welcome ".$user['firstName'];

            exit();
        } elseif ($linkedIn->hasError()) {
            echo "User canceled the login.";
            exit();
        }

    }

    public function grabBing()
    {
    }
}
