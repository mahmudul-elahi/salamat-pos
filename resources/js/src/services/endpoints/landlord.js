/**
 * Landlord API URL
 *
 */

/* ===========================================================================
* Landlord Dashboard module endpoints start here
* ============================================================================
*/

export const DASHBOARD_STATISTICS = "/admin/pos/dashboard/statistics";
export const DASHBOARD_PURCHASE_SALE_OVERVIEW = "/admin/pos/dashboard/purchases-and-sales";
export const DASHBOARD_PURCHASE_SALE_RETURN_OVERVIEW = "/admin/pos/dashboard/purchase-sale-and-return-overviews";
export const DASHBOARD_TOP_PRODUCT_OVERVIEW = "/admin/pos/dashboard/top-sell-products";
export const DASHBOARD_TOP_CUSTOMER_OVERVIEW = "/admin/pos/dashboard/top-sell-customers";
export const DASHBOARD_PAYMENT_RECEIVE_SENT_OVERVIEW = "/admin/pos/dashboard/payment-sent-and-received";

/* ===========================================================================
* Landlord Subscriber module endpoints start here
* ============================================================================
*/

export const LANDLORD_COMPANIES = "/admin/landlord/companies";
export const LANDLORD_COMPANIES_DELETE_REQUEST = "/admin/landlord/delete-request-companies";


/* ===========================================================================
* Landlord Administrator endpoints start here
* ============================================================================
*/

export const LANDLORD_USERS = "/admin/landlord/users";
export const LANDLORD_USER_INVITE = "/admin/landlord/user-invite";
export const LANDLORD_ROLES = "/admin/landlord/roles";
export const LANDLORD_PERMISSIONS = "/admin/landlord/support/permissions";
export const LANDLORD_DETACH_ROLE_USER = "/admin/landlord/detach-user-role";
export const LANDLORD_ATTACH_ROLE_USER = "/admin/landlord/attach-user-role";

/* ===========================================================================
* Landlord Settings endpoints start here
* ============================================================================
*/

export const LANDLORD_SETTINGS = "/admin/landlord/setting/settings";
export const LANDLORD_PAYRUN_SETTINGS = "/admin/hrm/payrun-settings";
export const LANDLORD_LEAVE_SETTINGS = "/admin/landlord/setting/leave-settings";
export const LANDLORD_ATTENDANCE_SETTINGS = "/admin/landlord/setting/attendance-settings";
export const LANDLORD_DELIVERY_CHARGE_SETTINGS = "/admin/landlord/setting/delivery-charge-settings";
export const LANDLORD_AREA_SHIPPING_CHARGES = "/admin/landlord/setting/area-shipping-charges";
export const LANDLORD_LEAVE_SETTINGS_ASSIGN = "/admin/landlord/setting/leave-settings/assign";
export const POLICY_SETTINGS = "/admin/landlord/setting/policy-settings";
export const POLICY_SETTINGS_UPDATE = "/admin/landlord/setting/policy-settings/update";
export const LANDLORD_EMAIL_SETTINGS = "/admin/landlord/setting/email-settings";
export const LANDLORD_PAYMENT_METHODS = "/admin/landlord/setting/payment-methods";
export const LANDLORD_SHOPS = "/admin/landlord/setting/shops";
export const LANDLORD_ECOMMERCES = "/admin/landlord/setting/ecommerces";
export const LANDLORD_SHOP_SETTINGS = "/admin/landlord/setting/shop-settings";
export const LANDLORD_SHOPS_STATUS = "/admin/landlord/setting/shops/change-status";

/* ===========================================================================
* Landlord selected endpoints start here
* ============================================================================
*/

export const LANDLORD_SELECTED_EXIST_EMAIL_SETTINGS = "/admin/landlord/support/exist-mail-setup";
export const LANDLORD_SELECTED_TEST_EMAIL = "/admin/landlord/support/send-test-mail";
export const LANDLORD_SELECTED_ROLES = "/admin/landlord/support/roles";
export const LANDLORD_SELECTED_USERS = "/admin/landlord/support/users";
export const LANDLORD_SELECTED_ROLE_WITHOUT_USERS = "/admin/landlord/support/role-without-users";

/* ===========================================================================
* Landlord Email template endpoints start here
* ============================================================================
*/

