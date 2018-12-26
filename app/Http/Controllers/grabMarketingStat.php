<?php

namespace App\Http\Controllers;

use App\Api\GoogleClient;
use App\BingReportRequest as BingReport;
use App\GoogleSheet as Sheet;
use Exception;
use Google;
use Google\AdsApi\AdWords\AdWordsSession;
use Google\AdsApi\AdWords\AdWordsSessionBuilder;
use Google\AdsApi\AdWords\Reporting\v201809\DownloadFormat;
use Google\AdsApi\AdWords\Reporting\v201809\ReportDownloader;
use Google\AdsApi\AdWords\ReportSettingsBuilder;
use Google\AdsApi\Common\ConfigurationLoader;
use Google\AdsApi\Common\OAuth2TokenBuilder;
use Google_Service_Analytics;
use Google_Service_AnalyticsReporting;
use Google_Service_AnalyticsReporting_DateRange;
use Google_Service_AnalyticsReporting_Dimension;
use Google_Service_AnalyticsReporting_DimensionFilter;
use Google_Service_AnalyticsReporting_DimensionFilterClause;
use Google_Service_AnalyticsReporting_GetReportsRequest;
use Google_Service_AnalyticsReporting_Metric;
use Google_Service_AnalyticsReporting_ReportRequest;
use Google_Service_Sheets;
use Illuminate\Http\Request;
use ZipArchive;

class grabMarketingStat extends Controller
{

    const PAGE_LIMIT = 500;
    public $input = [];
    public $inputArbitary = [];
    public $inputArbitaryEvents = [];
    public $inputArbitaryUsers = [];
    public $message = '';

    public function get(Request $request)
    {
        try {
            $data = array();
            $bufer = explode('&', $request->getContent());
            $fields_form = ['type-report',
                'date-from',
                'date-to',
            ];

            foreach ($bufer as $fb) {
                foreach ($fields_form as $ff) {
                    if (stripos($fb, $ff) !== false)
                        $data[$ff] = str_replace($ff . '=', '', $fb);
                }
            }
            self::grab('main', $data['type-report'], $data['date-from'], $data['date-to'], $request);
        } catch (Exception $e) {
            dd($e);
            return false;
        }
        return view('ppc.index', ["message" => $this->message]);
    }

    public function index(Request $request)
    {
        return view('ppc.index', ["message" => $this->message]);
    }

    public static function getReportAnalyticsEvents(Google_Service_AnalyticsReporting $analytics, $date_from, $date_to)
    {
        // Replace with your view ID, for example XXXX.
        $VIEW_ID = "169690080";

        // Create the DateRange object.
        $dateRange = new Google_Service_AnalyticsReporting_DateRange();
        $dateRange->setStartDate("$date_from");
        $dateRange->setEndDate("$date_to");

        // Create the Metrics object.
        //$metrics = new Google_Service_AnalyticsReporting_Metric();
        //$metrics->setExpression("ga:totalEvents");

        $metrics2 = new Google_Service_AnalyticsReporting_Metric();
        $metrics2->setExpression("ga:uniqueEvents");


        $dimensions = new Google_Service_AnalyticsReporting_Dimension();
        $dimensions->setName('ga:adwordsCampaignID');


        $dimensions2 = new Google_Service_AnalyticsReporting_Dimension();
        $dimensions2->setName('ga:eventAction');


        // Create the DimensionFilter.
        $dimensionFilter = new Google_Service_AnalyticsReporting_DimensionFilter();
        $dimensionFilter->setDimensionName('ga:eventAction');
        $dimensionFilter->setOperator('EXACT');
        $dimensionFilter->setExpressions(array('More than 30 seconds'));

// Create the DimensionFilterClauses
        $dimensionFilterClause = new Google_Service_AnalyticsReporting_DimensionFilterClause();
        $dimensionFilterClause->setFilters(array($dimensionFilter));

        // Create the ReportRequest object.
        $request = new Google_Service_AnalyticsReporting_ReportRequest();
        $request->setViewId($VIEW_ID);
        $request->setDateRanges($dateRange);
        $request->setMetrics(array( $metrics2));
        $request->setDimensions(array($dimensions, $dimensions2));
        $request->setDimensionFilterClauses(array($dimensionFilterClause));

        $body = new Google_Service_AnalyticsReporting_GetReportsRequest();
        $body->setReportRequests( array( $request) );
        return $analytics->reports->batchGet($body);
    }


