<?php

namespace App\Http\Controllers;

use App\Api\GoogleClient;
use App\GAReports;
use App\GAReportsCluster;
use App\GAReportsPosts;
use App\GAReportsSchedule;
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
use Exception;

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
        'avgSessionDuration' => '00:00:00',
    ];
    protected $summary;
    public $source_names = ['Direct', 'Organic Search', 'Social', 'Email', 'Affiliates', 'Referral', 'Paid Search', 'Other Advertising', 'Display', '(Other)'];
    public $sources = [];

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
        try {
            $reports = GAReports::all();

            return view('ga_reports.index', [
                'reports' => $reports,
            ]);
        } catch (Exception $e) {
            dd($e);
        }
    }

    public function show_add_form(Request $request)
    {
        $schedules = GAReportsSchedule::all();

        return view('ga_reports.ga_report_form_add', [
            'schedules' => $schedules,
        ]);
    }

    public function show_for_edit(Request $request)
    {
        $report = GAReports::whereId($request->id)->first();
        $schedules = GAReportsSchedule::all();

        return view('ga_reports.ga_report_edit', [
            'report' => $report,
            'schedules' => $schedules,
        ]);
    }

    public function edit(Request $request)
    {
        $this->validate($request, [
            'report_name' => 'required|max:70',
            'report_start_date_range' => 'required|max:10',
            'report_end_date_range' => 'required|max:10',
            'report_schedule' => 'required|max:10',
        ]);

        $report = GAReports::whereId($request->id)->first();
        $report->report_name = $request->report_name;
        $report->report_start_date_range = $request->report_start_date_range;
        $report->report_end_date_range = $request->report_end_date_range;
        $report->report_schedule_id = $request->report_schedule;
        $report->report_active = $request->report_active ? 1 : 0;
        $report->save();

        return redirect('/ga_reports');
    }

    public function add(Request $request)
    {
        $this->validate($request, [
            'report_name' => 'required|max:70',
            'report_start_date_range' => 'required|max:10',
            'report_end_date_range' => 'required|max:10',
            'report_schedule' => 'required|max:10',
        ]);

        $report = new GAReports();
        $report->report_name = $request->report_name;
        $report->report_start_date_range = $request->report_start_date_range;
        $report->report_end_date_range = $request->report_end_date_range;
        $report->report_schedule_id = $request->report_schedule;
        $report->report_active = $request->report_active ? 1 : 0;
        $report->save();

        return redirect('/ga_reports');
    }

    public function delete(Request $request)
    {
        $report = GAReports::whereId($request->id)->first();
        $report->delete();

        return redirect('/ga_reports');
    }

    public function show_posts(Request $request)
    {
        $posts = GAReportsPosts::all();
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
                $result = $this->CLUSTERS_COLUMN;
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

    /**
     * @param \Google_Service_AnalyticsReporting $analytics
     * @param string $viewID
     * @param string $dimensionName
     * @param string $dimensionFilterUrl
     * @return \Google_Service_AnalyticsReporting_Report
     */
    protected function getReport(Google_Service_AnalyticsReporting $analytics, $viewID = '101383010', $dimensionName = 'ga:pagePath', $dimensionFilterUrl = '/blog/')
    {
        $dateRange = new Google_Service_AnalyticsReporting_DateRange();
        $dateRange->setStartDate($this->GOOGLE_REPORT_START_DATE);
        $dateRange->setEndDate($this->GOOGLE_REPORT_END_DATE);

        $pageviews = new Google_Service_AnalyticsReporting_Metric();
        $pageviews->setExpression("ga:pageviews");
        $pageviews->setAlias("pageviews");

        $uniquePageviews = new Google_Service_AnalyticsReporting_Metric();
        $uniquePageviews->setExpression("ga:uniquePageviews");
        $uniquePageviews->setAlias("uniquePageviews");

        $bounceRate = new Google_Service_AnalyticsReporting_Metric();
        $bounceRate->setExpression("ga:bounceRate");
        $bounceRate->setAlias("bounceRate");

        $avgSessionDuration = new Google_Service_AnalyticsReporting_Metric();
        $avgSessionDuration->setExpression("ga:avgSessionDuration");
        $avgSessionDuration->setAlias("avgSessionDuration");

        $dimension1 = new \Google_Service_AnalyticsReporting_Dimension();
        $dimension1->setName('ga:channelGrouping');

        if (!is_array($dimensionFilterUrl)) $dimensionFilterUrl = [$dimensionFilterUrl];
        $dimensionFilters = [];
        foreach ($dimensionFilterUrl as $url) {
            $dimensionFilter = new Google_Service_AnalyticsReporting_DimensionFilter();
            $dimensionFilter->setDimensionName($dimensionName);
            $dimensionFilter->setOperator('PARTIAL');
            $dimensionFilter->setExpressions($url);
            $dimensionFilters[] = $dimensionFilter;
        }

        $dimensionFilterClause = new Google_Service_AnalyticsReporting_DimensionFilterClause();
        $dimensionFilterClause->setFilters($dimensionFilters);

        $request = new Google_Service_AnalyticsReporting_ReportRequest();
        $request->setViewId($viewID);
        $request->setDateRanges($dateRange);
        $request->setMetrics(array($pageviews, $uniquePageviews, $bounceRate, $avgSessionDuration));
        $request->setDimensions(array($dimension1));
        $request->setDimensionFilterClauses(array($dimensionFilterClause));

        $body = new Google_Service_AnalyticsReporting_GetReportsRequest();
        $body->setReportRequests(array($request));

        return $analytics->reports->batchGet($body)->getReports()[0];
    }


    protected function getTimeFromSeconds(int $seconds)
    {
        return gmdate('H:i:s', $seconds);
    }

    public function get(Request $request)
    {
        $result = ['message' => 'error'];
        $data = json_decode($request->getContent());
        if (empty($data->action)) {
            return json_encode($result);
        }
        if (!empty($data->start_date)) {
            $this->GOOGLE_REPORT_START_DATE = $data->start_date;
        }
        if (!empty($data->end_date)) {
            $this->GOOGLE_REPORT_END_DATE = $data->end_date;
        }

        $clusters = GAReportsCluster::all();
        if (empty($clusters)) {
            return json_encode($result);
        }

        $default = [
            'APPLICATION_NAME' => 'Google API',
            'CREDENTIALS_PATH' => app_path() . '/ApiSources/google-analytics.json',
            'CLIENT_SECRET_PATH' => app_path() . '/ApiSources/client_secret_analytics.json',
            'SCOPES' => array(
                Google_Service_Analytics::ANALYTICS_READONLY,
                Google_Service_Analytics::ANALYTICS,
                Google_Service_Analytics::ANALYTICS_EDIT,
                Google_Service_Analytics::ANALYTICS_PROVISION,
            ),
        ];

        $client = GoogleClient::get_instance($default);
        $serviceAnalytics = new Google_Service_AnalyticsReporting($client->client);
        $report = [];
        $this->summary = $this->null_stat;

        foreach ($this->source_names as $source) {
            $this->sources[$source] = $this->null_stat;
        }

        foreach ($this->dimensions as $k => $v) {
            /** @var GAReportsCluster $cluster */
            foreach ($clusters as $cluster) {
                $posts = $cluster->posts()->get();
                if (empty($posts) || $posts->isEmpty()) {
                    continue;
                }
                $posts_array = [];
                foreach ($posts as $post) {
                    $posts_array[] = $post->post_url;
                }

                $responseAnalyticsReport = $this->getReport($serviceAnalytics, $this->VIEW_ID, $k, $posts_array);

                $totals = $responseAnalyticsReport->getData()->getTotals()[0]["values"];

                $this->summary['pageviews'] = (int)$totals[0];
                $this->summary['uniquePageviews'] = (int)$totals[1];
                $this->summary['bounceRate'] = round($totals[2]);
                $this->summary['avgSessionDuration'] = $this->getTimeFromSeconds(round($totals[3]));

                /** @var \Google_Service_AnalyticsReporting_ReportRow $row */
                foreach ($responseAnalyticsReport->getData()->getRows() as $row) {
                    $dimension = $row->getDimensions()[0];
                    $metrics = $row->getMetrics()[0]->values;

                    $this->sources[$dimension]['pageviews'] = (int)$metrics[0];
                    $this->sources[$dimension]['uniquePageviews'] = (int)$metrics[1];
                    $this->sources[$dimension]['bounceRate'] = round($metrics[2]);
                    $this->sources[$dimension]['avgSessionDuration'] = $this->getTimeFromSeconds(round($metrics[3]));
                }

                array_push($report, [
                    'cluster' => $cluster->cluster_name,
                    'summary' => $this->summary,
                    'source' => $this->sources,
                ]);
                $this->summary = $this->null_stat;
                foreach ($this->source_names as $source) {
                    $this->sources[$source] = $this->null_stat;
                }
            }
        }

        if (!empty($report)) {
            $result['clusters'] = $report;
            $result['message'] = 'success';
        }

        return json_encode($result);
    }
}
