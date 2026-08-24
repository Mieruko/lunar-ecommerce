<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Customer-support transcript retention
    |--------------------------------------------------------------------------
    |
    | Only resolved conversations older than this number of days are eligible
    | for the support:purge-resolved command. Choose the production value with
    | the store's privacy policy and legal adviser.
    |
    */
    'retention_days' => (int) env('SUPPORT_CHAT_RETENTION_DAYS', 180),
];
