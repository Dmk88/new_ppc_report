<?php

namespace App\Http\Controllers;

use App\Api\GoogleClient;
use Carbon\Carbon;
use Google_Service_Analytics;
use Google_Service_AnalyticsReporting;
use Google_Service_AnalyticsReporting_DateRange;
use Google_Service_AnalyticsReporting_GetReportsRequest;
use Google_Service_AnalyticsReporting_Metric;
use Google_Service_AnalyticsReporting_ReportRequest;
use Google_Service_Sheets;
use Google_Service_Sheets_ClearValuesRequest;
use Google_Service_Sheets_ValueRange;

class GrabDimensionsFromAnalyticsController extends Controller
{
    protected $VIEW_ID = '101383010';
    
    protected $GOOGLE_SHEETS_ID = '1i1_0ewGNcXekSpLRx_C8UnKMOpHZMzHAZl7M0XDbL_I';
    
    protected $GOOGLE_SHEETS_RANGE = 'A1:Z';
    
    protected $GOOGLE_SHEETS_DEFAULT_ROW = 1;
    
    protected $GOOGLE_SHEETS_CURRENT_ROW = 1;
    
    // protected $GOOGLE_REPORT_START_DATE = "7daysAgo";
    protected $GOOGLE_REPORT_START_DATE = "2017-12-01";
    
    protected $GOOGLE_REPORT_END_DATE = "2017-12-31";
    // protected $GOOGLE_REPORT_END_DATE = "today";
    
    protected $GOOGLE_REPORT_METRIC_TIME_TYPE = "TIME";
    
    protected $dimensions = [
        'ga:dimension1' => 'Category',
        'ga:dimension2' => 'Tag',
        'ga:dimension3' => 'Page',
    ];
    
    function getReport($analytics, $viewID = '101383010', $dimensionName = 'ga:dimension1')
    {
        $dateRange = new Google_Service_AnalyticsReporting_DateRange();
        $dateRange->setStartDate($this->GOOGLE_REPORT_START_DATE);
        $dateRange->setEndDate($this->GOOGLE_REPORT_END_DATE);
        
        $sessions = new Google_Service_AnalyticsReporting_Metric();
        $sessions->setExpression("ga:sessions");
        $sessions->setAlias("sessions");
        
        $pageviews = new Google_Service_AnalyticsReporting_Metric();
        $pageviews->setExpression("ga:pageviews");
        $pageviews->setAlias("pageviews");
        
        $bounceRate = new Google_Service_AnalyticsReporting_Metric();
        $bounceRate->setExpression("ga:bounceRate");
        $bounceRate->setAlias("bounceRate");
        
        $newUsers = new Google_Service_AnalyticsReporting_Metric();
        $newUsers->setExpression("ga:newUsers");
        $newUsers->setAlias("newUsers");
        
        $avgSessionDuration = new Google_Service_AnalyticsReporting_Metric();
        $avgSessionDuration->setExpression("ga:avgSessionDuration");
        $avgSessionDuration->setAlias("avgSessionDuration");
        
        $dimension = new \Google_Service_AnalyticsReporting_Dimension();
        $dimension->setName($dimensionName);
        
        $request = new Google_Service_AnalyticsReporting_ReportRequest();
        $request->setViewId($viewID);
        $request->setDateRanges($dateRange);
        $request->setMetrics(array($sessions, $newUsers, $bounceRate, $avgSessionDuration));
        $request->setDimensions($dimension);
        
        $body = new Google_Service_AnalyticsReporting_GetReportsRequest();
        $body->setReportRequests(array($request));
        
        return $analytics->reports->batchGet($body);
    }
    
    protected function getTimeFromSeconds($seconds)
    {
        return gmdate('H:i:s', $seconds);
    }
    
    protected function clearSheet($service)
    {
        $requestBody = new Google_Service_Sheets_ClearValuesRequest();
        
        $response                        = $service->spreadsheets_values->clear($this->GOOGLE_SHEETS_ID,
            $this->GOOGLE_SHEETS_RANGE, $requestBody);
        $this->GOOGLE_SHEETS_CURRENT_ROW = $this->GOOGLE_SHEETS_DEFAULT_ROW;
        
        return $response;
    }
    
    protected function addClearRowToSheet($service)
    {
        $body      = new Google_Service_Sheets_ValueRange(array(
            'values' => [],
        ));
        $range     = 'A' . $this->GOOGLE_SHEETS_CURRENT_ROW . ':Z';
        $optParams = ["valueInputOption" => "USER_ENTERED"];
        $result    = $service->spreadsheets_values->update($this->GOOGLE_SHEETS_ID, $range, $body, $optParams);
        if ($result) {
            $this->GOOGLE_SHEETS_CURRENT_ROW = $this->GOOGLE_SHEETS_CURRENT_ROW + 1;
        }
        
        return $result;
    }
    
