<?php

// Non-secret settings. WooCommerce measurements take priority over this temporary fallback.
return [
    'base_url' => 'https://api.cdek.ru/v2',
    'shipment_point' => 'MTSHCH91',
    'tariff_code' => 136, // Parcel: pickup office -> pickup office. Availability checked before payment.
    'sender_name' => 'ИП Брода Сергей Борисович',
    'sender_phone' => '+79153544469',
    'default_package' => ['weight' => 400, 'length' => 40, 'width' => 30, 'height' => 25],
    'package_comment' => 'Ориентир клиента: объём двух обувных коробок, вес 300–400 г. Перед сдачей проверить фактические вес и габариты.',
];
