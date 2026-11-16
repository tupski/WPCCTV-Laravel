<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Services\WhatsAppTrackingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class WhatsAppTrackingController extends Controller
{
    public function __construct(protected WhatsAppTrackingService $tracking)
    {
    }

    /**
     * GET /track/whatsapp?context={page_type}&slug={slug}
     *
     * Records the click, dispatches the notification job, redirects to wa.me.
     * GET request — CSRF-exempt by default.
     */
    public function track(Request $request): Response|RedirectResponse
    {
        return $this->tracking->handle($request);
    }
}
