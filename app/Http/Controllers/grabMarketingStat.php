<?php

namespace App\Http\Controllers;

use App\GoogleSheet;
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
use Google\AdsApi\Common\ConfigurationLoader;

use Google\AdsApi\AdWords\Reporting\v201708\DownloadFormat;
use Google\AdsApi\AdWords\Reporting\v201708\ReportDefinition;
use Google\AdsApi\AdWords\Reporting\v201708\ReportDefinitionDateRangeType;
use Google\AdsApi\AdWords\Reporting\v201708\ReportDownloader;
use Google\AdsApi\AdWords\ReportSettingsBuilder;
use Google\AdsApi\AdWords\v201708\cm\Predicate;
use Google\AdsApi\AdWords\v201708\cm\PredicateOperator;
use Google\AdsApi\AdWords\v201708\cm\ReportDefinitionReportType;
use Happyr\LinkedIn;
use Google_Service_Sheets;
use Google_Service_Sheets_ClearValuesRequest;
use Google_Service_Sheets_ValueRange;
use Carbon\Carbon;
use App\Api\GoogleClient;
use Sheets;
use \App\GoogleSheet as Sheet;



class grabMarketingStat extends Controller
{
    protected $GOOGLE_SHEETS_ID = '10G1ev9mY2Sa0rhjmNAE751nRa3ryluFO_Y6KHgji2A0';

    protected $GOOGLE_SHEETS_RANGE = 'C4:E';

    protected $GOOGLE_SHEETS_DEFAULT_ROW = 1;

    protected $GOOGLE_SHEETS_CURRENT_ROW = 1;

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
        // Create AWQL query.
        $query = 'SELECT Name, Status ORDER BY Name';
        // Create paging controls.
        $totalNumEntries = 0;
        $offset = 0;
        do {
            $pageQuery = sprintf('%s LIMIT %d,%d', $query, $offset, self::PAGE_LIMIT);
            // Make the query request.
            $page = $campaignService->query($pageQuery);
            // Display results from the query.
            if ($page->getEntries() !== null) {
                $totalNumEntries = $page->getTotalNumEntries();
                foreach ($page->getEntries() as $campaign) {
                    printf("Campaign with ID %d and name '%s' was found.\n", $campaign->getCost(), $campaign->getName());
                }
            }
            // Advance the paging offset.
            $offset += self::PAGE_LIMIT;
        } while ($offset < $totalNumEntries);
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

    public static function runExample2(AdWordsSession $session, $filePath)
    {
        // Create selector.
        $selector = new Selector();
        $selector->setFields(['Impressions', 'Clicks', 'Cost']);

        // Use a predicate to filter out paused criteria (this is optional).
        $selector->setPredicates([
            new Predicate('Status', PredicateOperator::NOT_IN, ['PAUSED'])]);
        // Create report definition.
        $reportDefinition = new ReportDefinition();

        $reportDefinition->setSelector($selector);
        $reportDefinition->setReportName(
            'Criteria performance report #' . uniqid());
        $reportDefinition->setDateRangeType(
            ReportDefinitionDateRangeType::LAST_MONTH);

        $reportDefinition->setReportType(
            ReportDefinitionReportType::CRITERIA_PERFORMANCE_REPORT);
        $reportDefinition->setDownloadFormat(DownloadFormat::XML);

        // Download report.
        $reportDownloader = new ReportDownloader($session);
        //dd($reportDownloader);
        // Optional: If you need to adjust report settings just for this one
        // request, you can create and supply the settings overrid  e here. Otherwise,
        // default values from the configuration file (adsapi_php.ini) are used.
        $reportSettingsOverride = (new ReportSettingsBuilder())
            ->includeZeroImpressions(false)
            ->build();

//        echo "<pre>";
//        var_dump($reportDefinition);
//        echo '<br>';
//        var_dump($reportSettingsOverride);
//        echo '<br>';
//        var_dump($reportDownloader);
//        echo "</pre>";
//        dd($reportDefinition);
        $reportDownloadResult = $reportDownloader->downloadReport(
            $reportDefinition, $reportSettingsOverride);
        // dd($reportDownloadResult);
        $reportDownloadResult->saveToFile($filePath);


        printf("Report with name '%s' was downloaded to '%s'.\n",
            $reportDefinition->getReportName(), $filePath);
    }

