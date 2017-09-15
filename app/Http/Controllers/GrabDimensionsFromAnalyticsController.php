<?php

namespace App\Http\Controllers;

use App\Api\GoogleClient;
use Google_Service_Analytics;
use Google_Service_AnalyticsReporting;
use Google_Service_AnalyticsReporting_DateRange;
use Google_Service_AnalyticsReporting_GetReportsRequest;
use Google_Service_AnalyticsReporting_Metric;
use Google_Service_AnalyticsReporting_ReportRequest;
use Google_Service_Sheets;
use Google_Service_Sheets_ValueRange;

class GrabDimensionsFromAnalyticsController extends Controller
{
    protected $VIEW_ID = '101383010';
    
    protected $GOOGLE_SHEETS_ID = '1i1_0ewGNcXekSpLRx_C8UnKMOpHZMzHAZl7M0XDbL_I';
    
    protected $dimensions = [
        'ga:dimension1' => 'Category',
        'ga:dimension2' => 'Tag',
        'ga:dimension3' => 'Page',
    ];
    
    function getReport($analytics, $viewID = '101383010', $dimensionName = 'ga:dimension1')
    {
        $dateRange = new Google_Service_AnalyticsReporting_DateRange();
        $dateRange->setStartDate("7daysAgo");
        $dateRange->setEndDate("today");
        
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
    
    function printResults($reports)
    {
        for ($reportIndex = 0; $reportIndex < count($reports); $reportIndex++) {
            $report           = $reports[$reportIndex];
            $header           = $report->getColumnHeader();
            $dimensionHeaders = $header->getDimensions();
            $metricHeaders    = $header->getMetricHeader()->getMetricHeaderEntries();
            $rows             = $report->getData()->getRows();
            
            for ($rowIndex = 0; $rowIndex < count($rows); $rowIndex++) {
                $row        = $rows[$rowIndex];
                $dimensions = $row->getDimensions();
                $metrics    = $row->getMetrics();
                for ($i = 0; $i < count($dimensionHeaders) && $i < count($dimensions); $i++) {
                    print($dimensionHeaders[$i] . ": " . $dimensions[$i] . "\n");
                }
                
                for ($j = 0; $j < count($metricHeaders) && $j < count($metrics); $j++) {
                    $entry  = $metricHeaders[$j];
                    $values = $metrics[$j];
                    print("Metric type: " . $entry->getType() . "<br>");
                    for ($valueIndex = 0; $valueIndex < count($values->getValues()); $valueIndex++) {
                        $value = $values->getValues()[$valueIndex];
                        print($entry->getName() . ": " . $value . "<br>");
                    }
                }
            }
        }
    }
    
    function setToSheet($report, $sheet)
    {
        for ($reportIndex = 0; $reportIndex < count($reports); $reportIndex++) {
            $report           = $reports[$reportIndex];
            $header           = $report->getColumnHeader();
            $dimensionHeaders = $header->getDimensions();
            $metricHeaders    = $header->getMetricHeader()->getMetricHeaderEntries();
            $rows             = $report->getData()->getRows();
            
            for ($rowIndex = 0; $rowIndex < count($rows); $rowIndex++) {
                $row        = $rows[$rowIndex];
                $dimensions = $row->getDimensions();
                $metrics    = $row->getMetrics();
                for ($i = 0; $i < count($dimensionHeaders) && $i < count($dimensions); $i++) {
                    print($dimensionHeaders[$i] . ": " . $dimensions[$i] . "\n");
                }
                
                for ($j = 0; $j < count($metricHeaders) && $j < count($metrics); $j++) {
                    $entry  = $metricHeaders[$j];
                    $values = $metrics[$j];
                    print("Metric type: " . $entry->getType() . "<br>");
                    for ($valueIndex = 0; $valueIndex < count($values->getValues()); $valueIndex++) {
                        $value = $values->getValues()[$valueIndex];
                        print($entry->getName() . ": " . $value . "<br>");
                    }
                }
            }
        }
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
        
        
        $values = array(
            array(
                // Cell values ...
                'cell1',
                'cell2',
                'cell3',
            ),
            // Additional rows ...
            'row1',
            'row2',
            'row3',
        );
        $body   = new Google_Service_Sheets_ValueRange(array(
            'values' => $values,
        ));
        $body   = new Google_Service_Sheets_ValueRange();
        $range  = 'A2:M23';
        // Create the value range Object
        $valueRange = new Google_Service_Sheets_ValueRange();
        
        $asSpreadsheetRows = [
            [
                "Mickey",
                "Mouse " . rand(11111, 99999),
            ],
            [
                "Donald",
                "Duck",
            ],
        ];
        $body              = new Google_Service_Sheets_ValueRange(array(
            'values' => $asSpreadsheetRows,
        ));
        // Then you need to add some configuration
        // $conf   = ["valueInputOption" => "RAW"];
        $optParams   = ["valueInputOption" => "USER_ENTERED"];
        $result = $serviceSheets->spreadsheets_values->update($this->GOOGLE_SHEETS_ID, $range, $body, $optParams);
        dd($result);
        
        $responseSheet = $serviceSheets->spreadsheets_values->get($this->GOOGLE_SHEETS_ID, $range);
        
        $serviceAnalytics = new Google_Service_AnalyticsReporting($client->client);
        foreach ($this->dimensions as $dimension => $name) {
            $responseAnalyticsReport = $this->getReport($serviceAnalytics, $this->VIEW_ID, $dimension);
            $this->setToSheet($responseAnalyticsReport, $responseSheet);
            
            dd($this->VIEW_ID, $dimension, $name, $responseAnalyticsReport, $responseSheet);
        }
        
        // $this->printResults($response);
        
    }
}
