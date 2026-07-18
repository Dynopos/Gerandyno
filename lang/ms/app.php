<?php

return [

    'nav' => [
        'dashboard' => 'Dashboard',
        'reports' => 'Laporan',
        'sales' => 'Jualan',
        'monthly' => 'Bulanan',
        'yearly' => 'Tahunan',
        'products' => 'Produk',
        'profile' => 'Profil',
        'logout' => 'Log Keluar',
        'open_menu' => 'Buka menu',
        'close_menu' => 'Tutup menu',
    ],

    'dashboard' => [
        'welcome_back' => 'Selamat kembali',
        'vs_yesterday' => 'berbanding semalam',
        'today_sales' => 'Jualan Hari Ini',
        'this_month_sales' => 'Jualan Bulan Ini',
        'this_year_sales' => 'Jualan Tahun Ini',
        'today_transactions' => 'Transaksi Hari Ini',
        'daily_sales_title' => 'Jualan Harian &mdash; :month',
        'daily_sales_subtitle' => 'Jumlah jualan mengikut hari, bulan semasa',
        'monthly_sales_title' => 'Jualan Bulanan &mdash; :year',
        'monthly_sales_subtitle' => 'Jumlah jualan mengikut bulan, tahun semasa',
        'recent_receipts' => 'Resit Terkini',
        'view_all' => 'Lihat Semua &rarr;',
        'no_receipts' => 'Tiada resit lagi.',
        'top_categories' => 'Top Kategori Jualan',
        'this_month' => 'Bulan semasa',
        'no_category_data' => 'Tiada data kategori bulan ini.',
    ],

    'dashboard_admin' => [
        'title' => 'Panel Admin akan datang',
        'description' => 'Dashboard laporan jualan ini adalah untuk akaun customer. Panel pengurusan company dan SalesPlay account untuk admin sedang dibina.',
    ],

    'filters' => [
        'today' => 'Hari Ini',
        'yesterday' => 'Semalam',
        'this_month' => 'Bulan Ini',
        'last_month' => 'Bulan Lepas',
        'custom' => 'Julat Tarikh',
        'apply' => 'Guna',
    ],

    'reports' => [
        'sales' => [
            'title' => 'Laporan Jualan',
            'subtitle' => 'Senarai transaksi mengikut tarikh',
            'total_sales' => 'Jumlah Jualan - :period',
            'total_transactions' => 'Bilangan Transaksi - :period',
            'no_transactions' => 'Tiada transaksi untuk tempoh ini.',
            'date' => 'Tarikh',
            'receipt_number' => 'No. Resit',
            'payment_method' => 'Kaedah Bayaran',
            'total' => 'Jumlah',
            'back_to_list' => '&larr; Laporan Jualan',
        ],
        'monthly' => [
            'title' => 'Laporan Bulanan',
            'subtitle' => 'Ringkasan jualan mengikut bulan',
            'total_sales' => 'Jumlah Jualan :year',
            'total_transactions' => 'Bilangan Transaksi :year',
            'chart_title' => 'Jualan Bulanan &mdash; :year',
            'month' => 'Bulan',
            'transactions' => 'Bilangan Transaksi',
            'total_sales_col' => 'Jumlah Jualan',
        ],
        'yearly' => [
            'title' => 'Laporan Tahunan',
            'subtitle' => 'Ringkasan jualan mengikut tahun',
            'grand_total' => 'Jumlah Jualan Keseluruhan',
            'grand_transactions' => 'Jumlah Transaksi Keseluruhan',
            'no_data' => 'Tiada data jualan lagi.',
            'year' => 'Tahun',
            'transactions' => 'Bilangan Transaksi',
            'total_sales' => 'Jumlah Jualan',
            'view_monthly' => 'Lihat Bulanan &rarr;',
        ],
        'products' => [
            'title' => 'Laporan Produk',
            'subtitle' => 'Prestasi jualan mengikut produk',
            'no_data' => 'Tiada jualan produk untuk tempoh ini.',
            'product_name' => 'Nama Produk',
            'quantity_sold' => 'Kuantiti Dijual',
            'total_sales' => 'Jumlah Jualan',
        ],
    ],

    'receipt' => [
        'item' => 'Item',
        'subtotal' => 'Subtotal',
        'discount' => 'Diskaun',
        'tax' => 'Cukai',
        'total' => 'Jumlah',
        'payment' => 'Bayaran',
    ],

];
