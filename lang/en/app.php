<?php

return [

    'nav' => [
        'dashboard' => 'Dashboard',
        'reports' => 'Reports',
        'sales' => 'Sales',
        'monthly' => 'Monthly',
        'yearly' => 'Yearly',
        'products' => 'Products',
        'profile' => 'Profile',
        'logout' => 'Log Out',
        'open_menu' => 'Open menu',
        'close_menu' => 'Close menu',
    ],

    'dashboard' => [
        'welcome_back' => 'Welcome back',
        'vs_yesterday' => 'vs yesterday',
        'today_sales' => "Today's Sales",
        'this_month_sales' => 'This Month Sales',
        'this_year_sales' => 'This Year Sales',
        'today_transactions' => "Today's Transactions",
        'daily_sales_title' => 'Daily Sales &mdash; :month',
        'daily_sales_subtitle' => 'Total sales by day, current month',
        'monthly_sales_title' => 'Monthly Sales &mdash; :year',
        'monthly_sales_subtitle' => 'Total sales by month, current year',
        'recent_receipts' => 'Recent Receipts',
        'view_all' => 'View All &rarr;',
        'no_receipts' => 'No receipts yet.',
        'top_categories' => 'Top Selling Categories',
        'this_month' => 'This month',
        'no_category_data' => 'No category data for this month.',
    ],

    'dashboard_admin' => [
        'title' => 'Admin panel coming soon',
        'description' => 'This sales report dashboard is for customer accounts. The company and SalesPlay account management panel for admins is under construction.',
    ],

    'filters' => [
        'today' => 'Today',
        'yesterday' => 'Yesterday',
        'this_month' => 'This Month',
        'last_month' => 'Last Month',
        'custom' => 'Date Range',
        'apply' => 'Apply',
    ],

    'reports' => [
        'sales' => [
            'title' => 'Sales Report',
            'subtitle' => 'List of transactions by date',
            'total_sales' => 'Total Sales - :period',
            'total_transactions' => 'Total Transactions - :period',
            'no_transactions' => 'No transactions for this period.',
            'date' => 'Date',
            'receipt_number' => 'Receipt No.',
            'payment_method' => 'Payment Method',
            'total' => 'Total',
            'back_to_list' => '&larr; Sales Report',
        ],
        'monthly' => [
            'title' => 'Monthly Report',
            'subtitle' => 'Sales summary by month',
            'total_sales' => 'Total Sales :year',
            'total_transactions' => 'Total Transactions :year',
            'chart_title' => 'Monthly Sales &mdash; :year',
            'month' => 'Month',
            'transactions' => 'Transactions',
            'total_sales_col' => 'Total Sales',
        ],
        'yearly' => [
            'title' => 'Yearly Report',
            'subtitle' => 'Sales summary by year',
            'grand_total' => 'Grand Total Sales',
            'grand_transactions' => 'Grand Total Transactions',
            'no_data' => 'No sales data yet.',
            'year' => 'Year',
            'transactions' => 'Transactions',
            'total_sales' => 'Total Sales',
            'view_monthly' => 'View Monthly &rarr;',
        ],
        'products' => [
            'title' => 'Product Report',
            'subtitle' => 'Sales performance by product',
            'no_data' => 'No product sales for this period.',
            'product_name' => 'Product Name',
            'quantity_sold' => 'Quantity Sold',
            'total_sales' => 'Total Sales',
        ],
    ],

    'receipt' => [
        'item' => 'Item',
        'subtotal' => 'Subtotal',
        'discount' => 'Discount',
        'tax' => 'Tax',
        'total' => 'Total',
        'payment' => 'Payment',
    ],

];