export const LANDLORD_EMAIL_TEMPLATE_TYPE = '/admin/landlord/email-template-types';
export const LANDLORD_EMAIL_TEMPLATE = '/admin/landlord/templates';

/* ===========================================================================
*  adjustment module endpoints start here
* ============================================================================
*/
export const ADJUSTMENTS = "/admin/landlord/adjustments"


/* ===========================================================================
*  product module endpoints start here
* ============================================================================
*/
export const CATEGORIES = "/admin/landlord/categories";
export const UPDATE_CATEGORIES_ORDER = '/admin/landlord/categories/update-order'
export const PRODUCTS = "admin/landlord/products";
export const PRODUCT_VARIANT_SHOW = (productId) => `admin/landlord/products/variant-show/${productId}`;
export const PRODUCTS_STATUS = "admin/landlord/product-status";
export const PRODUCTS_WARRANTY = "admin/landlord/product-warranty";
export const PRODUCTS_ECOMMERCE = "admin/landlord/product-ecommerce";
export const PRODUCTS_CHINESE = "admin/landlord/product-chinese";
export const PRODUCTS_UNOFFICIAL = "admin/landlord/product-unofficial";
export const PRODUCTS_REFURBISHED = "admin/landlord/product-refurbished";
export const PRODUCTS_SCRAPE_SPECS = "admin/landlord/products/scrape-specs";
export const PRODUCTS_SAVE_SPECS = "admin/landlord/products";
export const PRODUCT_VARIANT_UPDATE = "admin/landlord/products/variant-update";
export const PRODUCT_PRINT_BARCODES = "admin/landlord/product-print-barcodes";
export const BRANDS = "/admin/landlord/brands";
export const BRAND_STATUS_CHANGE = "/admin/landlord/brands";
export const UPDATE_BRANDS_ORDER = "/admin/landlord/brands/update-order";
export const ATTRIBUTES = "/admin/landlord/attributes";
export const SPECIFICATION_GROUPS = "/admin/landlord/specification-groups";
export const EXPENSES = "/admin/pos/expenses";
export const EXPENSE_CATEGORY = "/admin/pos/expense-categories";
export const INCOMES = "/admin/pos/incomes";
export const DEALERS = "/admin/pos/dealers";
export const RETAILERS = "/admin/pos/retailers";
export const FRANCHISES = "/admin/pos/franchises";
export const PARTNERS = "/admin/pos/partners";
export const B2B = "/admin/pos/b2b";
export const INCOME_CATEGORY = "/admin/pos/income-categories";
export const INVESTMENTS = "/admin/pos/investments";
export const INVESTMENT_CATEGORY = "/admin/pos/investment-categories";
export const CUSTOMERS = "/admin/landlord/customers";
export const PEOPLE = "/admin/landlord/people";
export const PEOPLE_BULK_DELETE = "/admin/landlord/people/bulk-delete";
export const PAYMENTS = "/admin/landlord/payments";
export const PAYMENT_HISTORY = "/admin/landlord/payment-history";
export const PAYMENT_DEBIT_VOUCHERS = "/admin/landlord/debit-vouchers";
export const PAYMENT_CREDIT_VOUCHERS = "/admin/landlord/credit-vouchers";
export const SUPPLIERS = "/admin/landlord/suppliers";
export const WAREHOUSES = "/admin/landlord/warehouses";
export const PRODUCT_BARCODE_GENERATE = '/admin/landlord/product-barcode-generate'
export const PRODUCT_MULTI_BARCODE_PDF = '/admin/landlord/barcode-multi-pdf'
export const PRODUCT_SINGLE_BARCODE_PDF = '/admin/landlord/barcode-single-pdf'
export const PRODUCT_GENERATED_BARCODE_PDF = '/admin/landlord/barcode-generated-pdf'
export const PRODUCT_BARCODES = '/admin/landlord/product-barcodes'
export const PRODUCT_BARCODE_HISTORY = '/admin/landlord/product-serial-history'
export const PRODUCT_SERIAL_HISTORY_DELETE = '/admin/landlord/product/barcodes'
export const PRODUCT_BARCODE_GENERATES = '/admin/landlord/barcode-generates'