    public static function getReportAnalyticsUsers(Google_Service_AnalyticsReporting $analytics, $date_from, $date_to)
    {
        // Replace with your view ID, for example XXXX.
        $VIEW_ID = "169690080";

        // Create the DateRange object.
        $dateRange = new Google_Service_AnalyticsReporting_DateRange();
        $dateRange->setStartDate("$date_from");
        $dateRange->setEndDate("$date_to");

        $metrics = new Google_Service_AnalyticsReporting_Metric();
        $metrics->setExpression("ga:users");

        $dimensions = new Google_Service_AnalyticsReporting_Dimension();
        $dimensions->setName('ga:adwordsCampaignID');

        // Create the ReportRequest object.
        $request = new Google_Service_AnalyticsReporting_ReportRequest();
        $request->setViewId($VIEW_ID);
        $request->setDateRanges($dateRange);
        $request->setMetrics(array( $metrics));
        $request->setDimensions(array($dimensions));

        $body = new Google_Service_AnalyticsReporting_GetReportsRequest();
        $body->setReportRequests( array( $request) );
        return $analytics->reports->batchGet($body);
    }


    public function getResultsAnalytics($reports, $compaign_id)
    {
        $arr = [];
        for ($reportIndex = 0; $reportIndex < count($reports); $reportIndex++) {
            $report = $reports[$reportIndex];
            $header = $report->getColumnHeader();
            $dimensionHeaders = $header->getDimensions();
            $metricHeaders = $header->getMetricHeader()->getMetricHeaderEntries();
            $rows = $report->getData()->getRows();

            for ($rowIndex = 0; $rowIndex < count($rows); $rowIndex++) {
                $row = $rows[$rowIndex];
                $dimensions = $row->getDimensions();
                $metrics = $row->getMetrics();

                for ($j = 0; $j < count($metrics); $j++) {
                    $values = $metrics[$j]->getValues();
                    $arr[] = array_merge($dimensions, $values);
                }
            }

            $data = json_encode($arr);
            file_put_contents(public_path() . "/../app/ApiSources/analytics/analytics.json", $data);
        }

        $events = null;
        $compaign_id = explode(',', $compaign_id);

            foreach ($compaign_id as $comp) {
                foreach ($arr as $val) {

                    if ($val[0] == $comp) {
                        $events += $val[2];
                    }
                }
            }

        if($events !== null){
            array_push($this->inputArbitaryEvents, array($events));
        }
        else{
            array_push($this->inputArbitaryEvents, array(null));
        }

    }

    public function getResultsAnalyticsStatic($compaign_id){
        $json = file_get_contents(public_path() . "/../app/ApiSources/analytics/analytics.json");
        $arr = json_decode($json, true);
        $events = null;
        $compaign_id = explode(',', $compaign_id);

            foreach ($compaign_id as $comp) {
                foreach ($arr as $val) {

                    if ($val[0] == $comp) {
                        $events += $val[2];
                    }
                }
            }

        if($events !== null){
            array_push($this->inputArbitaryEvents, array($events));
        }
        else{
            array_push($this->inputArbitaryEvents, array(null));
        }
    }


    public function getResultsAnalyticsUsers($reports, $compaign_id)
    {
        $arr = [];
        for ($reportIndex = 0; $reportIndex < count($reports); $reportIndex++) {
            $report = $reports[$reportIndex];
            $header = $report->getColumnHeader();
            $dimensionHeaders = $header->getDimensions();
            $metricHeaders = $header->getMetricHeader()->getMetricHeaderEntries();
            $rows = $report->getData()->getRows();

            for ($rowIndex = 0; $rowIndex < count($rows); $rowIndex++) {
                $row = $rows[$rowIndex];
                $dimensions = $row->getDimensions();
                $metrics = $row->getMetrics();

                for ($j = 0; $j < count($metrics); $j++) {
                    $values = $metrics[$j]->getValues();
                    $arr[] = array_merge($dimensions, $values);
                }
            }

            $data = json_encode($arr);
            file_put_contents(public_path() . "/../app/ApiSources/analytics/users.json", $data);
        }
        $users = null;
        $compaign_id = explode(',', $compaign_id);
            foreach ($compaign_id as $comp) {
                foreach ($arr as $val) {

                    if ($val[0] == $comp) {
                        $users += $val[1];
                    }
                }
            }

        if($users != null){
            array_push($this->inputArbitaryUsers, array($users));
        }
        else{
            array_push($this->inputArbitaryUsers, array(null));
        }

    }

