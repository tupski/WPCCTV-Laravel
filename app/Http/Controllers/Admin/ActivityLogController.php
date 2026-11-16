<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $query = ActivityLog::query()->with('user:id,name,email');

        if ($userId = $request->query('user_id')) {
            $query->where('user_id', $userId);
        }

        if ($action = $request->query('action')) {
            $query->where('action', (string) $action);
        }

        if ($subjectType = $request->query('subject_type')) {
            $query->where('subject_type', (string) $subjectType);
        }

        if ($from = $this->parseDate($request->query('from'))) {
            $query->whereDate('created_at', '>=', $from->toDateString());
        }

        if ($to = $this->parseDate($request->query('to'))) {
            $query->whereDate('created_at', '<=', $to->toDateString());
        }

        $logs = $query->orderByDesc('created_at')->orderByDesc('id')
            ->paginate(50)
            ->appends($request->query());

        return response()->json([
            'data' => $logs->items(),
            'meta' => $logs->toArray()['meta'] ?? null,
        ]);
    }

    protected function parseDate(mixed $value): ?CarbonImmutable
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        $date = CarbonImmutable::createFromFormat('Y-m-d', $value);

        if ($date === false || $date->format('Y-m-d') !== $value) {
            return null;
        }

        return $date->startOfDay();
    }
}