export const PURCHASES = "/admin/pos/purchases";
export const PURCHASE_INVOICE_DOWNLOAD = "/admin/pos/purchase-invoice-download";
export const PURCHASE_INVOICE_DATA = "/admin/pos/purchase-invoice-data";
export const PURCHASES_PAYMENT = "/admin/pos/purchase-payment";
export const PURCHASE_RETURNS = "/admin/pos/purchase-returns";
export const PURCHASE_RETURN_INVOICE_DOWNLOAD = "/admin/pos/purchase-return-invoice-download";
export const PURCHASE_RETURN_INVOICE_DATA = "/admin/pos/purchase-return-invoice-data";
export const PURCHASE_RETURNS_PAYMENT = "/admin/pos/purchase-return-payment";
export const PURCHASE_RETURNS_TRANSACTION_DELETE = "/admin/pos/purchase-return-transactions";
export const SALES = "/admin/pos/sales";
export const SALE_INVOICE_DOWNLOAD = "/admin/pos/sale-invoice-download";
export const SALE_INVOICE_DATA = "/admin/pos/sale-invoice-data";
export const SALE_RETURN_INVOICE_DOWNLOAD = "/admin/pos/sale-return-invoice-download";
export const SALE_RETURN_INVOICE_DATA = "/admin/pos/sale-return-invoice-data";
export const SALE_RETURNS_PAYMENT = "/admin/pos/sale-return-payment"
export const SALES_RETURNS = "/admin/pos/sale-returns";
export const SALES_PAYMENT = "/admin/pos/sale-payment";
export const COURIER_ORDERS = "/admin/pos/courier-orders";
export const PATHAO_ORDERS = "/admin/pos/pathao-orders";
export const PATHAO_ORDERS_SYNC = "/admin/pos/pathao-orders/sync";
export const PATHAO_STORES = "/admin/pos/pathao/stores";
export const PATHAO_CITIES = "/admin/pos/pathao/cities";
export const PATHAO_ZONES = (cityId) => `/admin/pos/pathao/cities/${cityId}/zones`;
export const PATHAO_AREAS = (zoneId) => `/admin/pos/pathao/zones/${zoneId}/areas`;
export const PATHAO_CREATE_ORDER = (saleId) => `/admin/pos/sales/${saleId}/pathao-order`;
export const STEADFAST_ORDERS = "/admin/pos/steadfast-orders";
export const STEADFAST_ORDERS_IMPORT = "/admin/pos/steadfast-orders/import";
export const STEADFAST_CREATE_ORDER = (saleId) => `/admin/pos/sales/${saleId}/steadfast-order`;
export const STEADFAST_DISTRICTS = "/admin/pos/steadfast/districts";
export const STEADFAST_THANAS = (districtId) => `/admin/pos/steadfast/districts/${districtId}/thanas`;
export const BDCOURIER_CHECK_FRAUD = "/admin/pos/bdcourier/check-fraud";
export const BDCOURIER_CHECK_FRAUD_API = "/admin/pos/bdcourier/check-fraud-api";
export const CHAT_CONVERSATIONS = "/admin/pos/chat-conversations";
export const CHAT_CONVERSATION_DETAILS = (id) => `/admin/pos/chat-conversations/${id}`;
export const CHAT_CONVERSATION_MESSAGES = (id) => `/admin/pos/chat-conversations/${id}/messages`;
export const CHAT_CONVERSATION_ASSIGN = (id) => `/admin/pos/chat-conversations/${id}/assign`;
export const CHAT_CONVERSATION_CLAIM = (id) => `/admin/pos/chat-conversations/${id}/claim`;
export const CHAT_CONVERSATION_REASSIGN = (id) => `/admin/pos/chat-conversations/${id}/reassign`;
export const CHAT_CONVERSATION_CLOSE = (id) => `/admin/pos/chat-conversations/${id}/close`;
export const CHAT_CONVERSATION_REOPEN = (id) => `/admin/pos/chat-conversations/${id}/reopen`;
export const CHAT_CONVERSATION_ASSIGN_TEAM = (id) => `/admin/pos/chat-conversations/${id}/assign-team`;
export const CHAT_CONVERSATION_PRIORITY = (id) => `/admin/pos/chat-conversations/${id}/priority`;
export const CHAT_CONVERSATION_LABELS = (id) => `/admin/pos/chat-conversations/${id}/labels`;
export const CHAT_CONVERSATION_ATTACH_CONTACT = (id) => `/admin/pos/chat-conversations/${id}/attach-contact`;
export const CHAT_TEAMS = "/admin/pos/chat-teams";
export const CHAT_TEAM = (id) => `/admin/pos/chat-teams/${id}`;
export const CHAT_LABELS = "/admin/pos/chat-labels";
export const WHATSAPP_TEMPLATES = "/admin/pos/whatsapp-templates";
export const WHATSAPP_TEMPLATES_SYNC = "/admin/pos/whatsapp-templates/sync";
export const EXPENSE_PAYMENT = "/admin/pos/expense-payment";
export const INCOME_PAYMENT = "/admin/pos/income-payment";
export const SALE_TRANSACTIONS = "/admin/pos/sale-transactions";
export const SALE_RETURN_TRANSACTIONS = "/admin/pos/sale-return-transactions";
export const PURCHASE_TRANSACTIONS = "/admin/pos/purchase-transactions";
export const PURCHASE_RETURN_TRANSACTIONS = "/admin/pos/purchase-return-transactions";
export const EXPENSE_TRANSACTIONS = "/admin/pos/expense-transactions";
export const CLOSING_STATEMENTS = "/admin/pos/closing-statements";
export const STOCK_STATEMENT_PDF = "/admin/pos/stock-statement-pdf";
export const STOCK_STATEMENT_PDF_STATUS = "/admin/pos/stock-statement-pdf-status";
export const STOCK_STATEMENT_PDF_CLEANUP = "/admin/pos/stock-statement-pdf-cleanup";
export const STOCK_REPORT_PDF = "/admin/pos/stock-report-pdf";
export const STOCK_REPORT_PDF_STATUS = "/admin/pos/stock-report-pdf-status";
export const STOCK_REPORT_PDF_CLEANUP = "/admin/pos/stock-report-pdf-cleanup";
export const SALE_STATEMENT_PDF = "/admin/pos/sale-statement-pdf";
export const SALE_RETURN_STATEMENT_PDF = "/admin/pos/sale-return-statement-pdf";
export const PURCHASE_STATEMENT_PDF = "/admin/pos/purchase-statement-pdf";
export const PURCHASE_RETURN_STATEMENT_PDF = "/admin/pos/purchase-return-statement-pdf";
export const EXPENSE_STATEMENT_PDF = "/admin/pos/expense-statement-pdf";
export const WITHDRAWAL_STATEMENT_PDF = "/admin/pos/withdrawal-statement-pdf";
export const DEPOSIT_STATEMENT_PDF = "/admin/pos/deposit-statement-pdf";
export const TRANSACTION_REPORT_PDF = "/admin/pos/transaction-report-pdf";
export const SALE_STATEMENTS = "/admin/pos/sale-statements";
export const PURCHASE_STATEMENTS = "/admin/pos/purchase-statements";
export const PURCHASE_RETURN_STATEMENTS = "/admin/pos/purchase-return-statements";
export const SALE_RETURN_STATEMENTS = "/admin/pos/sale-return-statements";
export const EXPENSE_STATEMENTS = "/admin/pos/expense-statements";
export const WITHDRAWAL_STATEMENTS = "/admin/pos/withdrawal-statements";
export const DEPOSIT_STATEMENTS = "/admin/pos/deposit-statements";
export const DAILY_CLOSINGS = "/admin/pos/daily-closings";
export const DAILY_CLOSING_PDF_DOWNLOAD = "/admin/pos/daily-closing-pdf-download";
export const DAILY_CLOSING_PROFIT_LOSS = "/admin/pos/daily-closing-profit-loss";
export const DAILY_CLOSING_PROFIT_LOSS_PDF = "/admin/pos/daily-closing-profit-loss-pdf";
export const DAILY_CLOSING_BALANCE = "/admin/pos/daily-closing-balance";
export const DAILY_CLOSING_BALANCE_PDF_DOWNLOAD = "/admin/pos/daily-closing-balance-download";
export const TRANSACTIONS = "/admin/pos/transactions";
export const STOCK_REPORTS = "/admin/pos/stock-reports";
export const EXPENSE_REPORTS = "/admin/pos/expense-reports";
export const SALE_REPORTS = "/admin/pos/sale-reports";
export const SALE_REPORT_PDF = "/admin/pos/sale-report-pdf";
export const SALE_RETURN_REPORTS = "/admin/pos/sale-return-reports";
export const DEPOSIT_REPORTS = "/admin/pos/deposit-reports";
export const DEPOSIT_REPORT_PDF = "/admin/pos/deposit-report-pdf";
export const EXPENSE_REPORT_PDF = "/admin/pos/expense-report-pdf";
export const EXPENSE_REPORT_PDF_STATUS = "/admin/pos/expense-report-pdf-status";
export const EXPENSE_REPORT_PDF_CLEANUP = "/admin/pos/expense-report-pdf-cleanup";
export const SALE_RETURN_REPORT_PDF = "/admin/pos/sale-return-report-pdf";
export const WITHDRAWAL_REPORTS = "/admin/pos/withdrawal-reports";
export const WITHDRAWAL_REPORT_PDF = "/admin/pos/withdrawal-report-pdf";
export const PURCHASE_REPORTS = "/admin/pos/purchase-reports";
export const PURCHASE_REPORT_PDF = "/admin/pos/purchase-report-pdf";
export const PURCHASE_RETURN_REPORTS = "/admin/pos/purchase-return-reports";
export const STOCK_TRANSFER_REPORTS = "/admin/pos/stock-transfer-reports";
export const STOCK_TRANSFER_REPORT_PDF = "/admin/pos/stock-transfer-report-pdf";
export const CASH_TRANSFER_REPORTS = "/admin/pos/cash-transfer-reports";
export const CASH_TRANSFER_REPORT_PDF = "/admin/pos/cash-transfer-report-pdf";
export const DAMAGE_PRODUCT_REPORTS = "/admin/pos/damage-product-reports";
export const DAMAGE_PRODUCT_REPORT_PDF = "/admin/pos/damage-product-report-pdf";
export const PURCHASE_RETURN_REPORT_PDF = "/admin/pos/purchase-return-report-pdf";
export const SUPPLIER_REPORTS = "/admin/pos/supplier-reports";
export const PROFIT_LOSS_REPORTS = "/admin/pos/profit-loss-reports";
export const PROFIT_LOSS_PDF = "/admin/pos/profit-loss-pdf";
export const PROFIT_LOSS_PDF_STATUS = "/admin/pos/profit-loss-pdf-status";
export const PROFIT_LOSS_PDF_CLEANUP = "/admin/pos/profit-loss-pdf-cleanup";

