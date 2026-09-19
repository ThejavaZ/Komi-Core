<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Report;
use Illuminate\Http\JsonResponse;

class AdminNotificationController extends Controller
{
    public function unread(): JsonResponse
    {
        $pendingReports = Report::where('status', 'pending')->count();
        $pendingAppeals = \App\Models\Appeal::where('status', 'pending')->count();

        return response()->json([
            'pending_reports' => $pendingReports,
            'pending_appeals' => $pendingAppeals,
            'total' => $pendingReports + $pendingAppeals,
        ]);
    }
}
