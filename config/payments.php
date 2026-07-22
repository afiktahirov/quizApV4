<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default ödəniş provayderi
    |--------------------------------------------------------------------------
    |
    | Yeni bank/provayder qoşmaq üçün: aşağıdakı 'providers' massivinə
    | driver (PaymentGateway interfeysini implement edən sinif) və
    | konfiqurasiyasını əlavə et. Kodun heç bir yerini dəyişmək lazım deyil —
    | PaymentGatewayManager provayderi ada görə həll edir.
    |
    */
    'default' => env('PAYMENT_DEFAULT_PROVIDER', 'kapital_bank'),

    'providers' => [
        'kapital_bank' => [
            'driver' => \App\Services\Payments\KapitalBank\KapitalBankGateway::class,

            'mode' => env('KAPITALBANK_MODE', 'test'),

            'base_url' => [
                'test'       => 'https://txpgtst.kapitalbank.az/api',
                'production' => 'https://e-commerce.kapitalbank.az/api',
            ],

            'hpp_url' => [
                'test'       => 'https://txpgtst.kapitalbank.az/flex',
                'production' => 'https://e-commerce.kapitalbank.az/flex',
            ],

            // Bank tərəfindən verilən Basic Auth login/şifrə. Test sandbox üçün
            // Kapital Bank-ın rəsmi test hesabı: TerminalSys/kapital / kapital123.
            'username' => env('KAPITALBANK_USERNAME', 'TerminalSys/kapital'),
            'password' => env('KAPITALBANK_PASSWORD', 'kapital123'),

            'currency' => env('KAPITALBANK_CURRENCY', 'AZN'),
            'language' => env('KAPITALBANK_LANGUAGE', 'az'),
            'timeout'  => (int) env('KAPITALBANK_TIMEOUT', 30),
        ],

        // Gələcəkdə başqa banklar buraya əlavə olunacaq, məsələn:
        // 'some_other_bank' => [
        //     'driver' => \App\Services\Payments\SomeOtherBank\SomeOtherBankGateway::class,
        //     ...
        // ],
    ],
];
