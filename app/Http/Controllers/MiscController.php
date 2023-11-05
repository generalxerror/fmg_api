<?php

namespace App\Http\Controllers;

use App\Models\Report;
use App\Models\StoreApp;
use Illuminate\Http\Request;

class MiscController extends Controller
{
    public function getMainPageItems()
    {
        $latest_reports = Report::
            select('id', 'comment', 'app_id', 'created_at')
            ->with([
                'storeApp' => function($query) {
                    $query->select('id', 'developer_id', 'title', 'rating', 'icon');
                },
                'storeApp.developer' => function($query) {
                    $query->select('id', 'name');
                }
            ])
            ->where('published', 1)
            ->orderBy('created_at', 'DESC')
            ->limit(6)
            ->get();

        $worst_apps = StoreApp::select('id', 'title', 'rating', 'developer_id', 'icon')
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
            ->limit(6)
            ->get();

        $worst_devs = [];
        foreach($worst_apps as $wa) {
            $ids    = array_column($worst_devs, 'id');
            $index  = array_search($wa['developer_id'], $ids);

            if($index !== false) {
                $worst_devs[$index]['apps_count']++;
                $worst_devs[$index]['reports_count'] += $wa->reports_count;
            } else {
                $worst_devs[] = [
                    "id" => $wa['developer_id'],
                    "name" => $wa->developer->name,
                    "apps_count" => 1,
                    "reports_count" => $wa->reports_count
                ];
            }
        }

        return response()->json([
            "latest_reports" => $latest_reports,
            "worst_apps" => $worst_apps,
            "worst_devs" => $worst_devs
        ]);
    }
}
