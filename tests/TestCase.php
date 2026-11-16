<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Cookie encryption exceptions live in bootstrap/app.php, where the real
     * middleware configuration is defined. They are intentionally NOT
     * re-registered here: doing so (as this class previously did) meant the
     * test suite passed while production still encrypted `wpc_visit`, which
     * is exactly how C2 in PHASE_4_CLOSURE_AUDIT.md stayed hidden.
     */
}
