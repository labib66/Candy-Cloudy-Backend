<?php namespace Common\Admin\Analytics;

use Carbon\CarbonImmutable;
use Common\Admin\Analytics\Actions\BuildAnalyticsReport;
use Common\Admin\Analytics\Actions\BuildDemoAnalyticsReport;
use Common\Admin\Analytics\Actions\BuildGoogleAnalyticsReport;
use Common\Admin\Analytics\Actions\BuildNullAnalyticsReport;
use Common\Admin\Analytics\Actions\GetAnalyticsHeaderDataAction;
use Common\Core\BaseController;
use Common\Database\Metrics\MetricDateRange;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class AnalyticsController extends BaseController
{
    public function __construct(
        protected Request $request,
        protected BuildAnalyticsReport $getDataAction,
        protected GetAnalyticsHeaderDataAction $getHeaderDataAction,
    ) {
    }

    public function visitorsReport($selected = null)
    {
        $types = explode(',', $this->request->get('types', 'visitors'));
        $dateRange = $this->getDateRange();
        $cacheKey = sprintf(
            '%s-%s',
            $dateRange->getCacheKey(),
            implode(',', $types),
        );

        $response = [];
        $reportParams = ['dateRange' => $dateRange];
        if (in_array('visitors', $types)) {
            try {
                // $response['visitorsReport'] = Cache::remember(
                //     "adminReport.main.$cacheKey",
                //     CarbonImmutable::now()->addDay(),
                //     fn() => $this->getDataAction->execute($reportParams),
                // );
                $report = (new BuildGoogleAnalyticsReport())->execute($reportParams);
            } catch (Exception $e) {
                $response['visitorsReport'] = app(
                    BuildDemoAnalyticsReport::class,
                )->execute($reportParams);
            }
        }
        
        if ($selected) {
            if (isset($response['visitorsReport'][$selected])) {
                return $this->success($response['visitorsReport'][$selected]);
            } else {
                return $this->success($response['visitorsReport']);
            }
        }
            return $this->success($response['visitorsReport']);
    }

    // public function visitorsReport($selected = null)
    // {
    //     $types = explode(',', $this->request->get('types', 'visitors'));
    //     $dateRange = $this->getDateRange();
    //     $cacheKey = sprintf(
    //         '%s-%s',
    //         $dateRange->getCacheKey(),
    //         implode(',', $types),
    //     );
    
    //     $response = [];
    //     $reportParams = ['dateRange' => $dateRange];
    
    //     if (in_array('visitors', $types)) {
    //         try {
    //             $report = (new BuildGoogleAnalyticsReport())->execute($reportParams);
    //             $response['visitorsReport'] = $report;
    //         } catch (Exception $e) {
    //             $response['visitorsReport'] = app(BuildDemoAnalyticsReport::class)->execute($reportParams);
    //         }
    //     }
    //     return $this->success($response['visitorsReport'][$selected]);
    //     if ($selected) {
    //         if (isset($response['visitorsReport'][$selected])) {
    //             return $this->success($response['visitorsReport'][$selected]);
    //         } else {
    //             return $this->success($response['visitorsReport']);
    //         }
    //     }
    //         return $this->success($response['visitorsReport']);
    // }
    

    public function mainReport()
    {
        $types = explode(',', $this->request->get('types', 'header'));
        $dateRange = $this->getDateRange();
        $cacheKey = sprintf(
            '%s-%s',
            $dateRange->getCacheKey(),
            implode(',', $types),
        );
    
        $response = [];
        $reportParams = ['dateRange' => $dateRange];
        if (in_array('header', $types)) {
            $headerReport = Cache::remember(
                "adminReport.header.$cacheKey",
                CarbonImmutable::now()->addDay(),
                fn() => $this->getHeaderDataAction->execute($reportParams),
            );
            
            // إضافة عناوين رئيسية لكل مجموعة
            $response['headerReport'] = [
                'files' => array_map(function ($item) {
                    return [
                        'name' => $item['name'],
                        'currentValue' => $item['currentValue'],
                        'previousValue' => $item['previousValue'],
                        'Value' => $item['currentValue'] + $item['previousValue'],
                        'percentageChange' => $item['percentageChange'],
                    ];
                }, array_filter($headerReport, function ($item) {
                    return $item['name'] === 'New files';
                })),
                'folders' => array_map(function ($item) {
                    return [
                        'name' => $item['name'],
                        'currentValue' => $item['currentValue'],
                        'previousValue' => $item['previousValue'],
                        'Value' => $item['currentValue'] + $item['previousValue'],
                        'percentageChange' => $item['percentageChange'],
                    ];
                }, array_filter($headerReport, function ($item) {
                    return $item['name'] === 'New folders';
                })),
                'users' => array_map(function ($item) {
                    return [
                        'name' => $item['name'],
                        'currentValue' => $item['currentValue'],
                        'previousValue' => $item['previousValue'],
                        'Value' => $item['currentValue'] + $item['previousValue'],
                        'percentageChange' => $item['percentageChange'],
                    ];
                }, array_filter($headerReport, function ($item) {
                    return $item['name'] === 'New users';
                })),
                'space' => array_map(function ($item) {
                    return [
                        'name' => $item['name'],
                        'currentValue' => $item['currentValue'],
                        'previousValue' => $item['previousValue'],
                        'Value' => $item['currentValue'] + $item['previousValue'],
                        'percentageChange' => $item['percentageChange'],
                    ];
                }, array_filter($headerReport, function ($item) {
                    return $item['name'] === 'Total Space Used';
                })),
            ];
        }

        // return array_column($response['headerReport']['files'], 'previousValue');
    
        return $this->success($response);
    }
    
    
    protected function getCategory($name, $currentValue, $previousValue): string
    {
        // تصنيف بناءً على القيم
        if ($currentValue > $previousValue) {
            return 'increased'; // قيمة زادت
        } elseif ($currentValue < $previousValue) {
            return 'decreased'; // قيمة انخفضت
        } else {
            return 'no_change'; // لا تغيير
        }
    }
    

    public function report()
    {
        // $this->authorize('index', 'ReportPolicy');
        $types = explode(',', $this->request->get('types', 'visitors,header'));
        $dateRange = $this->getDateRange();
        $cacheKey = sprintf(
            '%s-%s',
            $dateRange->getCacheKey(),
            implode(',', $types),
        );

        $response = [];
        $reportParams = ['dateRange' => $dateRange];
        if (in_array('visitors', $types)) {
            try {
                $response['visitorsReport'] = Cache::remember(
                    "adminReport.main.$cacheKey",
                    CarbonImmutable::now()->addDay(),
                    fn() => $this->getDataAction->execute($reportParams),
                );
            } catch (Exception $e) {
                $response['visitorsReport'] = app(
                    BuildNullAnalyticsReport::class,
                )->execute($reportParams);
            }
        }
        if (in_array('header', $types)) {
            $response['headerReport'] = Cache::remember(
                "adminReport.header.$cacheKey",
                CarbonImmutable::now()->addDay(),
                fn() => $this->getHeaderDataAction->execute($reportParams),
            );
        }

        return $this->success($response);
    }

    protected function getDateRange(): MetricDateRange
    {
        $startDate = $this->request->get('startDate');
        $endDate = $this->request->get('endDate');
        $timezone = $this->request->get('timezone', config('app.timezone'));
      
        // تعيين تاريخ ثابت كبداية إذا لم يكن هناك تاريخ بداية محدد
       if (!$startDate) {
        $startDate = '2020-01-01';
       }

        // تعيين تاريخ اليوم الحالي كتاريخ نهاية افتراضي إذا لم يكن هناك تاريخ نهاية محدد
        if (!$endDate) {
            $endDate = CarbonImmutable::now()->toDateString();
        }
        return new MetricDateRange(
            start: $startDate,
            end: $endDate,
            timezone: $timezone,
        );
    }
}
