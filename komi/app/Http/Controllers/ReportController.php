<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReportRequest;
use App\Models\Report;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class ReportController extends Controller
{
    public function store(StoreReportRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $reportableType = match ($validated['type']) {
            'user' => User::class,
            'post' => \App\Models\Post::class,
            'comment' => \App\Models\Comment::class,
            default => User::class,
        };

        $reportableId = $validated['reportable_id'];

        if ($validated['type'] === 'user') {
            $reportable = User::findOrFail($reportableId);
            if ($reportable->id === $request->user()->id) {
                return response()->json(['message' => 'No puedes reportarte a ti mismo.'], 400);
            }
        }

        $existing = Report::where('user_id', $request->user()->id)
            ->where('reportable_type', $reportableType)
            ->where('reportable_id', $reportableId)
            ->where('status', 'pending')
            ->exists();

        if ($existing) {
            return response()->json(['message' => 'Ya has reportado este contenido.'], 409);
        }

        $report = Report::create([
            'user_id' => $request->user()->id,
            'reportable_type' => $reportableType,
            'reportable_id' => $reportableId,
            'reason' => $validated['reason'],
            'description' => $validated['description'] ?? null,
            'status' => 'pending',
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Reporte enviado correctamente.',
            'report_id' => $report->id,
        ], 201);
    }
}
