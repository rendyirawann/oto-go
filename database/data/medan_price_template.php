<?php

/*
|--------------------------------------------------------------------------
| Tarif Pasar Medan 2026 - template harga awal (PLAN.md bagian 9)
|--------------------------------------------------------------------------
|
| Single source of truth for the default Medan price template. Read by
| MedanPriceTemplateSeeder, the public price estimator, and the AI agent's
| price tool. All money is integer rupiah.
|
| Angka ini ESTIMASI kisaran harga pasar, bukan harga resmi. Owner wajib
| meninjau dan menyesuaikan sebelum outlet aktif.
|
| Vehicle category codes (PLAN.md 4.3):
|   motor_matic, motor_bebek, motor_sport, motor_big,
|   car_city, car_sedan, car_mpv, car_suv, car_pickup, car_van, car_premium
|
*/

// Add-on prices in the plan are quoted for an MPV; other car categories sit
// roughly Rp 5.000-15.000 around it. These offsets spell that out.
$carOffsets = static function (int $mpv): array {
    $big = $mpv >= 100000;

    return [
        'car_city' => $mpv - ($big ? 15000 : 5000),
        'car_sedan' => $mpv - ($big ? 10000 : 5000),
        'car_mpv' => $mpv,
        'car_suv' => $mpv + ($big ? 10000 : 5000),
        'car_pickup' => $mpv + ($big ? 10000 : 5000),
        'car_van' => $mpv + ($big ? 15000 : 10000),
        'car_premium' => $mpv + 15000,
    ];
};

$motor = static fn (int $maticBebek, ?int $sport, ?int $big) => array_filter([
    'motor_matic' => $maticBebek,
    'motor_bebek' => $maticBebek,
    'motor_sport' => $sport,
    'motor_big' => $big,
], static fn ($v) => $v !== null);

$car = static fn (int $city, int $sedanMpv, int $suvPickup, int $vanPremium) => [
    'car_city' => $city,
    'car_sedan' => $sedanMpv,
    'car_mpv' => $sedanMpv,
    'car_suv' => $suvPickup,
    'car_pickup' => $suvPickup,
    'car_van' => $vanPremium,
    'car_premium' => $vanPremium,
];

