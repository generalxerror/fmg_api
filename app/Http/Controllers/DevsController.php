<?php

namespace App\Http\Controllers;

use App\Models\StoreApp;
use App\Models\Developer;
use Illuminate\Http\Request;

class DevsController extends Controller
{
    public function show($id)
    {
        $developer = Developer::find($id);

        if(!$developer) {
            return response()->json([
                'error_msg' => 'Developer not found'
            ], 404);
        }

        $apps = StoreApp::select('id', 'title', 'rating', 'developer_id', 'icon')
            ->withCount([
                'reports' => function ($query) {
                    $query->where('published', 1);
                }
            ])
            ->where('developer_id', $developer->id)
            ->orderBy('reports_count', 'DESC')
            ->having('reports_count', '>', 0)
            ->get();

        $total_reports = 0;
        foreach($apps as $app) {
            $total_reports += $app->reports_count;
        }

        return response()->json([
            "developer" => $developer,
            "apps" => $apps,
            "total_reports" => $total_reports
        ]);
    }
}
