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
            ->paginate(10);

        return response()->json([
            "app" => $store_app,
            "reports" => $reports
        ]);
    }

    public function search(Request $request) {
        $request->validate([
            'search_query' => 'required|string|min:3'
        ]);

        $search_result = StoreApp::select('id', 'title', 'rating', 'developer_id', 'icon')
            ->where('title', 'like', '%'.$request->search_query.'%')
            ->orWhere('store_id', 'like', '%'.$request->search_query.'%')
            ->with([
                'developer' => function($query) {
                    $query->select('id', 'name');
                }
            ])
            ->withCount([
                'reports' => function($query) {
                    $query->where('published', 1);
                }
            ])
            ->orderBy('reports_count', 'DESC')
            ->having('reports_count', '>', 0)
            ->limit(50)
            ->get();

        return response()->json([
            "search_result" => $search_result
        ]);
    }

    public function extensionSearch(Request $request) {
        $request->validate([
            'search_query' => 'required|string|min:3'
        ]);

        $search_result = StoreApp::select('id', 'title')
            ->where('store_id', '=', $request->search_query)
            ->withCount([
                'reports' => function($query) {
                    $query->where('published', 1);
                }
            ])
            ->orderBy('reports_count', 'DESC')
            ->having('reports_count', '>', 0)
            ->first();

        return response()->json([
            "search_result" => $search_result
        ]);
    }
}
