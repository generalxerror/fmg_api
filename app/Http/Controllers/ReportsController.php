<?php

namespace App\Http\Controllers;

use App\Models\FakeAd;
use App\Models\Report;
use App\Models\StoreApp;
use App\Models\Developer;
use Nelexa\GPlay\GPlayApps;
use Illuminate\Http\Request;
use Spatie\SlackAlerts\Facades\SlackAlert;

class ReportsController extends Controller
{
    public function mine(Request $request) {
        $my_reports = Report::select('id', 'comment', 'works_offline', 'published', 'app_id', 'created_at')
            ->where('author_id', $request->user()->id)
            ->with('storeApp', function($query) {
                $query->select('id', 'title');
            })
            ->orderBy('created_at', 'DESC')
            ->paginate(10);

        return response()->json([
            'my_reports' => $my_reports
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'app_id' => 'required|string|min:5',
            'comment' => 'required|string|min:10',
            'works_offline' => 'required|boolean',
            'fake_ad' => 'nullable|string|size:11'
        ]);

        $gplay = new GPlayApps();
        try {
            $appInfo = $gplay->getAppInfo($request->app_id);

            $existing_report = StoreApp::select('reports.published')
                ->join('reports', 'reports.app_id', '=', 'apps.id')
                ->where('apps.store_id', $request->app_id)
                ->where('reports.author_id', $request->user()->id)
                ->whereIn('reports.published', [1, 0])
                ->first();

            if($existing_report) {
                return response()->json([
                    'error_msg' => 'You already reported this app.'
                ], 422);
            }

            // DEVELOPER
            $developer = Developer::where('store_id', $appInfo->getDeveloper()->getId())->first();
            if(!$developer) {
                $developer              = new Developer();
                $developer->store_id    = $appInfo->getDeveloper()->getId();
                $developer->name        = $appInfo->getDeveloperName();
                $developer->store_url   = $appInfo->getDeveloper()->getUrl();
                $developer->website     = $appInfo->getDeveloper()->getWebsite();
                $developer->email       = $appInfo->getDeveloper()->getEmail();
                $developer->save();
            }

            // APP
            $app = StoreApp::where('store_id', $appInfo->getId())->first();
            if(!$app) {
                $app = new StoreApp();
                $app->title         = $appInfo->getName();
                $app->store_id      = $appInfo->getId();
                $app->store_url     = $appInfo->getUrl();
                $app->icon          = $appInfo->getIcon()->getUrl();
                $app->rating        = substr($appInfo->getScore(), 0, 3);
                $app->developer_id  = $developer->id;
                $app->save();
            }

            // REPORT
            $new_report = new Report();
            $new_report->comment        = $request->comment;
            $new_report->works_offline  = $request->works_offline;
            $new_report->app_id         = $app->id;
            $new_report->author_id      = $request->user()->id;
            $new_report->save();

            // FAKE AD
            $new_fake_ad = null;
            if($request->fake_ad) {
                $new_fake_ad = new FakeAd();
                $new_fake_ad->url       = 'https://www.youtube.com/embed/'.$request->fake_ad;
                $new_fake_ad->app_id    = $app->id;
                $new_fake_ad->save();
            }

            $this->sendSlackReportModeration($new_report, $app, $new_fake_ad);

            return response()->json([
                'message' => 'Thank you! Your report is in review'
            ]);
        } catch (\Throwable $th) {
            // dd($th);
            return response()->json([
                'error_msg' => 'Something went wrong. Try again later.'
            ], 404);
        }
    }

    public function sendSlackReportModeration(Report $report, StoreApp $app, $fake_ad)
    {
        SlackAlert::blocks([
            [
                "type" => "section",
                "text" => [
                    "type" => "mrkdwn",
                    "text" => "*New Report Added*"
                ]
            ],
            [
                "type" => "section",
                "fields" => [
                    [
                        "type" => "mrkdwn",
                        "text" => "*APP:*\n*<".$app->store_url."|".$app->title.">*"
                    ],
                    [
                        "type" => "mrkdwn",
                        "text" => "*Works Offline:*\n".($report->works_offline ? 'Yes' : 'No')
                    ],
                    [
                        "type" => "mrkdwn",
                        "text" => "*Fake Ad:*\n".($fake_ad ? "*<".$fake_ad->url."|".$fake_ad->url.">*" : '-')
                    ]
                ]
            ],
            [
                "type" => "section",
                "text" => [
                    "type" => "mrkdwn",
                    "text" => "*Comment:*\n".$report->comment
                ]
            ],
            [
                "type" => "actions",
                "elements" => [
                    [
                        "type" => "button",
                        "text" => [
                            "type" => "plain_text",
                            "emoji" => true,
                            "text" => "Publish"
                        ],
                        "style" => "primary",
                        "value" => $report->id."_approved"
                    ],
                    [
                        "type" => "button",
                        "text" => [
                            "type" => "plain_text",
                            "emoji" => true,
                            "text" => "Reject"
                        ],
                        "style" => "danger",
                        "value" => $report->id."_rejected"
                    ]
                ]
            ]
        ]);
    }
}
