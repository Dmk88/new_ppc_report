<?php

namespace App\Http\Controllers;

use App\Api\GoogleClient;
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
use Google\AdsApi\AdWords\v201809\cm\CampaignService;
use Google\AdsApi\AdWords\v201809\cm\OrderBy;
use Google\AdsApi\AdWords\v201809\cm\Paging;
use Google\AdsApi\AdWords\v201809\cm\Selector;
use Google\AdsApi\AdWords\v201809\cm\SortOrder;

use Google\AdsApi\AdWords\v201809\cm\AdGroupService;
use Google\AdsApi\AdWords\v201809\cm\Predicate;
use Google\AdsApi\AdWords\v201809\cm\PredicateOperator;
use Microsoft\BingAds\V12\CampaignManagement\Campaign;


class grabAdwordsData extends Controller
{

    public $inputArbitaryEvents = [];
    public $inputArbitaryUsers = [];

    const PAGE_LIMIT = 500;
    public $input = [];
    public $inputArbitary = [];
    public $inputLabel = [];
    public $message = '';
    public $customers = [2682652198];
    public $customersAdword = [2682652198, 4130241238, 8826845921, 3112040897];


    public function getReportAdwords($customer_id)
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

    $adWordsServices = new Google\AdsApi\AdWords\AdWordsServices();

    // Create report query
    $campaignService = $adWordsServices->get($session, CampaignService::class);

    // Create selector.
    $selector = new Selector();
    $selector->setFields(['Id', 'Name']);
    $selector->setOrdering([new OrderBy('Name', SortOrder::ASCENDING)]);
    $selector->setPaging(new Paging(0, self::PAGE_LIMIT));

    $totalNumEntries = 0;
    $campaigns_all = [];
    do {
        // Make the get request.
        $page = $campaignService->get($selector);

        // Display results.
        if ($page->getEntries() !== null) {
            $totalNumEntries = $page->getTotalNumEntries();
            foreach ($page->getEntries() as $campaign) {
                $campaigns_all[] = array( $campaign->getName(), $customer_id, $campaign->getId());
            }
        }

        // Advance the paging index.
        $selector->getPaging()->setStartIndex(
            $selector->getPaging()->getStartIndex() + self::PAGE_LIMIT
        );
    } while ($selector->getPaging()->getStartIndex() < $totalNumEntries);

    return $campaigns_all;

}


    public function getReportAdwordsAdGroup($customer_id = 2682652198)
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

        $adWordsServices = new Google\AdsApi\AdWords\AdWordsServices();

        // Create report query
        $campaignService = $adWordsServices->get($session, CampaignService::class);

        $adGroupService = $adWordsServices->get($session, AdGroupService::class);

        // Create a selector to select all ad groups for the specified campaign.
        $selector = new Selector();
        $selector->setFields(['Id', 'Name', 'CampaignId', 'CampaignName']);
        $selector->setOrdering([new OrderBy('Name', SortOrder::ASCENDING)]);

        $selector->setPaging(new Paging(0, self::PAGE_LIMIT));

        $totalNumEntries = 0;
        $campaigns_all = [];
        do {
            // Retrieve ad groups one page at a time, continuing to request pages
            // until all ad groups have been retrieved.
            $page = $adGroupService->get($selector);

            // Print out some information for each ad group.
            if ($page->getEntries() !== null) {
                $totalNumEntries = $page->getTotalNumEntries();
                foreach ($page->getEntries() as $adGroup) {
                    $campaigns_all[] = array($adGroup->getCampaignName(),$adGroup->getCampaignId(), $adGroup->getName(), $adGroup->getId(), $customer_id);
                }
            }

            $selector->getPaging()->setStartIndex(
                $selector->getPaging()->getStartIndex() + self::PAGE_LIMIT
            );
        } while ($selector->getPaging()->getStartIndex() < $totalNumEntries);

        return $campaigns_all;

    }


    public function grab(Request $request)
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

        $spreadsheetId = '1Kmzh51JHpxUTUpq0in-tHA86lTXEhggHIom_rl7T8sk'; //test
        //$spreadsheetId = '1iYsK6a3OONNS9dTIYC5i4Jkof52ieCsCz2VrUljFxn8'; //надо выгружать в этот спредшит

        $range = 'AdwordsCampaigns!A2:C';
        $array = array();
        foreach($this->customersAdword as &$customer){
            array_push($array, $this->getReportAdwords($customer));
        }
        $campaign_all = array();
        $campaign_all = array_merge($array[0], $array[1], $array[2], $array[3]);
        //var_dump($campaign_all);
        Sheet::setToSheet($service, $spreadsheetId, $range, $campaign_all);


        $rangeAdGroup = 'AdwordsAdGroups!A2:E';
        $arrayAdgroup = array();
        foreach($this->customersAdword as &$customer){
            array_push($arrayAdgroup, $this->getReportAdwordsAdGroup($customer));
        }
        $adgroups_all = array();
        $adgroups_all = array_merge($arrayAdgroup[0], $arrayAdgroup[1], $arrayAdgroup[2], $arrayAdgroup[3]);
        //var_dump($campaign_all);
        Sheet::setToSheet($service, $spreadsheetId, $rangeAdGroup, $adgroups_all);

    }
}