export const CUSTOMER_REPORTS = "/admin/pos/customer-reports";
export const SUPPLIER_PURCHASE_REPORTS = "/admin/pos/supplier-purchase-reports";
export const SUPPLIER_PURCHASE_RETURN_REPORTS = "/admin/pos/supplier-purchase-return-reports";
export const SUPPLIER_PURCHASE_TRANSACTION_REPORTS = "/admin/pos/supplier-purchase-transaction-reports";
export const SUPPLIER_PURCHASE_RETURN_TRANSACTION_REPORTS = "/admin/pos/supplier-purchase-return-transaction-reports";
export const PROFIT_LOSS_STATISTICS = "/admin/pos/profit-loss-statistic";
export const SUPPLIER_LEDGER_REPORTS = "/admin/pos/supplier-ledger-reports";
export const SUPPLIER_LEDGER_REPORTS_PDF = "/admin/pos/supplier-ledger-reports-download";
export const CUSTOMER_LEDGER_REPORTS = "/admin/pos/customer-ledger-reports";
export const LEDGER_REPORTS = "/admin/pos/ledger-reports";

export const CONTACT_HISTORY = "/admin/pos/contact-history";

export const CUSTOMER_LEDGER_REPORTS_PDF = "/admin/pos/customer-ledger-reports-download";
export const BRAND_SALE_REPORTS = "/admin/pos/brand-sale-reports";
export const CATEGORY_SALE_REPORTS = "/admin/pos/category-sale-reports";
export const SALE_BY_PRODUCT_REPORTS = "/admin/pos/sale-by-product-reports";
export const CUSTOMER_PAYMENT = "/admin/landlord/customers-payment";
export const SUPPLIER_PAYMENT_SEND = "/admin/landlord/supplier-payment-send";
export const CUSTOMER_DUE = (customerId) => `/admin/landlord/customers-due/${customerId}`;
export const SEO_MANAGE = "/admin/landlord/ecommerce/seo";
export const ELITES = "/admin/ecommerce/elites";
export const LEDGERS = "/admin/pos/ledgers";
export const DIRECTORS = "/admin/landlord/directors";
export const DIRECTOR_PROFILE_PDF_STATUS = "/admin/landlord/director-profile-pdf-status";
export const DIRECTOR_PROFILE_PDF_CLEANUP = "/admin/landlord/director-profile-pdf-cleanup";
export const CARES = "/admin/pos/cares";
export const SELECTED_CARES = "/admin/selected/cares";
export const BALANCE_SHEETS = "/admin/pos/balance-sheets";
export const RECEIVABLES = "/admin/pos/receivables";

