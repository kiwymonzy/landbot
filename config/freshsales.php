<?php

return [
    // API Key
    'key' => env('FRESHSALES_KEY', ''),

    // API URL
    'url' => 'https://' . env('FRESHSALES_DOMAIN') . '.freshsales.io/api/',
];
