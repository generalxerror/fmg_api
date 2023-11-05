<?php

namespace App\Http\Controllers;

use Illuminate\Support\Str;
use Nelexa\GPlay\GPlayApps;
use Illuminate\Http\Request;

class ScraperController extends Controller
{
    public function scrape(Request $request)
    {
        $request->validate([
            'search_query' => 'required|string|min:3'
        ]);

        $app_id = $request->search_query;

        $isUrl = Str::isUrl($request->search_query);
        if($isUrl) {
            $url_request = Request::create($request->search_query);
            $app_id = $url_request->query('id');
        }

        $gplay = new GPlayApps();

        try {
            $appInfo = $gplay->getAppInfo($app_id);

            return response()->json([
                'title'         => $appInfo->getName(),
                'store_id'      => $appInfo->getId(),
                'store_url'     => $appInfo->getUrl(),
                'icon'          => $appInfo->getIcon()->getUrl(),
                'rating'        => substr($appInfo->getScore(), 0, 3),
                'dev_name'      => $appInfo->getDeveloperName(),
                'dev_store_url' => $appInfo->getDeveloper()->getUrl()
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'error_msg' => 'App not found on the Play Store'
            ], 404);
        }
    }
}
