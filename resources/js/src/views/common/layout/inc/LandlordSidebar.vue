<script setup>
import { ref, onMounted, markRaw } from 'vue';
import { useI18n } from 'vue-i18n';
import { Collapse } from 'bootstrap'
import usePermission from "@/composable/permission/usePermission.js";
import {
    ViewGrid,
    UserStar,
    Settings,
    Xmark,
    NavArrowDown,
    Wallet,
    Package,
    WebWindowXmark,
    ShoppingBag,
    Activity,
    Group,
    Dollar,
    MediaImage,
    ReportColumns,
    MultiplePages,
    BubbleIncome,
    Cube,
    ProfileCircle,
    UserSquare,
    BookmarkBook,
    Gift,
    PageFlip,
    ClipboardCheck,
    Page,
    MediaImageFolder,
    Star,
    ScanBarcode,
    HandCash,
    WarningTriangle,
    DatabaseStar,
    CubeDotsSolid,
    Community,
    MediaVideo,
    ShoppingBagCheck,
    Percentage,
    Calendar,
    DeliveryTruck,
    SmartphoneDevice,
    PhoneOutcome,
    Cart,
} from '@iconoir/vue';

const { canAccess } = usePermission();
const { t } = useI18n();

const props = defineProps({
    settingData: {},
});

