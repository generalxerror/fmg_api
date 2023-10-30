<?php

namespace App\Http\Controllers;

use App\Models\FakeAd;
use App\Models\Report;
use App\Models\StoreApp;
use App\Models\Developer;
use Nelexa\GPlay\GPlayApps;
use Illuminate\Http\Request;

class ReportsController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'app_id' => 'required|string|min:5',
            'comment' => 'required|string|min:10',
            'works_offline' => 'required|boolean',
            'fake_ad' => 'nullable|url'
        ]);

        $gplay = new GPlayApps();
        try {
            $appInfo = $gplay->getAppInfo($request->app_id);

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
                $app->rating        = $appInfo->getScore();
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
            if($request->fake_ad) {
                $new_fake_ad = new FakeAd();
                $new_fake_ad->url       = $request->fake_ad;
                $new_fake_ad->app_id    = $app->id;
                $new_fake_ad->save();
            }

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
}
