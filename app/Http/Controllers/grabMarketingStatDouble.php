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
use Google\AdsApi\AdWords\Reporting\v201809\RequestOptionsFactory;
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


class grabMarketingStatDouble extends Controller
{

    public $inputArbitaryEvents = [];
    public $inputArbitaryUsers = [];

    const PAGE_LIMIT = 500;
    public $input = [];
    public $inputArbitary = [];
    public $inputLabel = [];
    public $message = '';
    public $customers = [2682652198, 8826845921, 3112040897, 858558, 17182159];
    public $customersBing = [858558, 17182159];
    public $customersAdword = [2682652198, 8826845921, 3112040897];

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
            //var_dump($data);

                self::grab($data['date-from'], $data['date-to'], $request);

        } catch (Exception $e) {
            dd($e);
            return false;
        }
        return view('ppc_double.index', ["message" => $this->message]);
    }

    public function index(Request $request)
    {

        return view('ppc_double.index', ["message" => $this->message]);
    }

    public static function getReport(AdWordsSession $session, $reportQuery, $reportFormat)
    {
        $options = [
            'stream_context' => [
                'http' => ['timeout' => 600]
            ]
        ];
        $requestOptionsFactory = new RequestOptionsFactory($session, $options);
        // Download report as a string.
        $reportDownloader = new ReportDownloader($session, $requestOptionsFactory);
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



    public function getReportAdwords($customer_id, $during, $arbitrary = null)
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
            . 'Clicks, Impressions, Cost, AllConversionValue, LabelIds, Labels FROM CAMPAIGN_PERFORMANCE_REPORT DURING %s';
        $buildReportQuery = sprintf($buildReportQuery, $during);

        $stringReport = self::getReport($session, $buildReportQuery, DownloadFormat::CSV);
        $arrayReport = explode(',', $stringReport);


        if ($arbitrary == 1) {
            file_put_contents(public_path() . "/../app/ApiSources/double/adwords/arbitrary/" . $customer_id . ".csv", $stringReport);
            $values = array_map('str_getcsv', file(public_path() . "/../app/ApiSources/double/adwords/arbitrary/" . $customer_id . ".csv"));

            $path = public_path() . "/../app/ApiSources/double/adwords/arbitrary/" . $customer_id . ".csv";

        } else {
            file_put_contents(public_path() . "/../app/ApiSources/double/adwords/" . $customer_id . ".csv", $stringReport);
            $values = array_map('str_getcsv', file(public_path() . "/../app/ApiSources/double/adwords/" . $customer_id . ".csv"));

            $path = public_path() . "/../app/ApiSources/double/adwords/" . $customer_id . ".csv";
        }


        if (is_file($path)) {
            $reader = @fopen($path, "r");
            while (($row = fgetcsv($reader, 10000, ",")) !== FALSE) {
                if ($row[0] == 'Campaign ID' &&
                    $row[1] == 'Clicks' &&
                    $row[2] == 'Impressions' &&
                    $row[3] == 'Cost' &&
                    $row[6] == 'Labels') {
                    break;
                }
            }
            $data = array();
            while (($row = fgetcsv($reader, 10000, ",")) !== FALSE) {
                if (isset($row[0]) &&
                    isset($row[1]) &&
                    isset($row[2]) &&
                    isset($row[3]) &&
                    isset($row[6])
                ) {

                    if (!isset($data[$row[0]])) {
                        $data[$row[0]]['clicks'] = 0;
                        $data[$row[0]]['cost'] = 0;
                        $data[$row[0]]['conversions'] = 0;
                        $data[$row[0]]['impressions'] = 0;
                        $data[$row[0]]['labels'] = 0;
                        $data[$row[0]]['campaigns'] = 0;
                    }
                    $data[$row[0]]['clicks'] += $row[1];
                    $data[$row[0]]['impressions'] += $row[2];
                    $data[$row[0]]['cost'] += $row[3];
                    $data[$row[0]]['conversions'] += $row[4];

                    $data[$row[0]]['campaigns'] +=  $row[0];

                    $row6 = explode('","', trim($row[6], '"[]'));
                    $row7 = [];
                    foreach ($row6 as $row1) {

                        $row12 = explode('-', $row1);
                        switch ($row12[0]) {
                            case 1:
                                $row7[1] = $row1;
                                break;
                            case 2:
                                $row7[2] = $row1;
                                break;
                            case 3:
                                $row7[3] = $row1;
                                break;
                            case 4:
                                $row7[4] = $row1;
                                break;
                            case 5:
                                $row7[5] = $row1;
                                break;
                        }
                        ksort($row7);
                    }

                    $data[$row[0]]['labels'] = $row7;

                }
            }

            $res = $data;
            $arr1 = [];
            foreach ($data as $key => $value) {
                $data[$key]['campaigns'] = array();
                $data[$key]['campaigns'][] = $key;
                foreach ($res as $key_res => $value_res) {

                    if ($value !== $value_res) {

                        if ($value['labels'] === $value_res['labels']) {
                            $data[$key]['clicks'] += $value_res['clicks'];
                            $data[$key]['cost'] += $value_res['cost'];
                            $data[$key]['conversions'] += $value_res['conversions'];
                            $data[$key]['impressions'] += $value_res['impressions'];

                            $data[$key]['campaigns'][] += $value_res['campaigns'];
                        }
                    }
                }
                sort($data[$key]['campaigns']);

            }


            $arrayResult = array_unique($data, SORT_REGULAR);

            return $arrayResult;
        }

    }

    public function grab($date_from = Null, $date_to = Null, Request $request)
    {
        $lock = fopen(public_path() . "/../app/ApiSources/double/lock.txt", 'a');
        if ( !($lock && flock($lock, LOCK_EX | LOCK_NB)) ) {
            $this->message .= "Script is already running. Please, try again later(in 5 minutes)!<br>";
            exit($this->message);
        }

        fwrite($lock, "\n" . date('Y-m-d H:i:s') . ' running' ."\n");


        ini_set("max_execution_time", 0);
        $default = [
            'APPLICATION_NAME' => 'Google API',
            'CREDENTIALS_PATH' => app_path() . '/ApiSources/google-analytics.json',
            'CLIENT_SECRET_PATH' => app_path() . '/ApiSources/client_secret_analytics.json',
            'SCOPES' => array(
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
        $spreadsheetId = '1ZSK0bQJOD0doie9Xqsfa6KN20etMubKcgtf0gdrM_d4';

        $ProcessingArbitary = isset($date_from) && !empty($date_from) && isset($date_to) && !empty($date_to) ? true : false;

        if($ProcessingArbitary){
            $CurrentSheet = 'Arbitrary range';
            $during = str_replace('-', '', $date_from) . ', ' . str_replace('-', '', $date_to);
            $date_start = $date_from;
            $date_end = $date_to;
        }
        else {
            $CurrentSheet = 'Current Month';
            $during = date('Ym') . '01, ' . date('Ymd'); // This month
            $date_start = date('Y-m-01');
            $date_end = date('Y-m-d');
        }

        $rangeInputArbitary = $CurrentSheet . '!H4:W';
        $rangeLabel = $CurrentSheet . '!A4:AD';
        $rangeArbitaryFrom = $CurrentSheet . '!' . 'E2';
        $rangeArbitaryTo = $CurrentSheet . '!' . 'F2';
        $rangeFromCurrent = $CurrentSheet . '!' . 'L2';

        //$ProcessingArbitary = isset($date_from) && !empty($date_from) && isset($date_to) && !empty($date_to) ? true : false;
        //$duringCurrent = date('Ym') . '01, ' . date('Ymd'); // This month
        if($ProcessingArbitary) {
            if (glob(public_path() . "/../app/ApiSources/double/bing/arbitrary/*.*")) {
                array_map('unlink', glob(public_path() . "/../app/ApiSources/double/bing/arbitrary/*.*"));
            }

            if (glob(public_path() . "/../app/ApiSources/double/analytics/users/arbitrary/*.json")) {
                array_map('unlink', glob(public_path() . "/../app/ApiSources/double/analytics/users/arbitrary/*.json"));
            }

            if (glob(public_path() . "/../app/ApiSources/double/analytics/unique/arbitrary/*.json")) {
                array_map('unlink', glob(public_path() . "/../app/ApiSources/double/analytics/unique/arbitrary/*.json"));
            }

            if (glob(public_path() . "/../app/ApiSources/double/adwords/arbitrary/*.csv")) {
                array_map('unlink', glob(public_path() . "/../app/ApiSources/double/adwords/arbitrary/*.csv"));
            }
        }else{
            if (glob(public_path() . "/../app/ApiSources/double/bing/*.*")) {
                array_map('unlink', glob(public_path() . "/../app/ApiSources/double/bing/*.*"));
            }

            if (glob(public_path() . "/../app/ApiSources/double/analytics/users/*.json")) {
                array_map('unlink', glob(public_path() . "/../app/ApiSources/double/analytics/users/*.json"));
            }

            if (glob(public_path() . "/../app/ApiSources/double/analytics/unique/*.json")) {
                array_map('unlink', glob(public_path() . "/../app/ApiSources/double/analytics/unique/*.json"));
            }

            if (glob(public_path() . "/../app/ApiSources/double/adwords/*.csv")) {
                array_map('unlink', glob(public_path() . "/../app/ApiSources/double/adwords/*.csv"));
            }
        }
        foreach ($this->customers as $customer_id) {
            if($ProcessingArbitary) {
                if (in_array($customer_id, $this->customersBing)) {
                    $data = $this->grabBing($customer_id, $date_start, $date_end, $request, 1);
                } elseif (in_array($customer_id, $this->customersAdword)) {
                    $data = self::getReportAdwords($customer_id, $during, 1);
                }
            }else{
                if (in_array($customer_id, $this->customersBing)) {
                    $data = $this->grabBing($customer_id, $date_start, $date_end, $request);
                } elseif (in_array($customer_id, $this->customersAdword)) {
                    $data = self::getReportAdwords($customer_id, $during);
                }
            }

            //var_dump($data);

            foreach ($data as $value => $item) {
                if ($item['labels']) {
                    $Click = $item['clicks'];
                    if(in_array($customer_id, $this->customersBing)){
                        $Cost = $item['cost'];
                    } elseif (in_array($customer_id, $this->customersAdword)) {
                        $Cost = $item['cost'] / 1000000;
                    }

                    $Conversion = $item['conversions'];
                    $Impressions = $item['impressions'];
                    $Campaigns = $item['campaigns'];
                    /*$Campaigns = '';
                    foreach($item['campaigns'] as &$campaign){

                        $Campaigns .= $campaign . ',';
                    }*/


                    if (array_key_exists(1, $item['labels'])) {
                        $lab1 = $item['labels'][1];
                    } else {
                        $lab1 = ' - ';
                    }
                    if (array_key_exists(2, $item['labels'])) {
                        $lab2 = $item['labels'][2];
                    } else {
                        $lab2 = ' - ';
                    }
                    if (array_key_exists(3, $item['labels'])) {
                        $lab3 = $item['labels'][3];
                    } else {
                        $lab3 = ' - ';
                    }
                    if (array_key_exists(4, $item['labels'])) {
                        $lab4 = $item['labels'][4];
                    } else {
                        $lab4 = ' - ';
                    }
                    if (array_key_exists(5, $item['labels'])) {
                        $lab5 = $item['labels'][5];
                    } else {
                        $lab5 = ' - ';
                    }
                    $customer_name = '';
                    if ($customer_id == '2682652198' || $customer_id == '858558') {
                        $customer_name = 'altoroslabs.com';

                    } elseif ($customer_id == '8826845921' || $customer_id == '17182159') {
                        $customer_name = 'altoros.no';

                    } elseif ($customer_id == '3112040897') {
                        $customer_name = 'altoros.fi';
                    }
                    $source = '';
                    if (in_array($customer_id, $this->customersAdword)) {
                        $source = 'Adwords';
                    } elseif (in_array($customer_id, $this->customersBing)) {
                        $source = 'Bing';
                    }

                    $array_of_values = array($customer_id,$customer_name, $source, $lab1, $lab2, $lab3, $lab4, $lab5, $Impressions, $Click, $Cost, $Conversion);

                    $DownloadPathEvents = '';
                    $DownloadPathUsers = '';
                    $dimension_name = '';
                    $report_name = '';

                    if ($ProcessingArbitary) {
                        if (in_array($customer_id, $this->customersAdword)) {

                            $DownloadPathEvents = public_path() . "/../app/ApiSources/double/analytics/unique/arbitrary/adwords.json";
                            $DownloadPathUsers = public_path() . "/../app/ApiSources/double/analytics/users/arbitrary/adwords.json";
                            $dimension_name = 'ga:adwordsCampaignID';
                            $report_name = 'adwords';

                        } elseif (in_array($customer_id, $this->customersBing)) {

                            $DownloadPathEvents = public_path() . "/../app/ApiSources/double/analytics/unique/arbitrary/bing.json";
                            $DownloadPathUsers = public_path() . "/../app/ApiSources/double/analytics/users/arbitrary/bing.json";
                            $dimension_name = 'ga:dimension11';
                            $report_name = 'bing';

                        }
                    } else {
                        if (in_array($customer_id, $this->customersAdword)) {

                            $DownloadPathEvents = public_path() . "/../app/ApiSources/double/analytics/unique/adwords.json";
                            $DownloadPathUsers = public_path() . "/../app/ApiSources/double/analytics/users/adwords.json";
                            $dimension_name = 'ga:adwordsCampaignID';
                            $report_name = 'adwords';

                        } elseif (in_array($customer_id, $this->customersBing)) {

                            $DownloadPathEvents = public_path() . "/../app/ApiSources/double/analytics/unique/bing.json";
                            $DownloadPathUsers = public_path() . "/../app/ApiSources/double/analytics/users/bing.json";
                            $dimension_name = 'ga:dimension11';
                            $report_name = 'bing';

                        }
                    }


                    $events = 0;
                    $users = 0;
                    foreach ($Campaigns as &$camp) {
                        if ($ProcessingArbitary) {
                            if (!file_exists($DownloadPathEvents)) {
                                $responseAnalyticsReport = self::getReportAnalyticsEvents($serviceAnalytics, $date_from, $date_to, $dimension_name);
                                $eventsCamp = $this->getResultsAnalyticsUnique($responseAnalyticsReport, $camp, $report_name, 1);
                            } else {
                                $eventsCamp = $this->getResultsAnalyticsUniqueStatic($camp, $report_name,1);
                            }

                            if (!file_exists($DownloadPathUsers)) {
                                $responseAnalyticsReportUsers = self::getReportAnalyticsUsers($serviceAnalytics, $date_from, $date_to, $dimension_name);
                                $usersCamp = $this->getResultsAnalyticsUsers($responseAnalyticsReportUsers, $camp, $report_name,1);
                            } else {
                                $usersCamp = $this->getResultsAnalyticsUsersStatic($camp, $report_name, 1);
                            }
                            $users += $usersCamp;
                            $events += $eventsCamp;
                        } else {
                            $date_from_this_month = date('Y-m-01');
                               $date_to_now = date('Y-m-d');

                            if (!file_exists($DownloadPathEvents)) {
                                $responseAnalyticsReport = self::getReportAnalyticsEvents($serviceAnalytics, $date_from_this_month, $date_to_now, $dimension_name);
                                $eventsCamp = $this->getResultsAnalyticsUnique($responseAnalyticsReport, $camp, $report_name);
                            } else {
                                $eventsCamp = $this->getResultsAnalyticsUniqueStatic($camp, $report_name);
                            }

                            if (!file_exists($DownloadPathUsers)) {
                                $responseAnalyticsReportUsers = self::getReportAnalyticsUsers($serviceAnalytics, $date_from_this_month, $date_to_now, $dimension_name);
                                $usersCamp = $this->getResultsAnalyticsUsers($responseAnalyticsReportUsers, $camp, $report_name);
                            } else {
                                $usersCamp = $this->getResultsAnalyticsUsersStatic($camp, $report_name);
                            }

                            $users += $usersCamp;
                            $events += $eventsCamp;
                        }
                    }

                    array_push($array_of_values, $events);
                    array_push($array_of_values, $users);

                    foreach ($Campaigns as &$camp) {
                        array_push($array_of_values, $camp);
                    }

                    array_push($this->inputLabel, $array_of_values);
                    //array_push($this->inputArbitary, $array_of_values);


                }
            }
        }
//var_dump($this->inputLabel);

        $this->inputLabel = $this->array_msort($this->inputLabel, array('1' =>SORT_DESC, '2' =>SORT_ASC, '3'=>SORT_ASC, '4'=>SORT_ASC, '5'=>SORT_ASC));
        $this->inputLabel = array_values($this->inputLabel);

//var_dump($this->inputLabel);
//$array = json_encode($this->inputLabel);
//file_put_contents(public_path() . "/../app/ApiSources/double/analytics/unique/array.json", $array);

                Sheet::deletePreviousValues($service, $spreadsheetId, $rangeLabel);
        //Sheet::deletePreviousValues($service, $spreadsheetId, $rangeInputArbitary);

        if($ProcessingArbitary){
            Sheet::setToSheet($service, $spreadsheetId, $rangeArbitaryFrom, array(array(date("F j, Y", strtotime($date_from)))));
            Sheet::setToSheet($service, $spreadsheetId, $rangeArbitaryTo, array(array(date("F j, Y", strtotime($date_to)))));

        } else {
            $now = date("F j, Y H:i:s", strtotime('+3 hours'));
            Sheet::setToSheet($service, $spreadsheetId, $rangeFromCurrent, array(array('updated at '. $now)));
            Sheet::setToSheet($service, $spreadsheetId, $rangeArbitaryTo, array(array(date('F'))));

        }

        Sheet::setToSheet($service, $spreadsheetId, $rangeLabel, $this->inputLabel);
        if ($ProcessingArbitary) {
            $this->message .= "Processing complete successfull!<br>";
            //Sheet::setToSheet($service, $spreadsheetId, $rangeInputArbitary, $this->inputArbitary);
        } else {
            echo "Processing complete successfull!<br>";
        }

        fwrite($lock, date('Y-m-d H:i:s') . ' complete' ."\n" ."\n");
        fclose($lock);

    }

    private function array_msort($array, $cols)
    {
        $colarr = array();
        foreach ($cols as $col => $order) {
            $colarr[$col] = array();
            foreach ($array as $k => $row) { $colarr[$col]['_'.$k] = strtolower($row[$col]); }
        }
        $eval = 'array_multisort(';
        foreach ($cols as $col => $order) {
            $eval .= '$colarr[\''.$col.'\'],'.$order.',';
        }
        $eval = substr($eval,0,-1).');';
        eval($eval);
        $ret = array();
        foreach ($colarr as $col => $arr) {
            foreach ($arr as $k => $v) {
                $k = substr($k,1);
                if (!isset($ret[$k])) $ret[$k] = $array[$k];
                $ret[$k][$col] = $array[$k][$col];
            }
        }
        return $ret;

    }


    public static function getReportAnalyticsEvents(Google_Service_AnalyticsReporting $analytics, $date_from, $date_to, $dimension_name)
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
        $dimensions->setName($dimension_name);


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


    public function getResultsAnalyticsUnique($reports, $compaign_id, $dimension_name, $arbitrary = null)
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
            if($arbitrary == 1){
                file_put_contents(public_path() . "/../app/ApiSources/double/analytics/unique/arbitrary/$dimension_name.json", $data);
            }else{
                file_put_contents(public_path() . "/../app/ApiSources/double/analytics/unique/$dimension_name.json", $data);
            }

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


        return $events;
    }

    public function getResultsAnalyticsUniqueStatic($compaign_id, $dimension_name, $arbitrary = null){
        if($arbitrary == 1){
            $json = file_get_contents(public_path() . "/../app/ApiSources/double/analytics/unique/arbitrary/$dimension_name.json");
        }else{
            $json = file_get_contents(public_path() . "/../app/ApiSources/double/analytics/unique/$dimension_name.json");
        }

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

        return $events;
    }


    public static function getReportAnalyticsUsers(Google_Service_AnalyticsReporting $analytics, $date_from, $date_to, $dimension_name)
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
        $dimensions->setName($dimension_name);

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

    public function getResultsAnalyticsUsers($reports, $compaign_id, $dimension_name, $arbitrary = null)
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

            if ($arbitrary == 1) {
                file_put_contents(public_path() . "/../app/ApiSources/double/analytics/users/arbitrary/$dimension_name.json", $data);
            } else {
                file_put_contents(public_path() . "/../app/ApiSources/double/analytics/users/$dimension_name.json", $data);
            }
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

        return $users;

    }

    public function getResultsAnalyticsUsersStatic($compaign_id, $dimension_name, $arbitrary = null){

        if ($arbitrary == 1) {
        $json = file_get_contents(public_path() . "/../app/ApiSources/double/analytics/users/arbitrary/$dimension_name.json");
        } else {
            $json = file_get_contents(public_path() . "/../app/ApiSources/double/analytics/users/$dimension_name.json");
        }
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


        return $users;
    }

    public function grabBing($customer_id, $date_from = null, $date_to = null, Request $request, $arbitrary = null)
    {
        if ($arbitrary == 1) {
            $DownloadPath = public_path() . "/../app/ApiSources/double/bing/arbitrary/" . $customer_id . ".zip";

            if (!file_exists($DownloadPath)) {
                $report = BingReport::getReportDouble($request, $date_from, $date_to, $customer_id,1);
                if ($report === false) {
                    return array();
                }
            }

        } else {
            $DownloadPath = public_path() . "/../app/ApiSources/double/bing/" . $customer_id . ".zip";

            if (!file_exists($DownloadPath)) {
                $report = BingReport::getReportDouble($request, $date_from, $date_to, $customer_id);
                if ($report === false) {
                    return array();
                }
            }
        }


        if (file_exists($DownloadPath)) {
            if($arbitrary == 1) {
                $filePath = $this->extractFile($DownloadPath, 1);
                //var_dump($filePath);
            } else {
                $filePath = $this->extractFile($DownloadPath);
            }

            if (is_file($filePath)) {
                $reader = @fopen($filePath, "r");

                while (($row = fgetcsv($reader, 10000, ",")) !== FALSE) {

                    if ($row[0] == 'CampaignId' &&
                        $row[1] == 'Clicks' &&
                        $row[2] == 'Spend' &&
                        $row[3] == 'Conversions' &&
                        $row[4] == 'Impressions' &&
                        $row[5] == 'CampaignLabels') {
                        break;
                    }
                }
                $data = array();
                while (($row = fgetcsv($reader, 10000, ",")) !== FALSE) {

                    if (isset($row[0]) &&
                        isset($row[1]) &&
                        isset($row[2]) &&
                        isset($row[3]) &&
                        isset($row[4]) &&
                        isset($row[5])
                    ) {

                        if (!isset($data[$row[0]])) {
                            $data[$row[0]]['clicks'] = 0;
                            $data[$row[0]]['cost'] = 0;
                            $data[$row[0]]['conversions'] = 0;
                            $data[$row[0]]['impressions'] = 0;
                            $data[$row[0]]['labels'] = 0;
                            $data[$row[0]]['campaigns'] = 0;
                        }
                        $data[$row[0]]['clicks'] += $row[1];
                        $data[$row[0]]['impressions'] += $row[4];
                        $data[$row[0]]['cost'] += $row[2];
                        $data[$row[0]]['conversions'] += $row[3];

                        $data[$row[0]]['campaigns'] +=  $row[0];

                        $row6 = explode(';', $row[5]);
                        $row7 = [];

                        foreach ($row6 as $row1) {

                            $row12 = explode('-', $row1);
                            switch ($row12[0]) {
                                case 1:
                                    $row7[1] = $row1;
                                    break;
                                case 2:
                                    $row7[2] = $row1;
                                    break;
                                case 3:
                                    $row7[3] = $row1;
                                    break;
                                case 4:
                                    $row7[4] = $row1;
                                    break;
                                case 5:
                                    $row7[5] = $row1;
                                    break;
                            }
                            ksort($row7);
                        }

                        $data[$row[0]]['labels'] = $row7;


                    }
                }

                $res = $data;
                foreach ($data as $key => $value) {
                    $data[$key]['campaigns'] = array();
                    $data[$key]['campaigns'][] = $key;
                    foreach ($res as $key_res => $value_res) {

                        if ($value !== $value_res) {

                            if ($value['labels'] === $value_res['labels']) {
                                $data[$key]['clicks'] += $value_res['clicks'];
                                $data[$key]['cost'] += $value_res['cost'];
                                $data[$key]['conversions'] += $value_res['conversions'];
                                $data[$key]['impressions'] += $value_res['impressions'];

                                $data[$key]['campaigns'][] += $value_res['campaigns'];
                            }
                        }
                    }
                    sort($data[$key]['campaigns']);

                }


                $arrayResult = array_unique($data, SORT_REGULAR);


                return $arrayResult;

            }

            }

    }

    public function extractFile($path, $arbitrary = null)
    {
        $archive = new ZipArchive();

        if ($archive->open($path) === TRUE) {
            if($arbitrary == 1){
                $archive->extractTo(public_path() . "/../app/ApiSources/double/bing/arbitrary/");
                $return = public_path() . "/../app/ApiSources/double/bing/arbitrary/" . $archive->statIndex(0)['name'];
                $archive->close();
            }else{
                $archive->extractTo(public_path() . "/../app/ApiSources/double/bing/");
                $return = public_path() . "/../app/ApiSources/double/bing/" . $archive->statIndex(0)['name'];
                $archive->close();
            }

        } else {
            throw new Exception ("Decompress operation from ZIP file failed.");
        }

        return $return;
    }


}