return [
    'name' => 'Tarif Pasar Medan 2026',
    'city' => 'Medan',
    'disclaimer' => 'Estimasi kisaran harga pasar Kota Medan 2026, bukan harga resmi. Harga akhir ditentukan masing-masing usaha.',

    'items' => [
        // --- 9.1 Doorsmeer: cuci reguler (cuci salju) ---------------------
        ['module' => 'carwash', 'group' => 'Cuci', 'service' => 'Cuci reguler (cuci salju)', 'duration' => 30, 'prices' => [
            'motor_matic' => 15000, 'motor_bebek' => 15000, 'motor_sport' => 20000, 'motor_big' => 35000,
            'car_city' => 35000, 'car_sedan' => 40000, 'car_mpv' => 45000, 'car_suv' => 50000,
            'car_pickup' => 55000, 'car_van' => 65000, 'car_premium' => 75000,
        ]],

        // --- 9.2 Doorsmeer: add-on dan paket lain (mobil) ----------------
        ['module' => 'carwash', 'group' => 'Interior', 'service' => 'Cuci + vakum interior', 'duration' => 45, 'prices' => $carOffsets(60000)],
        ['module' => 'carwash', 'group' => 'Cuci', 'service' => 'Cuci kolong / underbody', 'duration' => 20, 'addon' => true, 'prices' => $carOffsets(30000)],
        ['module' => 'carwash', 'group' => 'Cuci', 'service' => 'Cuci mesin (engine)', 'duration' => 30, 'addon' => true, 'prices' => $carOffsets(50000)],
        ['module' => 'carwash', 'group' => 'Detailing', 'service' => 'Wax / semir body', 'duration' => 30, 'addon' => true, 'prices' => $carOffsets(50000)],
        ['module' => 'carwash', 'group' => 'Interior', 'service' => 'Fogging / pengharum kabin', 'duration' => 20, 'addon' => true, 'prices' => $carOffsets(50000)],
        ['module' => 'carwash', 'group' => 'Interior', 'service' => 'Salon interior (jok, plafon, karpet)', 'duration' => 240, 'prices' => $carOffsets(350000)],
        ['module' => 'carwash', 'group' => 'Detailing', 'service' => 'Poles body / detailing exterior', 'duration' => 240, 'prices' => $carOffsets(400000)],

        // --- 9.3 Bengkel motor: jasa (belum termasuk sparepart/oli) ------
        ['module' => 'workshop', 'group' => 'Servis Berkala', 'service' => 'Servis ringan / tune-up motor', 'duration' => 60, 'prices' => $motor(60000, 80000, 200000)],
        ['module' => 'workshop', 'group' => 'Servis Berkala', 'service' => 'Ganti oli motor (jasa)', 'duration' => 15, 'prices' => $motor(10000, 10000, 30000)],
        ['module' => 'workshop', 'group' => 'Mesin', 'service' => 'Servis CVT (matic)', 'duration' => 60, 'prices' => ['motor_matic' => 60000]],
        ['module' => 'workshop', 'group' => 'Rem', 'service' => 'Ganti kampas rem motor (per roda)', 'duration' => 30, 'prices' => $motor(20000, 25000, 50000)],
        ['module' => 'workshop', 'group' => 'Kaki-kaki', 'service' => 'Setel / ganti rantai', 'duration' => 30, 'prices' => ['motor_bebek' => 25000, 'motor_sport' => 30000, 'motor_big' => 60000]],
        ['module' => 'workshop', 'group' => 'Mesin', 'service' => 'Servis injeksi / throttle body', 'duration' => 45, 'prices' => $motor(50000, 60000, 150000)],
        ['module' => 'workshop', 'group' => 'Kelistrikan', 'service' => 'Cek kelistrikan motor', 'duration' => 30, 'prices' => $motor(30000, 35000, 75000)],

        // --- 9.4 Bengkel mobil: jasa (belum termasuk sparepart/oli) ------
        ['module' => 'workshop', 'group' => 'Servis Berkala', 'service' => 'Servis berkala ringan mobil', 'duration' => 90, 'prices' => $car(150000, 200000, 250000, 350000)],
        ['module' => 'workshop', 'group' => 'Servis Berkala', 'service' => 'Ganti oli mobil (jasa)', 'duration' => 20, 'prices' => $car(25000, 30000, 35000, 50000)],
        ['module' => 'workshop', 'group' => 'Mesin', 'service' => 'Tune-up mobil', 'duration' => 120, 'prices' => $car(250000, 300000, 350000, 450000)],
        ['module' => 'workshop', 'group' => 'Kaki-kaki', 'service' => 'Spooring', 'duration' => 45, 'prices' => $car(100000, 120000, 150000, 200000)],
        ['module' => 'workshop', 'group' => 'Kaki-kaki', 'service' => 'Balancing 4 roda', 'duration' => 45, 'prices' => $car(80000, 100000, 120000, 160000)],
        ['module' => 'workshop', 'group' => 'Rem', 'service' => 'Servis rem 2 roda', 'duration' => 60, 'prices' => $car(100000, 120000, 150000, 200000)],
        ['module' => 'workshop', 'group' => 'Mesin', 'service' => 'Kuras radiator', 'duration' => 45, 'prices' => $car(75000, 85000, 100000, 125000)],
        ['module' => 'workshop', 'group' => 'AC', 'service' => 'Servis AC (cuci evaporator)', 'duration' => 180, 'prices' => $car(300000, 350000, 400000, 500000)],
        ['module' => 'workshop', 'group' => 'Kelistrikan', 'service' => 'Scan OBD / diagnosa', 'duration' => 30, 'prices' => [
            'car_city' => 100000, 'car_sedan' => 100000, 'car_mpv' => 100000, 'car_suv' => 125000,
            'car_pickup' => 125000, 'car_van' => 150000, 'car_premium' => 150000,
        ]],
    ],

    // --- 9.5 Sparepart: contoh produk seed (harga generik, tanpa merek) ---
    'products' => [
        ['name' => 'Oli motor matic 0,8 L', 'vehicle_type' => 'motor', 'unit' => 'botol', 'price' => 55000],
        ['name' => 'Oli motor 1 L', 'vehicle_type' => 'motor', 'unit' => 'botol', 'price' => 60000],
        ['name' => 'Oli gardan matic 120 ml', 'vehicle_type' => 'motor', 'unit' => 'botol', 'price' => 18000],
        ['name' => 'Busi motor', 'vehicle_type' => 'motor', 'unit' => 'pcs', 'price' => 25000],
        ['name' => 'Kampas rem motor (set)', 'vehicle_type' => 'motor', 'unit' => 'set', 'price' => 50000],
        ['name' => 'V-belt matic', 'vehicle_type' => 'motor', 'unit' => 'pcs', 'price' => 120000],
        ['name' => 'Aki motor kering', 'vehicle_type' => 'motor', 'unit' => 'pcs', 'price' => 250000],
        ['name' => 'Oli mobil 4 L', 'vehicle_type' => 'mobil', 'unit' => 'galon', 'price' => 300000],
        ['name' => 'Filter oli mobil', 'vehicle_type' => 'mobil', 'unit' => 'pcs', 'price' => 50000],
        ['name' => 'Filter udara mobil', 'vehicle_type' => 'mobil', 'unit' => 'pcs', 'price' => 90000],
        ['name' => 'Kampas rem depan mobil (set)', 'vehicle_type' => 'mobil', 'unit' => 'set', 'price' => 250000],
        ['name' => 'Wiper blade (sepasang)', 'vehicle_type' => 'mobil', 'unit' => 'pasang', 'price' => 100000],
        ['name' => 'Aki mobil NS40', 'vehicle_type' => 'mobil', 'unit' => 'pcs', 'price' => 800000],
        ['name' => 'Air radiator coolant 1 L', 'vehicle_type' => 'mobil', 'unit' => 'botol', 'price' => 35000],
    ],

    // --- 9.6 Tarif logistik default (per outlet) ------------------------
    // Biaya antar jemput mencakup perjalanan pergi dan pulang.
    'logistics' => [
        ['mode' => 'pickup_delivery', 'vehicle_type' => 'motor', 'base_fee' => 15000, 'included_km' => 3, 'per_km_fee' => 3000, 'max_radius_km' => 10],
        ['mode' => 'pickup_delivery', 'vehicle_type' => 'mobil', 'base_fee' => 40000, 'included_km' => 3, 'per_km_fee' => 5000, 'max_radius_km' => 15],
        ['mode' => 'home_service', 'vehicle_type' => 'motor', 'base_fee' => 20000, 'included_km' => 3, 'per_km_fee' => 3000, 'max_radius_km' => 10],
        ['mode' => 'home_service', 'vehicle_type' => 'mobil', 'base_fee' => 35000, 'included_km' => 3, 'per_km_fee' => 4000, 'max_radius_km' => 10],
    ],

    // --- 9.7 Wilayah layanan awal: 21 kecamatan Kota Medan --------------
    'service_areas' => [
        'Medan Amplas', 'Medan Area', 'Medan Barat', 'Medan Baru', 'Medan Belawan', 'Medan Deli',
        'Medan Denai', 'Medan Helvetia', 'Medan Johor', 'Medan Kota', 'Medan Labuhan', 'Medan Maimun',
        'Medan Marelan', 'Medan Perjuangan', 'Medan Petisah', 'Medan Polonia', 'Medan Selayang',
        'Medan Sunggal', 'Medan Tembung', 'Medan Timur', 'Medan Tuntungan',
    ],

    // --- 9.8 Paket langganan usaha (usulan awal) -------------------------
    // monthly_price in rupiah; yearly = 10x monthly (2 bulan gratis).
    'plans' => [
        ['code' => 'trial', 'name' => 'Trial', 'modules' => 'Semua modul', 'monthly_price' => 0, 'trial_days' => 14, 'max_outlets' => 1, 'commission_percent' => 5.0],
        ['code' => 'starter', 'name' => 'Starter', 'modules' => '1 modul pilihan', 'monthly_price' => 99000, 'max_outlets' => 1, 'commission_percent' => 5.0],
        ['code' => 'duo', 'name' => 'Duo', 'modules' => '2 modul pilihan', 'monthly_price' => 179000, 'max_outlets' => 2, 'commission_percent' => 4.0],
        ['code' => 'lengkap', 'name' => 'Lengkap', 'modules' => 'Semua modul', 'monthly_price' => 249000, 'max_outlets' => 3, 'commission_percent' => 3.0],
        ['code' => 'bisnis', 'name' => 'Bisnis', 'modules' => 'Semua modul', 'monthly_price' => 499000, 'max_outlets' => 10, 'commission_percent' => 2.5],
    ],

    'vehicle_categories' => [
        ['type' => 'motor', 'code' => 'motor_matic', 'name' => 'Motor Matic'],
        ['type' => 'motor', 'code' => 'motor_bebek', 'name' => 'Motor Bebek'],
        ['type' => 'motor', 'code' => 'motor_sport', 'name' => 'Motor Sport / Naked (≤250cc)'],
        ['type' => 'motor', 'code' => 'motor_big', 'name' => 'Motor Besar (>250cc)'],
        ['type' => 'mobil', 'code' => 'car_city', 'name' => 'City Car / Hatchback'],
        ['type' => 'mobil', 'code' => 'car_sedan', 'name' => 'Sedan'],
        ['type' => 'mobil', 'code' => 'car_mpv', 'name' => 'MPV (Avanza, Xenia, Ertiga, dll)'],
        ['type' => 'mobil', 'code' => 'car_suv', 'name' => 'SUV (Fortuner, Pajero, CR-V, dll)'],
        ['type' => 'mobil', 'code' => 'car_pickup', 'name' => 'Pickup / Double Cabin'],
        ['type' => 'mobil', 'code' => 'car_van', 'name' => 'Minibus / Van (HiAce, Elf, dll)'],
        ['type' => 'mobil', 'code' => 'car_premium', 'name' => 'Premium / Eropa / Mewah'],
    ],
];
