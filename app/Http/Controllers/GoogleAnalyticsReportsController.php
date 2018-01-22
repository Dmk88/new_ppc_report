<?php

namespace App\Http\Controllers;

use App\Api\GoogleClient;
use App\GAReportsCluster;
use App\GAReportsPosts;
use Google_Service_Analytics;
use Google_Service_AnalyticsReporting;
use Google_Service_AnalyticsReporting_DateRange;
use Google_Service_AnalyticsReporting_DimensionFilter;
use Google_Service_AnalyticsReporting_DimensionFilterClause;
use Google_Service_AnalyticsReporting_GetReportsRequest;
use Google_Service_AnalyticsReporting_Metric;
use Google_Service_AnalyticsReporting_ReportRequest;
use Illuminate\Http\Request;
use Yajra\Datatables\Datatables;


class GoogleAnalyticsReportsController extends Controller
{
    protected $VIEW_ID = '101383010';
    protected $dimensions = [
        'ga:pagePath' => 'Page Path',
    ];
    protected $null_stat = [
        'pageviews'          => 0,
        'uniquePageviews'    => 0,
        'bounceRate'         => 0,
        'avgSessionDuration' => 0,
    ];
    protected $summary;
    public $sources = [
        'Direct'            => [
            'pageviews'          => 0,
            'uniquePageviews'    => 0,
            'bounceRate'         => 0,
            'avgSessionDuration' => 0,
        ],
        'Organic Search'    => [
            'pageviews'          => 0,
            'uniquePageviews'    => 0,
            'bounceRate'         => 0,
            'avgSessionDuration' => 0,
        ],
        'Social'            => [
            'pageviews'          => 0,
            'uniquePageviews'    => 0,
            'bounceRate'         => 0,
            'avgSessionDuration' => 0,
        ],
        'Email'             => [
            'pageviews'          => 0,
            'uniquePageviews'    => 0,
            'bounceRate'         => 0,
            'avgSessionDuration' => 0,
        ],
        'Affiliates'        => [
            'pageviews'          => 0,
            'uniquePageviews'    => 0,
            'bounceRate'         => 0,
            'avgSessionDuration' => 0,
        ],
        'Referral'          => [
            'pageviews'          => 0,
            'uniquePageviews'    => 0,
            'bounceRate'         => 0,
            'avgSessionDuration' => 0,
        ],
        'Paid Search'       => [
            'pageviews'          => 0,
            'uniquePageviews'    => 0,
            'bounceRate'         => 0,
            'avgSessionDuration' => 0,
        ],
        'Other Advertising' => [
            'pageviews'          => 0,
            'uniquePageviews'    => 0,
            'bounceRate'         => 0,
            'avgSessionDuration' => 0,
        ],
        'Display'           => [
            'pageviews'          => 0,
            'uniquePageviews'    => 0,
            'bounceRate'         => 0,
            'avgSessionDuration' => 0,
        ],
        '(Other)'           => [
            'pageviews'          => 0,
            'uniquePageviews'    => 0,
            'bounceRate'         => 0,
            'avgSessionDuration' => 0,
        ],
    ];
    protected $post_count = 0;
    protected $GOOGLE_REPORT_METRIC_TIME_TYPE = "TIME";
    protected $GOOGLE_REPORT_START_DATE = "7daysAgo";
    
    protected $GOOGLE_REPORT_END_DATE = "today";
    // protected $CURRENT_POST_ID;
    protected $CLUSTERS;
    protected $CLUSTERS_COLUMN = '';
    protected $CHECKBOX = '<div class="post-cluster-block"><input %4$s type="checkbox" id="post-cluster-%1$d-%3$d" 
    name="post-cluster[%3$d][]" data-post="%3$d" value="%1$d" class="post-cluster"><label 
    for="post-cluster-%1$d-%3$d">%2$s</label></div>';
    protected $POST_URL = '<a href="%s">%s</a>';
    