const sidebarData = ref([
    {
        url: '/dashboard',
        icon: markRaw(ViewGrid),
        name: t('dashboard'),
        permission: true,
    },
    {
        icon: 'pie-chart',
    },
    {
        name: 'Main Menu',
        isTitle: true,
    },
    {
        icon: markRaw(Package),
        name: t('products'),
        permission: canAccess('view_products') ||
            canAccess('view_categories') ||
            canAccess('view_brands') ||
            canAccess('view_attributes') ||
            canAccess('view_pre_owned_products') ||
            canAccess('view_specification_groups'),
        id: 'products',
        submenu: [
            {
                url: '/products',
                name: t('products'),
                permission: canAccess('view_products'),
            },
            {
                url: '/pre-owned-products',
                name: t('pre_owned_products'),
                permission: canAccess('view_pre_owned_products'),
            },
            {
                url: '/categories',
                name: t('categories'),
                permission: canAccess('view_categories'),
            },
            {
                url: '/brands',
                name: t('brands'),
                permission: canAccess('view_brands'),
            },
            {
                url: '/all-pages',
                name: t('pages'),
                permission: canAccess('view_all_pages'),
            },
            {
                url: '/attributes',
                name: t('attributes'),
                permission: canAccess('view_attributes'),
            },
            {
                url: '/specification-groups',
                name: t('specification_groups'),
                permission: canAccess('view_specification_groups'),
            },
        ],
    },

    {
        icon: markRaw(ScanBarcode),
        name: t('barcodes'),
        permission: canAccess('view_product_barcodes') ||
            canAccess('product_barcode_generate') ||
            canAccess('product_print_barcodes') ||
            canAccess('view_product_serial_history') ||
            canAccess('view_barcode_generates'),
        id: 'barcodes',
        submenu: [
            {
                url: '/product-barcodes',
                name: t('barcode_list'),
                permission: canAccess('view_product_barcodes'),
            },
            {
                url: '/product-barcode-generate',
                name: 'Create barcode',
                permission: canAccess('product_barcode_generate'),
            },
            {
                url: '/print-barcode',
                name: 'Print Barcodes',
                permission: canAccess('product_print_barcodes'),
            },
            {
                url: '/product-barcode-history',
                name: 'Barcode History',
                permission: canAccess('view_product_serial_history'),
            },
            {
                url: '/barcode-generates',
                name: 'Generate Barcodes',
                permission: canAccess('view_barcode_generates'),
            },
        ],
    },
    {
        icon: markRaw(ShoppingBag),
        name: t('purchases'),
        permission: canAccess('view_purchases') || canAccess('create_purchases') || canAccess('view_purchase_returns'),
        id: 'purchases',
        submenu: [
            {
                url: '/purchases',
                name: t('purchases'),
                permission: canAccess('view_purchases'),
            },
            {
                url: '/create-purchase',
                name: t('add_purchase'),
                permission: canAccess('create_purchases'),
            },
            {
                url: '/purchase-return',
                name: t('purchase_return'),
                permission: canAccess('view_purchase_returns'),
            },
            {
                url: '/create-purchase-return',
                name: t('add_purchase_return'),
                permission: canAccess('create_purchase_returns'),
            },
        ],
    },
    {
        icon: markRaw(Dollar),
        name: t('sales'),
        permission: canAccess('view_sales') ||
            canAccess('create_sales') ||
            canAccess('create_sale_returns') ||
            canAccess('view_sale_returns'),
        id: 'sales',
        submenu: [
            {
                url: '/sales',
                name: t('sales'),
                permission: canAccess('view_sales'),
            },
            {
                url: '/create-sale',
                name: t('add_sale'),
                permission: canAccess('create_sales'),
            },
            {
                url: '/sales-return',
                name: t('sales_return'),
                permission: canAccess('view_sale_returns'),
            },
            {
                url: '/create-sale-return',
                name: t('add_sale_return'),
                permission: canAccess('create_sale_returns'),
            },
        ],
    },
    {
        icon: markRaw(Package),
        name: 'Stock Transfer',
        url: '/stock-transfers',
        permission: canAccess('view_stock_transfers'),
    },
    {
        icon: markRaw(HandCash),
        name: 'Cash Transfer',
        url: '/cash-transfers',
        permission: canAccess('view_cash_transfers'),
    },
    {
        icon: markRaw(WarningTriangle),
        name: 'Damage Product',
        url: '/damage-products',
        permission: canAccess('view_damage_products'),
    },
    {
        icon: markRaw(Group),
        name: t('people'),
        url: '/people',
        permission: canAccess('view_people'),
    },
    {
        icon: markRaw(PhoneOutcome),
        name: t('telesales'),
        permission: canAccess('view_telesales') || canAccess('view_telesale_results'),
        id: 'telesales',
        submenu: [
            {
                url: '/telesales',
                name: t('telesale_report'),
                permission: canAccess('view_telesales'),
            },
            {
                url: '/telesale-results',
                name: t('telesale_results'),
                permission: canAccess('view_telesale_results'),
            },
        ],
    },
    {
        icon: markRaw(Cart),
        name: t('order_confirmations'),
        permission: canAccess('view_order_confirmations') || canAccess('view_order_confirmation_options'),
        id: 'order-confirmations',
        submenu: [
            {
                url: '/order-confirmations',
                name: t('order_list'),
                permission: canAccess('view_order_confirmations'),
            },
            {
                url: '/order-confirmation-options',
                name: t('order_confirmation_options'),
                permission: canAccess('view_order_confirmation_options'),
            },
        ],
    },
    {
        icon: markRaw(HandCash),
        name: t('payments'),
        permission: canAccess('view_payments'),
        id: 'payments',
        submenu: [
            {
                url: '/payments',
                name: t('add_payment'),
                permission: canAccess('view_payments'),
            },

            {
                url: '/payment-history',
                name: t('payment_list'),
                permission: canAccess('view_payment_history'),
            },
        ],

    },

    {
        icon: markRaw(Activity),
        name: t('expenses'),
        permission: canAccess('view_expenses') || canAccess('view_expense_categories') || canAccess('create_expenses') || canAccess('create_expense_categories'),
        id: 'expenses',
        submenu: [
            {
                url: '/expenses',
                name: t('expenses'),
                permission: canAccess('view_expenses'),
            },
            {
                url: '/create-expense',
                name: t('add_expense'),
                permission: canAccess('create_expenses'),
            },
            {
                url: '/expense-categories',
                name: t('expense_categories'),
                permission: canAccess('view_expense_categories'),
            },
            {
                url: '/create-expense-categories',
                name: t('add_expense_categories'),
                permission: canAccess('create_expense_categories'),
            },
        ],
    },
    {
        icon: markRaw(BubbleIncome),
        name: t('incomes'),
        permission: canAccess('view_incomes') || canAccess('view_income_categories') || canAccess('create_incomes') || canAccess('create_income_categories'),
        id: 'income',
        submenu: [
            {
                url: '/incomes',
                name: t('incomes'),
                permission: canAccess('view_incomes'),
            },
            {
                url: '/create-income',
                name: t('add_income'),
                permission: canAccess('create_incomes'),
            },
            {
                url: '/income-categories',
                name: t('income_categories'),
                permission: canAccess('view_income_categories'),
            },
            {
                url: '/create-income-categories',
                name: t('add_income_categories'),
                permission: canAccess('create_income_categories'),
            },
        ],
    },
    {
        icon: markRaw(DatabaseStar),
        name: t('investments'),
        permission: canAccess('view_investment_categories') || canAccess('create_investment_categories') || canAccess('create_investments') || canAccess('view_investments'),
        id: 'investment',
        submenu: [
            {
                url: '/investments',
                name: t('investments'),
                permission: canAccess('view_investments'),
            },
            {
                url: '/create-investments',
                name: t('add_investments'),
                permission: canAccess('create_investments'),
            },
            {
                url: '/investment-categories',
                name: t('director'),
                permission: canAccess('view_investment_categories'),
            },
            {
                url: '/create-investment-categories',
                name: t('add_director'),
                permission: canAccess('create_investment_categories'),
            },
        ],
    },
    {
        icon: markRaw(ProfileCircle),
        name: 'Directors',
        permission: canAccess('view_directors'),
        url: '/directors',
    },

    {
        icon: markRaw(UserSquare),
        name: t('partners'),
        permission: canAccess('view_dealers') ||
            canAccess('view_retailers') ||
            canAccess('view_b2b') ||
            canAccess('view_franchises') ||
            canAccess('view_partners'),
        id: 'partners',
        submenu: [
            {
                url: '/dealers',
                name: t('dealers'),
                permission: canAccess('view_dealers'),
            },
            {
                url: '/retailers',
                name: t('retailers'),
                permission: canAccess('view_retailers'),
            },
            {
                url: '/b2b',
                name: t('b2b'),
                permission: canAccess('view_b2b'),
            },
            {
                url: '/franchises',
                name: t('franchises'),
                permission: canAccess('view_franchises'),
            },
            {
                url: '/partners',
                name: t('corporate_application'),
                permission: canAccess('view_partners'),
            },
        ],
    },
    {
        name: 'E-Commerce Menu',
        isTitle: true,
    },
    {
        icon: markRaw(MultiplePages),
        name: t('seo'),
        permission: canAccess('view_pages') || canAccess('view_tekzo_pages'),
        id: 'pages',
        submenu: [
            {
                url: '/pages',
                name: t('salamat'),
                permission: canAccess('view_pages'),
            },
            {
                url: '/tekzo-pages',
                name: t('tekzo'),
                permission: canAccess('view_tekzo_pages'),
            },
        ],
    },
    {
        icon: markRaw(ViewGrid),
        name: t('about_us'),
        permission: canAccess('view_page_blocks'),
        id: 'about-us',
        submenu: [
            {
                url: '/page-builder/54',
                name: t('tekzo_about_us'),
                permission: canAccess('view_page_blocks'),
            },
            {
                url: '/page-builder/5',
                name: t('salamat_about_us'),
                permission: canAccess('view_page_blocks'),
            },
        ],
    },
    {
        icon: markRaw(Cube),
        name: t('orders'),
        permission: canAccess('view_orders'),
        id: 'orders',
        submenu: [
            {
                url: '/orders',
                name: t('all_orders'),
                permission: true,
            },
            {
                url: '/pending-orders',
                name: t('pending_orders'),
                permission: true,
            },
            {
                url: '/hold-orders',
                name: t('hold_orders'),
                permission: true,
            },
            {
                url: '/confirm-orders',
                name: t('confirm_orders'),
                permission: true,
            },
            {
                url: '/approve-orders',
                name: t('approve_orders'),
                permission: true,
            },
            {
                url: '/processing-orders',
                name: t('processing_orders'),
                permission: true,
            },
            {
                url: '/onway-orders',
                name: t('onway_orders'),
                permission: true,
            },
            {
                url: '/return-orders',
                name: t('return_orders'),
                permission: true,
            },
            {
                url: '/complete-orders',
                name: t('complete_orders'),
                permission: true,
            },
            {
                url: '/rejected-orders',
                name: t('rejected_orders'),
                permission: true,
            },
        ],
    },
    {
        icon: markRaw(DeliveryTruck),
        name: 'Couriers',
        permission: canAccess('view_courier_orders') || canAccess('view_pathao_orders') || canAccess('view_steadfast_orders'),
        id: 'couriers',
        submenu: [
            {
                url: '/courier-orders',
                name: 'All Courier Orders',
                permission: canAccess('view_courier_orders'),
            },
            {
                url: '/pathao-orders',
                name: 'Pathao Orders',
                permission: canAccess('view_pathao_orders'),
            },
            {
                url: '/steadfast-orders',
                name: 'Steadfast Orders',
                permission: canAccess('view_steadfast_orders'),
            },
            {
                url: '/redx-orders',
                name: 'REDX Orders',
                permission: canAccess('view_redx_orders'),
            },
        ],
    },
    {
        icon: markRaw(SmartphoneDevice),
        name: 'Chat',
        permission: canAccess('view_chat_conversations'),
        url: '/chat',
    },
    {
        icon: markRaw(MediaVideo),
        name: t('reels_video'),
        permission: canAccess('view_reels_videos'),
        url: '/reels-videos',
    },
    {
        icon: markRaw(Star),
        name: t('elite'),
        permission: canAccess('view_elites'),
        url: '/elite',
    },
    {
        icon: markRaw(MultiplePages),
        name: t('popup_dialog'),
        permission: canAccess('view_popup_dialogs'),
        url: '/popup-dialogs',
    },
    {
        icon: markRaw(ClipboardCheck),
        name: 'Care',
        permission: canAccess('view_cares'),
        url: '/cares',
    },
    {
        icon: markRaw(Gift),
        name: t('offers'),
        permission: canAccess('view_offers'),
        url: '/offers',
    },
    {
        url: '/media-library',
        icon: markRaw(MediaImage),
        name: t('media'),
        permission: canAccess('view_medias'),
    },
    {
        url: '/coupon',
        icon: markRaw(BookmarkBook),
        name: t('coupon'),
        permission: canAccess('view_coupons'),
    },
    {
        url: '/news',
        icon: markRaw(Page),
        name: t('news'),
        permission: canAccess('view_news'),
    },
    // {
    //     url: '/blogs',
    //     icon: markRaw(Page),
    //     name: t('blogs'),
    //     permission: canAccess('view_blogs'),
    // },
    {
        url: '/sliders',
        icon: markRaw(MediaImageFolder),
        name: t('sliders'),
        permission: canAccess('view_sliders'),
    },
    {
        url: '/gift-products',
        icon: markRaw(Gift),
        name: t('gift_products'),
        permission: canAccess('view_gift_products'),
    },
    {
        url: '/warranties',
        icon: markRaw(PageFlip),
        name: t('warranty'),
        permission: canAccess('view_warranties'),
    },
    {
        url: '/warranty-claims',
        icon: markRaw(ScanBarcode),
        name: t('warranty_claim'),
        permission: canAccess('create_warranty_claims'),
    },
    {
        url: '/product-verifies',
        icon: markRaw(ClipboardCheck),
        name: t('product_verify'),
        permission: canAccess('view_product_verifies'),
    },

    {
        url: '/reviews',
        icon: markRaw(Star),
        name: t('reviews'),
        permission: canAccess('view_reviews'),
    },
    {
        url: '/sell-device-requests',
        icon: markRaw(SmartphoneDevice),
        name: 'Sell Device Requests',
        permission: canAccess('view_sell_device_requests'),
    },
    {
        url: '/our-teams',
        icon: markRaw(CubeDotsSolid),
        name: t('our_teams'),
        permission: canAccess('view_our_teams'),
    },
    {
        url: '/emi-banks',
        icon: markRaw(Percentage),
        name: 'EMI Banks',
        permission: canAccess('view_emi_banks'),
    },
    {
        icon: markRaw(Community),
        name: t('careers'),
        permission: canAccess('view_careers') || canAccess('create_careers'),
        id: 'careers',
        submenu: [
            {
                url: '/careers',
                name: t('careers'),
                permission: canAccess('view_careers'),
            },
            {
                url: '/create-career',
                name: t('add_career'),
                permission: canAccess('create_careers'),
            },
            {
                url: '/applicants',
                name: t('applicants'),
                permission: canAccess('view_career_applicants'),
            },
        ],
    },
    {
        icon: markRaw(Page),
        name: t('faqs'),
        permission: canAccess('view_faq_categories') || canAccess('view_faqs'),
        id: 'faq',
        submenu: [
            {
                url: '/faq-categories',
                name: t('faq_categories'),
                permission: canAccess('view_faq_categories'),
            },
            {
                url: '/faqs',
                name: t('faqs'),
                permission: canAccess('view_faqs'),
            },
        ],
    },
    {
        icon: markRaw(PageFlip),
        name: t('blog'),
        permission: canAccess('view_blog_categories') || canAccess('view_blog_tags') || canAccess('view_blog_posts'),
        id: 'blog',
        submenu: [
            {
                url: '/blog-posts',
                name: t('blog_posts'),
                permission: canAccess('view_blog_posts'),
            },
            {
                url: '/blog-categories',
                name: t('blog_categories'),
                permission: canAccess('view_blog_categories'),
            },
            {
                url: '/blog-tags',
                name: t('blog_tags'),
                permission: canAccess('view_blog_tags'),
            },
        ],
    },
    {
        icon: markRaw(MediaImageFolder),
        name: t('outlets'),
        permission: canAccess('view_outlets'),
        id: 'outlets',
        submenu: [
            {
                url: '/outlets',
                name: t('outlets'),
                permission: canAccess('view_outlets'),
            },
            {
                url: '/create-outlet',
                name: t('add_outlet'),
                permission: canAccess('create_outlets'),
            },
        ],
    },
    {
        icon: markRaw(Settings),
        url: '/policy-settings/salamat',
        name: t('salamat_policy'),
        permission: canAccess('view_policy_settings'),
    },
    {
        icon: markRaw(Settings),
        url: '/policy-settings/tekzo',
        name: t('tekzo_policy'),
        permission: canAccess('view_policy_settings'),
    },

    {
        name: 'HRM',
        isTitle: true,
    },
    {
        icon: markRaw(Group),
        name: t('employee'),
        permission: canAccess('view_employees') || canAccess('view_designations') || canAccess('view_employee_statuses') || canAccess('view_departments'),
        id: 'hrm-employee',
        submenu: [
            {
                url: '/hrm/employees',
                name: t('employee'),
                permission: canAccess('view_employees'),
            },
            {
                url: '/hrm/designations',
                name: t('designation'),
                permission: canAccess('view_designations'),
            },
            {
                url: '/hrm/employee-statuses',
                name: t('employee_status'),
                permission: canAccess('view_employee_statuses'),
            },
            {
                url: '/hrm/departments',
                name: t('department'),
                permission: canAccess('view_departments'),
            },
        ],
    },

    {
        icon: markRaw(ClipboardCheck),
        name: t('attendance'),
        permission: canAccess('view_break_times') || canAccess('view_work_shifts') || canAccess('view_attendances') || canAccess('report_attendances') || canAccess('view_attendance_requests'),
        id: 'hrm-attendance',
        submenu: [
            {
                url: '/hrm/attendances',
                name: t('attendances'),
                permission: canAccess('view_attendances'),
            },
            {
                url: '/hrm/attendance-report',
                name: t('attendance_report'),
                permission: canAccess('report_attendances'),
            },
            {
                url: '/hrm/attendance-daily-log',
                name: t('daily_attendance_log'),
                permission: canAccess('view_attendances'),
            },
            {
                url: '/hrm/attendance-requests',
                name: t('attendance_requests'),
                permission: canAccess('view_attendance_requests'),
            },
            {
                url: '/hrm/break-times',
                name: t('break_time'),
                permission: canAccess('view_break_times'),
            },
            {
                url: '/hrm/work-shifts',
                name: t('work_shift'),
                permission: canAccess('view_work_shifts'),
            },
        ],
    },
    {
        icon: markRaw(PageFlip),
        name: t('leaves'),
        permission: canAccess('view_leave_types') || canAccess('view_leave_requests'),
        id: 'hrm-leaves',
        submenu: [
            {
                url: '/hrm/leave-types',
                name: t('leave_type'),
                permission: canAccess('view_leave_types'),
            },
            {
                url: '/hrm/leave-requests',
                name: t('leave_request'),
                permission: canAccess('view_leave_requests'),
            },
        ],
    },
    {
        icon: markRaw(Wallet),
        name: t('payroll'),
        permission: canAccess('view_beneficiaries') || canAccess('default_beneficiary_values')
            || canAccess('view_payruns') || canAccess('view_payslips'),
        id: 'payroll',
        submenu: [
            {
                url: '/hrm/beneficiaries',
                name: t('beneficiary'),
                permission: canAccess('view_beneficiaries'),
            },
            {
                url: '/hrm/beneficiary-values',
                name: t('badge_value'),
                permission: canAccess('default_beneficiary_values'),
            },
            {
                url: '/hrm/payruns',
                name: t('payruns'),
                permission: canAccess('view_payruns'),
            },
            {
                url: '/hrm/payslips',
                name: t('payslips'),
                permission: canAccess('view_payslips'),
            },
        ],
    },
    {
        icon: markRaw(Cube),
        name: t('assets'),
        permission: canAccess('view_asset_categories') || canAccess('view_assets'),
        id: 'hrm-assets',
        submenu: [
            {
                url: '/hrm/asset-categories',
                name: t('asset_category'),
                permission: canAccess('view_asset_categories'),
            },
            {
                url: '/hrm/assets',
                name: t('asset'),
                permission: canAccess('view_assets'),
            },
        ],
    },

    {
        icon: markRaw(BookmarkBook),
        name: t('holidays'),
        permission: canAccess('view_holidays'),
        id: 'hrm-holidays',
        submenu: [
            {
                url: '/hrm/holidays',
                name: t('holiday'),
                permission: canAccess('view_holidays'),
            },
            {
                url: '/hrm/holiday-calendar',
                name: t('holiday_calendar'),
                permission: canAccess('view_holidays'),
            },
        ],
    },
    {
        icon: markRaw(Calendar),
        name: t('events'),
        permission: canAccess('view_events'),
        url: '/hrm/events',
    },
    {
        icon: markRaw(BubbleIncome),
        name: t('announcements'),
        permission: canAccess('view_announcements'),
        url: '/hrm/announcements',
    },

    {
        name: 'Others',
        isTitle: true,
    },
    {
        icon: markRaw(UserSquare),
        name: t('ledger_name'),
        permission: canAccess('view_ledgers'),
        url: '/ledgers',
    },
    {
        icon: markRaw(ShoppingBagCheck),
        name: t('shop_investment'),
        url: '/purchase-stocks',
        permission: canAccess('view_purchase_stocks'),

    },

    // {
    //     icon: markRaw(Wallet),
    //     name: t('payment_records'),
    //     permission: canAccess('view_sale_transactions') ||
    //         canAccess('view_sale_return_transactions') ||
    //         canAccess('view_purchase_transactions') ||
    //         canAccess('view_purchase_return_transactions') ||
    //         canAccess('view_expense_transactions'),
    //     id: 'paymentRecords',
    //     submenu: [
    //         {
    //             url: '/sales-transactions',
    //             name: t('sale_payments'),
    //             permission: canAccess('view_sale_transactions'),
    //         },
    //         {
    //             url: '/sales-return-transactions',
    //             name: t('sale_return_payments'),
    //             permission: canAccess('view_sale_return_transactions'),
    //         },
    //         {
    //             url: '/purchase-transactions',
    //             name: t('purchase_payments'),
    //             permission: canAccess('view_purchase_transactions'),
    //         },
    //         {
    //             url: '/purchase-return-transactions',
    //             name: t('purchase_return_payments'),
    //             permission: canAccess('view_purchase_return_transactions'),
    //         },
    //         {
    //             url: '/expenses-transactions',
    //             name: t('expense_payments'),
    //             permission: canAccess('view_expense_transactions'),
    //         },
    //     ],
    // },
    {
        icon: markRaw(ReportColumns),
        name: t('Reports'),
        permission: canAccess('view_stock_reports') ||
            canAccess('view_sale_reports') ||
            canAccess('view_sale_return_reports') ||
            canAccess('view_purchase_reports') ||
            canAccess('view_purchase_return_reports') ||
            canAccess('view_deposit_reports') ||
            canAccess('view_withdrawal_reports') ||
            canAccess('view_transaction_reports') ||
            canAccess('view_supplier_reports') ||
            canAccess('view_profit_loss_statistics') ||
            canAccess('view_brand_sale_reports') ||
            canAccess('view_category_sale_reports') ||
            canAccess('view_sale_by_product_reports') ||
            canAccess('view_deposit_report') ||
            canAccess('view_withdrawal_report') ||
            canAccess('view_expense_reports') ||
            canAccess('view_stock_transfer_reports') ||
            canAccess('view_cash_transfer_reports') ||
            canAccess('view_damage_product_reports'),
        id: 'reports',
        submenu: [
            {
                url: '/stock-reports',
                name: t('stock'),
                permission: canAccess('view_stock_reports'),
            },
            {
                url: '/profit-loss-reports',
                name: 'Profit Loss Report',
                permission: canAccess('view_profit_loss_reports'),
            },
            {
                url: '/sale-reports',
                name: t('sale'),
                permission: canAccess('view_sale_reports'),
            },
            {
                url: '/sale-return-reports',
                name: t('sales_return'),
                permission: canAccess('view_sale_return_reports'),
            },
            {
                url: '/purchase-reports',
                name: t('purchase'),
                permission: canAccess('view_purchase_reports'),
            },
            {
                url: '/purchase-return-reports',
                name: t('purchase_return'),
                permission: canAccess('view_purchase_return_reports'),
            },
            {
                url: '/stock-transfer-reports',
                name: 'Stock Transfer',
                permission: canAccess('view_stock_transfer_reports'),
            },
            {
                url: '/cash-transfer-reports',
                name: 'Cash Transfer',
                permission: canAccess('view_cash_transfer_reports'),
            },
            {
                url: '/damage-product-reports',
                name: 'Damage Product',
                permission: canAccess('view_damage_product_reports'),
            },
            {
                url: '/expense-reports',
                name: t('expense'),
                permission: canAccess('view_expense_reports'),
            },
            {
                url: '/deposit-report',
                name: t('deposit_report'),
                permission: canAccess('view_deposit_reports'),
            },
            {
                url: '/withdrawal-report',
                name: t('withdrawal_report'),
                permission: canAccess('view_withdrawal_reports'),
            },
            {
                url: '/transaction-reports',
                name: t('transactions'),
                permission: canAccess('view_transaction_reports'),
            },
            // {
            //     url: '/supplier-reports',
            //     name: t('supplier'),
            //     permission: canAccess('view_supplier_reports'),
            // },
            // {
            //     url: '/profit-loss-statistic',
            //     name: t('profit_loss'),
            //     permission: canAccess('view_profit_loss_statistics'),
            // },
            // {
            //     url: '/brand-sale-reports',
            //     name: t('brand_sale'),
            //     permission: canAccess('view_brand_sale_reports'),
            // },
            // {
            //     url: '/category-sale-reports',
            //     name: t('category_sale'),
            //     permission: canAccess('view_category_sale_reports'),
            // },
            // {
            //     url: '/sale-by-product-reports',
            //     name: t('sale_by_product'),
            //     permission: canAccess('view_sale_by_product_reports'),
            // },
        ],
    },
    {
        icon: markRaw(WebWindowXmark),
        name: t('daily_closing_statement'),
        permission: canAccess('view_daily_closings') ||
            canAccess('view_daily_closing_balance') ||
            canAccess('view_daily_closing_profit_loss') ||
            canAccess('view_balance_sheets') ||
            canAccess('view_stock_statements') ||
            canAccess('view_sale_statements') ||
            canAccess('view_purchase_statements') ||
            canAccess('view_purchase_return_statements') ||
            canAccess('view_sale_return_statements') ||
            canAccess('view_expense_statements') ||
            canAccess('view_withdrawal_statements') ||
            canAccess('view_deposit_statements') ||
            canAccess('view_transactions'),
        id: 'dailyClosing',
        submenu: [
            {
                url: '/daily-closing',
                name: t('daily_closings'),
                permission: canAccess('view_daily_closings'),
            },
            {
                url: '/daily-closing-balance',
                name: t('daily_closing_balance'),
                permission: canAccess('view_daily_closing_balance'),
            },
            {
                url: '/daily-closing-profit-loss',
                name: 'Profit Loss',
                permission: canAccess('view_daily_closing_profit_loss'),
            },
            {
                url: '/balance-sheets',
                name: t('balance_sheets'),
                permission: canAccess('view_balance_sheets'),
            },
            {
                url: '/stock-statement',
                name: t('stock_statements'),
                permission: canAccess('view_stock_statements'),
            },
            {
                url: '/sale-statement',
                name: t('sale_statements'),
                permission: canAccess('view_sale_statements'),
            },
            {
                url: '/purchase-statement',
                name: t('purchase_statements'),
                permission: canAccess('view_purchase_statements'),
            },
            {
                url: '/purchase-return-statement',
                name: t('purchase_return_statements'),
                permission: canAccess('view_purchase_return_statements'),
            },
            {
                url: '/sale-return-statement',
                name: t('sale_return_statements'),
                permission: canAccess('view_sale_return_statements'),
            },
            {
                url: '/expense-statement',
                name: t('expense_statements'),
                permission: canAccess('view_expense_statements'),
            },
            {
                url: '/withdrawal-statement',
                name: t('withdrawal_statements'),
                permission: canAccess('view_withdrawal_statements'),
            },
            {
                url: '/deposit-statement',
                name: t('deposit_statements'),
                permission: canAccess('view_deposit_statements'),
            },
        ],
    },

    {
        icon: markRaw(UserStar),
        name: t('user_roles'),
        permission: canAccess('view_users') || canAccess('view_roles'),
        id: 'administrator',
        submenu: [
            {
                url: '/users',
                name: t('users'),
                permission: canAccess('view_users'),
            },
            {
                url: '/roles',
                name: t('roles'),
                permission: canAccess('view_roles'),
            },
        ],
    },
    {
        icon: markRaw(Settings),
        name: t('settings'),
        permission: canAccess('view_settings') ||
            canAccess('view_payment_methods') ||
            canAccess('view_shops') ||
            canAccess('view_ecommerces') ||
            canAccess('view_payrun_settings') ||
            canAccess('default_payrun_types') ||
            canAccess('view_leave_settings') ||
            canAccess('view_attendance_settings') ||
            canAccess('view_delivery_charge_settings') ||
            canAccess('view_area_shipping_charges'),
        id: 'settings',
        submenu: [
            {
                url: '/settings/general',
                name: t('general_setting'),
                permission: canAccess('view_settings'),
            },
            {
                url: '/settings/payment-method',
                name: t('payment_method'),
                permission: canAccess('view_payment_methods'),
            },
            {
                url: '/settings/shop',
                name: t('shop'),
                permission: canAccess('view_shops'),
            },
            {
                url: '/settings/ecommerce',
                name: t('ecommerce'),
                permission: canAccess('view_ecommerces'),
            },
            {
                url: '/payrun-settings',
                name: t('payrun_settings'),
                permission: canAccess('view_payrun_settings'),
            },
            {
                url: '/leave-settings',
                name: t('leave_settings'),
                permission: canAccess('view_leave_settings'),
            },
            {
                url: '/attendance-settings',
                name: t('attendance_settings'),
                permission: canAccess('view_attendance_settings'),
            },
            {
                url: '/delivery-charge-settings',
                name: t('delivery_charge_settings'),
                permission: canAccess('view_delivery_charge_settings'),
            },
            {
                url: '/area-shipping-charges',
                name: t('area_shipping_charges'),
                permission: canAccess('view_area_shipping_charges'),
            },

        ],
    },

]);
const activeMenu = ref('');
const sidebarStatus = ref('open');
const menuChanged = (e) => {
    const toggleMenu = document.querySelector('.collapse.show');
    if (toggleMenu) {
        const collapseInstance = Collapse.getInstance(toggleMenu);
        if (collapseInstance) {
            collapseInstance.hide();
        } else {
            new Collapse(toggleMenu).hide();
        }
    }
    activeMenu.value = '';
};
const removeSidebarActive = (e) => {
    e.stopPropagation();
    const sidebar = document.querySelector(".sidebar-off-canvas");
    if (sidebar.classList.contains("active")) {
        sidebar.classList.remove("active");
    }

};
const SideBarNav = ref();
onMounted(() => {
    let element = SideBarNav.value.querySelectorAll('[data-bs-toggle="collapse"]');

    element.forEach((e) => {
        let sibling = e.nextElementSibling;
        if (sibling.classList.contains('collapse')) {
            const child = sibling.querySelectorAll('li .active');
            if (child.length) {
                activeMenu.value = e.getAttribute('aria-controls');
            }
        }
    });
});
const sidebarOffCanvas = () => {
    const sidebar = document.querySelector('.sidebar-off-canvas.active');
    if (sidebar) sidebar.classList.remove('active');
};
</script>

