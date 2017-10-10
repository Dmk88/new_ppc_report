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

    const PAGE_LIMIT = 500;

    public static function getReport(AdWordsSession $session, $reportQuery, $reportFormat) {

        // Download report as a string.
        $reportDownloader = new ReportDownloader($session);
        // Optional: If you need to adjust report settings just for this one
        // request, you can create and supply the settings override here. Otherwise,
        // default values from the configuration file (adsapi_php.ini) are used.
        $reportSettingsOverride = (new ReportSettingsBuilder())
            ->includeZeroImpressions(false)
            ->build();
        $reportDownloadResult = $reportDownloader->downloadReportWithAwql(
            $reportQuery, $reportFormat, $reportSettingsOverride);
        $stringResult= $reportDownloadResult->getAsString();
        return $stringResult;
    }


    public function grab()
    {
        $OAuth2TokenBuilder = new OAuth2TokenBuilder();
        $configurationLoader = new ConfigurationLoader();
        $customer_id = "6200435280";
        $config = '[ADWORDS]
developerToken = "' . $_ENV['ADWORDS_DEVELOPER_TOKEN'] . '"
clientCustomerId = "' . $customer_id . '"
[OAUTH2]
 clientId = "' . $_ENV['ADWORDS_CLIENT_ID'] . '"
 clientSecret = "' . $_ENV['ADWORDS_CLIENT_SECRET'] . '"
 refreshToken = "' . $_ENV['ADWORDS_REFRESH_TOKEN'] . '"';

        $config_cool = sprintf($_ENV['ADWORDS_CONFIG'], $_ENV['ADWORDS_DEVELOPER_TOKEN'], $customer_id, $_ENV['ADWORDS_CLIENT_ID'], $_ENV['ADWORDS_CLIENT_SECRET'], $_ENV['ADWORDS_REFRESH_TOKEN']);

        $oAuth2Credential = ($OAuth2TokenBuilder->from($configurationLoader->fromString($config)))->build();

        $session = (new AdWordsSessionBuilder())->from($configurationLoader->fromString($config))->withOAuth2Credential($oAuth2Credential)->build();
        // Create report query to get the data for today.
        $today=date('Ymd');
        $reportQuery = 'SELECT CampaignId, '
            . 'Impressions, Clicks, Cost FROM CRITERIA_PERFORMANCE_REPORT '
            . 'WHERE Status IN [ENABLED, PAUSED] AND CampaignId IN [886858006, 886858015, 886858009, 886857994] DURING 20171001, '.$today;
        $buildReportQuery = 'SELECT CampaignId, '
            . 'Impressions, Clicks, Cost FROM CRITERIA_PERFORMANCE_REPORT '
            . 'WHERE Status IN [ENABLED, PAUSED] AND CampaignId IN [%s] DURING %s';

        $stringReport=self::getReport($session, $reportQuery, DownloadFormat::CSV);
        $arrayReport=explode(',',$stringReport);
        $Impressions=$arrayReport[count($arrayReport)-3];
        $Click=$arrayReport[count($arrayReport)-2];
        $Cost=$arrayReport[count($arrayReport)-1];
        echo "<pre>";
        print "Report was downloaded and printed below:\n";
        print $stringReport;
        printf("<br> Impressions = %s, Click = %s, Cost = %s", $Impressions,$Click,$Cost);
        echo "</pre>";




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
        //get ranges of input and source
        $ranges=Sheet::getOfSheet($service, $spreadsheetId, 'Raw data!A1:C2');
        $rangeInput = 'Raw data!'.$ranges[0][1].':'.$ranges[0][2];
        $rangeSource ='Raw data!'.$ranges[1][1].':'.$ranges[1][2];

        $source = Sheet::getOfSheet($service, $spreadsheetId, $rangeSource);
        foreach ($source as $row){
            if(isset($row) && !empty($row)) {
                if ($row[0]=="adwords") {
                    $customer_id = str_replace('-', '', $row[1]);
                    if (isset($row[2]) && !empty($row[2])) {
                        $compaign_id = $row[2];
                        if (count($row) > 2) {
                            for ($i = 2; $i <= count($row); $i++) {
                                //$compaign_id = $compaign_id . ', ' . $row[$i];
                            }
                        }
                        printf("CustomerID=%s, CompaignID=%s <br>", $customer_id, $compaign_id);
                    }
                    else
                    printf ("CustomerID=%s, Empty CompaignID<br>", $customer_id);
                }
                //print($row[0] . "\n");
            }
        }

//        echo "<pre>";
//        print_r($source);
//        echo "</pre>";

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
