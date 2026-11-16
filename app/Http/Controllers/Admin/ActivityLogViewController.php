<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use App\Support\Format;
use App\Support\Labels;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Audit trail as a plain table: when, who, what, on which record.
 */
class ActivityLogViewController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('view-activity-logs');

        $filters = [
            'user_id' => $this->digitsOrNull($request->query('user_id')),
            'action' => $this->stringOrNull($request->query('action')),
            'from' => $this->dateOrNull($request->query('from')),
            'to' => $this->dateOrNull($request->query('to')),
        ];

        $query = ActivityLog::query()->with('user:id,name');

        if ($filters['user_id'] !== null) {
            $query->where('user_id', (int) $filters['user_id']);
        }

        if ($filters['action'] !== null) {
            $query->where('action', $filters['action']);
        }

        if ($filters['from'] !== null) {
            $query->whereDate('created_at', '>=', $filters['from']);
        }

        if ($filters['to'] !== null) {
            $query->whereDate('created_at', '<=', $filters['to']);
        }

        $logs = $query->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(50)
            ->appends($request->query());

        // Each row is flattened in the controller so the view only prints.
        $logs->through(fn (ActivityLog $log): array => [
            'when' => Format::dateTime($log->created_at),
            'who' => $log->user?->name ?? 'Sistem',
            'action' => $log->action,
            'action_label' => Labels::activity($log->action),
            'subject' => Labels::subject($log->subject_type),
            'subject_id' => $log->subject_id,
        ]);

        return view('admin.activity-logs.index', [
            'logs' => $logs,
            'filters' => $filters,
            'users' => User::query()->orderBy('name')->get(['id', 'name']),
            'actions' => $this->knownActions(),
            'hasFilters' => array_filter($filters, fn ($value) => $value !== null) !== [],
        ]);
    }

    /**
     * Distinct actions actually present, so the filter never offers a dead end.
     *
     * @return \Illuminate\Support\Collection<int, string>
     */
    protected function knownActions()
    {
        return ActivityLog::query()
            ->select('action')
            ->distinct()
            ->orderBy('action')
            ->pluck('action');
    }

    protected function stringOrNull(mixed $value): ?string
    {
        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }

    protected function digitsOrNull(mixed $value): ?string
    {
        return is_string($value) && ctype_digit($value) ? $value : null;
    }

    protected function dateOrNull(mixed $value): ?string
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        $date = CarbonImmutable::createFromFormat('Y-m-d', $value);

        return $date !== false && $date->format('Y-m-d') === $value ? $value : null;
    }
}
