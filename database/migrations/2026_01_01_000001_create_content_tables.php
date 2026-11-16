<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /*
     * Explicit index/constraint names — why this migration names some indexes.
     *
     * MySQL and MariaDB cap identifiers at 64 characters; SQLite does not. So
     * Laravel's conventional auto-generated names (`{table}_{columns}_{type}`)
     * pass the SQLite-backed test suite and then abort `migrate` on the target
     * engine. Measured against the MySQL grammar, and reproduced for real on
     * MariaDB 12.2.2:
     *
     *   package_usage_category_package_package_usage_category_id_package_id_unique  → 74  FAILS
     *   package_usage_category_package_package_usage_category_id_foreign            → 64  exactly at the limit
     *   package_category_package_package_category_id_package_id_unique              → 62
     *   package_category_package_package_category_id_foreign                        → 52
     *
     * The 74-character name is not a warning, it is a hard stop:
     *   SQLSTATE[42000] 1059 Identifier name '...' is too long
     * which aborts the whole migration on MariaDB/MySQL while SQLite accepts it
     * silently — the failure mode is invisible to a green SQLite suite.
     *
     * The two pivot tables whose columns push the generated names to 62-74
     * characters therefore carry explicit names of the form
     * `{short_table}_{short_columns}_{type}`. They stay readable (unlike
     * `idx_1`) and sit far inside the limit. A name is only shortened when it
     * would otherwise breach or graze 64; every other index in this file keeps
     * its conventional name, so the fix is minimal and reviewable.
     *
     * `packages` is named explicitly for a different reason: its generated
     * `packages_brand_id_published_index` measured 33 and was never at risk,
     * but pinning the name documents the intent of the composite and survives
     * a future column rename.
     */

    /**
     * Run the migrations.
     *
     * Ordered by dependency:
     * 1. media_assets (no FK to content)
     * 2. seo_metadata (polymorphic, no FK)
     * 3. product_brands
     * 4. products
     * 5. package_brands
     * 6. package_categories
     * 7. package_usage_categories
     * 8. packages
     * 9. package_category pivot
     * 10. package_usage_category pivot
     * 11. services
     * 12. blog_categories
     * 13. blog_tags
     * 14. blog_articles
     * 15. blog_article_tag pivot
     * 16. faqs
     * 17. pages
     */
    public function up(): void
    {
        // ── media_assets ──────────────────────────────────────────────
        Schema::create('media_assets', function (Blueprint $table) {
            $table->id();
            $table->string('file_path');
            $table->string('disk', 100)->default('public');
            $table->string('type', 50)->nullable();
            $table->string('alt_text')->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->string('mime_type')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // ── seo_metadata ──────────────────────────────────────────────
        Schema::create('seo_metadata', function (Blueprint $table) {
            $table->id();
            $table->morphs('seoable');
            $table->string('seo_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->string('canonical')->nullable();
            $table->string('robots')->nullable();
            $table->string('og_title')->nullable();
            $table->text('og_description')->nullable();
            $table->string('og_image')->nullable();
            $table->string('og_type', 50)->nullable();
            $table->string('twitter_card', 50)->nullable();
            $table->string('twitter_title')->nullable();
            $table->text('twitter_description')->nullable();
            $table->string('twitter_image')->nullable();
            $table->timestamps();

            $table->unique(['seoable_type', 'seoable_id']);
        });

        // ── product_brands ────────────────────────────────────────────
        Schema::create('product_brands', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('logo')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index('name');
        });

        // ── products ──────────────────────────────────────────────────
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_id')->constrained('product_brands')->cascadeOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('model')->nullable();
            $table->string('camera_type', 100)->nullable();
            $table->text('description')->nullable();
            $table->json('key_features')->nullable();
            $table->json('technical_specs')->nullable();
            $table->json('recommended_applications')->nullable();
            $table->string('image')->nullable();
            $table->json('gallery')->nullable();
            $table->boolean('published')->default(false);
            $table->timestamps();

            $table->index(['brand_id', 'published']);
            $table->index('name');
        });

        // ── package_brands ────────────────────────────────────────────
        Schema::create('package_brands', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('logo')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index('name');
        });

        // ── package_categories ────────────────────────────────────────
        Schema::create('package_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->timestamps();
        });

        // ── package_usage_categories ──────────────────────────────────
        Schema::create('package_usage_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->timestamps();
        });

        // ── packages ──────────────────────────────────────────────────
        Schema::create('packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_id')->nullable()->constrained('package_brands')->nullOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('hero_image')->nullable();
            $table->tinyInteger('camera_quantity')->nullable();
            $table->json('included_equipment')->nullable();
            $table->json('installation_scope')->nullable();
            $table->json('relevant_specs')->nullable();
            $table->json('installation_process')->nullable();
            $table->json('exclusions')->nullable();
            $table->decimal('price', 12, 2)->nullable();
            $table->boolean('published')->default(false);
            $table->timestamps();

            $table->index(['brand_id', 'published'], 'packages_brand_published_idx');
            $table->index('title', 'packages_title_idx');
        });

        // ── package_category pivot ────────────────────────────────────
        Schema::create('package_category_package', function (Blueprint $table) {
            $table->id();
            $table->foreignId('package_category_id')->constrained('package_categories')->cascadeOnDelete();
            $table->foreignId('package_id')->constrained('packages')->cascadeOnDelete();
            $table->unique(['package_category_id', 'package_id'], 'pc_pkg_category_id_package_id_unique');
        });

        // ── package_usage_category pivot ──────────────────────────────
        // Explicit names on every index + FK: see the class docblock. The
        // auto-generated unique name was 74 chars and aborted migrate on
        // MariaDB; the auto-generated FK name was exactly 64 — one character
        // of headroom, which is not headroom.
        Schema::create('package_usage_category_package', function (Blueprint $table) {
            $table->id();
            $table->foreignId('package_usage_category_id')
                ->constrained('package_usage_categories', null, 'fk_puc_pkg_usage_category')
                ->cascadeOnDelete();
            $table->foreignId('package_id')
                ->constrained('packages', null, 'fk_puc_pkg_package')
                ->cascadeOnDelete();
            $table->unique(['package_usage_category_id', 'package_id'], 'puc_pkg_usage_category_id_package_id_unique');
        });

        // ── services ──────────────────────────────────────────────────
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('type', 50); // installation, repair, product_only
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->json('scope')->nullable();
            $table->json('applications')->nullable();
            $table->json('process')->nullable();
            $table->boolean('published')->default(false);
            $table->timestamps();

            $table->index('type');
        });

        // ── blog_categories ───────────────────────────────────────────
        Schema::create('blog_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // ── blog_tags ─────────────────────────────────────────────────
        Schema::create('blog_tags', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->timestamps();
        });

        // ── blog_articles ─────────────────────────────────────────────
        Schema::create('blog_articles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('blog_category_id')->nullable()->constrained('blog_categories')->nullOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('excerpt')->nullable();
            $table->longText('body');
            $table->string('featured_image')->nullable();
            $table->string('status', 20)->default('draft'); // draft, published, archived
            $table->timestamp('published_at')->nullable();
            $table->timestamp('scheduled_for')->nullable();
            $table->integer('reading_time')->default(0);
            $table->boolean('published')->default(false); // denormalized
            $table->timestamps();

            $table->index(['status', 'published_at']);
            $table->index('title');
        });

        // ── blog_article_tag pivot ────────────────────────────────────
        Schema::create('blog_article_tag', function (Blueprint $table) {
            $table->foreignId('blog_article_id')->constrained('blog_articles')->cascadeOnDelete();
            $table->foreignId('blog_tag_id')->constrained('blog_tags')->cascadeOnDelete();
            $table->primary(['blog_article_id', 'blog_tag_id']);
        });

        // ── faqs ──────────────────────────────────────────────────────
        Schema::create('faqs', function (Blueprint $table) {
            $table->id();
            $table->string('context_type', 100)->nullable();
            $table->unsignedBigInteger('context_id')->nullable();
            $table->string('question');
            $table->text('answer');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('published')->default(true);
            $table->timestamps();

            $table->index(['context_type', 'context_id', 'published']);
            $table->index('question');
        });

        // ── pages ─────────────────────────────────────────────────────
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->longText('body');
            $table->boolean('published')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pages');
        Schema::dropIfExists('faqs');
        Schema::dropIfExists('blog_article_tag');
        Schema::dropIfExists('blog_articles');
        Schema::dropIfExists('blog_tags');
        Schema::dropIfExists('blog_categories');
        Schema::dropIfExists('services');
        Schema::dropIfExists('package_usage_category_package');
        Schema::dropIfExists('package_category_package');
        Schema::dropIfExists('packages');
        Schema::dropIfExists('package_usage_categories');
        Schema::dropIfExists('package_categories');
        Schema::dropIfExists('package_brands');
        Schema::dropIfExists('products');
        Schema::dropIfExists('product_brands');
        Schema::dropIfExists('seo_metadata');
        Schema::dropIfExists('media_assets');
    }
};
