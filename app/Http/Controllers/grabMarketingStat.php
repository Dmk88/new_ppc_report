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
    public $input=[];
    public $inputArbitary=[];


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


    public function getReportAdwords($customer_id, $compaign_id, $during, $arbitary){
        ini_set("max_execution_time", 0);
        $OAuth2TokenBuilder = new OAuth2TokenBuilder();
        $configurationLoader = new ConfigurationLoader();
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

        // Create report query
        $buildReportQuery = 'SELECT CampaignId, '
            . 'Clicks, Impressions, Cost FROM CRITERIA_PERFORMANCE_REPORT '
            . 'WHERE CampaignId IN [%s] DURING %s';
        $buildReportQuery=sprintf($buildReportQuery,$compaign_id,$during);

        $stringReport=self::getReport($session, $buildReportQuery, DownloadFormat::CSV);
        $arrayReport=explode(',',$stringReport);
        $Click=$arrayReport[count($arrayReport)-3];
        $Impressions=$arrayReport[count($arrayReport)-2];
        $Cost=$arrayReport[count($arrayReport)-1];
        //Cut of zeros
        if($Cost!=0){
            $Cost=substr($Cost,0,-5);
        }
        //Switch input result to current or arbitary month

        if($arbitary!=0) {
            array_push($this->inputArbitary, array($Click, $Impressions, $Cost));
        }
        else {
            array_push($this->input, array($Click, $Impressions, $Cost));
        }

        echo " successful! <br>";

    }

    public function grab()
    {
        ini_set("max_execution_time", 0);
//        var_dump(ini_get("max_execution_time"));
//        die('Error');
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
        $spreadsheetId = '1Q4j81zbUXfi2trsiZORF0fGgx_cSFKN5uokJIZOwP0I';
        //get ranges of input,
        $ranges=Sheet::getOfSheet($service, $spreadsheetId, 'Raw data!A3:G4');

        $rangeInputCurrent = 'Raw data!'.$ranges[0][1].':'.$ranges[0][2];
        $rangeInputLast = 'Raw data!'.$ranges[0][3].':'.$ranges[0][4];
        $rangeInputArbitary ='Raw data!'.$ranges[0][5].':'.$ranges[0][6];
        $rangeSource ='Raw data!'.$ranges[1][1].':'.$ranges[1][2];
        $rangeNameCurrent='Raw data!C5:E5';
        $rangeNameLast='Raw data!F5:H5';
        $rangeDateUpdated='Raw data!B4';
        $duringCurrent=date('Ym').'01, '.date('Ymd'); // This month
        if (isset($ranges[1][5]) && !empty($ranges[1][5]) && isset($ranges[1][6]) && !empty($ranges[1][6])) {
            $duringArbitary = $ranges[1][5] . ', ' . $ranges[1][6]; // Arbitary month
        }

        $source = Sheet::getOfSheet($service, $spreadsheetId, $rangeSource);
        echo "Processing started!<br>";
        foreach ($source as $row){
            if(isset($row) && !empty($row)) {
                if ($row[0]=="adwords") {
                    //get adwords account
                    echo "Adwords AccountID=".$row[1]." - ";
                    $customer_id = str_replace('-', '', $row[1]);
                    if (isset($row[2]) && !empty($row[2])) {
                        $compaign_id = $row[2];
                        if (count($row) > 2) {
                            for ($i = 3; $i <= count($row); $i++) {
                                if (isset($row[$i]) && !empty($row[$i])) {
                                    $compaign_id = $compaign_id . ', ' . $row[$i];
                                }
                                else break;
                            }
                        }
                        if (isset($ranges[1][5]) && !empty($ranges[1][5]) && isset($ranges[1][6]) && !empty($ranges[1][6])) {
                            self::getReportAdwords($customer_id, $compaign_id, $duringArbitary, 1);
                        }
                        else {
                            self::getReportAdwords($customer_id, $compaign_id, $duringCurrent, 0);
                        }
                    }
                    else {
                        if (isset($ranges[1][5]) && !empty($ranges[1][5]) && isset($ranges[1][6]) && !empty($ranges[1][6])) {
                            array_push($this->inputArbitary, array(0, 0, 0));
                        }
                        else {
                            array_push($this->input, array(0, 0, 0));
                        }

                        echo " empty CompaignID! <br>";
                    }
                }
                elseif ($row[0]=="bing") {
                    //get bing account
                    echo "Bing AccountID=".$row[1]." - ";
                    if (isset($ranges[1][5]) && !empty($ranges[1][5]) && isset($ranges[1][6]) && !empty($ranges[1][6])) {
                        array_push($this->inputArbitary, array(0, 0, 0));
                    }
                    else {
                        array_push($this->input, array(0, 0, 0));
                    }
                    echo " not processed! <br>";
                }
                elseif ($row[0]=="linkedin"){
                    //get linkedin account
                    echo "Linkedin AccountID=".$row[1]." - ";
                    if (isset($ranges[1][5]) && !empty($ranges[1][5]) && isset($ranges[1][6]) && !empty($ranges[1][6])) {
                        array_push($this->inputArbitary, array(0, 0, 0));
                    }
                    else {
                        array_push($this->input, array(0, 0, 0));
                    }
                    echo " not processed! <br>";
                }
            }
            else
                //get empty row
                if (isset($ranges[1][5]) && !empty($ranges[1][5]) && isset($ranges[1][6]) && !empty($ranges[1][6])) {
                    array_push($this->inputArbitary, array('', '', ''));
                }
                else {
                    array_push($this->input, array('', '', ''));
                }
        }
        echo "Processing complete!<br>";


        //Set result to Google Sheets


        if (isset($ranges[1][5]) && !empty($ranges[1][5]) && isset($ranges[1][6]) && !empty($ranges[1][6])) {
            //The arbitary month
            If (!Sheet::setToSheet($service, $spreadsheetId, $rangeInputArbitary, $this->inputArbitary)){
                echo "Error update range to Sheet!";
            }
            else {
                echo "Statistics for the arbitary month have been updated!<br>";
            }
        }
        else {
            //The current month
            If (!Sheet::setToSheet($service, $spreadsheetId, $rangeInputCurrent, $this->input)){
                echo "Error update range to Sheet!";
            }
            else{
                echo "Statistics for the current month have been updated!<br>";
                Sheet::setToSheet($service, $spreadsheetId, $rangeDateUpdated, array(array(date('r'))));
                Sheet::setToSheet($service, $spreadsheetId, $rangeNameCurrent, array(array(date('F'))));


            };
            //The last month
            If (date('t')==date('d')){
                If (!Sheet::setToSheet($service, $spreadsheetId, $rangeInputLast, $this->input)){
                    echo "Error update range to Sheet!";
                }
                else{
                    echo "Statistics for the last month have been updated!<br>";
                    Sheet::setToSheet($service, $spreadsheetId, $rangeNameLast, array(array(date('F'))));
                };
            }

        }
        echo "<a href='https://docs.google.com/spreadsheets/d/1Q4j81zbUXfi2trsiZORF0fGgx_cSFKN5uokJIZOwP0I/edit#gid=1311329247'>View result</a><br>";
    }

    //**************LINKEDIN***********
    public function grabLinkedin()
    {
        // *************Login***********
        $linkedIn = new \Happyr\LinkedIn\LinkedIn('77dtkigf97865t', 'anVsVZjlXTj6Hcr6');
        $linkedIn->setHttpClient(new \Http\Adapter\Guzzle6\Client());
        $linkedIn->setHttpMessageFactory(new \Http\Message\MessageFactory\GuzzleMessageFactory());
        if ($linkedIn->isAuthenticated()) {
            //we know that the user is authenticated now. Start query the API
            $user = $linkedIn->get('v1/people/~:(firstName,lastName)');
            $compaign=$linkedIn->get('v2/adCampaignsV2/{500610594}');
            echo "Welcome " . $user['firstName'];
            echo "<pre>";
            var_dump($compaign);
            echo "</pre>";

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

}
