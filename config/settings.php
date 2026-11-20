<?php
return [
    'app' => [
        ['name' => 'company_name', 'value' => 'Pos'],
        ['name' => 'company_phone', 'value' => '+1 xxx-xxx-xxxx'],
        ['name' => 'company_address', 'value' => 'Dhaka Bangladesh'],
        ['name' => 'company_email', 'value' => 'example@example.com'],
        ['name' => 'company_tax_id', 'value' => null],
        ['name' => 'company_website', 'value' => null],
        ['name' => 'language', 'value' => 'en'],
        ['name' => 'date_format', 'value' => 'd-m-Y'],
        ['name' => 'time_format', 'value' => 'h'],
        ['name' => 'time_zone', 'value' => 'Asia/Dhaka'],
        ['name' => 'currency_symbol', 'value' => '$'],
        ['name' => 'decimal_separator', 'value' => '.'],
        ['name' => 'thousand_separator', 'value' => ','],
        ['name' => 'number_of_decimal', 'value' => '2'],
        ['name' => 'currency_position', 'value' => 'prefix_with_space'],
        ['name' => 'primary_color', 'value' => '#2b8768'],
        ['name' => 'primary_dark_color', 'value' => '#2b8768'],
        ['name' => 'primary_hover_color', 'value' => '#1f604a'],
        ['name' => 'primary_hover_dark_color', 'value' => '#1f604a'],
        ['name' => 'font_color', 'value' => '#4F5867'],
        ['name' => 'font_dark_color', 'value' => '#eff2f6'],
        ['name' => 'border_color', 'value' => '#ececec'],
        ['name' => 'border_dark_color', 'value' => '#252c3b'],
        ['name' => 'dashboard_color', 'value' => '#f5f6fa'],
        ['name' => 'dashboard_dark_color', 'value' => '#0e1217'],
        ['name' => 'background_color', 'value' => '#ffffff'],
        ['name' => 'background_dark_color', 'value' => '#182028'],
    ],

    'time_format' => [
        'h',
        'H'
    ],

    'currency_position' => [
        'prefix_only',
        'prefix_with_space',
        'suffix_only',
        'suffix_with_space'
    ],

    'date_format' => [
        'd-m-Y',
        'm-d-Y',
        'Y-m-d',
        'm/d/Y',
        'd/m/Y',
        'Y/m/d',
        'm.d.Y',
        'd.m.Y',
        'Y.m.d'
    ],

    'decimal_separator' => [
        '.',
        ','
    ],

    'thousand_separator' => [
        '.',
        ',',
        'space'
    ],
    'number_of_decimal' => [
        '2'
    ],
];
