<?php

return [
    /*
    |--------------------------------------------------------------------------
    | İstifadəçi (müştəri) abunəliyi
    |--------------------------------------------------------------------------
    |
    | gate — tətbiqin hansı mərhələsində abunəlik tələb olunur:
    |   'off'   → abunəlik tələb olunmur (sistem yalnız satış üçün işləyir)
    |   'claim' → oynamaq sərbəstdir, KUPON almaq üçün aktiv abunəlik lazımdır (default)
    |   'play'  → quiz-i başlatmaq üçün login + aktiv abunəlik lazımdır (qonaq axını bağlanır)
    |
    | grace_days — abunəlik bitdikdən sonra neçə gün daha icazə verilsin.
    |
    */
    'customer' => [
        'enabled'    => (bool) env('CUSTOMER_SUBSCRIPTION_ENABLED', true),
        'gate'       => env('CUSTOMER_SUBSCRIPTION_GATE', 'claim'),
        'grace_days' => (int) env('CUSTOMER_SUBSCRIPTION_GRACE_DAYS', 0),
    ],

    /*
    | React tətbiqinin ünvanı — bank ödənişindən sonra müştəri buraya qaytarılır.
    */
    'frontend_url' => rtrim((string) env('FRONTEND_URL', 'http://localhost:3000'), '/'),
];
