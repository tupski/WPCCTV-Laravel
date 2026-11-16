<?php

return [

    /*
    |--------------------------------------------------------------------------
    | VAPID (Web Push) configuration
    |--------------------------------------------------------------------------
    |
    | VAPID public key is stored in the settings table via the `vapid:generate`
    | command and exposed to the client. The private key is env-only and must
    | never be written to the settings table or committed to the repository.
    |
    */

    'public_key' => env('VAPID_PUBLIC_KEY'),
    'private_key' => env('VAPID_PRIVATE_KEY'),
    'subject' => env('VAPID_ADMIN_EMAIL', 'mailto:admin@wpcctv.id'),

];