    public function index(Request $request)
    {
        return view('ga_reports.index');
    }
    
    public function show_posts(Request $request)
    {
        $posts          = GAReportsPosts::all();
        $this->CLUSTERS = GAReportsCluster::all()->toArray();
        
        if ($request->ajax()) {
            return Datatables::of($posts)->addColumn('clusters', function ($post) {
                if (!empty($this->CLUSTERS)) {
                    foreach ($this->CLUSTERS as $cl) {
                        $checked = !empty($post->clusters()->where(["id" => $cl['id']])->get()->toArray());
                        $this->CLUSTERS_COLUMN .= sprintf($this->CHECKBOX, $cl['id'], $cl['cluster_name'], $post->id,
                            $checked ? 'checked' : '');
                    }
                }
                $result                = $this->CLUSTERS_COLUMN;
                $this->CLUSTERS_COLUMN = '';
                
                return $result;
                // $this->CURRENT_POST_ID = $post->id;
                //
                // return GAReportsPosts::whereId($post->id)->first()->clusters()->get()->map(function ($cluster) {
                //     return sprintf($this->CHECKBOX, $cluster->id, $cluster->cluster_name, $this->CURRENT_POST_ID, '');
                // })->implode(' ');
            })->addColumn('post_url', function ($post) {
                return sprintf($this->POST_URL, $_ENV['ALTOROS_BLOG_WP'] . $post->post_url, $post->post_name);
            })->rawColumns(['post_url', 'clusters'])->make(true);
        }
        
        return view('ga_reports.posts.index', [
            'posts' => $posts,
        ]);
    }
    
    function getReport(
        $analytics,
        $viewID = '101383010',
        $dimensionName = 'ga:pagePath',
        $dimensionFilterUrl = '/blog/'
    ) {
        $dateRange = new Google_Service_AnalyticsReporting_DateRange();
        $dateRange->setStartDate($this->GOOGLE_REPORT_START_DATE);
        $dateRange->setEndDate($this->GOOGLE_REPORT_END_DATE);
        
        // $sessions = new Google_Service_AnalyticsReporting_Metric();
        // $sessions->setExpression("ga:sessions");
        // $sessions->setAlias("sessions");
        
        $pageviews = new Google_Service_AnalyticsReporting_Metric();
        $pageviews->setExpression("ga:pageviews");
        $pageviews->setAlias("pageviews");
        
        $uniquePageviews = new Google_Service_AnalyticsReporting_Metric();
        $uniquePageviews->setExpression("ga:uniquePageviews");
        $uniquePageviews->setAlias("uniquePageviews");
        
        $bounceRate = new Google_Service_AnalyticsReporting_Metric();
        $bounceRate->setExpression("ga:bounceRate");
        $bounceRate->setAlias("bounceRate");
        
        // $newUsers = new Google_Service_AnalyticsReporting_Metric();
        // $newUsers->setExpression("ga:newUsers");
        // $newUsers->setAlias("newUsers");
        
        $avgSessionDuration = new Google_Service_AnalyticsReporting_Metric();
        $avgSessionDuration->setExpression("ga:avgSessionDuration");
        $avgSessionDuration->setAlias("avgSessionDuration");
        
        $dimension1 = new \Google_Service_AnalyticsReporting_Dimension();
        $dimension1->setName($dimensionName);
        
        $dimension2 = new \Google_Service_AnalyticsReporting_Dimension();
        $dimension2->setName('ga:channelGrouping');
        
        $dimensionFilter = new Google_Service_AnalyticsReporting_DimensionFilter();
        $dimensionFilter->setDimensionName($dimensionName);
        $dimensionFilter->setOperator('PARTIAL');
        $dimensionFilter->setExpressions(array($dimensionFilterUrl));
        
        $dimensionFilterClause = new Google_Service_AnalyticsReporting_DimensionFilterClause();
        $dimensionFilterClause->setFilters(array($dimensionFilter));
        
        $request = new Google_Service_AnalyticsReporting_ReportRequest();
        $request->setViewId($viewID);
        $request->setDateRanges($dateRange);
        $request->setMetrics(array($pageviews, $uniquePageviews, $bounceRate, $avgSessionDuration));
        $request->setDimensions(array($dimension1, $dimension2));
        $request->setDimensionFilterClauses(array($dimensionFilterClause));
        
        $body = new Google_Service_AnalyticsReporting_GetReportsRequest();
        $body->setReportRequests(array($request));
        
        return $analytics->reports->batchGet($body);
    }
    
