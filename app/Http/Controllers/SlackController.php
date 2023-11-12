<?php

namespace App\Http\Controllers;

use App\Models\Report;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class SlackController extends Controller
{
    public function interact(Request $request) {
        $payload        = json_decode($request->payload);
        $response_url   = $payload->response_url;
        $action         = explode("_", $payload->actions[0]->value);

        $report_id      = $action[0];
        $publish_value  = $action[1];
        $report         = Report::find($report_id);

        if($report) {
            $report->published = $publish_value === 'approved' ? 1 : -1;
            $report->save();
        }

        Http::post($response_url, [
            "replace_original" => "true",
            "text" => "Report ID ".$report_id." has been ".$publish_value."!"
        ]);
    }
}
