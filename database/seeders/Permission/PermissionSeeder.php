<?php

namespace Database\Seeders\Permission;

use App\Models\Permission\Permission;
use App\Models\Role\Role;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            // ===== Dashboard =====
            [
                'name' => 'view_dashboard_statistics',
                'label' => 'Can view dashboard statistics',
                'group_name' => 'dashboards',
            ],
            [
                'name' => 'view_dashboard_purchases_and_sales_graph',
                'label' => 'Can view dashboard purchases and sales overview',
                'group_name' => 'dashboards',
            ],
            [
                'name' => 'view_dashboard_top_sell_products_graph',
                'label' => 'Can view dashboard top sell products',
                'group_name' => 'dashboards',
            ],
            [
                'name' => 'view_dashboard_top_sell_customer_graph',
                'label' => 'Can view dashboard top sell customer',
                'group_name' => 'dashboards',
            ],
            [
                'name' => 'view_dashboard_sent_and_received_graph',
                'label' => 'Can view dashboard sent and received overview',
                'group_name' => 'dashboards',
            ],
            [
                'name' => 'view_dashboard_purchase_sale_and_return_overviews',
                'label' => 'Can view dashboard purchase sale and return overview',
                'group_name' => 'dashboards',
            ],

            // ===== Products =====
            [
                'name' => 'view_products',
                'label' => 'Can view products',
                'group_name' => 'products',
            ],
            [
                'name' => 'create_products',
                'label' => 'Can create products',
                'group_name' => 'products',
            ],
            [
                'name' => 'update_products',
                'label' => 'Can update products',
                'group_name' => 'products',
            ],
            [
                'name' => 'delete_products',
                'label' => 'Can delete products',
                'group_name' => 'products',
            ],
            [
                'name' => 'update_products_variant',
                'label' => 'Can update product variants',
                'group_name' => 'products',
            ],
            [
                'name' => 'update_product_status',
                'label' => 'Can update product status',
                'group_name' => 'products',
            ],
            [
                'name' => 'update_product_warranty',
                'label' => 'Can update product warranty',
                'group_name' => 'products',
            ],
            [
                'name' => 'update_product_ecommerce',
                'label' => 'Can toggle product ecommerce visibility',
                'group_name' => 'products',
            ],
            [
                'name' => 'update_product_chinese',
                'label' => 'Can toggle product Chinese badge',
                'group_name' => 'products',
            ],
            [
                'name' => 'update_product_unofficial',
                'label' => 'Can toggle product Unofficial badge',
                'group_name' => 'products',
            ],
            [
                'name' => 'update_product_refurbished',
                'label' => 'Can toggle product Refurbished badge',
                'group_name' => 'products',
            ],

            [
                'name' => 'view_products_care',
                'label' => 'Can view products care',
                'group_name' => 'products',
            ],
            [
                'name' => 'create_products_care',
                'label' => 'Can create products care',
                'group_name' => 'products',
            ],

            [
                'name' => 'view_pre_owned_products',
                'label' => 'Can view pre owned products',
                'group_name' => 'pre_owneds_products',
            ],
            [
                'name' => 'create_pre_owned_products',
                'label' => 'Can create pre owned products',
                'group_name' => 'pre_owneds_products',
            ],
            [
                'name' => 'update_pre_owned_products',
                'label' => 'Can update pre owned products',
                'group_name' => 'pre_owneds_products',
            ],
            [
                'name' => 'delete_pre_owned_products',
                'label' => 'Can delete pre owned products',
                'group_name' => 'pre_owneds_products',
            ],
            [
                'name' => 'update_status_pre_owned_products',
                'label' => 'Can update status pre owned products',
                'group_name' => 'pre_owneds_products',
            ],
            [
                'name' => 'view_pre_owned_products_care',
                'label' => 'Can view pre owned products care',
                'group_name' => 'pre_owneds_products',
            ],
            [
                'name' => 'create_pre_owned_products_care',
                'label' => 'Can create pre owned products care',
                'group_name' => 'pre_owneds_products',
            ],
            [
                'name' => 'view_categories',
                'label' => 'Can view categories',
                'group_name' => 'categories',
            ],
            [
                'name' => 'create_categories',
                'label' => 'Can create categories',
                'group_name' => 'categories',
            ],
            [
                'name' => 'update_categories',
                'label' => 'Can update categories',
                'group_name' => 'categories',
            ],
            [
                'name' => 'delete_categories',
                'label' => 'Can delete categories',
                'group_name' => 'categories',
            ],
            [
                'name' => 'view_brands',
                'label' => 'Can view brands',
                'group_name' => 'brands',
            ],
            [
                'name' => 'create_brands',
                'label' => 'Can create brands',
                'group_name' => 'brands',
            ],
            [
                'name' => 'update_brands',
                'label' => 'Can update brands',
                'group_name' => 'brands',
            ],
            [
                'name' => 'delete_brands',
                'label' => 'Can delete brands',
                'group_name' => 'brands',
            ],
            [
                'name' => 'view_all_pages',
                'label' => 'Can view all pages',
                'group_name' => 'pages',
            ],
            [
                'name' => 'update_all_pages',
                'label' => 'Can update all pages',
                'group_name' => 'pages',
            ],
            [
                'name' => 'view_attributes',
                'label' => 'Can view attributes',
                'group_name' => 'attributes',
            ],
            [
                'name' => 'create_attributes',
                'label' => 'Can create attributes',
                'group_name' => 'attributes',
            ],
            [
                'name' => 'update_attributes',
                'label' => 'Can update attributes',
                'group_name' => 'attributes',
            ],
            [
                'name' => 'delete_attributes',
                'label' => 'Can delete attributes',
                'group_name' => 'attributes',
            ],
            [
                'name' => 'view_specification_groups',
                'label' => 'Can view specification groups',
                'group_name' => 'specification_groups',
            ],
            [
                'name' => 'create_specification_groups',
                'label' => 'Can create specification groups',
                'group_name' => 'specification_groups',
            ],
            [
                'name' => 'update_specification_groups',
                'label' => 'Can update specification groups',
                'group_name' => 'specification_groups',
            ],
            [
                'name' => 'delete_specification_groups',
                'label' => 'Can delete specification groups',
                'group_name' => 'specification_groups',
            ],
            [
                'name' => 'scrape_product_specifications',
                'label' => 'Can scrape product specifications',
                'group_name' => 'product_specifications',
            ],
            [
                'name' => 'create_scrape_product_specifications',
                'label' => 'Can create scrape product specifications',
                'group_name' => 'product_specifications',
            ],
            [
                'name' => 'view_product_specifications',
                'label' => 'Can view product specifications',
                'group_name' => 'product_specifications',
            ],
            [
                'name' => 'create_product_specifications',
                'label' => 'Can create product specifications',
                'group_name' => 'product_specifications',
            ],
            [
                'name' => 'update_product_specifications',
                'label' => 'Can update product specifications',
                'group_name' => 'product_specifications',
            ],
            [
                'name' => 'delete_product_specifications',
                'label' => 'Can delete product specifications',
                'group_name' => 'product_specifications',
            ],

            [
                'name' => 'product_barcode_generate',
                'label' => 'Can generate product barcode',
                'group_name' => 'barcodes',
            ],
            [
                'name' => 'product_print_barcodes',
                'label' => 'Can print barcodes',
                'group_name' => 'barcodes',
            ],
            [
                'name' => 'view_product_barcodes',
                'label' => 'Can view product barcodes',
                'group_name' => 'barcodes',
            ],
            [
                'name' => 'view_product_serial_history',
                'label' => 'Can view product barcode history',
                'group_name' => 'barcodes',
            ],
            [
                'name' => 'delete_product_barcodes',
                'label' => 'Can delete product barcode history',
                'group_name' => 'barcodes',
            ],
            [
                'name' => 'view_barcode_generates',
                'label' => 'Can view generate barcodes',
                'group_name' => 'barcodes',
            ],

            // ===== Purchases =====
            [
                'name' => 'view_purchases',
                'label' => 'Can view purchases',
                'group_name' => 'purchases',
            ],

            [
                'name' => 'create_purchases',
                'label' => 'Can create purchases',
                'group_name' => 'purchases',
            ],
            [
                'name' => 'update_purchases',
                'label' => 'Can update purchases',
                'group_name' => 'purchases',
            ],
            [
                'name' => 'delete_purchases',
                'label' => 'Can delete purchases',
                'group_name' => 'purchases',
            ],
            [
                'name' => 'download_purchase_details',
                'label' => 'Can download purchase details',
                'group_name' => 'purchases',
            ],

            [
                'name' => 'view_purchase_returns',
                'label' => 'Can view purchase returns',
                'group_name' => 'purchase_returns',
            ],

            [
                'name' => 'create_purchase_returns',
                'label' => 'Can create purchase returns',
                'group_name' => 'purchase_returns',
            ],
            [
                'name' => 'update_purchase_returns',
                'label' => 'Can update purchase returns',
                'group_name' => 'purchase_returns',
            ],
            [
                'name' => 'delete_purchase_returns',
                'label' => 'Can delete purchase returns',
                'group_name' => 'purchase_returns',
            ],

            [
                'name' => 'download_purchase_return_details',
                'label' => 'Can download purchase return details',
                'group_name' => 'purchase_returns',
            ],

            // ===== Sales =====
            [
                'name' => 'view_sales',
                'label' => 'Can view sales',
                'group_name' => 'sales',
            ],

            [
                'name' => 'create_sales',
                'label' => 'Can create sales',
                'group_name' => 'sales',
            ],
            [
                'name' => 'update_sales',
                'label' => 'Can update sales',
                'group_name' => 'sales',
            ],

            [
                'name' => 'delete_sales',
                'label' => 'Can delete sales',
                'group_name' => 'sales',
            ],

            [
                'name' => 'download_sale_details',
                'label' => 'Can download sale details',
                'group_name' => 'sales',
            ],
            [
                'name' => 'update_seller_sales',
                'label' => 'Can update seller on sales',
                'group_name' => 'sales',
            ],

            [
                'name' => 'view_sale_returns',
                'label' => 'Can view sale returns',
                'group_name' => 'sale_returns',
            ],

            [
                'name' => 'create_sale_returns',
                'label' => 'Can create sale returns',
                'group_name' => 'sale_returns',
            ],
            [
                'name' => 'update_sale_returns',
                'label' => 'Can update sale returns',
                'group_name' => 'sale_returns',
            ],
            [
                'name' => 'delete_sale_returns',
                'label' => 'Can update sale returns',
                'group_name' => 'sale_returns',
            ],
            [
                'name' => 'download_sale_return_details',
                'label' => 'Can download sale return details',
                'group_name' => 'sale_returns',
            ],

            // ===== Couriers =====
            [
                'name' => 'view_courier_orders',
                'label' => 'Can view all courier orders',
                'group_name' => 'couriers',
            ],
            [
                'name' => 'view_pathao_orders',
                'label' => 'Can view Pathao orders',
                'group_name' => 'couriers',
            ],
            [
                'name' => 'sync_pathao_orders',
                'label' => 'Can sync Pathao orders',
                'group_name' => 'couriers',
            ],
            [
                'name' => 'view_pathao_stores',
                'label' => 'Can view Pathao stores',
                'group_name' => 'couriers',
            ],
            [
                'name' => 'view_pathao_cities',
                'label' => 'Can view Pathao cities',
                'group_name' => 'couriers',
            ],
            [
                'name' => 'view_pathao_zones',
                'label' => 'Can view Pathao zones',
                'group_name' => 'couriers',
            ],
            [
                'name' => 'view_pathao_areas',
                'label' => 'Can view Pathao areas',
                'group_name' => 'couriers',
            ],
            [
                'name' => 'create_pathao_order',
                'label' => 'Can send a sale via Pathao',
                'group_name' => 'couriers',
            ],

            [
                'name' => 'view_steadfast_orders',
                'label' => 'Can view Steadfast orders',
                'group_name' => 'couriers',
            ],
            [
                'name' => 'create_steadfast_order',
                'label' => 'Can send a sale via Steadfast',
                'group_name' => 'couriers',
            ],
            [
                'name' => 'view_steadfast_districts',
                'label' => 'Can view Steadfast districts',
                'group_name' => 'couriers',
            ],
            [
                'name' => 'view_steadfast_thanas',
                'label' => 'Can view Steadfast thanas',
                'group_name' => 'couriers',
            ],
            [
                'name' => 'import_steadfast_orders',
                'label' => 'Can import Steadfast orders from Excel',
                'group_name' => 'couriers',
            ],
            [
                'name' => 'view_steadfast_trackings',
                'label' => 'Can track Steadfast parcels',
                'group_name' => 'couriers',
            ],
            [
                'name' => 'view_pathao_trackings',
                'label' => 'Can track Pathao parcels',
                'group_name' => 'couriers',
            ],

            [
                'name' => 'view_redx_orders',
                'label' => 'Can view REDX orders',
                'group_name' => 'couriers',
            ],
            [
                'name' => 'create_redx_order',
                'label' => 'Can send a sale via REDX',
                'group_name' => 'couriers',
            ],
            [
                'name' => 'view_redx_areas',
                'label' => 'Can view REDX delivery areas',
                'group_name' => 'couriers',
            ],
            [
                'name' => 'view_redx_stores',
                'label' => 'Can view REDX pickup stores',
                'group_name' => 'couriers',
            ],
            [
                'name' => 'view_redx_trackings',
                'label' => 'Can track REDX parcels',
                'group_name' => 'couriers',
            ],

            [
                'name' => 'view_bdcourier_check_fraud',
                'label' => 'Can run the courier fraud check',
                'group_name' => 'couriers',
            ],
            [
                'name' => 'view_bdcourier_check_fraud_api',
                'label' => 'Can run the BD Courier API fraud check',
                'group_name' => 'couriers',
            ],

            // ===== Stock & Cash Transfer =====
            [
                'name' => 'view_stock_transfers',
                'label' => 'Can view stock transfers',
                'group_name' => 'stock_transfers',
            ],
            [
                'name' => 'create_stock_transfers',
                'label' => 'Can create stock transfers',
                'group_name' => 'stock_transfers',
            ],
            [
                'name' => 'approve_stock_transfers',
                'label' => 'Can approve stock transfers',
                'group_name' => 'stock_transfers',
            ],
            [
                'name' => 'cancel_stock_transfers',
                'label' => 'Can cancel stock transfers',
                'group_name' => 'stock_transfers',
            ],
            [
                'name' => 'delete_stock_transfers',
                'label' => 'Can delete stock transfers',
                'group_name' => 'stock_transfers',
            ],
            [
                'name' => 'view_cash_transfers',
                'label' => 'Can view cash transfers',
                'group_name' => 'cash_transfers',
            ],
            [
                'name' => 'create_cash_transfers',
                'label' => 'Can create cash transfers',
                'group_name' => 'cash_transfers',
            ],
            [
                'name' => 'delete_cash_transfers',
                'label' => 'Can delete cash transfers',
                'group_name' => 'cash_transfers',
            ],
            [
                'name' => 'approve_cash_transfers',
                'label' => 'Can approve cash transfers',
                'group_name' => 'cash_transfers',
            ],
            [
                'name' => 'cancel_cash_transfers',
                'label' => 'Can cancel cash transfers',
                'group_name' => 'cash_transfers',
            ],
            [
                'name' => 'view_damage_products',
                'label' => 'Can view damage products',
                'group_name' => 'damage_products',
            ],
            [
                'name' => 'create_damage_products',
                'label' => 'Can create damage products',
                'group_name' => 'damage_products',
            ],
            [
                'name' => 'approve_damage_products',
                'label' => 'Can approve damage products',
                'group_name' => 'damage_products',
            ],
            [
                'name' => 'cancel_damage_products',
                'label' => 'Can cancel damage products',
                'group_name' => 'damage_products',
            ],
            [
                'name' => 'delete_damage_products',
                'label' => 'Can delete damage products',
                'group_name' => 'damage_products',
            ],

            // ===== People =====
            [
                'name' => 'view_customers',
                'label' => 'Can view customers',
                'group_name' => 'customers',
            ],
            [
                'name' => 'create_customers',
                'label' => 'Can create customers',
                'group_name' => 'customers',
            ],
            [
                'name' => 'update_customers',
                'label' => 'Can update customers',
                'group_name' => 'customers',
            ],
            [
                'name' => 'delete_customers',
                'label' => 'Can delete customers',
                'group_name' => 'customers',
            ],
            [
                'name' => 'update_customer_ratings',
                'label' => 'Can rate customers',
                'group_name' => 'customers',
            ],
            [
                'name' => 'update_customer_notes',
                'label' => 'Can set customer notes',
                'group_name' => 'customers',
            ],
            [
                'name' => 'update_customer_price_documents',
                'label' => 'Can upload customer price documents',
                'group_name' => 'customers',
            ],
            [
                'name' => 'view_online_contacts',
                'label' => 'Can view online customers',
                'group_name' => 'online_contacts',
            ],
            [
                'name' => 'update_online_contacts',
                'label' => 'Can rate online customers',
                'group_name' => 'online_contacts',
            ],
            [
                'name' => 'update_online_contact_price_documents',
                'label' => 'Can upload online customer price documents',
                'group_name' => 'online_contacts',
            ],
            [
                'name' => 'delete_online_contacts',
                'label' => 'Can delete online customers',
                'group_name' => 'online_contacts',
            ],

            [
                'name' => 'view_customer_addresses',
                'label' => 'Can view customer addresses',
                'group_name' => 'online_contacts',
            ],
            [
                'name' => 'view_customer_elite_histories',
                'label' => 'Can view customer elite histories',
                'group_name' => 'online_contacts',
            ],
            [
                'name' => 'view_customer_shopping_carts',
                'label' => 'Can view customer shopping bags',
                'group_name' => 'online_contacts',
            ],
            [
                'name' => 'view_customer_orders',
                'label' => 'Can view customer orders',
                'group_name' => 'online_contacts',
            ],
            [
                'name' => 'view_customer_wishlists',
                'label' => 'Can view customer wishlists',
                'group_name' => 'online_contacts',
            ],

            [
                'name' => 'view_customer_reviews',
                'label' => 'Can view customer reviews',
                'group_name' => 'online_contacts',
            ],
            [
                'name' => 'view_customer_sell_device_requests',
                'label' => 'Can view customer sell device requests',
                'group_name' => 'online_contacts',
            ],
            [
                'name' => 'view_people',
                'label' => 'Can view people list',
                'group_name' => 'people',
            ],
            [
                'name' => 'view_timeline_people_history',
                'label' => 'Can view people activity history timeline',
                'group_name' => 'people',
            ],
            [
                'name' => 'bulk_delete_people',
                'label' => 'Can bulk-delete people',
                'group_name' => 'people',
            ],
            [
                'name' => 'view_suppliers',
                'label' => 'Can view suppliers',
                'group_name' => 'suppliers',
            ],
            [
                'name' => 'create_suppliers',
                'label' => 'Can create suppliers',
                'group_name' => 'suppliers',
            ],
            [
                'name' => 'update_suppliers',
                'label' => 'Can update suppliers',
                'group_name' => 'suppliers',
            ],
            [
                'name' => 'delete_suppliers',
                'label' => 'Can delete suppliers',
                'group_name' => 'suppliers',
            ],
            [
                'name' => 'update_supplier_ratings',
                'label' => 'Can rate suppliers',
                'group_name' => 'suppliers',
            ],
            [
                'name' => 'update_supplier_notes',
                'label' => 'Can set supplier notes',
                'group_name' => 'suppliers',
            ],
            [
                'name' => 'update_supplier_price_documents',
                'label' => 'Can upload supplier price documents',
                'group_name' => 'suppliers',
            ],

            // ===== Telesales =====
            [
                'name' => 'view_telesales',
                'label' => 'Can view telesales',
                'group_name' => 'telesales',
            ],
            [
                'name' => 'create_telesales',
                'label' => 'Can create telesales',
                'group_name' => 'telesales',
            ],
            [
                'name' => 'update_telesales',
                'label' => 'Can update telesales',
                'group_name' => 'telesales',
            ],
            [
                'name' => 'delete_telesales',
                'label' => 'Can delete telesales',
                'group_name' => 'telesales',
            ],
            [
                'name' => 'view_telesale_results',
                'label' => 'Can view telesale results',
                'group_name' => 'telesale_results',
            ],
            [
                'name' => 'create_telesale_results',
                'label' => 'Can create telesale results',
                'group_name' => 'telesale_results',
            ],
            [
                'name' => 'update_telesale_results',
                'label' => 'Can update telesale results',
                'group_name' => 'telesale_results',
            ],
            [
                'name' => 'delete_telesale_results',
                'label' => 'Can delete telesale results',
                'group_name' => 'telesale_results',
            ],

            // ===== Order Confirmations =====
            [
                'name' => 'view_order_confirmations',
                'label' => 'Can view order confirmations',
                'group_name' => 'order_confirmations',
            ],
            [
                'name' => 'create_order_confirmations',
                'label' => 'Can create order confirmations',
                'group_name' => 'order_confirmations',
            ],
            [
                'name' => 'update_order_confirmations',
                'label' => 'Can update order confirmations',
                'group_name' => 'order_confirmations',
            ],
            [
                'name' => 'delete_order_confirmations',
                'label' => 'Can delete order confirmations',
                'group_name' => 'order_confirmations',
            ],
            [
                'name' => 'update_order_confirmation_deliveries',
                'label' => 'Can update order delivery info',
                'group_name' => 'order_confirmations',
            ],
            [
                'name' => 'view_order_confirmation_options',
                'label' => 'Can view order confirmation options',
                'group_name' => 'order_confirmation_options',
            ],
            [
                'name' => 'create_order_confirmation_options',
                'label' => 'Can create order confirmation options',
                'group_name' => 'order_confirmation_options',
            ],
            [
                'name' => 'update_order_confirmation_options',
                'label' => 'Can update order confirmation options',
                'group_name' => 'order_confirmation_options',
            ],
            [
                'name' => 'delete_order_confirmation_options',
                'label' => 'Can delete order confirmation options',
                'group_name' => 'order_confirmation_options',
            ],

            // ===== Payments =====
            [
                'name' => 'view_payments',
                'label' => 'Can view payments',
                'group_name' => 'payments',
            ],
            [
                'name' => 'receive_customer_payment',
                'label' => 'Can receive customer payments',
                'group_name' => 'payments',
            ],
            [
                'name' => 'send_supplier_payment',
                'label' => 'Can send supplier payments',
                'group_name' => 'payments',
            ],
            [
                'name' => 'view_payment_history',
                'label' => 'Can view payment history',
                'group_name' => 'payments',
            ],
            [
                'name' => 'delete_payment_history',
                'label' => 'Can delete payment history',
                'group_name' => 'payments',
            ],
            [
                'name' => 'view_debit_voucher',
                'label' => 'Can view debit voucher',
                'group_name' => 'payments',
            ],
            [
                'name' => 'view_credit_voucher',
                'label' => 'Can view credit voucher',
                'group_name' => 'payments',
            ],
            // [
            //     'name' => 'view_sale_transactions',
            //     'label' => 'Can view sale transactions',
            //     'group_name' => 'payment_records',
            // ],
            // [
            //     'name' => 'view_sale_return_transactions',
            //     'label' => 'Can view sale return transactions',
            //     'group_name' => 'payment_records',
            // ],
            // [
            //     'name' => 'view_purchase_transactions',
            //     'label' => 'Can view purchase transactions',
            //     'group_name' => 'payment_records',
            // ],
            // [
            //     'name' => 'view_purchase_return_transactions',
            //     'label' => 'Can view purchase return transactions',
            //     'group_name' => 'payment_records',
            // ],
            // [
            //     'name' => 'view_expense_transactions',
            //     'label' => 'Can view expense transactions',
            //     'group_name' => 'payment_records',
            // ],

            // ===== Expenses =====
            [
                'name' => 'view_expenses',
                'label' => 'Can view expense',
                'group_name' => 'expenses',
            ],
            [
                'name' => 'create_expenses',
                'label' => 'Can create expense',
                'group_name' => 'expenses',
            ],
            [
                'name' => 'update_expenses',
                'label' => 'Can update expense',
                'group_name' => 'expenses',
            ],
            [
                'name' => 'delete_expenses',
                'label' => 'Can delete expense',
                'group_name' => 'expenses',
            ],
            [
                'name' => 'view_expense_voucher',
                'label' => 'Can print expense voucher',
                'group_name' => 'expenses',
            ],

            [
                'name' => 'view_expense_categories',
                'label' => 'Can view expense categories',
                'group_name' => 'expense_categories',
            ],
            [
                'name' => 'create_expense_categories',
                'label' => 'Can create expense categories',
                'group_name' => 'expense_categories',
            ],
            [
                'name' => 'update_expense_categories',
                'label' => 'Can update expense categories',
                'group_name' => 'expense_categories',
            ],
            [
                'name' => 'delete_expense_categories',
                'label' => 'Can delete expense categories',
                'group_name' => 'expense_categories',
            ],

            // ===== Incomes =====
            [
                'name' => 'view_incomes',
                'label' => 'Can view incomes',
                'group_name' => 'incomes',
            ],
            [
                'name' => 'create_incomes',
                'label' => 'Can create incomes',
                'group_name' => 'incomes',
            ],
            [
                'name' => 'update_incomes',
                'label' => 'Can update incomes',
                'group_name' => 'incomes',
            ],
            [
                'name' => 'delete_incomes',
                'label' => 'Can delete incomes',
                'group_name' => 'incomes',
            ],
            [
                'name' => 'create_income_payment',
                'label' => 'Can create income payment',
                'group_name' => 'incomes',
            ],
            [
                'name' => 'view_income_categories',
                'label' => 'Can view income categories',
                'group_name' => 'income_categories',
            ],
            [
                'name' => 'create_income_categories',
                'label' => 'Can create income categories',
                'group_name' => 'income_categories',
            ],
            [
                'name' => 'update_income_categories',
                'label' => 'Can update income categories',
                'group_name' => 'income_categories',
            ],
            [
                'name' => 'delete_income_categories',
                'label' => 'Can delete income categories',
                'group_name' => 'income_categories',
            ],

            // ===== Investments =====
            [
                'name' => 'view_investments',
                'label' => 'Can view investments',
                'group_name' => 'investments',
            ],
            [
                'name' => 'create_investments',
                'label' => 'Can create investments',
                'group_name' => 'investments',
            ],
            [
                'name' => 'update_investments',
                'label' => 'Can update investments',
                'group_name' => 'investments',
            ],
            [
                'name' => 'delete_investments',
                'label' => 'Can delete investments',
                'group_name' => 'investments',
            ],
            [
                'name' => 'view_investment_categories',
                'label' => 'Can view investment categories',
                'group_name' => 'investment_categories',
            ],
            [
                'name' => 'create_investment_categories',
                'label' => 'Can create investment categories',
                'group_name' => 'investment_categories',
            ],
            [
                'name' => 'update_investment_categories',
                'label' => 'Can update investment categories',
                'group_name' => 'investment_categories',
            ],
            [
                'name' => 'delete_investment_categories',
                'label' => 'Can delete investment categories',
                'group_name' => 'investment_categories',
            ],

            [
                'name' => 'view_directors',
                'label' => 'Can view directors',
                'group_name' => 'directors',
            ],

            [
                'name' => 'create_directors',
                'label' => 'Can create directors',
                'group_name' => 'directors',
            ],
            [
                'name' => 'update_directors',
                'label' => 'Can update directors',
                'group_name' => 'directors',
            ],
            [
                'name' => 'generate_director_profile_pdf',
                'label' => 'Can generate director profile PDF',
                'group_name' => 'directors',
            ],
            [
                'name' => 'generate_director_shop_profile_pdf',
                'label' => 'Can generate director shop-wise profile PDF',
                'group_name' => 'directors',
            ],

            // ===== Partners =====
            [
                'name' => 'view_dealers',
                'label' => 'Can view dealers',
                'group_name' => 'dealers',
            ],
            [
                'name' => 'create_dealers',
                'label' => 'Can create dealers',
                'group_name' => 'dealers',
            ],
            [
                'name' => 'update_dealers',
                'label' => 'Can update dealers',
                'group_name' => 'dealers',
            ],
            [
                'name' => 'delete_dealers',
                'label' => 'Can delete dealers',
                'group_name' => 'dealers',
            ],
            [
                'name' => 'view_retailers',
                'label' => 'Can view retailers',
                'group_name' => 'retailers',
            ],
            [
                'name' => 'create_retailers',
                'label' => 'Can create retailers',
                'group_name' => 'retailers',
            ],
            [
                'name' => 'update_retailers',
                'label' => 'Can update retailers',
                'group_name' => 'retailers',
            ],
            [
                'name' => 'delete_retailers',
                'label' => 'Can delete retailers',
                'group_name' => 'retailers',
            ],
            [
                'name' => 'view_b2b',
                'label' => 'Can view b2b',
                'group_name' => 'b2b',
            ],
            [
                'name' => 'create_b2b',
                'label' => 'Can create b2b',
                'group_name' => 'b2b',
            ],
            [
                'name' => 'update_b2b',
                'label' => 'Can update b2b',
                'group_name' => 'b2b',
            ],
            [
                'name' => 'delete_b2b',
                'label' => 'Can delete b2b',
                'group_name' => 'b2b',
            ],
            [
                'name' => 'view_franchises',
                'label' => 'Can view franchises',
                'group_name' => 'franchises',
            ],
            [
                'name' => 'create_franchises',
                'label' => 'Can create franchises',
                'group_name' => 'franchises',
            ],
            [
                'name' => 'update_franchises',
                'label' => 'Can update franchises',
                'group_name' => 'franchises',
            ],
            [
                'name' => 'delete_franchises',
                'label' => 'Can delete franchises',
                'group_name' => 'franchises',
            ],
            [
                'name' => 'view_partners',
                'label' => 'Can view partners',
                'group_name' => 'partners',
            ],
            [
                'name' => 'create_partners',
                'label' => 'Can create partners',
                'group_name' => 'partners',
            ],
            [
                'name' => 'update_partners',
                'label' => 'Can update partners',
                'group_name' => 'partners',
            ],
            [
                'name' => 'delete_partners',
                'label' => 'Can delete partners',
                'group_name' => 'partners',
            ],

            // ===== E-Commerce: SEO & Orders =====
            [
                'name' => 'view_pages',
                'label' => 'Can view pages',
                'group_name' => 'seo',
            ],
            [
                'name' => 'create_pages',
                'label' => 'Can create pages',
                'group_name' => 'seo',
            ],
            [
                'name' => 'update_pages',
                'label' => 'Can update pages',
                'group_name' => 'seo',
            ],
            [
                'name' => 'delete_pages',
                'label' => 'Can delete pages',
                'group_name' => 'seo',
            ],
            [
                'name' => 'view_page_blocks',
                'label' => 'Can view a page\'s section builder',
                'group_name' => 'seo',
            ],
            [
                'name' => 'update_page_blocks',
                'label' => 'Can add, edit, reorder, and delete page sections',
                'group_name' => 'seo',
            ],
            [
                'name' => 'view_tekzo_pages',
                'label' => 'Can view tekzo pages',
                'group_name' => 'seo',
            ],
            [
                'name' => 'create_tekzo_pages',
                'label' => 'Can create tekzo pages',
                'group_name' => 'seo',
            ],
            [
                'name' => 'update_tekzo_pages',
                'label' => 'Can update tekzo pages',
                'group_name' => 'seo',
            ],
            [
                'name' => 'delete_tekzo_pages',
                'label' => 'Can delete tekzo pages',
                'group_name' => 'seo',
            ],

            [
                'name' => 'update_product_seo',
                'label' => 'Can update product seo',
                'group_name' => 'seo',
            ],
            [
                'name' => 'update_category_seo',
                'label' => 'Can update category seo',
                'group_name' => 'seo',
            ],
            [
                'name' => 'update_brand_seo',
                'label' => 'Can update brand seo',
                'group_name' => 'seo',
            ],
            [
                'name' => 'view_seo',
                'label' => 'Can view seo',
                'group_name' => 'seo',
            ],
            [
                'name' => 'view_orders',
                'label' => 'Can view orders',
                'group_name' => 'orders',
            ],
            [
                'name' => 'view_order_details',
                'label' => 'Can view order details',
                'group_name' => 'orders',
            ],
            [
                'name' => 'update_order_status',
                'label' => 'Can update order status',
                'group_name' => 'orders',
            ],
            [
                'name' => 'approved_order_status',
                'label' => 'Can update approved order status',
                'group_name' => 'orders',
            ],
            [
                'name' => 'received_order_amount',
                'label' => 'Can update received order amount',
                'group_name' => 'orders',
            ],
            [
                'name' => 'update_order_item_variant',
                'label' => 'Can change the product/variant on an order item',
                'group_name' => 'orders',
            ],
            [
                'name' => 'create_order_items',
                'label' => 'Can add a product to a placed order',
                'group_name' => 'orders',
            ],
            [
                'name' => 'delete_order_items',
                'label' => 'Can remove a product from a placed order',
                'group_name' => 'orders',
            ],
            [
                'name' => 'update_order_gift_item_variant',
                'label' => 'Can change the gift item variant on an order item',
                'group_name' => 'orders',
            ],
            [
                'name' => 'status_orders_check_sslcommerz',
                'label' => 'Can check SSLCommerz online payment status for an order',
                'group_name' => 'orders',
            ],

            // ===== E-Commerce: Content & Marketing =====
            [
                'name' => 'view_reels_videos',
                'label' => 'Can view reels videos',
                'group_name' => 'reels_videos',
            ],
            [
                'name' => 'create_reels_videos',
                'label' => 'Can create reels videos',
                'group_name' => 'reels_videos',
            ],
            [
                'name' => 'update_reels_videos',
                'label' => 'Can update reels videos',
                'group_name' => 'reels_videos',
            ],
            [
                'name' => 'delete_reels_videos',
                'label' => 'Can delete reels videos',
                'group_name' => 'reels_videos',
            ],
            [
                'name' => 'view_elites',
                'label' => 'Can view elites',
                'group_name' => 'elites',
            ],
            [
                'name' => 'create_elites',
                'label' => 'Can create elites',
                'group_name' => 'elites',
            ],
            [
                'name' => 'update_elites',
                'label' => 'Can update elites',
                'group_name' => 'elites',
            ],
            [
                'name' => 'delete_elites',
                'label' => 'Can delete elites',
                'group_name' => 'elites',
            ],
            [
                'name' => 'view_popup_dialogs',
                'label' => 'Can view popup dialogs',
                'group_name' => 'popup_dialogs',
            ],
            [
                'name' => 'create_popup_dialogs',
                'label' => 'Can create popup dialogs',
                'group_name' => 'popup_dialogs',
            ],
            [
                'name' => 'update_popup_dialogs',
                'label' => 'Can update popup dialogs',
                'group_name' => 'popup_dialogs',
            ],
            [
                'name' => 'delete_popup_dialogs',
                'label' => 'Can delete popup dialogs',
                'group_name' => 'popup_dialogs',
            ],
            [
                'name' => 'view_cares',
                'label' => 'Can view cares',
                'group_name' => 'cares',
            ],
            [
                'name' => 'create_cares',
                'label' => 'Can create cares',
                'group_name' => 'cares',
            ],
            [
                'name' => 'update_cares',
                'label' => 'Can update cares',
                'group_name' => 'cares',
            ],
            [
                'name' => 'delete_cares',
                'label' => 'Can delete cares',
                'group_name' => 'cares',
            ],
            [
                'name' => 'view_offers',
                'label' => 'Can view offers',
                'group_name' => 'offers',
            ],
            [
                'name' => 'create_offers',
                'label' => 'Can create offers',
                'group_name' => 'offers',
            ],
            [
                'name' => 'update_offers',
                'label' => 'Can update offers',
                'group_name' => 'offers',
            ],
            [
                'name' => 'delete_offers',
                'label' => 'Can delete offers',
                'group_name' => 'offers',
            ],
            [
                'name' => 'view_medias',
                'label' => 'Can view all medias',
                'group_name' => 'medias',
            ],
            [
                'name' => 'update_medias',
                'label' => 'Can update media files',
                'group_name' => 'medias',
            ],
            [
                'name' => 'delete_medias',
                'label' => 'Can delete media files',
                'group_name' => 'medias',
            ],
            [
                'name' => 'bulk_delete_medias',
                'label' => 'Can bulk delete media files',
                'group_name' => 'medias',
            ],
            [
                'name' => 'create_media_folders',
                'label' => 'Can create media folders',
                'group_name' => 'medias',
            ],
            [
                'name' => 'create_media_alt_text',
                'label' => 'Can create media alt text',
                'group_name' => 'medias',
            ],
            [
                'name' => 'upload_medias_video',
                'label' => 'Can upload media videos',
                'group_name' => 'medias',
            ],
            [
                'name' => 'view_coupons',
                'label' => 'Can view coupons',
                'group_name' => 'coupons',
            ],
            [
                'name' => 'create_coupons',
                'label' => 'Can create coupons',
                'group_name' => 'coupons',
            ],
            [
                'name' => 'update_coupons',
                'label' => 'Can update coupons',
                'group_name' => 'coupons',
            ],
            [
                'name' => 'delete_coupons',
                'label' => 'Can delete coupons',
                'group_name' => 'coupons',
            ],
            [
                'name' => 'status_update_coupons',
                'label' => 'Can status update coupons',
                'group_name' => 'coupons',
            ],
            [
                'name' => 'view_news',
                'label' => 'Can view news',
                'group_name' => 'news',
            ],
            [
                'name' => 'create_news',
                'label' => 'Can create news',
                'group_name' => 'news',
            ],
            [
                'name' => 'update_news',
                'label' => 'Can update news',
                'group_name' => 'news',
            ],
            [
                'name' => 'delete_news',
                'label' => 'Can delete news',
                'group_name' => 'news',
            ],
            [
                'name' => 'view_blogs',
                'label' => 'Can view blogs',
                'group_name' => 'blogs',
            ],
            [
                'name' => 'create_blogs',
                'label' => 'Can create blogs',
                'group_name' => 'blogs',
            ],
            [
                'name' => 'update_blogs',
                'label' => 'Can update blogs',
                'group_name' => 'blogs',
            ],
            [
                'name' => 'delete_blogs',
                'label' => 'Can delete blogs',
                'group_name' => 'blogs',
            ],
            // ===== Blog (categories, tags, posts) =====
            [
                'name' => 'view_blog_categories',
                'label' => 'Can view blog categories',
                'group_name' => 'blog_categories',
            ],
            [
                'name' => 'create_blog_categories',
                'label' => 'Can create blog categories',
                'group_name' => 'blog_categories',
            ],
            [
                'name' => 'update_blog_categories',
                'label' => 'Can update blog categories',
                'group_name' => 'blog_categories',
            ],
            [
                'name' => 'delete_blog_categories',
                'label' => 'Can delete blog categories',
                'group_name' => 'blog_categories',
            ],
            [
                'name' => 'view_blog_tags',
                'label' => 'Can view blog tags',
                'group_name' => 'blog_tags',
            ],
            [
                'name' => 'create_blog_tags',
                'label' => 'Can create blog tags',
                'group_name' => 'blog_tags',
            ],
            [
                'name' => 'update_blog_tags',
                'label' => 'Can update blog tags',
                'group_name' => 'blog_tags',
            ],
            [
                'name' => 'delete_blog_tags',
                'label' => 'Can delete blog tags',
                'group_name' => 'blog_tags',
            ],
            [
                'name' => 'view_blog_posts',
                'label' => 'Can view blog posts',
                'group_name' => 'blog_posts',
            ],
            [
                'name' => 'create_blog_posts',
                'label' => 'Can create blog posts',
                'group_name' => 'blog_posts',
            ],
            [
                'name' => 'update_blog_posts',
                'label' => 'Can update blog posts',
                'group_name' => 'blog_posts',
            ],
            [
                'name' => 'delete_blog_posts',
                'label' => 'Can delete blog posts',
                'group_name' => 'blog_posts',
            ],
            [
                'name' => 'toggle_blog_posts',
                'label' => 'Can toggle blog post published/featured/trending/popular flags',
                'group_name' => 'blog_posts',
            ],
            [
                'name' => 'view_sliders',
                'label' => 'Can view sliders',
                'group_name' => 'sliders',
            ],
            [
                'name' => 'create_sliders',
                'label' => 'Can create sliders',
                'group_name' => 'sliders',
            ],
            [
                'name' => 'update_sliders',
                'label' => 'Can update sliders',
                'group_name' => 'sliders',
            ],
            [
                'name' => 'delete_sliders',
                'label' => 'Can delete sliders',
                'group_name' => 'sliders',
            ],
            [
                'name' => 'status_update_sliders',
                'label' => 'Can status update sliders',
                'group_name' => 'sliders',
            ],
            [
                'name' => 'view_gift_products',
                'label' => 'Can view gift products',
                'group_name' => 'gift_products',
            ],
            [
                'name' => 'create_gift_products',
                'label' => 'Can create gift products',
                'group_name' => 'gift_products',
            ],
            [
                'name' => 'update_gift_products',
                'label' => 'Can update gift products',
                'group_name' => 'gift_products',
            ],
            [
                'name' => 'delete_gift_products',
                'label' => 'Can delete gift products',
                'group_name' => 'gift_products',
            ],
            [
                'name' => 'view_warranties',
                'label' => 'Can view warranties',
                'group_name' => 'warranties',
            ],

            [
                'name' => 'update_warranties',
                'label' => 'Can update warranties',
                'group_name' => 'warranties',
            ],
            [
                'name' => 'delete_warranties',
                'label' => 'Can delete warranties',
                'group_name' => 'warranties',
            ],
            [
                'name' => 'status_update_warranties',
                'label' => 'Can status update warranties',
                'group_name' => 'warranties',
            ],
            [
                'name' => 'create_warranty_claims',
                'label' => 'Can verify IMEI/SL and log a warranty claim',
                'group_name' => 'warranties',
            ],
            [
                'name' => 'view_warranty_claims',
                'label' => 'Can view warranty claims',
                'group_name' => 'warranties',
            ],
            [
                'name' => 'status_update_warranty_claims',
                'label' => 'Can approve/reject warranty claims',
                'group_name' => 'warranties',
            ],
            [
                'name' => 'view_product_verifies',
                'label' => 'Can view product verifies',
                'group_name' => 'product_verifies',
            ],

            [
                'name' => 'status_update_product_verifies',
                'label' => 'Can status update product verifies',
                'group_name' => 'product_verifies',
            ],
            [
                'name' => 'view_reviews',
                'label' => 'Can view reviews',
                'group_name' => 'reviews',
            ],
            [
                'name' => 'create_reviews',
                'label' => 'Can add a review as admin',
                'group_name' => 'reviews',
            ],
            [
                'name' => 'update_reviews',
                'label' => 'Can update reviews',
                'group_name' => 'reviews',
            ],
            [
                'name' => 'delete_reviews',
                'label' => 'Can delete reviews',
                'group_name' => 'reviews',
            ],
            [
                'name' => 'view_sell_device_requests',
                'label' => 'Can view sell device requests',
                'group_name' => 'sell_device_requests',
            ],
            [
                'name' => 'update_sell_device_requests',
                'label' => 'Can update sell device requests',
                'group_name' => 'sell_device_requests',
            ],
            [
                'name' => 'delete_sell_device_requests',
                'label' => 'Can delete sell device requests',
                'group_name' => 'sell_device_requests',
            ],
            [
                'name' => 'view_our_teams',
                'label' => 'Can view our teams',
                'group_name' => 'our_teams',
            ],
            [
                'name' => 'create_our_teams',
                'label' => 'Can create our teams',
                'group_name' => 'our_teams',
            ],
            [
                'name' => 'update_our_teams',
                'label' => 'Can update our teams',
                'group_name' => 'our_teams',
            ],
            [
                'name' => 'delete_our_teams',
                'label' => 'Can delete our teams',
                'group_name' => 'our_teams',
            ],
            [
                'name' => 'view_emi_banks',
                'label' => 'Can view emi banks',
                'group_name' => 'emi_banks',
            ],
            [
                'name' => 'create_emi_banks',
                'label' => 'Can create emi banks',
                'group_name' => 'emi_banks',
            ],
            [
                'name' => 'update_emi_banks',
                'label' => 'Can update emi banks',
                'group_name' => 'emi_banks',
            ],
            [
                'name' => 'delete_emi_banks',
                'label' => 'Can delete emi banks',
                'group_name' => 'emi_banks',
            ],

            // ===== Careers =====
            [
                'name' => 'view_careers',
                'label' => 'Can view careers',
                'group_name' => 'careers',
            ],
            [
                'name' => 'create_careers',
                'label' => 'Can create careers',
                'group_name' => 'careers',
            ],
            [
                'name' => 'update_careers',
                'label' => 'Can update careers',
                'group_name' => 'careers',
            ],
            [
                'name' => 'delete_careers',
                'label' => 'Can delete careers',
                'group_name' => 'careers',
            ],
            [
                'name' => 'update_career_status',
                'label' => 'Can update careers status',
                'group_name' => 'careers',
            ],
            [
                'name' => 'view_career_applicants',
                'label' => 'Can view applicants',
                'group_name' => 'careers',
            ],

            // ===== FAQs =====
            // // [
            // //     'name' => 'view_faq_categories',
            // //     'label' => 'Can view FAQ categories',
            // //     'group_name' => 'faq_categories',
            // // ],
            // // [
            // //     'name' => 'create_faq_categories',
            // //     'label' => 'Can create FAQ categories',
            // //     'group_name' => 'faq_categories',
            // // ],
            // // [
            // //     'name' => 'update_faq_categories',
            // //     'label' => 'Can update FAQ categories',
            // //     'group_name' => 'faq_categories',
            // // ],
            // // [
            // //     'name' => 'delete_faq_categories',
            // //     'label' => 'Can delete FAQ categories',
            // //     'group_name' => 'faq_categories',
            // // ],
            // [
            //     'name' => 'active_faq_categories',
            //     'label' => 'Can view active FAQ categories list',
            //     'group_name' => 'faq_categories',
            // ],
            [
                'name' => 'view_faqs',
                'label' => 'Can view FAQs',
                'group_name' => 'faqs',
            ],
            [
                'name' => 'create_faqs',
                'label' => 'Can create FAQs',
                'group_name' => 'faqs',
            ],
            [
                'name' => 'update_faqs',
                'label' => 'Can update FAQs',
                'group_name' => 'faqs',
            ],
            [
                'name' => 'delete_faqs',
                'label' => 'Can delete FAQs',
                'group_name' => 'faqs',
            ],

            // ===== Outlets =====
            [
                'name' => 'view_outlets',
                'label' => 'Can view outlets',
                'group_name' => 'outlets',
            ],
            [
                'name' => 'create_outlets',
                'label' => 'Can create outlets',
                'group_name' => 'outlets',
            ],
            [
                'name' => 'update_outlets',
                'label' => 'Can update outlets',
                'group_name' => 'outlets',
            ],
            [
                'name' => 'delete_outlets',
                'label' => 'Can delete outlets',
                'group_name' => 'outlets',
            ],

            [
                'name' => 'view_policy_settings',
                'label' => 'Can view policy settings',
                'group_name' => 'policy_settings',
            ],
            [
                'name' => 'update_policy_settings',
                'label' => 'Can update policy settings',
                'group_name' => 'policy_settings',
            ],

            // ===== HRM: Employee =====
            [
                'name' => 'view_employees',
                'label' => 'Can view employees',
                'group_name' => 'hrm_employees',
            ],
            [
                'name' => 'create_employees',
                'label' => 'Can create employees',
                'group_name' => 'hrm_employees',
            ],
            [
                'name' => 'update_employees',
                'label' => 'Can update employees',
                'group_name' => 'hrm_employees',
            ],
            [
                'name' => 'delete_employees',
                'label' => 'Can delete employees',
                'group_name' => 'hrm_employees',
            ],
            [
                'name' => 'resend_email_employees',
                'label' => 'Can resend employee email',
                'group_name' => 'hrm_employees',
            ],
            [
                'name' => 'summary_employees',
                'label' => 'Can view employee summary',
                'group_name' => 'hrm_employees',
            ],
            [
                'name' => 'profile_employees',
                'label' => 'Can view employee profile',
                'group_name' => 'hrm_employees',
            ],
            [
                'name' => 'update_profile_employees',
                'label' => 'Can update employee profile',
                'group_name' => 'hrm_employees',
            ],
            [
                'name' => 'view_employees_contacts',
                'label' => 'Can view employee contacts',
                'group_name' => 'hrm_employees',
            ],
            [
                'name' => 'update_employees_contacts',
                'label' => 'Can update employee contacts',
                'group_name' => 'hrm_employees',
            ],
            [
                'name' => 'view_employees_contact_address',
                'label' => 'Can view employee contact address',
                'group_name' => 'hrm_employees',
            ],
            [
                'name' => 'update_employees_contact_address',
                'label' => 'Can update employee contact address',
                'group_name' => 'hrm_employees',
            ],
            [
                'name' => 'leave_allowances_employees',
                'label' => 'Can view employee leave allowances',
                'group_name' => 'hrm_employees',
            ],
            [
                'name' => 'attendance_stats_employees',
                'label' => 'Can view employee attendance stats',
                'group_name' => 'hrm_employees',
            ],
            [
                'name' => 'leave_summary_employees',
                'label' => 'Can view employee leave summary',
                'group_name' => 'hrm_employees',
            ],
            [
                'name' => 'job_history_employees',
                'label' => 'Can view employee job history',
                'group_name' => 'hrm_employees',
            ],
            [
                'name' => 'view_employee_documents',
                'label' => 'Can view employee documents',
                'group_name' => 'hrm_employees',
            ],
            [
                'name' => 'create_employee_documents',
                'label' => 'Can upload employee documents',
                'group_name' => 'hrm_employees',
            ],
            [
                'name' => 'delete_employee_documents',
                'label' => 'Can delete employee documents',
                'group_name' => 'hrm_employees',
            ],
            [
                'name' => 'view_designations',
                'label' => 'Can view designations',
                'group_name' => 'hrm_designations',
            ],
            [
                'name' => 'create_designations',
                'label' => 'Can create designations',
                'group_name' => 'hrm_designations',
            ],
            [
                'name' => 'update_designations',
                'label' => 'Can update designations',
                'group_name' => 'hrm_designations',
            ],
            [
                'name' => 'delete_designations',
                'label' => 'Can delete designations',
                'group_name' => 'hrm_designations',
            ],
            [
                'name' => 'view_employee_statuses',
                'label' => 'Can view employee statuses',
                'group_name' => 'hrm_employee_statuses',
            ],
            [
                'name' => 'create_employee_statuses',
                'label' => 'Can create employee statuses',
                'group_name' => 'hrm_employee_statuses',
            ],
            [
                'name' => 'update_employee_statuses',
                'label' => 'Can update employee statuses',
                'group_name' => 'hrm_employee_statuses',
            ],
            [
                'name' => 'delete_employee_statuses',
                'label' => 'Can delete employee statuses',
                'group_name' => 'hrm_employee_statuses',
            ],
            [
                'name' => 'view_departments',
                'label' => 'Can view departments',
                'group_name' => 'hrm_departments',
            ],
            [
                'name' => 'create_departments',
                'label' => 'Can create departments',
                'group_name' => 'hrm_departments',
            ],
            [
                'name' => 'update_departments',
                'label' => 'Can update departments',
                'group_name' => 'hrm_departments',
            ],
            [
                'name' => 'delete_departments',
                'label' => 'Can delete departments',
                'group_name' => 'hrm_departments',
            ],

            // ===== HRM: Attendance =====
            [
                'name' => 'view_break_times',
                'label' => 'Can view break times',
                'group_name' => 'hrm_attendance',
            ],
            [
                'name' => 'create_break_times',
                'label' => 'Can create break times',
                'group_name' => 'hrm_attendance',
            ],
            [
                'name' => 'update_break_times',
                'label' => 'Can update break times',
                'group_name' => 'hrm_attendance',
            ],
            [
                'name' => 'delete_break_times',
                'label' => 'Can delete break times',
                'group_name' => 'hrm_attendance',
            ],
            [
                'name' => 'view_work_shifts',
                'label' => 'Can view work shifts',
                'group_name' => 'hrm_attendance',
            ],
            [
                'name' => 'create_work_shifts',
                'label' => 'Can create work shifts',
                'group_name' => 'hrm_attendance',
            ],
            [
                'name' => 'update_work_shifts',
                'label' => 'Can update work shifts',
                'group_name' => 'hrm_attendance',
            ],
            [
                'name' => 'delete_work_shifts',
                'label' => 'Can delete work shifts',
                'group_name' => 'hrm_attendance',
            ],
            [
                'name' => 'view_attendances',
                'label' => 'Can view attendances',
                'group_name' => 'hrm_attendances',
            ],
            [
                'name' => 'create_attendances',
                'label' => 'Can create attendances',
                'group_name' => 'hrm_attendances',
            ],
            [
                'name' => 'update_attendances',
                'label' => 'Can update attendances',
                'group_name' => 'hrm_attendances',
            ],
            [
                'name' => 'delete_attendances',
                'label' => 'Can delete attendances',
                'group_name' => 'hrm_attendances',
            ],
            [
                'name' => 'punch_details_attendances',
                'label' => 'Can view attendance punch details',
                'group_name' => 'hrm_attendances',
            ],
            [
                'name' => 'report_attendances',
                'label' => 'Can view attendance report',
                'group_name' => 'hrm_attendances',
            ],
            [
                'name' => 'view_attendance_requests',
                'label' => 'Can view attendance requests',
                'group_name' => 'hrm_attendances',
            ],
            [
                'name' => 'create_attendance_requests',
                'label' => 'Can create attendance requests',
                'group_name' => 'hrm_attendances',
            ],
            [
                'name' => 'update_attendance_requests',
                'label' => 'Can update attendance requests',
                'group_name' => 'hrm_attendances',
            ],
            [
                'name' => 'update_attendance_requests_status',
                'label' => 'Can approve/reject attendance requests',
                'group_name' => 'hrm_attendances',
            ],
            [
                'name' => 'delete_attendance_requests',
                'label' => 'Can delete attendance requests',
                'group_name' => 'hrm_attendances',
            ],

            // ===== HRM: Leaves =====
            [
                'name' => 'view_leave_types',
                'label' => 'Can view leave types',
                'group_name' => 'hrm_leaves',
            ],
            [
                'name' => 'create_leave_types',
                'label' => 'Can create leave types',
                'group_name' => 'hrm_leaves',
            ],
            [
                'name' => 'update_leave_types',
                'label' => 'Can update leave types',
                'group_name' => 'hrm_leaves',
            ],
            [
                'name' => 'delete_leave_types',
                'label' => 'Can delete leave types',
                'group_name' => 'hrm_leaves',
            ],
            [
                'name' => 'view_leave_requests',
                'label' => 'Can view leave requests',
                'group_name' => 'hrm_leaves',
            ],
            [
                'name' => 'create_leave_requests',
                'label' => 'Can create leave requests',
                'group_name' => 'hrm_leaves',
            ],
            [
                'name' => 'update_leave_requests',
                'label' => 'Can update leave requests',
                'group_name' => 'hrm_leaves',
            ],
            [
                'name' => 'delete_leave_requests',
                'label' => 'Can delete leave requests',
                'group_name' => 'hrm_leaves',
            ],

            // ===== HRM: Assets =====
            [
                'name' => 'view_asset_categories',
                'label' => 'Can view asset categories',
                'group_name' => 'hrm_assets',
            ],
            [
                'name' => 'create_asset_categories',
                'label' => 'Can create asset categories',
                'group_name' => 'hrm_assets',
            ],
            [
                'name' => 'update_asset_categories',
                'label' => 'Can update asset categories',
                'group_name' => 'hrm_assets',
            ],
            [
                'name' => 'delete_asset_categories',
                'label' => 'Can delete asset categories',
                'group_name' => 'hrm_assets',
            ],
            [
                'name' => 'view_assets',
                'label' => 'Can view assets',
                'group_name' => 'hrm_assets',
            ],
            [
                'name' => 'create_assets',
                'label' => 'Can create assets',
                'group_name' => 'hrm_assets',
            ],
            [
                'name' => 'update_assets',
                'label' => 'Can update assets',
                'group_name' => 'hrm_assets',
            ],
            [
                'name' => 'delete_assets',
                'label' => 'Can delete assets',
                'group_name' => 'hrm_assets',
            ],
            [
                'name' => 'assign_assets',
                'label' => 'Can assign assets to an employee',
                'group_name' => 'hrm_assets',
            ],
            [
                'name' => 'return_assets',
                'label' => 'Can return an assigned asset',
                'group_name' => 'hrm_assets',
            ],
            [
                'name' => 'history_assets',
                'label' => 'Can view asset custody history',
                'group_name' => 'hrm_assets',
            ],

            // ===== HRM: Payroll =====
            [
                'name' => 'default_payrun_types',
                'label' => 'Can view default payrun',
                'group_name' => 'hrm_payrun',
            ],
            [
                'name' => 'update_default_payrun_types',
                'label' => 'Can update default payrun',
                'group_name' => 'hrm_payrun',
            ],
            [
                'name' => 'view_beneficiaries',
                'label' => 'Can view beneficiaries',
                'group_name' => 'payroll',
            ],
            [
                'name' => 'create_beneficiaries',
                'label' => 'Can create beneficiaries',
                'group_name' => 'payroll',
            ],
            [
                'name' => 'update_beneficiaries',
                'label' => 'Can update beneficiaries',
                'group_name' => 'payroll',
            ],
            [
                'name' => 'delete_beneficiaries',
                'label' => 'Can delete beneficiaries',
                'group_name' => 'payroll',
            ],
            [
                'name' => 'default_beneficiary_values',
                'label' => 'Can view default badge values',
                'group_name' => 'payroll',
            ],
            [
                'name' => 'update_default_beneficiary_values',
                'label' => 'Can update default badge values',
                'group_name' => 'payroll',
            ],
            [
                'name' => 'view_payruns',
                'label' => 'Can view payruns',
                'group_name' => 'payroll',
            ],
            [
                'name' => 'default_preview_payruns',
                'label' => 'Can preview default payrun',
                'group_name' => 'payroll',
            ],
            [
                'name' => 'run_default_payruns',
                'label' => 'Can run default payrun',
                'group_name' => 'payroll',
            ],
            [
                'name' => 'progress_payruns',
                'label' => 'Can view payrun progress',
                'group_name' => 'payroll',
            ],
            [
                'name' => 'view_payslips',
                'label' => 'Can view payslips',
                'group_name' => 'payroll',
            ],
            [
                'name' => 'send_payslips',
                'label' => 'Can send a payslip',
                'group_name' => 'payroll',
            ],
            [
                'name' => 'download_payslips',
                'label' => 'Can download a payslip PDF',
                'group_name' => 'payroll',
            ],
            [
                'name' => 'send_all_payruns',
                'label' => 'Can send all payslips for a payrun',
                'group_name' => 'payroll',
            ],

            // ===== HRM: Holidays, Events & Announcements =====
            [
                'name' => 'view_holidays',
                'label' => 'Can view holidays',
                'group_name' => 'hrm_holidays',
            ],
            [
                'name' => 'create_holidays',
                'label' => 'Can create holidays',
                'group_name' => 'hrm_holidays',
            ],
            [
                'name' => 'update_holidays',
                'label' => 'Can update holidays',
                'group_name' => 'hrm_holidays',
            ],
            [
                'name' => 'delete_holidays',
                'label' => 'Can delete holidays',
                'group_name' => 'hrm_holidays',
            ],
            [
                'name' => 'view_events',
                'label' => 'Can view events',
                'group_name' => 'hrm_events',
            ],
            [
                'name' => 'create_events',
                'label' => 'Can create events',
                'group_name' => 'hrm_events',
            ],
            [
                'name' => 'update_events',
                'label' => 'Can update events',
                'group_name' => 'hrm_events',
            ],
            [
                'name' => 'delete_events',
                'label' => 'Can delete events',
                'group_name' => 'hrm_events',
            ],
            [
                'name' => 'view_announcements',
                'label' => 'Can view announcements',
                'group_name' => 'hrm_announcements',
            ],
            [
                'name' => 'create_announcements',
                'label' => 'Can create announcements',
                'group_name' => 'hrm_announcements',
            ],
            [
                'name' => 'update_announcements',
                'label' => 'Can update announcements',
                'group_name' => 'hrm_announcements',
            ],
            [
                'name' => 'delete_announcements',
                'label' => 'Can delete announcements',
                'group_name' => 'hrm_announcements',
            ],

            // ===== Ledger =====
            [
                'name' => 'view_ledgers',
                'label' => 'Can view ledgers',
                'group_name' => 'ledgers',
            ],
            [
                'name' => 'view_customer_ledger_reports',
                'label' => 'Can view ledger details',
                'group_name' => 'ledgers',
            ],

            [
                'name' => 'download_customer_ledger_reports',
                'label' => 'Can download ledger details',
                'group_name' => 'ledgers',
            ],

            // ===== Reports =====
            [
                'name' => 'view_stock_reports',
                'label' => 'Can view stock reports',
                'group_name' => 'reports',
            ],
            [
                'name' => 'view_profit_loss_reports',
                'label' => 'Can view profit loss reports',
                'group_name' => 'reports',
            ],
            [
                'name' => 'generate_profit_loss_pdf',
                'label' => 'Can generate profit loss report PDF',
                'group_name' => 'reports',
            ],
            [
                'name' => 'view_sale_reports',
                'label' => 'Can view sale reports',
                'group_name' => 'reports',
            ],
            [
                'name' => 'view_sale_return_reports',
                'label' => 'Can view sale return reports',
                'group_name' => 'reports',
            ],
            [
                'name' => 'view_purchase_reports',
                'label' => 'Can view purchase reports',
                'group_name' => 'reports',
            ],
            [
                'name' => 'view_stock_transfer_reports',
                'label' => 'Can view stock transfer reports',
                'group_name' => 'reports',
            ],
            [
                'name' => 'pdf_stock_transfer_reports',
                'label' => 'Can download stock transfer report PDF',
                'group_name' => 'reports',
            ],
            [
                'name' => 'view_cash_transfer_reports',
                'label' => 'Can view cash transfer reports',
                'group_name' => 'reports',
            ],
            [
                'name' => 'pdf_cash_transfer_reports',
                'label' => 'Can download cash transfer report PDF',
                'group_name' => 'reports',
            ],
            [
                'name' => 'view_damage_product_reports',
                'label' => 'Can view damage product reports',
                'group_name' => 'reports',
            ],
            [
                'name' => 'pdf_damage_product_reports',
                'label' => 'Can download damage product report PDF',
                'group_name' => 'reports',
            ],
            [
                'name' => 'view_purchase_return_reports',
                'label' => 'Can view purchase return reports',
                'group_name' => 'reports',
            ],

            [
                'name' => 'view_expense_reports',
                'label' => 'Can view expense reports',
                'group_name' => 'reports',
            ],

            [
                'name' => 'view_deposit_reports',
                'label' => 'Can view deposit reports',
                'group_name' => 'reports',
            ],
            [
                'name' => 'view_withdrawal_reports',
                'label' => 'Can view withdrawal reports',
                'group_name' => 'reports',
            ],
            [
                'name' => 'view_transaction_reports',
                'label' => 'Can view transaction reports',
                'group_name' => 'reports',
            ],

            [
                'name' => 'download_stock_report_pdf',
                'label' => 'Can download stock report pdf',
                'group_name' => 'report_downloads',
            ],
            [
                'name' => 'download_sale_report_pdf',
                'label' => 'Can download sale report pdf',
                'group_name' => 'report_downloads',
            ],
            [
                'name' => 'download_sale_return_report_pdf',
                'label' => 'Can download sale return report pdf',
                'group_name' => 'report_downloads',
            ],
            [
                'name' => 'download_purchase_report_pdf',
                'label' => 'Can download purchase report pdf',
                'group_name' => 'report_downloads',
            ],
            [
                'name' => 'download_purchase_return_report_pdf',
                'label' => 'Can download purchase return report pdf',
                'group_name' => 'report_downloads',
            ],
            [
                'name' => 'download_expense_report_pdf',
                'label' => 'Can download expense report pdf',
                'group_name' => 'report_downloads',
            ],
            [
                'name' => 'download_deposit_report_pdf',
                'label' => 'Can download deposit report pdf',
                'group_name' => 'report_downloads',
            ],
            [
                'name' => 'download_withdrawal_report_pdf',
                'label' => 'Can download withdrawal report pdf',
                'group_name' => 'report_downloads',
            ],
            [
                'name' => 'download_transaction_report_pdf',
                'label' => 'Can download transaction report pdf',
                'group_name' => 'report_downloads',
            ],

            // ===== Daily Closing Statement =====
            [
                'name' => 'view_daily_closings',
                'label' => 'Can view daily closing statements',
                'group_name' => 'closing_statements',
            ],
            [
                'name' => 'download_daily_closings',
                'label' => 'Can download daily closing statements',
                'group_name' => 'closing_statements',
            ],
            [
                'name' => 'view_daily_closing_balance',
                'label' => 'Can view daily closing balance',
                'group_name' => 'closing_statements',
            ],
            [
                'name' => 'view_daily_closing_profit_loss',
                'label' => "Can view today's profit & loss on daily closing",
                'group_name' => 'closing_statements',
            ],
            [
                'name' => 'generate_daily_closing_profit_loss_pdf',
                'label' => "Can generate today's profit & loss PDF on daily closing",
                'group_name' => 'closing_statements',
            ],
            [
                'name' => 'view_balance_sheets',
                'label' => 'Can view balance sheets',
                'group_name' => 'closing_statements',
            ],
            [
                'name' => 'view_stock_statements',
                'label' => 'Can view stock statements',
                'group_name' => 'closing_statements',
            ],
            [
                'name' => 'view_sale_statements',
                'label' => 'Can view sale closing statements',
                'group_name' => 'closing_statements',
            ],
            [
                'name' => 'view_purchase_statements',
                'label' => 'Can view purchase statements',
                'group_name' => 'closing_statements',
            ],
            [
                'name' => 'view_purchase_return_statements',
                'label' => 'Can view purchase return statements',
                'group_name' => 'closing_statements',
            ],
            [
                'name' => 'view_sale_return_statements',
                'label' => 'Can view sale return statements',
                'group_name' => 'closing_statements',
            ],
            [
                'name' => 'view_expense_statements',
                'label' => 'Can view expense statements',
                'group_name' => 'closing_statements',
            ],
            [
                'name' => 'view_withdrawal_statements',
                'label' => 'Can view withdrawal statements',
                'group_name' => 'closing_statements',
            ],
            [
                'name' => 'view_deposit_statements',
                'label' => 'Can view deposit statements',
                'group_name' => 'closing_statements',
            ],
            [
                'name' => 'generate_stock_statement_pdf',
                'label' => 'Can generate stock statement pdf',
                'group_name' => 'closing_statements',
            ],
            [
                'name' => 'generate_sale_statement_pdf',
                'label' => 'Can generate sale statement pdf',
                'group_name' => 'closing_statements',
            ],
            [
                'name' => 'generate_sale_return_statement_pdf',
                'label' => 'Can generate sale return statement pdf',
                'group_name' => 'closing_statements',
            ],
            [
                'name' => 'generate_purchase_statement_pdf',
                'label' => 'Can generate purchase statement pdf',
                'group_name' => 'closing_statements',
            ],
            [
                'name' => 'generate_purchase_return_statement_pdf',
                'label' => 'Can generate purchase return statement pdf',
                'group_name' => 'closing_statements',
            ],
            [
                'name' => 'generate_expense_statement_pdf',
                'label' => 'Can generate expense statement pdf',
                'group_name' => 'closing_statements',
            ],
            [
                'name' => 'generate_withdrawal_statement_pdf',
                'label' => 'Can generate withdrawal statement pdf',
                'group_name' => 'closing_statements',
            ],
            [
                'name' => 'generate_deposit_statement_pdf',
                'label' => 'Can generate deposit statement pdf',
                'group_name' => 'closing_statements',
            ],

            // ===== Users & Roles =====
            [
                'name' => 'view_users',
                'label' => 'Can view users',
                'group_name' => 'users',
            ],
            [
                'name' => 'create_users',
                'label' => 'Can create users',
                'group_name' => 'users',
            ],
            [
                'name' => 'update_users',
                'label' => 'Can update users',
                'group_name' => 'users',
            ],
            [
                'name' => 'delete_users',
                'label' => 'Can delete users',
                'group_name' => 'users',
            ],
            [
                'name' => 'view_roles',
                'label' => 'Can view roles',
                'group_name' => 'roles',
            ],
            [
                'name' => 'create_roles',
                'label' => 'Can create roles',
                'group_name' => 'roles',
            ],
            [
                'name' => 'update_roles',
                'label' => 'Can update roles',
                'group_name' => 'roles',
            ],
            [
                'name' => 'delete_roles',
                'label' => 'Can delete roles',
                'group_name' => 'roles',
            ],

            // ===== Settings =====
            [
                'name' => 'view_settings',
                'label' => 'Can view settings',
                'group_name' => 'settings',
            ],
            [
                'name' => 'update_settings',
                'label' => 'Can update settings',
                'group_name' => 'settings',
            ],

            [
                'name' => 'view_payrun_settings',
                'label' => 'Can view payrun settings',
                'group_name' => 'settings',
            ],
            [
                'name' => 'update_payrun_settings',
                'label' => 'Can update payrun settings',
                'group_name' => 'settings',
            ],
            [
                'name' => 'view_leave_settings',
                'label' => 'Can view leave settings',
                'group_name' => 'settings',
            ],
            [
                'name' => 'update_leave_settings',
                'label' => 'Can update leave settings',
                'group_name' => 'settings',
            ],
            [
                'name' => 'view_attendance_settings',
                'label' => 'Can view attendance settings',
                'group_name' => 'settings',
            ],
            [
                'name' => 'update_attendance_settings',
                'label' => 'Can update attendance settings',
                'group_name' => 'settings',
            ],
            [
                'name' => 'assign_leave_settings',
                'label' => 'Can manage leave settings',
                'group_name' => 'settings',
            ],
            [
                'name' => 'view_delivery_charge_settings',
                'label' => 'Can view delivery charge settings',
                'group_name' => 'settings',
            ],
            [
                'name' => 'update_delivery_charge_settings',
                'label' => 'Can update delivery charge settings',
                'group_name' => 'settings',
            ],
            [
                'name' => 'view_area_shipping_charges',
                'label' => 'Can view area shipping charges',
                'group_name' => 'settings',
            ],
            [
                'name' => 'update_area_shipping_charges',
                'label' => 'Can update area shipping charges',
                'group_name' => 'settings',
            ],
            [
                'name' => 'view_payment_methods',
                'label' => 'Can view payment methods',
                'group_name' => 'payment_methods',
            ],
            [
                'name' => 'create_payment_methods',
                'label' => 'Can create payment methods',
                'group_name' => 'payment_methods',
            ],
            [
                'name' => 'update_payment_methods',
                'label' => 'Can update payment methods',
                'group_name' => 'payment_methods',
            ],
            [
                'name' => 'delete_payment_methods',
                'label' => 'Can delete payment methods',
                'group_name' => 'payment_methods',
            ],
            [
                'name' => 'view_shops',
                'label' => 'Can view shops',
                'group_name' => 'shops',
            ],
            [
                'name' => 'create_shops',
                'label' => 'Can create shops',
                'group_name' => 'shops',
            ],
            [
                'name' => 'update_shops',
                'label' => 'Can update shops',
                'group_name' => 'shops',
            ],
            [
                'name' => 'delete_shops',
                'label' => 'Can delete shops',
                'group_name' => 'shops',
            ],
            [
                'name' => 'shop_update_status',
                'label' => 'Can update shop status',
                'group_name' => 'shops',
            ],
            [
                'name' => 'view_ecommerces',
                'label' => 'Can view ecommerces',
                'group_name' => 'ecommerces',
            ],
            [
                'name' => 'create_ecommerces',
                'label' => 'Can create ecommerces',
                'group_name' => 'ecommerces',
            ],
            [
                'name' => 'update_ecommerces',
                'label' => 'Can update ecommerces',
                'group_name' => 'ecommerces',
            ],
            [
                'name' => 'delete_ecommerces',
                'label' => 'Can delete ecommerces',
                'group_name' => 'ecommerces',
            ],
            [
                'name' => 'ecommerce_update_status',
                'label' => 'Can update ecommerce status',
                'group_name' => 'ecommerces',
            ],
            [
                'name' => 'view_shop_settings',
                'label' => 'Can view shop settings',
                'group_name' => 'ecommerces',
            ],
            [
                'name' => 'update_shop_settings',
                'label' => 'Can update shop settings',
                'group_name' => 'ecommerces',
            ],

            // ===== Chat (WhatsApp + Messenger) =====
            [
                'name' => 'view_chat_conversations',
                'label' => 'Can view chat conversations',
                'group_name' => 'chat',
            ],
            [
                'name' => 'assign_chat_conversations',
                'label' => 'Can assign chat conversations',
                'group_name' => 'chat',
            ],
            [
                'name' => 'claim_chat_conversations',
                'label' => 'Can claim chat conversations',
                'group_name' => 'chat',
            ],
            [
                'name' => 'reassign_chat_conversations',
                'label' => 'Can reassign chat conversations',
                'group_name' => 'chat',
            ],
            [
                'name' => 'close_chat_conversations',
                'label' => 'Can close chat conversations',
                'group_name' => 'chat',
            ],
            [
                'name' => 'reopen_chat_conversations',
                'label' => 'Can reopen chat conversations',
                'group_name' => 'chat',
            ],
            [
                'name' => 'view_chat_messages',
                'label' => 'Can view chat messages',
                'group_name' => 'chat',
            ],
            [
                'name' => 'create_chat_messages',
                'label' => 'Can send chat messages',
                'group_name' => 'chat',
            ],
            [
                'name' => 'view_whatsapp_templates',
                'label' => 'Can view WhatsApp templates',
                'group_name' => 'chat',
            ],
            [
                'name' => 'sync_whatsapp_templates',
                'label' => 'Can sync WhatsApp templates',
                'group_name' => 'chat',
            ],
            [
                'name' => 'view_chat_teams',
                'label' => 'Can view chat teams',
                'group_name' => 'chat',
            ],
            [
                'name' => 'create_chat_teams',
                'label' => 'Can create chat teams',
                'group_name' => 'chat',
            ],
            [
                'name' => 'update_chat_teams',
                'label' => 'Can update chat teams',
                'group_name' => 'chat',
            ],
            [
                'name' => 'delete_chat_teams',
                'label' => 'Can delete chat teams',
                'group_name' => 'chat',
            ],
            [
                'name' => 'view_chat_labels',
                'label' => 'Can view chat labels',
                'group_name' => 'chat',
            ],
            [
                'name' => 'create_chat_labels',
                'label' => 'Can create chat labels',
                'group_name' => 'chat',
            ],
            [
                'name' => 'assign_team_chat_conversations',
                'label' => 'Can assign a team to chat conversations',
                'group_name' => 'chat',
            ],
            [
                'name' => 'priority_chat_conversations',
                'label' => 'Can set chat conversation priority',
                'group_name' => 'chat',
            ],
            [
                'name' => 'labels_chat_conversations',
                'label' => 'Can manage chat conversation labels',
                'group_name' => 'chat',
            ],
            [
                'name' => 'attach_contact_chat_conversations',
                'label' => 'Can attach a contact to a chat conversation',
                'group_name' => 'chat',
            ],

        ];

        // $role = Role::query()->first();
        foreach ($permissions as $permission) {
            $exist_permissions = Permission::query()
                ->where('name', '=', $permission['name'])
                ->first();
            if (! $exist_permissions) {
                $this->command->alert('Permissions Added: '.$permission['name']);
                $permission = Permission::query()->create($permission);
                // $permission->roles()->attach([$role->id]);
            }
        }
    }
}
