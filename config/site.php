<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Brand Identity
    |--------------------------------------------------------------------------
    |
    | Static fallbacks for the public website. Runtime values stored in the
    | `settings` table (edited by an admin) take precedence over these — see
    | App\Support\SiteInfo. Nothing here is a secret.
    |
    */

    'name' => 'WP CCTV Indonesia',

    'tagline' => 'SMART SECURITY SOLUTIONS',

    /*
    | WhatsApp is the primary conversion channel. Stored in international
    | format (no leading zero) so `https://wa.me/{number}` is always valid;
    | the human-readable form is derived by SiteInfo::formatWhatsapp().
    */

    'whatsapp_number' => '6287811556995',

    'email' => 'halo@wpcctv.co.id',

    /*
    | Deliberately no street address or unit number: the postal address is a
    | business fact that has not been verified for this project. City-level
    | information plus service areas is honest and still useful for
    | LocalBusiness structured data.
    */

    'address_locality' => 'Jakarta Timur',

    'address_region' => 'DKI Jakarta',

    'address_country' => 'ID',

    'operating_hours' => 'Senin–Sabtu, 08.00–18.00 WIB',

    /*
    | Structured-data opening hours (schema.org syntax).
    */

    'opening_hours_spec' => 'Mo-Sa 08:00-18:00',

    'service_areas' => [
        'Jakarta',
        'Bogor',
        'Depok',
        'Tangerang',
        'Bekasi',
        'Bandung',
    ],

];
