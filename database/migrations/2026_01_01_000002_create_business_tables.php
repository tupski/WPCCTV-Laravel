<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Ordered by FK dependency:
     * 1. settings (FK users)
     * 2. whatsapp_clicks (FK leads nullable -> leads must exist first, so create leads before whatsapp_clicks? No: whatsapp_clicks.converted_lead_id FK leads. leads.whatsapp_click_id FK whatsapp_clicks. Circular FK.
     *    Resolution: create whatsapp_clicks first WITHOUT the FK, create leads WITH FK to whatsapp_clicks, then add the FK on whatsapp_clicks via Schema::table.
     */
    public function up(): void
    {
        // ── settings ────────────────────────────────────────────────────
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->json('value')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // ── whatsapp_clicks (FK leads added after leads exists) ────────
        Schema::create('whatsapp_clicks', function (Blueprint $table) {
            $table->id();
            $table->uuid('event_id')->unique();
            $table->string('page_url');
            $table->string('page_title')->nullable();
            $table->string('page_type', 50); // product, package, service, article, page, home
            $table->unsignedBigInteger('context_id')->nullable(); // cross-type polymorphic ref, no FK
            $table->string('referrer')->nullable();
            $table->string('utm_source')->nullable();
            $table->string('utm_medium')->nullable();
            $table->string('utm_campaign')->nullable();
            $table->string('device_category', 50);
            $table->string('visitor_token')->nullable();
            $table->foreignId('converted_lead_id')->nullable(); // FK added after leads table
            $table->timestamps();

            $table->index('created_at');
            $table->index(['page_type', 'created_at']);
            $table->index(['context_id', 'created_at']);
            $table->index('converted_lead_id');
        });

        // ── leads ───────────────────────────────────────────────────────
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('whatsapp_click_id')->nullable()->constrained('whatsapp_clicks')->nullOnDelete()->unique();
            $table->string('customer_name')->nullable();
            $table->string('whatsapp_number')->nullable();
            $table->string('lead_type', 50)->nullable(); // installation, repair, consultation
            $table->text('requirements')->nullable();
            $table->string('status', 50)->default('new'); // new, contacted, qualified, quotation, negotiation, won, lost, archived
            $table->string('priority', 50)->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('follow_up_at')->nullable();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('converted_at')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('assigned_to');
            $table->index('whatsapp_click_id');
            $table->index('follow_up_at');
        });

        // Add deferred FK: whatsapp_clicks.converted_lead_id -> leads
        Schema::table('whatsapp_clicks', function (Blueprint $table) {
            $table->foreign('converted_lead_id', 'whatsapp_clicks_converted_lead_id_fk')
                ->references('id')->on('leads')->nullOnDelete();
        });

        // ── lead_activities ─────────────────────────────────────────────
        Schema::create('lead_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained('leads')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 50); // status_change, note, assign, followup, identify, convert, whatsapp_click
            $table->string('field')->nullable();
            $table->text('old_value')->nullable();
            $table->text('new_value')->nullable();
            $table->foreignId('whatsapp_click_id')->nullable()->constrained('whatsapp_clicks')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['lead_id', 'created_at']);
        });

        // ── push_subscriptions ──────────────────────────────────────────
        Schema::create('push_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('endpoint')->unique();
            $table->string('p256dh');
            $table->string('auth');
            $table->string('user_agent')->nullable();
            $table->string('browser')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->index('user_id');
            $table->index('expires_at');
            $table->index('active');
        });

        // ── notification_preferences ────────────────────────────────────
        Schema::create('notification_preferences', function (Blueprint $table) {
            $table->foreignId('user_id')->primary()->constrained('users')->cascadeOnDelete();
            $table->boolean('whatsapp_clicks_enabled')->default(true);
            $table->boolean('lead_updates_enabled')->default(true);
            $table->timestamp('updated_at')->nullable();
        });

        // ── activity_logs ───────────────────────────────────────────────
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 100);
            $table->string('subject_type', 100)->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->json('context')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('user_id');
            $table->index('created_at');
            $table->index(['subject_type', 'subject_id']);
        });

        // ── analytics_page_views ────────────────────────────────────────
        Schema::create('analytics_page_views', function (Blueprint $table) {
            $table->id();
            $table->string('session_id', 100);
            $table->string('page_url');
            $table->string('page_type', 50)->nullable();
            $table->string('ref_domain')->nullable();
            $table->string('utm_source')->nullable();
            $table->string('utm_medium')->nullable();
            $table->string('utm_campaign')->nullable();
            $table->string('device_category', 50);
            $table->timestamp('created_at')->useCurrent();

            $table->index('session_id');
            $table->index('created_at');
            $table->index(['page_type', 'created_at']);
        });

        // ── analytics_daily ─────────────────────────────────────────────
        Schema::create('analytics_daily', function (Blueprint $table) {
            $table->id();
            $table->date('date')->unique();
            $table->unsignedInteger('page_views')->default(0);
            $table->unsignedInteger('unique_sessions')->default(0);
            $table->unsignedInteger('whatsapp_clicks')->default(0);
            $table->unsignedInteger('leads_created')->default(0);
            $table->unsignedInteger('leads_qualified')->default(0);
            $table->unsignedInteger('leads_won')->default(0);
            $table->foreignId('top_product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->foreignId('top_package_id')->nullable()->constrained('packages')->nullOnDelete();
            $table->foreignId('top_article_id')->nullable()->constrained('blog_articles')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * Drop order is the exact reverse of creation so no child table is
     * referenced by a surviving foreign key. SQLite ignores FK ordering at
     * drop time, but MySQL/MariaDB does not; `whatsapp_clicks` must go before
     * `leads` because `whatsapp_clicks.converted_lead_id` references it.
     *
     * NOTE (H3): the legacy `users.role` normalization that used to live at
     * the end of up() has been extracted to its own migration
     * (2026_01_01_000003_normalize_legacy_admin_role.php) so that the data
     * change is legible in `git log` and independently revertible.
     */
    public function down(): void
    {
        // Break the circular FK before dropping either side. MySQL/MariaDB
        // refuse to drop a table that a surviving FK points at, so the named
        // constraint must go first. SQLite cannot drop a foreign key by name
        // (and does not need to — dropping tables cascades its schema), so the
        // statement is skipped there.
        if (Schema::hasTable('whatsapp_clicks') && $this->canDropForeignKeysByName()) {
            Schema::table('whatsapp_clicks', function (Blueprint $table) {
                $table->dropForeign('whatsapp_clicks_converted_lead_id_fk');
            });
        }

        Schema::dropIfExists('analytics_daily');
        Schema::dropIfExists('analytics_page_views');
        Schema::dropIfExists('activity_logs');
        Schema::dropIfExists('notification_preferences');
        Schema::dropIfExists('push_subscriptions');
        Schema::dropIfExists('lead_activities');
        Schema::dropIfExists('leads');
        Schema::dropIfExists('whatsapp_clicks');
        Schema::dropIfExists('settings');
    }

    /**
     * SQLite rejects `ALTER TABLE ... DROP FOREIGN KEY <name>`; MySQL, MariaDB
     * and Postgres accept it. Only the latter group needs the explicit unlock
     * before dropping the tables involved.
     */
    private function canDropForeignKeysByName(): bool
    {
        return ! in_array(DB::connection()->getDriverName(), ['sqlite'], true);
    }
};