    protected function getTimeFromSeconds($seconds)
    {
        return gmdate('H:i:s', $seconds);
    }
    
    protected function getReportRows($report)
    {
        $reportRows        = [];
        $header            = $report->getColumnHeader();
        $dimensionHeaders  = $header->getDimensions();
        $metricHeaders     = $header->getMetricHeader()->getMetricHeaderEntries();
        $rows              = $report->getData()->getRows();
        $reportRowsHeaders = [];
        // if (!empty($header)) {
        //     array_push($reportRowsHeaders, $header[0] . ' name: ' . $this->dimensions[$header[0]]);
        // }
        // foreach ($metricHeaders as $metricHeader) {
        //     array_push($reportRowsHeaders, 'Metric name: ' . $metricHeader->name . ' [' . $metricHeader->type . ']');
        // }
        
        // array_push($reportRows, $reportRowsHeaders);
        
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
                                // $metricValue = $this->getTimeFromSeconds($metricValue);
                                $metricValue = $metricValue;
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
    
    protected function take_summary($report_values, $c = 100)
    {
        if (empty($report_values)) {
            return null;
        }
        foreach ($report_values as $key => $report_value) {
            // if($c == 1 && $key == 3 ){
            //
            //     $this->sources[$report_value[1]]['pageviews'] += (int)$report_value[2];
            //     $this->sources[$report_value[1]]['uniquePageviews'] += (int)$report_value[3];
            //     $this->sources[$report_value[1]]['bounceRate'] += (float)$report_value[4];
            //     $this->sources[$report_value[1]]['avgSessionDuration'] += (float)$report_value[5];
            //     dd($this->sources, $report_value, $key);
            // }
            $this->summary['pageviews'] += $report_value[2];
            $this->summary['uniquePageviews'] += $report_value[3];
            $this->summary['bounceRate'] += $report_value[4];
            $this->summary['avgSessionDuration'] += $report_value[5];
            // if (!in_array($report_value[1], $this->sources)) {
            //     // array_push($this->sources, $report_value[1]);
            //     $this->sources[$report_value[1]]['pageviews']          = (int)$report_value[2];
            //     $this->sources[$report_value[1]]['uniquePageviews']    = (int)$report_value[3];
            //     $this->sources[$report_value[1]]['bounceRate']         = (float)$report_value[4];
            //     $this->sources[$report_value[1]]['avgSessionDuration'] = (float)$report_value[5];
            // }
            // dd($this->sources[$report_value[1]]['pageviews'], (int)$report_value[2], ($this->sources[$report_value[1]]['pageviews'] +
            //     (int)$report_value[2]));
            // var_dump($report_value[1]);
            // $int = $this->sources[$report_value[1]]['pageviews'] + (int)$report_value[2];
            // var_dump($int, $this->sources[$report_value[1]]['pageviews'], $report_value[2]);
            // if($c == 1 && $key == 1){
            // if($c == 2 ){
            //     dd($this->sources, $report_value, $key);
            //     $this->sources[$report_value[1]]['pageviews'] += (int)$report_value[2];
            //     $this->sources[$report_value[1]]['uniquePageviews'] += (int)$report_value[3];
            //     $this->sources[$report_value[1]]['bounceRate'] += (float)$report_value[4];
            //     $this->sources[$report_value[1]]['avgSessionDuration'] += (float)$report_value[5];
            //
            // }
            
            $this->sources[$report_value[1]]['pageviews'] += (int)$report_value[2];
            $this->sources[$report_value[1]]['uniquePageviews'] += (int)$report_value[3];
            $this->sources[$report_value[1]]['bounceRate'] += (float)$report_value[4];
            $this->sources[$report_value[1]]['avgSessionDuration'] += (float)$report_value[5];
            
        }
    }
    
    public function get(Request $request)
    {
        $result = ['message' => 'error'];
        $data   = json_decode($request->getContent());
        if (empty($data->action)) {
            return json_encode($result);
        }
        if (empty($data->start_date)) {
            $this->GOOGLE_REPORT_START_DATE = $data->start_date;
        }
        if (empty($data->end_date)) {
            $this->GOOGLE_REPORT_END_DATE = $data->end_date;
        }
        
        $clusters = GAReportsCluster::all();
        if (empty($clusters)) {
            return json_encode($result);
        }
        
        $default = [
            'APPLICATION_NAME'   => 'Google API',
            'CREDENTIALS_PATH'   => app_path() . '/ApiSources/google-analytics.json',
            'CLIENT_SECRET_PATH' => app_path() . '/ApiSources/client_secret_analytics.json',
            'SCOPES'             => array(
                Google_Service_Analytics::ANALYTICS_READONLY,
                Google_Service_Analytics::ANALYTICS,
                Google_Service_Analytics::ANALYTICS_EDIT,
                Google_Service_Analytics::ANALYTICS_PROVISION,
            ),
        ];
        $client  = GoogleClient::get_instance($default);
        
        $serviceAnalytics = new Google_Service_AnalyticsReporting($client->client);
        $report           = [];
        $this->summary    = $this->null_stat;
        foreach ($this->sources as $source) {
            $source = $this->null_stat;
        }
        foreach ($this->dimensions as $k => $v) {
            foreach ($clusters as $cluster) {
                $posts = $cluster->posts()->get();
                if (empty($posts)) {
                    continue;
                }
                foreach ($posts as $post) {
                    // dd($serviceAnalytics, $this->VIEW_ID, $k, $post->post_url);
                    // if ($ccc == 1) {
                    //     dd($serviceAnalytics, $this->VIEW_ID, $k, $post->post_url);
                    //     dd($this->sources, $spreadsheetRows);
                    //     dd($posts, $this->sources, $this->summary);
                    // }
                    $responseAnalyticsReport = $this->getReport($serviceAnalytics, $this->VIEW_ID, $k, $post->post_url);
                    $spreadsheetRows         = $this->getReportRows($responseAnalyticsReport[0]);
                    $this->take_summary($spreadsheetRows, $this->post_count);
                    $this->post_count++;
                }
                $this->summary['bounceRate']         = round($this->summary['bounceRate'] / $this->post_count, 2);
                $this->summary['avgSessionDuration'] = $this->getTimeFromSeconds(round($this->summary['avgSessionDuration'] / $this->post_count));
                foreach ($this->sources as $key_s => $source) {
                    $this->sources[$key_s]['bounceRate']         = round($this->sources[$key_s]['bounceRate'] / $this->post_count,
                        2);
                    $this->sources[$key_s]['avgSessionDuration'] = $this->getTimeFromSeconds(round($this->sources[$key_s]['avgSessionDuration'] / $this->post_count));
                }
                array_push($report, [
                    'cluster' => $cluster->cluster_name,
                    'summary' => $this->summary,
                    'source'  => $this->sources,
                ]);
                $this->summary = $this->null_stat;
                foreach ($this->sources as $key_d => $source) {
                    $this->sources[$key_d] = $this->null_stat;
                }
                $this->post_count = 0;
            }
        }
        // dd($report);
        if (!empty($report)) {
            $result['clusters'] = $report;
            $result['message']  = 'success';
        }
        
        return json_encode($result);
        
    }
}