    public function getResultsAnalyticsUsersStatic($compaign_id){
        $json = file_get_contents(public_path() . "/../app/ApiSources/analytics/users.json");
        $arr = json_decode($json, true);
        $users = null;

        $compaign_id = explode(',', $compaign_id);
            foreach ($compaign_id as $comp) {
                foreach ($arr as $val) {

                    if ($val[0] == $comp) {
                        $users += $val[1];
                    }
                }
            }


        if($users != null){
            array_push($this->inputArbitaryUsers, array($users));
        }
        else{
            array_push($this->inputArbitaryUsers, array(null));
        }
    }


    public static function getReport(AdWordsSession $session, $reportQuery, $reportFormat)
    {
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
        $stringResult = $reportDownloadResult->getAsString();
        return $stringResult;
    }




    public function getReportAdwords($customer_id, $compaign_id, $during, $arbitary)
    {
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
            . 'Clicks, Impressions, Cost, AllConversionValue FROM CRITERIA_PERFORMANCE_REPORT DURING %s';
        $buildReportQuery = sprintf($buildReportQuery, $during);

        $stringReport = self::getReport($session, $buildReportQuery, DownloadFormat::CSV);
        $arrayReport = explode(',', $stringReport);

        file_put_contents(public_path() . "/../app/ApiSources/adwords/" . $customer_id . ".csv", $stringReport);

        $values = array_map('str_getcsv', file(public_path() . "/../app/ApiSources/adwords/" . $customer_id . ".csv"));
        // var_dump($values);
        $Click = 0;
        $Impressions = 0;
        $Cost = 0;
        $Conversion = 0;

        $compaign_id = explode(',', $compaign_id);
        foreach ($compaign_id as $comp) {
            foreach ($values as $val) {

                if ($val[0] == $comp) {
                    $Click += $val[1];
                    $Impressions += $val[2];
                    $Cost += $val[3];
                    $Conversion += $val[4];
                }
            }
        }


        //Cut of zeros
        if ($Cost != 0) {
            $Cost = (float)$Cost / 1000000;
        }

        //Switch input result to current or arbitary month

        if ($arbitary != 0) {
            array_push($this->inputArbitary, array($Impressions, $Click, $Cost, $Conversion));
        } else {
            array_push($this->input, array($Impressions, $Click, $Cost, $Conversion));
        }

        $this->message .= " successful! <br>";

    }

    public function getReportAdwordsStatic($customer_id, $compaign_id, $arbitary){
        $values = array_map('str_getcsv', file(public_path() . "/../app/ApiSources/adwords/".$customer_id.".csv"));
        // var_dump($values);
        $Click = 0;
        $Impressions = 0;
        $Cost = 0;
        $Conversion = 0;

        $compaign_id = explode(',', $compaign_id);
            foreach ($compaign_id as $comp) {
                foreach ($values as $val) {

                    if ($val[0] == $comp) {
                        $Click += $val[1];
                        $Impressions += $val[2];
                        $Cost += $val[3];
                        $Conversion += $val[4];
                    }
                }
            }

        //Cut of zeros
        if ($Cost != 0) {
            $Cost = (float)$Cost / 1000000;
        }

        //Switch input result to current or arbitary month

        if ($arbitary != 0) {
            array_push($this->inputArbitary, array($Impressions, $Click, $Cost, $Conversion));
        } else {
            array_push($this->input, array($Impressions, $Click, $Cost, $Conversion));
        }

        $this->message .= " successful! <br>";
    }

