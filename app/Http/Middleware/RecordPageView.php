<?php

namespace App\Http\Middleware;

use App\Services\AnalyticsRecorderService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Records a page view for public GET renders.
 *
 * Applied only to the public route group. Exclusion rules are also enforced
 * here defensively so a mis-applied middleware cannot pollute analytics:
 * - non-GET requests
 * - admin / auth / tracking / health endpoints
 * - requests carrying `DNT: 1` or `Sec-GPC: 1` (explicit visitor opt-out)
 */
class RecordPageView
{
    /**
     * Path prefixes that must never be recorded.
     */
    public const EXCLUDED_PREFIXES = [
        'admin',
        'track',
        'login',
        'logout',
        'up',
        'health',
        'healthz',
        'api',
        'livewire',
        '_ignition',
        'sanctum',
    ];

    public function __construct(protected AnalyticsRecorderService $recorder) {}

    public function handle(Request $request, Closure $next): Response
    {
        if ($this->shouldRecord($request)) {
            $this->recorder->recordPageView($request);
        }

        return $next($request);
    }

    public function shouldRecord(Request $request): bool
    {
        if (! $request->isMethod('GET')) {
            return false;
        }

        if ($this->optedOut($request)) {
            return false;
        }

        return ! $this->isExcludedPath($request);
    }

    /**
     * Honor Do Not Track and Global Privacy Control.
     */
    public function optedOut(Request $request): bool
    {
        $dnt = trim((string) $request->headers->get('DNT'));

        if ($dnt === '1') {
            return true;
        }

        $gpc = trim((string) $request->headers->get('Sec-GPC'));

        return $gpc === '1';
    }

    public function isExcludedPath(Request $request): bool
    {
        $path = trim($request->path(), '/');

        if ($path === '') {
            return false;
        }

        foreach (self::EXCLUDED_PREFIXES as $prefix) {
            if ($path === $prefix || str_starts_with($path, $prefix.'/')) {
                return true;
            }
        }

        return false;
    }
}