    public static function runExample3(AdWordsSession $session, $filePath) {
        // Create selector.
        $selector = new Selector();
        $selector->setFields(['CampaignId', 'AdGroupId', 'Id', 'Criteria',
            'CriteriaType', 'Impressions', 'Clicks', 'Cost']);

        // Use a predicate to filter out paused criteria (this is optional).
        $selector->setPredicates([
            new Predicate('Status', PredicateOperator::NOT_IN, ['PAUSED'])]);

        // Create report definition.
        $reportDefinition = new ReportDefinition();
        $reportDefinition->setSelector($selector);
        $reportDefinition->setReportName(
            'Criteria performance report #' . uniqid());
        $reportDefinition->setDateRangeType(
            ReportDefinitionDateRangeType::LAST_7_DAYS);
        $reportDefinition->setReportType(
            ReportDefinitionReportType::CRITERIA_PERFORMANCE_REPORT);
        $reportDefinition->setDownloadFormat(DownloadFormat::CSV);

        // Download report.
        $reportDownloader = new ReportDownloader($session);
        // Optional: If you need to adjust report settings just for this one
        // request, you can create and supply the settings override here. Otherwise,
        // default values from the configuration file (adsapi_php.ini) are used.
        $reportSettingsOverride = (new ReportSettingsBuilder())
            ->includeZeroImpressions(false)
            ->build();
        $reportDownloadResult = $reportDownloader->downloadReport(
            $reportDefinition, $reportSettingsOverride);
        $reportDownloadResult->saveToFile($filePath);
        printf("Report with name '%s' was downloaded to '%s'.\n",
            $reportDefinition->getReportName(), $filePath);
    }


    public function grab()
    {
        $OAuth2TokenBuilder = new OAuth2TokenBuilder();
        $configurationLoader = new ConfigurationLoader();
        $customer_id = "4130241238";
        $config_0 = '[ADWORDS]
developerToken = "' . $_ENV['ADWORDS_DEVELOPER_TOKEN'] . '"
clientCustomerId = "' . $customer_id . '"
[OAUTH2]
 clientId = "' . $_ENV['ADWORDS_CLIENT_ID'] . '"
 clientSecret = "' . $_ENV['ADWORDS_CLIENT_SECRET'] . '"
 refreshToken = "' . $_ENV['ADWORDS_REFRESH_TOKEN'] . '"';
        $config = '[ADWORDS]
developerToken = "%s"
clientCustomerId = "%s"
[OAUTH2]
 clientId = "%s"
 clientSecret = "%s"
 refreshToken = "%s"';
        $config = sprintf($config, $_ENV['ADWORDS_DEVELOPER_TOKEN'], $customer_id, $_ENV['ADWORDS_CLIENT_ID'], $_ENV['ADWORDS_CLIENT_SECRET'], $_ENV['ADWORDS_REFRESH_TOKEN']);

        $oAuth2Credential = ($OAuth2TokenBuilder->from($configurationLoader->fromString($config_0)))->build();
        // Construct an API session configured from a properties file and the OAuth2
        // credentials above.
        // dd($oAuth2Credential);
        $session = (new AdWordsSessionBuilder())->from($configurationLoader->fromString($config_0))->withOAuth2Credential($oAuth2Credential)->build();

        // self::runExample(new AdWordsServices(), $session);
        $filePath = 'criteria-report.csv';

        self::runExample2($session, $filePath);

        exit;

        $adWordsServices = new AdWordsServices();
        $campaignService = $adWordsServices->get($session, CampaignService::class);
        // dd($campaignService);
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


        // $client = $this->getClient();
        // $this->main();
        // // dd('ddd');
    }

    //**************LINKEDIN***********
    public function grabLinkedin()
    {
        // *************Login***********
        $linkedIn = new \Happyr\LinkedIn\LinkedIn('78ruqha4he57aa', 'Ai3zGFNAV6cpYKxO');
        $linkedIn->setHttpClient(new \Http\Adapter\Guzzle6\Client());
        $linkedIn->setHttpMessageFactory(new \Http\Message\MessageFactory\GuzzleMessageFactory());
        if ($linkedIn->isAuthenticated()) {
            //we know that the user is authenticated now. Start query the API
            $user = $linkedIn->get('v1/people/~:(firstName,lastName)');
            echo "Welcome " . $user['firstName'];

            exit();
        } elseif ($linkedIn->hasError()) {
            echo "User canceled the login.";
            exit();
        }

        //if not authenticated
        $url = $linkedIn->getLoginUrl();
        echo "<a href='$url'>Login with LinkedIn</a>";


    }

    public function grabBing()
    {


    }
    //**************Google Sheets***********
    public function googleSheets()
    {
        $default = [
            'APPLICATION_NAME'   => 'Google Sheets API',
            'CREDENTIALS_PATH'   => app_path() . '/ApiSources/google-sheets.json',
            'CLIENT_SECRET_PATH' => app_path() . '/ApiSources/client_secret_sheets.json',
            'SCOPES'             => array(
                Google_Service_Sheets::SPREADSHEETS_READONLY,
            ),
        ];
        $client  = GoogleClient::get_instance($default);
        $service = new Google_Service_Sheets($client->client);
        $spreadsheetId = '10G1ev9mY2Sa0rhjmNAE751nRa3ryluFO_Y6KHgji2A0';

        $rangeGet = 'Raw data!J5:L';
        $rangeSet ='Raw data!C5:E';

        $values = Sheet::getOfSheet($service, $spreadsheetId, $rangeGet);

        echo "<pre>";
        print_r($values);
        echo "</pre>";

         If (!Sheet::setToSheet($service, $spreadsheetId, $rangeSet, $values)){
            echo "Error update range to Sheet";
        };

    }
}