    protected function setToSheet($service, $spreadsheetRows)
    {
        $body      = new Google_Service_Sheets_ValueRange(array(
            'values' => $spreadsheetRows,
        ));
        $range     = 'A' . $this->GOOGLE_SHEETS_CURRENT_ROW . ':Z';
        $optParams = ["valueInputOption" => "USER_ENTERED"];
        $result    = $service->spreadsheets_values->update($this->GOOGLE_SHEETS_ID, $range, $body, $optParams);
        if ($result) {
            $this->GOOGLE_SHEETS_CURRENT_ROW = $this->GOOGLE_SHEETS_CURRENT_ROW + count($spreadsheetRows);
        }
        
        return $result;
    }
    
    protected function setReportToSheet($service, $spreadsheetRows)
    {
        $body      = new Google_Service_Sheets_ValueRange(array(
            'values' => $spreadsheetRows,
        ));
        $range     = 'A' . $this->GOOGLE_SHEETS_CURRENT_ROW . ':Z';
        $optParams = ["valueInputOption" => "USER_ENTERED"];
        $result    = $service->spreadsheets_values->update($this->GOOGLE_SHEETS_ID, $range, $body, $optParams);
        if ($result) {
            $this->GOOGLE_SHEETS_CURRENT_ROW = $this->GOOGLE_SHEETS_CURRENT_ROW + count($spreadsheetRows);
        }
        
        return $result;
    }
    
    protected function getReportRows($report)
    {
        $reportRows        = [];
        $header            = $report->getColumnHeader();
        $dimensionHeaders  = $header->getDimensions();
        $metricHeaders     = $header->getMetricHeader()->getMetricHeaderEntries();
        $rows              = $report->getData()->getRows();
        $reportRowsHeaders = [];
        if (!empty($header)) {
            array_push($reportRowsHeaders, $header[0] . ' name: ' . $this->dimensions[$header[0]]);
        }
        foreach ($metricHeaders as $metricHeader) {
            array_push($reportRowsHeaders, 'Metric name: ' . $metricHeader->name . ' [' . $metricHeader->type . ']');
        }
        
        array_push($reportRows, $reportRowsHeaders);
        
        foreach ($rows as $index => $row) {
            $reportRow = [];
            if (!empty($row->dimensions)) {
                foreach ($row->dimensions as $dimension) {
                    array_push($reportRow, $dimension);
                }
            }
            $metrics = $row->getMetrics();
            if (!empty($metrics)) {
                foreach ($metrics as $metric) {
                    $metricValues = $metric->getValues();
                    if (!empty($metricValues)) {
                        foreach ($metricValues as $number => $metricValue) {
                            if ($metricHeaders[$number]->type === $this->GOOGLE_REPORT_METRIC_TIME_TYPE) {
                                $metricValue = $this->getTimeFromSeconds($metricValue);
                            }
                            array_push($reportRow, $metricValue);
                        }
                    }
                }
            }
            array_push($reportRows, $reportRow);
        }
        
        return $reportRows;
    }
    
    public function grab()
    {
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
        $client  = GoogleClient::get_instance($default);
        
        $serviceSheets = new Google_Service_Sheets($client->client);
        
        $this->clearSheet($serviceSheets);
        $dates = [
            [
                // 'Start date: ' . (new Carbon($this->GOOGLE_REPORT_START_DATE))->format('M d, Y'),
                // 'End date: ' . (new Carbon($this->GOOGLE_REPORT_END_DATE))->format('M d, Y'),
                'Start date: ' . $this->GOOGLE_REPORT_START_DATE,
                'End date: ' . $this->GOOGLE_REPORT_END_DATE,
            ],
        ];
        $this->setToSheet($serviceSheets, $dates);
        
        $this->addClearRowToSheet($serviceSheets);
        
        $serviceAnalytics = new Google_Service_AnalyticsReporting($client->client);
        foreach ($this->dimensions as $dimension => $name) {
            $responseAnalyticsReport = $this->getReport($serviceAnalytics, $this->VIEW_ID, $dimension);
            $spreadsheetRows         = $this->getReportRows($responseAnalyticsReport[0]);
            $this->setToSheet($serviceSheets, $spreadsheetRows);
            $this->addClearRowToSheet($serviceSheets);
        }
    }
}
