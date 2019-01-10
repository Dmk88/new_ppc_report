<?php

namespace App\Http\Controllers;

use App\Api\GoogleClient;
use App\GoogleSheet as Sheet;
use Exception;
use Google;
use Google\AdsApi\AdWords\AdWordsSession;
use Google\AdsApi\AdWords\Reporting\v201809\ReportDownloader;
use Google\AdsApi\AdWords\ReportSettingsBuilder;
use Google_Service_Analytics;
use Google_Service_AnalyticsReporting;
use Google_Service_Sheets;
use Illuminate\Http\Request;
use Google\AdsApi\AdWords\AdWordsSessionBuilder;
use Google\AdsApi\AdWords\Reporting\v201809\DownloadFormat;
use Google\AdsApi\Common\ConfigurationLoader;
use Google\AdsApi\Common\OAuth2TokenBuilder;


class grabMarketingStatDouble extends Controller
{

    const PAGE_LIMIT = 500;
    public $input = [];
    public $inputArbitary = [];
    public $inputLabel = [];
    public $message = '';
    public $customers = [2682652198, 8826845921];

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
            self::grab($data['date-from'], $data['date-to']);
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


    public function getReportAdwords($customer_id, $during)
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

        file_put_contents(public_path() . "/../app/ApiSources/double/" . $customer_id . ".csv", $stringReport);


        $values = array_map('str_getcsv', file(public_path() . "/../app/ApiSources/double/" . $customer_id . ".csv"));

        $path = public_path() . "/../app/ApiSources/double/" . $customer_id . ".csv";
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
                    }
                    $data[$row[0]]['clicks'] += $row[1];
                    $data[$row[0]]['impressions'] += $row[2];
                    $data[$row[0]]['cost'] += $row[3];
                    $data[$row[0]]['conversions'] += $row[4];
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
                        }
                        ksort($row7);
                    }

                    $data[$row[0]]['labels'] = $row7;

                }
            }

            $res = $data;
            foreach ($data as $key => $value) {
                foreach ($res as $key_res => $value_res) {
                    if ($value !== $value_res) {

                        if ($value['labels'] === $value_res['labels']) {
                            $data[$key]['clicks'] += $value_res['clicks'];
                            $data[$key]['cost'] += $value_res['cost'];
                            $data[$key]['conversions'] += $value_res['conversions'];
                            $data[$key]['impressions'] += $value_res['impressions'];

                        }
                    }
                }
            }

            $arrayResult = array_unique($data, SORT_REGULAR);
            return $arrayResult;
        }

    }

    public function grab($date_from = Null, $date_to = Null)
    {
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
        }
        else{
            $CurrentSheet = 'Current Month';
            $during = date('Ym') . '01, ' . date('Ymd'); // This month
        }

        $rangeInputArbitary = $CurrentSheet . '!H4:K';
        $rangeLabel = $CurrentSheet . '!A4:G';
        $rangeArbitaryFrom = $CurrentSheet . '!' . 'E2';
        $rangeArbitaryTo = $CurrentSheet . '!' . 'F2';
        $rangeFromCurrent = $CurrentSheet . '!' . 'K2';

        //$ProcessingArbitary = isset($date_from) && !empty($date_from) && isset($date_to) && !empty($date_to) ? true : false;
        //$duringCurrent = date('Ym') . '01, ' . date('Ymd'); // This month

        foreach ($this->customers as $customer_id) {
            $data = self::getReportAdwords($customer_id, $during);

            foreach ($data as $value => $item) {
                if ($item['labels']) {
                    $Click = $item['clicks'];
                    $Cost = $item['cost'] / 1000000;
                    $Conversion = $item['conversions'];
                    $Impressions = $item['impressions'];

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
                    $customer_name = '';
                    if($customer_id == '2682652198'){
                        $customer_name = 'altoroslabs.com';
                    }elseif($customer_id == '8826845921'){
                        $customer_name = 'altoros.no';
                    }

                    array_push($this->inputLabel, array($customer_id,$customer_name, 'Adwords', $lab1, $lab2, $lab3, $lab4));
                    array_push($this->inputArbitary, array($Impressions, $Click, $Cost, $Conversion));
                }
            }
        }
        Sheet::deletePreviousValues($service, $spreadsheetId, $rangeLabel);
        Sheet::deletePreviousValues($service, $spreadsheetId, $rangeInputArbitary);

        if($ProcessingArbitary){
            Sheet::setToSheet($service, $spreadsheetId, $rangeArbitaryFrom, array(array(date("F j, Y", strtotime($date_from)))));
            Sheet::setToSheet($service, $spreadsheetId, $rangeArbitaryTo, array(array(date("F j, Y", strtotime($date_to)))));

        } else {
            $now = date("F j, Y H:i:s", strtotime('+3 hours'));
            Sheet::setToSheet($service, $spreadsheetId, $rangeFromCurrent, array(array('updated at '. $now)));
            Sheet::setToSheet($service, $spreadsheetId, $rangeArbitaryTo, array(array(date('F'))));

        }

        Sheet::setToSheet($service, $spreadsheetId, $rangeLabel, $this->inputLabel);
        Sheet::setToSheet($service, $spreadsheetId, $rangeInputArbitary, $this->inputArbitary);

    }

}