<template>
    <nav ref="SideBarNav" class="sidebar sidebar-off-canvas custom-scrollbar" id="sidebar">
        <router-link :to="{ name: 'dashboard' }" class="navbar-brand" @click="menuChanged">
            <img :src="settingData.company_logo" alt="brand-logo" />
        </router-link>
        <div class="rounded-box sidebar-close-icon" @click="removeSidebarActive">
            <Xmark />
        </div>

        <ul class="nav responsive-nav" id="linkContainer">
            <template v-for="(item, index) in sidebarData" :key="index">
                <li v-if="item.isTitle" class="nav-item nav-menu-title">
                    <span>{{ item.name }}</span>
                </li>
                <li v-if="item.permission === true && !item.submenu" class="nav-item single-list" @click="menuChanged">
                    <router-link :to="item.id ? '#' + item.id : item.url" class="nav-link" @click="sidebarOffCanvas">
                        <div class="icon-wrapper">
                            <component :is="item.icon" />
                        </div>
                        <span v-if="!item.submenu" class="menu-title">{{ item.name }}</span>
                    </router-link>
                </li>
                <li v-if="item.permission && item.id && item.submenu" class="nav-item single-list nav-item-dropdown"
                    :class="{ 'dropdown dropend': sidebarStatus !== 'open' }">
                    <a class="nav-link collapsible" :class="{ active: activeMenu === item.id }"
                        :data-bs-toggle="sidebarStatus === 'open' ? 'collapse' : 'dropdown'" :href="`#${item.id}`"
                        role="button" aria-expanded="false" :aria-controls="item.id">
                        <div class="icon-wrapper">
                            <component :is="item.icon" />
                        </div>
                        <div class="w-100 d-flex justify-content-between align-items-center menu-title gap-2">
                            <span>{{ item.name }}</span>
                            <div class="rotate-a">
                                <NavArrowDown />
                            </div>
                        </div>
                    </a>
                    <div class="sidebarDropdownItems pb-2" :class="{
                        collapse: sidebarStatus === 'open',
                        'dropdown-menu shadow-sm border-0': sidebarStatus !== 'open',
                        show: sidebarStatus === 'open' && activeMenu === item.id,
                    }" data-bs-parent="#linkContainer"
                        :style="sidebarStatus === 'open' ? 'position: relative; inset: none; margin: 0px; transform: none' : ''"
                        :id="item.id">
                        <ul class="nav flex-column sub-menu my-0">
                            <template v-for="(menu, index) in item.submenu" :key="index">
                                <li v-if="menu.permission" class="nav-item" @click="activeMenu = item.id">
                                    <router-link :to="menu.url" class="nav-link">
                                        <span class="submenu-title">{{ menu.name }}</span>
                                    </router-link>
                                </li>
                            </template>
                        </ul>
                    </div>
                </li>
            </template>
        </ul>
    </nav>
</template>

<style lang="scss"></style>