    public function grab($params, $report = Null, $date_from = Null, $date_to = Null, Request $request)
    {
        try {

            ini_set("max_execution_time", 0);
            $default = [
                'APPLICATION_NAME'   => 'Google API',
                'CREDENTIALS_PATH'   => app_path() . '/ApiSources/google-analytics.json',
                'CLIENT_SECRET_PATH' => app_path() . '/ApiSources/client_secret_analytics.json',
                'SCOPES'             => array(
                    Google_Service_Analytics::ANALYTICS_READONLY,
                    Google_Service_Analytics::ANALYTICS,
                    Google_Service_Analytics::ANALYTICS_EDIT,
                    Google_Service_Analytics::ANALYTICS_PROVISION,
                    Google_Service_Sheets::SPREADSHEETS,
                    Google_Service_Sheets::SPREADSHEETS_READONLY,
                ),
            ];
            $client = GoogleClient::get_instance($default);
            $service = new Google_Service_Sheets($client->client);

            $serviceAnalytics = new Google_Service_AnalyticsReporting($client->client);


            //get ranges of input
            switch ($params) {
                case 'clone':
                    $spreadsheetId = '1Q4j81zbUXfi2trsiZORF0fGgx_cSFKN5uokJIZOwP0I';
                    $CurrentSheet = 'Raw Data - All Details';
                    $urlSheet = "<a href='https://docs.google.com/spreadsheets/d/1Q4j81zbUXfi2trsiZORF0fGgx_cSFKN5uokJIZOwP0I/edit#gid=1523362544'>View result</a><br>";
                    break;
                case 'main':
                    $spreadsheetId = '1D5XkP8t6cmpxdNfa2E_81GVP0es2ouk__7UGvRm_6Iw';
                    $CurrentSheet = 'Raw data';
                    $urlSheet = "<a href='https://docs.google.com/spreadsheets/d/1Q4j81zbUXfi2trsiZORF0fGgx_cSFKN5uokJIZOwP0I/edit#gid=1311329247'>View result</a><br>";
                    break;
                default:
                    $this->message .= "Invalid URL ...";
                    exit();
                    break;
            }
            if (isset($report)) {
                switch ($report) {
                    case "1":
                        $spreadsheetId = '1Q4j81zbUXfi2trsiZORF0fGgx_cSFKN5uokJIZOwP0I';
                        $CurrentSheet = 'Raw Data - All Details';
                        $urlSheet = "<a href='https://docs.google.com/spreadsheets/d/1Q4j81zbUXfi2trsiZORF0fGgx_cSFKN5uokJIZOwP0I/edit#gid=1523362544'>View result</a><br>";
                        break;
                    case "0":
                        $spreadsheetId = '1D5XkP8t6cmpxdNfa2E_81GVP0es2ouk__7UGvRm_6Iw';
                        $CurrentSheet = 'Raw data';
                        $urlSheet = "<a href='https://docs.google.com/spreadsheets/d/1D5XkP8t6cmpxdNfa2E_81GVP0es2ouk__7UGvRm_6Iw/edit#gid=1144216345'>View result</a><br>";
                        break;
                    default:
                        $this->message .= "Error ...";
                        exit();
                        break;
                }
            }
            $ranges = Sheet::getOfSheet($service, $spreadsheetId, $CurrentSheet . '!F3:M5');

            $rangeInputCurrent = $CurrentSheet . '!' . $ranges[0][1] . ':' . $ranges[0][2];
            $rangeInputLast = $CurrentSheet . '!' . $ranges[0][3] . ':' . $ranges[0][4];
            $rangeInputArbitary = $CurrentSheet . '!' . $ranges[0][5] . ':' . $ranges[0][6];
            $rangeSource = $CurrentSheet . '!' . $ranges[1][1] . ':' . $ranges[1][2];
            $rangeNameCurrent = $CurrentSheet . '!' . 'H8:K8';
            $rangeNameLast = $CurrentSheet . '!' . 'L8:O8';
            $rangeDateUpdated = $CurrentSheet . '!' . 'G5';
            $rangeArbitaryFrom = $CurrentSheet . '!' . 'Q8';
            $rangeArbitaryTo = $CurrentSheet . '!' . 'R8';
            $rangeInputArbitaryEvents = $CurrentSheet . '!' . $ranges[1][5] . ':' . $ranges[1][6];
            $rangeInputArbitaryUsers = $CurrentSheet . '!' . $ranges[2][5] . ':' . $ranges[2][6];
            $ProcessingArbitary = isset($date_from) && !empty($date_from) && isset($date_to) && !empty($date_to) ? true : false;

            $duringCurrent = date('Ym') . '01, ' . date('Ymd'); // This month
            if ($ProcessingArbitary) {
                $duringArbitary = str_replace('-', '', $date_from) . ', ' . str_replace('-', '', $date_to); // Arbitary month
            }

            if (glob(public_path() . "/../app/ApiSources/bingReport/*.zip")) {
                array_map('unlink', glob(public_path() . "/../app/ApiSources/bingReport/*.zip"));
            }

            if (glob(public_path() . "/../app/ApiSources/analytics/*.json")) {
                array_map('unlink', glob(public_path() . "/../app/ApiSources/analytics/*.json"));
            }

            if (glob(public_path() . "/../app/ApiSources/adwords/*.csv")) {
                array_map('unlink', glob(public_path() . "/../app/ApiSources/adwords/*.csv"));
            }

            $source = Sheet::getOfSheet($service, $spreadsheetId, $rangeSource);
            $this->message .= "Processing started!<br>";

            foreach ($source as $row) {
                if (isset($row) && !empty($row)) {
                    if ($row[0] == "adwords") {
                        //get adwords account
                        $this->message .= "Adwords AccountID=" . isset($row[1]) && !empty($row[1]) ? $row[1] : 'Empty AccountID' . " - ";
                        $customer_id = str_replace('-', '', isset($row[1]) && !empty($row[1]) ? $row[1] : '');
                        if (isset($row[2]) && !empty($row[2])) {
                            $compaign_id = $row[2];
                            if (count($row) > 2) {
                                for ($i = 3; $i <= count($row); $i++) {
                                    if (isset($row[$i]) && !empty($row[$i])) {
                                        $compaign_id = $compaign_id . ', ' . $row[$i];
                                    } else break;
                                }
                            }
                            if ($ProcessingArbitary) {
                                $DownloadPathAdwords = public_path() . "/../app/ApiSources/adwords/".$customer_id.".csv";
                                if (!file_exists($DownloadPathAdwords)) {
                                    self::getReportAdwords($customer_id, $compaign_id, $duringArbitary, 1);
                                }else {
                                    self::getReportAdwordsStatic($customer_id, $compaign_id,  1);
                                }

                                $DownloadPathEvents = public_path() . "/../app/ApiSources/analytics/analytics.json";
                                $DownloadPathUsers = public_path() . "/../app/ApiSources/analytics/users.json";
                                if (!file_exists($DownloadPathEvents)) {
                                    $responseAnalyticsReport = self::getReportAnalyticsEvents($serviceAnalytics, $date_from, $date_to);
                                    $this->getResultsAnalytics($responseAnalyticsReport, $compaign_id);
                                } else {
                                    $this->getResultsAnalyticsStatic($compaign_id);
                                }

                                if (!file_exists($DownloadPathUsers)) {
                                    $responseAnalyticsReportUsers = self::getReportAnalyticsUsers($serviceAnalytics, $date_from, $date_to);
                                    $this->getResultsAnalyticsUsers($responseAnalyticsReportUsers, $compaign_id);
                                } else {
                                    $this->getResultsAnalyticsUsersStatic($compaign_id);
                                }

                            } else {

                                $DownloadPathAdwords = public_path() . "/../app/ApiSources/adwords/".$customer_id.".csv";
                                if (!file_exists($DownloadPathAdwords)) {
                                    self::getReportAdwords($customer_id, $compaign_id, $duringCurrent, 0);
                                }else {
                                    self::getReportAdwordsStatic($customer_id, $compaign_id,  0);
                                }
                            }
                        } else {
                            if ($ProcessingArbitary) {
                                array_push($this->inputArbitary, array(0, 0, 0, 0));
                                array_push($this->inputArbitaryEvents, array(Null));
                                array_push($this->inputArbitaryUsers, array(Null));
                            } else {
                                array_push($this->input, array(0, 0, 0, 0));
                            }

                            $this->message .= " empty CompaignID! <br>";
                        }
                    } elseif ($row[0] == "bing") {
                        //get bing account
                        $this->message .= "Bing AccountID=" . isset($row[1]) && !empty($row[1]) ? $row[1] : 'Empty AccountID' . " - ";
                        $customer_id = isset($row[1]) && !empty($row[1]) ? $row[1] : '';

                        if (isset($row[2]) && !empty($row[2])) {
                            $compaign_id = $row[2];
                            if (count($row) > 2) {
                                for ($i = 3; $i <= count($row); $i++) {
                                    if (isset($row[$i]) && !empty($row[$i])) {
                                        $compaign_id = $compaign_id . ', ' . $row[$i];
                                    } else break;
                                }
                            }
                            if ($ProcessingArbitary) {
                                self::grabBing($customer_id, $compaign_id, 1, $date_from, $date_to, $request);
                                array_push($this->inputArbitaryEvents, array(null));
                                array_push($this->inputArbitaryUsers, array(null));
                            } else {
                                $date_from_this_month = date('Y-m-01');
                                $date_to_this_month = date('Y-m-d');
                                self::grabBing($customer_id, $compaign_id, 0, $date_from_this_month, $date_to_this_month, $request);
                            }
                        } else {
                            if ($ProcessingArbitary) {
                                array_push($this->inputArbitary, array(0, 0, 0, 0));
                                array_push($this->inputArbitaryEvents, array(null));
                                array_push($this->inputArbitaryUsers, array(null));
                            } else {
                                array_push($this->input, array(0, 0, 0, 0));
                            }

                            $this->message .= " empty CompaignID! <br>";
                        }

                    } elseif ($row[0] == "linkedin") {
                        //get linkedin account
                        $this->message .= "Linkedin AccountID=" . isset($row[1]) && !empty($row[1]) ? $row[1] : 'Empty AccountID' . " - ";
                        if ($ProcessingArbitary) {
                            array_push($this->inputArbitary, array(0, 0, 0, 0));
                            array_push($this->inputArbitaryEvents, array(null));
                            array_push($this->inputArbitaryUsers, array(null));
                        } else {
                            array_push($this->input, array(0, 0, 0, 0));
                        }
                        $this->message .= " not processed! <br>";

                    } elseif ($row[0] == NULL) {
                        //get empty row
                        if ($ProcessingArbitary) {
                            array_push($this->inputArbitary, array(NULL, NULL, NULL, NULL));
                            array_push($this->inputArbitaryEvents, array(NULL));
                            array_push($this->inputArbitaryUsers, array(NULL));
                        } else {
                            array_push($this->input, array(NULL, NULL, NULL, NULL));
                        }
                    }
                } else
                    //get empty row
                    if ($ProcessingArbitary) {
                        array_push($this->inputArbitary, array(NULL, NULL, NULL, NULL));
                        array_push($this->inputArbitaryEvents, array(NULL));
                        array_push($this->inputArbitaryUsers, array(NULL));
                    } else {
                        array_push($this->input, array(NULL, NULL, NULL, NULL));
                    }
            }


            $this->message .= "Processing complete!<br>";


            //Set result to Google Sheets


            if ($ProcessingArbitary) {
                //The arbitary month
                If (!Sheet::setToSheet($service, $spreadsheetId, $rangeInputArbitary, $this->inputArbitary)) {
                    $this->message .= "Error update range to Sheet!";
                } else {
                    Sheet::setToSheet($service, $spreadsheetId, $rangeArbitaryFrom, array(array(date("F j, Y", strtotime($date_from)))));
                    Sheet::setToSheet($service, $spreadsheetId, $rangeArbitaryTo, array(array(date("F j, Y", strtotime($date_to)))));
                    $this->message .= "Statistics for the arbitary month have been updated!<br>";
                }

                if(!Sheet::setToSheet($service, $spreadsheetId, $rangeInputArbitaryEvents, $this->inputArbitaryEvents)){
                    $this->message .= "Error update range to Sheet!";
                } else {
                    $this->message .= "Statistics of Events for the arbitary month have been updated!<br>";
                }
                if(!Sheet::setToSheet($service, $spreadsheetId, $rangeInputArbitaryUsers, $this->inputArbitaryUsers)){
                    $this->message .= "Error update range to Sheet!";
                } else {
                    $this->message .= "Statistics of Users for the arbitary month have been updated!<br>";
                }

            } else {
                //The current month
                If (!Sheet::setToSheet($service, $spreadsheetId, $rangeInputCurrent, $this->input)) {
                    $this->message .= "Error update range to Sheet!";
                } else {
                    $this->message .= "Statistics for the current month have been updated!<br>";
                    Sheet::setToSheet($service, $spreadsheetId, $rangeDateUpdated, array(array(date('r'))));
                    Sheet::setToSheet($service, $spreadsheetId, $rangeNameCurrent, array(array(date('F'))));
                };
                //The last month
                If (date('t') == date('d')) {
                    If (!Sheet::setToSheet($service, $spreadsheetId, $rangeInputLast, $this->input)) {
                        $this->message .= "Error update range to Sheet!";
                    } else {
                        $this->message .= "Statistics for the last month have been updated!<br>";
                        Sheet::setToSheet($service, $spreadsheetId, $rangeNameLast, array(array(date('F'))));
                    };
                }

            }
            $this->message .= $urlSheet;
        } catch (Exception $e) {
            dd($e);
        }
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
            $compaign = $linkedIn->get('v2/adCampaignsV2/{500610594}');
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

    public function grabBing($customer_id, $compaign_id, $arbitary, $date_from, $date_to, $request)
    {

        $DownloadPath = public_path() . "/../app/ApiSources/bingReport/" . $customer_id . ".zip";
        if (!file_exists($DownloadPath)) {
            BingReport::getReport($request, $date_from, $date_to, $customer_id);
        }
        if (file_exists($DownloadPath)) {
            $filePath = $this->extractFile($DownloadPath);

            $Click = 0;
            $Impressions = 0;
            $Cost = 0;
            $Conversion = 0;

            $compaigns = explode(',', $compaign_id);
                foreach ($compaigns as $comp) {
                    $comp = trim($comp);
                    $data = $this->processingFile($filePath, $comp);
                    $Click += $data['clicks'];
                    $Impressions += $data['impressions'];
                    $Cost += $data['cost'];
                    $Conversion += $data['conversions'];
                }

            $data = $Click+$Impressions+$Cost+$Conversion;

             if ($data !== 0) {

                if ($arbitary != 0) {
                    array_push($this->inputArbitary, array($Impressions, $Click, $Cost, $Conversion));
                } else {
                    array_push($this->input, array($Impressions, $Click, $Cost, $Conversion));
                }
                 array_map('unlink', glob(public_path() . "/../app/ApiSources/bingReport/*.csv"));
                $this->message .= " successful! <br>";
            } else {
                if ($arbitary != 0) {
                    array_push($this->inputArbitary, array(0, 0, 0, 0));
                } else {
                    array_push($this->input, array(0, 0, 0, 0));
                }
                array_map('unlink', glob(public_path() . "/../app/ApiSources/bingReport/*.csv"));

                $this->message .= " invalid campaign_id! <br>";
            }
        }
    }

    public function extractFile($path)
    {
        $archive = new ZipArchive();

        if ($archive->open($path) === TRUE) {
            $archive->extractTo(public_path() . "/../app/ApiSources/bingReport//");
            $return = public_path() . "/../app/ApiSources/bingReport/" . $archive->statIndex(0)['name'];
            $archive->close();
        } else {
            throw new Exception ("Decompress operation from ZIP file failed.");
        }

        return $return;
    }

    public function processingFile($path, $compaign_id)
    {
        if (is_file($path)) {
            $reader = @fopen($path, "r");
            while (($row = fgetcsv($reader, 10000, ",")) !== FALSE) {
                if ($row[0] == 'TimePeriod' &&
                    $row[2] == 'CampaignId' &&
                    $row[5] == 'Clicks' &&
                    $row[6] == 'Spend' &&
                    $row[7] == 'Conversions' &&
                    $row[8] == 'Impressions') {
                    break;
                }
            }
            $data = array();
            while (($row = fgetcsv($reader, 10000, ",")) !== FALSE) {
                if (isset($row[0]) &&
                    isset($row[2]) &&
                    isset($row[5]) &&
                    isset($row[6]) &&
                    isset($row[7]) &&
                    isset($row[8])
                ) {

                    if (!isset($data[$row[2]])) {
                        $data[$row[2]]['clicks'] = 0;
                        $data[$row[2]]['cost'] = 0;
                        $data[$row[2]]['conversions'] = 0;
                        $data[$row[2]]['impressions'] = 0;
                    }
                    $data[$row[2]]['clicks'] += $row[5];
                    $data[$row[2]]['cost'] += $row[6];
                    $data[$row[2]]['conversions'] += $row[7];
                    $data[$row[2]]['impressions'] += $row[8];

                }
            }

        }


        if (array_key_exists($compaign_id, $data)) {
            return $data[$compaign_id];
        } else {
            return $data = 0;
        }


    }
}