/* ===========================================================================
*  selected endpoints start here
* ===========================================================================
*/
export const SELECTED_CATEGORY = "/admin/selected/categories";
export const SELECTED_NESTED_CATEGORIES = "/admin/selected/nested-categories";
export const SELECTED_SPECIFICATION_GROUPS = "/admin/selected/specifications";
export const SELECTED_ALL_SPECIFICATION_GROUPS = "/admin/selected/all-specifications";
export const SELECTED_EXPENSE_CATEGORY = "/admin/selected/expense-categories";
export const SELECTED_INCOME_CATEGORY = "/admin/selected/income-categories";
export const SELECTED_INVESTMENT_CATEGORY = "/admin/selected/investment-categories";
export const SELECTED_PRODUCT = "/admin/selected/products";
export const SELECTED_PRODUCT_VARIANTS = "/admin/selected/products-variant";
export const SELECTED_SKU = (productId) => `/admin/selected/${productId}/products/variants`;
export const SELECTED_PRODUCT_ATTRIBUTE = `/admin/selected/products/variant/attributes`;
export const SELECTED_BRAND = "/admin/selected/brands";
export const SELECTED_PRE_OWNED = "/admin/selected/pre-owned-products";
export const SEARCH_PRODUCTS = '/admin/selected/search-product';
export const PURCHASE_SEARCH_PRODUCTS = '/admin/selected/purchase-search-product';
export const PURCHASE_RETURN_SEARCH_PRODUCTS = '/admin/selected/purchase-return-search-product';
export const SALE_SEARCH_PRODUCTS = '/admin/selected/sale-search-product';
export const SALE_RETURN_SEARCH_PRODUCTS = '/admin/selected/sale-return-search-product';
export const SELECTED_WAREHOUSES = '/admin/selected/warehouses';
export const SELECTED_PAYMENT_METHODS = '/admin/selected/payment-methods';
export const SELECTED_COLORS = '/admin/selected/colors';
export const SELECTED_ATTRIBUTES = '/admin/selected/attributes';
export const SELECTED_ATTRIBUTE_VALUES = (attributeId) => `/admin/selected/attribute-values/${attributeId}`
export const SELECTED_PRODUCT_VARIANT_SERIAL_NUMBER = (productVariantId) => `/admin/selected/product/variant-serial/${productVariantId}`
export const SELECTED_COUNTRIES = '/admin/selected/countries';
export const SELECTED_DISTRICTS = "/admin/selected/districts";
export const SELECTED_AREAS = (districtId) => `/admin/selected/${districtId}/districts/areas`;
export const SELECTED_SUPPLIERS = `/admin/selected/suppliers`;
export const CONTACTS = "/admin/pos/contacts";
export const ONLINE_CONTACTS = "/admin/landlord/online-contacts";
export const ONLINE_CONTACT_ORDERS          = (id) => `/admin/landlord/online-contacts/${id}/orders`;
export const ONLINE_CONTACT_ELITE_HISTORIES = (id) => `/admin/landlord/online-contacts/${id}/elite-histories`;
export const ONLINE_CONTACT_ADDRESSES       = (id) => `/admin/landlord/online-contacts/${id}/addresses`;
export const ONLINE_CONTACT_REVIEWS         = (id) => `/admin/landlord/online-contacts/${id}/reviews`;
export const ONLINE_CONTACT_CART            = (id) => `/admin/landlord/online-contacts/${id}/cart`;
export const ONLINE_CONTACT_WISHLIST        = (id) => `/admin/landlord/online-contacts/${id}/wishlist`;
export const ONLINE_CONTACT_SELL_DEVICE_REQUESTS = (id) => `/admin/landlord/online-contacts/${id}/sell-device-requests`;
export const SELECTED_CONTACTS = `/admin/selected/contacts`;
export const SELECTED_CUSTOMERS = `/admin/selected/customers`;
export const SELECTED_CUSTOMER_LEVELS = `/admin/selected/customer-levels`;
export const SELECTED_SHOPS = `/admin/selected/shops`;
export const SELECTED_ECOMMERCES = `/admin/selected/ecommerces`;
export const SELECTED_PRODUCT_LIST = `/admin/selected/product-lists`;
export const SEARCH_PRODUCT_SERIAL = `/admin/selected/search-product-serial`;
export const SELECTED_DIRECTORS = `/admin/selected/directors`;
export const SELECTED_EMPLOYEES = `/admin/selected/employees`;
export const SELECTED_SELLERS = `/admin/selected/sellers`;

