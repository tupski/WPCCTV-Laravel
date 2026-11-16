<?php

namespace App\Services;

use App\Models\BlogArticle;
use App\Models\Faq;
use App\Models\Package;
use App\Models\Product;
use App\Models\Service;
use App\Support\SiteInfo;

/**
 * schema.org JSON-LD graph fragments for the public site.
 *
 * Deliberately no `Offer`/`price` properties: the business sells installation
 * services, not equipment through an online checkout, and structured data
 * promising a purchasable price would misrepresent the site to search engines
 * (PRD §2: no e-commerce).
 */
class StructuredData
{
    /**
     * LocalBusiness describing the company itself. `@id` is the site root so
     * other nodes can reference it without duplicating the address.
     *
     * @return array<string, mixed>
     */
    public function organization(): array
    {
        $info = SiteInfo::all();
        $home = url('/');

        $node = [
            '@context' => 'https://schema.org',
            '@type' => 'LocalBusiness',
            '@id' => $home.'#organization',
            'name' => $info['name'],
            'url' => $home,
            'description' => 'Jasa instalasi dan perbaikan sistem CCTV untuk rumah, toko, kantor, gudang, sekolah, dan pabrik.',
            'telephone' => $info['whatsapp_number'] !== '' ? '+'.$info['whatsapp_number'] : null,
            'email' => $info['email'] !== '' ? $info['email'] : null,
            'address' => [
                '@type' => 'PostalAddress',
                'addressLocality' => $info['address_locality'],
                'addressRegion' => $info['address_region'],
                'addressCountry' => $info['address_country'],
            ],
            'areaServed' => array_map(
                static fn (string $area): array => ['@type' => 'AdministrativeArea', 'name' => $area],
                $info['service_areas'],
            ),
            'openingHours' => $info['opening_hours_spec'] !== '' ? $info['opening_hours_spec'] : null,
        ];

        return $this->prune($node);
    }

    /**
     * BreadcrumbList from [['name' => ..., 'url' => ...], ...].
     *
     * @param  array<int, array{name: string, url: string}>  $items
     * @return array<string, mixed>
     */
    public function breadcrumbs(array $items): array
    {
        $list = [];

        foreach (array_values($items) as $index => $item) {
            $list[] = [
                '@type' => 'ListItem',
                'position' => $index + 1,
                'name' => $item['name'],
                'item' => $item['url'],
            ];
        }

        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => $list,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function product(Product $product): array
    {
        return $this->prune([
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => $product->name,
            'description' => $product->description,
            'model' => $product->model,
            'category' => $product->camera_type,
            'url' => route('products.show', $product->slug),
            'brand' => $product->brand !== null ? [
                '@type' => 'Brand',
                'name' => $product->brand->name,
            ] : null,
        ]);
    }

    /**
     * Packages are installation service offers, so they are described as a
     * Service with the equipment list as its subject — not as a Product.
     *
     * @return array<string, mixed>
     */
    public function package(Package $package): array
    {
        return $this->prune([
            '@context' => 'https://schema.org',
            '@type' => 'Service',
            'name' => $package->title,
            'description' => $package->description,
            'serviceType' => 'Instalasi CCTV',
            'url' => route('packages.show', $package->slug),
            'provider' => ['@id' => url('/').'#organization'],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function service(Service $service): array
    {
        return $this->prune([
            '@context' => 'https://schema.org',
            '@type' => 'Service',
            'name' => $service->title,
            'description' => $service->description,
            'serviceType' => $service->title,
            'url' => route('services.show', $service->slug),
            'provider' => ['@id' => url('/').'#organization'],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function article(BlogArticle $article): array
    {
        return $this->prune([
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => $article->title,
            'description' => $article->excerpt,
            'url' => route('blog.show', $article->slug),
            'datePublished' => $article->published_at?->toIso8601String(),
            'dateModified' => $article->updated_at?->toIso8601String(),
            'author' => $article->author !== null ? [
                '@type' => 'Person',
                'name' => $article->author->name,
            ] : null,
            'publisher' => ['@id' => url('/').'#organization'],
            'articleSection' => $article->category?->name,
        ]);
    }

    /**
     * @param  iterable<int, Faq>  $faqs
     * @return array<string, mixed>|null
     */
    public function faqPage(iterable $faqs): ?array
    {
        $entries = [];

        foreach ($faqs as $faq) {
            $entries[] = [
                '@type' => 'Question',
                'name' => $faq->question,
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => $faq->answer,
                ],
            ];
        }

        if ($entries === []) {
            return null;
        }

        return [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => $entries,
        ];
    }

    /**
     * Remove null values so schema.org does not receive empty properties.
     *
     * @param  array<string, mixed>  $node
     * @return array<string, mixed>
     */
    private function prune(array $node): array
    {
        return array_filter($node, static fn (mixed $value): bool => $value !== null && $value !== '' && $value !== []);
    }
}
