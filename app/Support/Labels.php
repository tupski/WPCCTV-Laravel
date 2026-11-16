<?php

namespace App\Support;

use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\WhatsAppClick;

/**
 * Single source of Indonesian display labels for stored enum values.
 *
 * Values stay in English in the database; only the label differs. Unknown
 * values fall back to the raw value so an unmapped state is visible rather
 * than silently blank.
 */
final class Labels
{
    /**
     * Lead status → label. Order matches Lead::STATUSES.
     */
    public const STATUS = [
        Lead::STATUS_NEW => 'Baru',
        Lead::STATUS_CONTACTED => 'Dihubungi',
        Lead::STATUS_QUALIFIED => 'Kualifikasi',
        Lead::STATUS_QUOTATION => 'Penawaran',
        Lead::STATUS_NEGOTIATION => 'Negosiasi',
        Lead::STATUS_WON => 'Deal',
        Lead::STATUS_LOST => 'Batal',
        Lead::STATUS_ARCHIVED => 'Diarsipkan',
    ];

    /**
     * Status → semantic tone. Tones carry meaning, never decoration:
     * neutral = still open, info = qualified, success = won, danger = lost,
     * muted = archived.
     */
    public const STATUS_TONE = [
        Lead::STATUS_NEW => 'neutral',
        Lead::STATUS_CONTACTED => 'neutral',
        Lead::STATUS_QUALIFIED => 'info',
        Lead::STATUS_QUOTATION => 'neutral',
        Lead::STATUS_NEGOTIATION => 'neutral',
        Lead::STATUS_WON => 'success',
        Lead::STATUS_LOST => 'danger',
        Lead::STATUS_ARCHIVED => 'muted',
    ];

    public const LEAD_TYPE = [
        Lead::TYPE_INSTALLATION => 'Pemasangan baru',
        Lead::TYPE_REPAIR => 'Perbaikan',
        Lead::TYPE_CONSULTATION => 'Konsultasi',
    ];

    public const PRIORITY = [
        'low' => 'Rendah',
        'medium' => 'Sedang',
        'high' => 'Tinggi',
        'urgent' => 'Mendesak',
    ];

    public const PAGE_TYPE = [
        'product' => 'Halaman produk',
        'package' => 'Halaman paket',
        'service' => 'Halaman layanan',
        'article' => 'Artikel blog',
        'page' => 'Halaman statis',
        'home' => 'Beranda',
    ];

    public const DEVICE = [
        'desktop' => 'Desktop',
        'tablet' => 'Tablet',
        'mobile' => 'Ponsel',
    ];

    public const ACTIVITY = [
        LeadActivity::ACTION_STATUS_CHANGE => 'Perubahan status',
        LeadActivity::ACTION_NOTE => 'Catatan',
        LeadActivity::ACTION_ASSIGN => 'Penugasan',
        LeadActivity::ACTION_FOLLOWUP => 'Jadwal tindak lanjut',
        LeadActivity::ACTION_IDENTIFY => 'Identifikasi kontak',
        LeadActivity::ACTION_CONVERT => 'Konversi dari klik WhatsApp',
        LeadActivity::ACTION_WHATSAPP_CLICK => 'Klik WhatsApp',
        // `activity_logs.action` values written by the services.
        'lead.created' => 'Lead dibuat',
        'lead.updated' => 'Lead diperbarui',
        'lead.deleted' => 'Lead dihapus',
        'lead.converted' => 'Lead hasil konversi',
        'lead.status_changed' => 'Status lead diubah',
        'lead.assigned' => 'Lead ditugaskan',
        'lead.followup_scheduled' => 'Tindak lanjut dijadwalkan',
        'settings.updated' => 'Pengaturan diperbarui',
        'settings.forbidden_key_rejected' => 'Kunci pengaturan ditolak',
    ];

    /**
     * Short subject name for the activity log table.
     */
    public const SUBJECT = [
        Lead::class => 'Lead',
        WhatsAppClick::class => 'Klik WhatsApp',
        'App\\Models\\Page' => 'Halaman',
        'App\\Models\\Product' => 'Produk',
        'App\\Models\\Package' => 'Paket',
        'App\\Models\\BlogArticle' => 'Artikel',
    ];

    public static function status(?string $status): string
    {
        return $status === null ? 'Tanpa status' : (self::STATUS[$status] ?? $status);
    }

    public static function statusTone(?string $status): string
    {
        return $status === null ? 'neutral' : (self::STATUS_TONE[$status] ?? 'neutral');
    }

    public static function leadType(?string $type): string
    {
        return $type === null ? 'Belum ditentukan' : (self::LEAD_TYPE[$type] ?? $type);
    }

    public static function priority(?string $priority): string
    {
        return $priority === null ? 'Belum ditentukan' : (self::PRIORITY[$priority] ?? $priority);
    }

    public static function pageType(?string $type): string
    {
        return $type === null ? 'Halaman tidak diketahui' : (self::PAGE_TYPE[$type] ?? $type);
    }

    public static function device(?string $device): string
    {
        return $device === null ? 'Tidak diketahui' : (self::DEVICE[$device] ?? $device);
    }

    public static function activity(?string $action): string
    {
        return $action === null ? 'Aktivitas' : (self::ACTIVITY[$action] ?? $action);
    }

    public static function subject(?string $subjectType): string
    {
        if ($subjectType === null) {
            return 'Sistem';
        }

        return self::SUBJECT[$subjectType] ?? class_basename($subjectType);
    }
}