export const SELECTED_CATEGORY_DIRECTORS = `/admin/selected/category-directors`;
export const SELECTED_INCOME_CATEGORY_DIRECTORS = `/admin/selected/withdrawal/category-directors`;
export const SELECTED_LEDGER_CONTACTS = `/admin/selected/ledger-contacts`;

export const ADVANCE_CREDIT_STORE = "/admin/landlord/advance-credit/store";

export const STOCK_TRANSFERS = "/admin/landlord/stock-transfers";
export const STOCK_TRANSFER_SEARCH_SERIAL = "/admin/selected/stock-transfer-search-serial";
export const DAMAGE_PRODUCTS = "/admin/landlord/damage-products";

export const CASH_TRANSFERS = "/admin/landlord/cash-transfers";

export const TELESALES = "/admin/landlord/telesales";
export const TELESALE_CHECK_NUMBER = "/admin/landlord/telesales/check-number";
export const TELESALE_RESULTS = "/admin/landlord/telesale-results";
export const SELECTED_TELESALE_RESULTS = "/admin/selected/telesale-results?only_active=1&per_page=100";
export const SELECTED_TELESALE_CUSTOMERS = "/admin/selected/telesale-customers";

export const ORDER_CONFIRMATIONS = "/admin/landlord/order-confirmations";
export const ORDER_CONFIRMATION_OPTIONS = "/admin/landlord/order-confirmation-options";
const SELECTED_ORDER_OPTIONS = "/admin/selected/order-confirmation-options";
export const SELECTED_ADVANCE_MEDIAS = `${SELECTED_ORDER_OPTIONS}?type=advance_media&only_active=1&per_page=100`;
export const SELECTED_DELIVERY_STATUSES = `${SELECTED_ORDER_OPTIONS}?type=delivery_status&only_active=1&per_page=100`;
export const SELECTED_DELIVERY_BYS = `${SELECTED_ORDER_OPTIONS}?type=delivery_by&only_active=1&per_page=100`;

export const REDX_ORDERS = "/admin/pos/redx-orders";
export const REDX_CREATE_ORDER = (saleId) => `/admin/pos/sales/${saleId}/redx-order`;
export const REDX_AREAS = "/admin/pos/redx/areas";
export const REDX_STORES = "/admin/pos/redx/stores";
export const STEADFAST_TRACK = (consignmentId) => `/admin/pos/steadfast/track/${consignmentId}`;
export const PATHAO_TRACK = (consignmentId) => `/admin/pos/pathao/track/${consignmentId}`;
export const REDX_TRACK = (trackingId) => `/admin/pos/redx/track/${trackingId}`;
export const BLOG_POSTS_PINNED = "/admin/ecommerce/blog-posts/pinned";
export const BLOG_POSTS_PINNED_ORDER = "/admin/ecommerce/blog-posts/pinned-order";
export const AI_ASSISTANT_MESSAGES = "/admin/ai-assistant/messages";
export const AI_ASSISTANT_CONVERSATION = (conversationId) => `/admin/ai-assistant/conversations/${conversationId}`;
export const AI_ASSISTANT_APPROVALS = (conversationId) => `/admin/ai-assistant/conversations/${conversationId}/approvals`;
