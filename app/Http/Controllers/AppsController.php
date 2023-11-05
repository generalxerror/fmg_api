<?php

namespace App\Http\Controllers;

use App\Models\Report;
use App\Models\StoreApp;
use Illuminate\Http\Request;

class AppsController extends Controller
{
    public function show($id)
    {
        $store_app = StoreApp::find($id);

        if(!$store_app) {
            return response()->json([
                'error_msg' => 'App not found'
            ], 404);
        }

        $store_app->load('developer');
        $store_app->load('fakeAds');

        $reports = Report::select()
            ->where('app_id', $store_app->id)
            ->where('published', 1)
            ->orderBy('created_at', 'DESC')
            ->get();

        return response()->json([
            "app" => $store_app,
            "reports" => $reports
        ]);
    }
}
