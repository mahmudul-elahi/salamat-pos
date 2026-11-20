import {alreadyLogin} from "@/router/gurd.js";

export default [
    {
        path: "/",
        component: () => import("@/views/common/layout/Main.vue"),
        beforeEnter: [alreadyLogin],
        children: [
            {
                path: "/dashboard",
                name: "dashboard",
                component: () =>
                    import("@/views/landlord/dashboard/Dashboard.vue"),
            },
            {
                path: "/users",
                name: "users",
                component: () =>
                    import("@/views/landlord/administrator/user/User.vue"),
                meta: {
                    permissions: ["view_users"],
                },
            },
            {
                path: "/roles",
                name: "roles",
                component: () =>
                    import("@/views/landlord/administrator/role/Role.vue"),
                meta: {
                    permissions: ["view_roles"],
                },
            },
            {
                path: "/create-role",
                name: "createRole",
                component: () =>
                    import("@/views/landlord/administrator/role/CreateRole.vue"),
                meta: {
                    permissions: ["create_roles"],
                },
            },
            {
                path: "/edit-role/:id",
                name: "editRole",
                props: true,
                component: () =>
                    import("@/views/landlord/administrator/role/EditRole.vue"),
                meta: {
                    permissions: ["update_roles"],
                },
            },
            {
                path: "/categories",
                name: "categories",
                component: () =>
                    import(
                        "@/views/landlord/pos/product/category/Category.vue"
                        ),
                meta: {
                    permissions: ["view_categories"],
                },
            },
            {
                path: "/categories/order",
                name: "categoryOrder",
                component: () =>
                    import(
                        "@/views/landlord/pos/product/category/CategoryView.vue"
                        ),
                meta: {
                    permissions: ["view_categories", "update_categories"],
                },
            },
            {
                path: "attributes",
                name: "attributes",
                component: () =>
                    import(
                        "@/views/landlord/pos/product/attributes/Attribute.vue"
                        ),
                meta: {
                    permissions: ["view_attributes"],
                },
            },
            {
                path: "/products",
                name: "products",
                component: () =>
                    import("@/views/landlord/pos/product/Product.vue"),
                meta: {
                    permissions: ["view_products"],
                },
            },
            {
                path: "/product-barcode-generate",
                name: "productBarcode",
                component: () =>
                    import(
                        "@/views/landlord/pos/product/barcodes/BarcodePrint.vue"
                        ),
                meta: {
                    permissions: ["product_barcode_generate"],
                },
            },
            {
                path: "/product-barcode-history",
                name: "productBarcodeHistory",
                component: () =>
                    import(
                        "@/views/landlord/pos/product/barcodes/BarcodeHistory.vue"
                        ),
                meta: {
                    permissions: ["view_product_serial_history"],
                },
            },
            {
                path: "/product-barcodes",
                name: "productBarcodes",
                component: () =>
                    import(
                        "@/views/landlord/pos/product/barcodes/GeneratedBarcode.vue"
                        ),
                meta: {
                    permissions: ["view_product_barcodes"],
                },
            },
            {
                path: "/barcode-generates",
                name: "barcodeGenerates",
                component: () =>
                    import(
                        "@/views/landlord/pos/product/barcodes/BarcodeGenerator.vue"
                        ),
                meta: {
                    permissions: ["view_product_barcodes"],
                },
            },
            {
                path: "/print-barcode",
                name: "printBarcode",
                component: () =>
                    import(
                        "@/views/landlord/pos/product/barcodes/PrintBarcode.vue"
                        ),
                meta: {
                    permissions: ["product_print_barcodes"],
                },
            },
            {
                path: "/create-product",
                name: "createProduct",
                component: () =>
                    import("@/views/landlord/pos/product/CreateProduct.vue"),
                meta: {
                    permissions: ["create_products"],
                },
            },
            {
                path: "/edit-product/:id",
                name: "editProduct",
                props: true,
                component: () =>
                    import("@/views/landlord/pos/product/EditProduct.vue"),
                meta: {
                    permissions: ["update_products"],
                },
            },
            {
                path: "/brands",
                name: "brands",
                component: () =>
                    import("@/views/landlord/pos/product/brand/Brand.vue"),
                meta: {
                    permissions: ["view_brands"],
                },
            },
            {
                path: "/brands/order",
                name: "brandOrder",
                component: () =>
                    import("@/views/landlord/pos/product/brand/BrandOrder.vue"),
                meta: {
                    permissions: ["update_brands"],
                },
            },
            {
                path: "/news",
                name: "news",
                component: () =>
                    import("@/views/landlord/pos/news/News.vue"),
                meta: {
                    permissions: ["view_news"],
                },
            },
            {
                path: "/blogs",
                name: "blogs",
                component: () =>
                    import("@/views/landlord/pos/news/Blog.vue"),
                meta: {
                    permissions: ["view_blogs"],
                },
            },
            {
                path: "/specification-groups",
                name: "specificationGroups",
                component: () =>
                    import(
                        "@/views/landlord/pos/product/specificationGroup/SpecificationGroup.vue"
                        ),
                meta: {
                    permissions: ["view_specification_groups"],
                },
            },
            {
                path: "purchases",
                name: "purchases",
                component: () =>
                    import("@/views/landlord/pos/purchases/Purchase.vue"),
                meta: {
                    permissions: ["view_purchases"],
                },
            },
            {
                path: "edit-purchase/:id",
                name: "editPurchase",
                props: true,
                component: () =>
                    import("@/views/landlord/pos/purchases/EditPurchase.vue"),
                meta: {
                    permissions: ["update_purchases"],
                },
            },
            {
                path: "purchase-details/:id",
                name: "purchaseDetails",
                props: true,
                component: () =>
                    import(
                        "@/views/landlord/pos/purchases/PurchaseDetails.vue"
                        ),
                meta: {
                    permissions: ["download_purchase_details"],
                },
            },
            {
                path: "create-purchase",
                name: "createPurchase",
                component: () =>
                    import("@/views/landlord/pos/purchases/CreatePurchase.vue"),
                meta: {
                    permissions: ["create_purchases"],
                },
            },
            {
                path: "purchase-return",
                name: "purchaseReturn",
                component: () =>
                    import("@/views/landlord/pos/purchases/PurchaseReturn.vue"),
                meta: {
                    permissions: ["view_purchase_returns"],
                },
            },
            {
                path: "edit-purchase-return/:id",
                name: "editPurchaseReturn",
                props: true,
                component: () =>
                    import(
                        "@/views/landlord/pos/purchases/EditPurchaseReturn.vue"
                        ),
                meta: {
                    permissions: ["update_purchase_returns"],
                },
            },
            {
                path: "/purchase-return-details/:id",
                name: "purchaseReturnDetails",
                props: true,
                component: () =>
                    import(
                        "@/views/landlord/pos/purchases/PurchaseReturnDetails.vue"
                        ),
                meta: {
                    permissions: ["download_purchase_return_details"],
                },
            },
            {
                path: "create-purchase-return",
                name: "createPurchaseReturn",
                component: () =>
                    import(
                        "@/views/landlord/pos/purchases/CreatePurchaseReturn.vue"
                        ),
                meta: {
                    permissions: ["create_purchase_returns"],
                },
            },
            {
                path: "/sales",
                name: "sales",
                component: () => import("@/views/landlord/pos/sales/Sale.vue"),
                meta: {
                    permissions: ["view_sales"],
                },
            },
            {
                path: "/courier-orders",
                name: "courierOrders",
                component: () =>
                    import("@/views/landlord/pos/courier/CourierOrders.vue"),
                meta: {
                    permissions: ["view_courier_orders"],
                },
            },
            {
                path: "/pathao-orders",
                name: "pathaoOrders",
                component: () =>
                    import("@/views/landlord/pos/courier/PathaoOrders.vue"),
                meta: {
                    permissions: ["view_pathao_orders"],
                },
            },
            {
                path: "/steadfast-orders",
                name: "steadfastOrders",
                component: () =>
                    import("@/views/landlord/pos/courier/SteadfastOrders.vue"),
                meta: {
                    permissions: ["view_steadfast_orders"],
                },
            },
            {
                path: "/chat",
                name: "chatInbox",
                component: () =>
                    import("@/views/landlord/pos/chat/ChatInbox.vue"),
                meta: {
                    permissions: ["view_chat_conversations"],
                },
            },
            {
                path: "/redx-orders",
                name: "redxOrders",
                component: () =>
                    import("@/views/landlord/pos/courier/RedxOrders.vue"),
                meta: {
                    permissions: ["view_redx_orders"],
                },
            },
            {
                path: "edit-sale/:id",
                name: "editSale",
                props: true,
                component: () =>
                    import("@/views/landlord/pos/sales/EditSale.vue"),
                meta: {
                    permissions: ["update_sales"],
                },
            },
            {
                path: "/sale-details/:id",
                name: "saleDetails",
                props: true,
                component: () =>
                    import("@/views/landlord/pos/sales/SaleDetails.vue"),
                meta: {
                    permissions: ["download_sale_details"],
                },
            },
            {
                path: "create-sale",
                name: "createSale",
                component: () =>
                    import("@/views/landlord/pos/sales/CreateSale.vue"),
                meta: {
                    permissions: ["create_sales"],
                },
            },
            {
                path: "/create-sale-return",
                name: "createSaleReturn",
                component: () =>
                    import("@/views/landlord/pos/sales/CreateSaleReturn.vue"),
                meta: {
                    permissions: ["create_sale_returns"],
                },
            },
            {
                path: "edit-sale-return/:id",
                name: "editSaleReturn",
                props: true,
                component: () =>
                    import("@/views/landlord/pos/sales/EditSaleReturn.vue"),
                meta: {
                    permissions: ["update_sale_returns"],
                },
            },
            {
                path: "sales-return",
                name: "salesReturn",
                component: () =>
                    import("@/views/landlord/pos/sales/SalesReturn.vue"),
                meta: {
                    permissions: ["view_sale_returns"],
                },
            },
            {
                path: "/sale-return-details/:id",
                name: "saleReturnDetails",
                props: true,
                component: () =>
                    import("@/views/landlord/pos/sales/SaleReturnDetails.vue"),
                meta: {
                    permissions: ["download_sale_return_details"],
                },
            },
            
            {
                path: "/suppliers",
                name: "suppliers",
                component: () =>
                    import("@/views/landlord/pos/people/suppliers/Supplier.vue"),
                meta: {
                    permissions: ["view_suppliers"],
                },
            },
            {
                path: "/online-customers",
                name: "online-customers",
                component: () =>
                    import("@/views/landlord/pos/people/contact/OnlineCustomer.vue"),
                meta: {
                    permissions: ["view_online_contacts"],
                },
            },
            {
                path: "/customers",
                name: "customers",
                component: () =>
                    import("@/views/landlord/pos/people/customers/Customer.vue"),
                meta: {
                    permissions: ["view_customers"],
                },
            },
            {
                path: "/people",
                name: "people",
                component: () =>
                    import("@/views/landlord/pos/people/People.vue"),
                meta: {
                    permissions: ["view_customers", "view_suppliers", "view_online_contacts"],
                },
            },
            {
                path: "/telesales",
                name: "telesales",
                component: () =>
                    import("@/views/landlord/pos/telesale/Telesale.vue"),
                meta: {
                    permissions: ["view_telesales"],
                },
            },
            {
                path: "/telesale-results",
                name: "telesale-results",
                component: () =>
                    import("@/views/landlord/pos/telesale/TelesaleResult.vue"),
                meta: {
                    permissions: ["view_telesale_results"],
                },
            },
            {
                path: "/order-confirmations",
                name: "order-confirmations",
                component: () =>
                    import("@/views/landlord/pos/orderConfirmation/OrderConfirmation.vue"),
                meta: {
                    permissions: ["view_order_confirmations"],
                },
            },
            {
                path: "/order-confirmation-options",
                name: "order-confirmation-options",
                component: () =>
                    import("@/views/landlord/pos/orderConfirmation/OrderConfirmationOption.vue"),
                meta: {
                    permissions: ["view_order_confirmation_options"],
                },
            },
            {
                path: "/payments",
                name: "payments",
                component: () =>
                    import("@/views/landlord/pos/people/payment/Payment.vue"),
                meta: {
                    permissions: ["view_payments"],
                },
            },
              {
                path: "/payment-history",
                name: "paymentHistory",
                component: () =>
                    import("@/views/landlord/pos/people/payment/PaymentHistory.vue"),
                meta: {
                    permissions: ["view_payment_history"],
                },
            },
            {
                path: "/debit-voucher/:id",
                props: true,
                name: "debitVoucher",
                component: () =>
                    import("@/views/landlord/pos/people/payment/DebitVoucher.vue"),
                meta: {
                    permissions: ["view_payments"],
                },
            },
            {
                path: "/credit-voucher/:id",
                props: true,
                name: "creditVoucher",
                component: () =>
                    import("@/views/landlord/pos/people/payment/CreditVoucher.vue"),
                meta: {
                    permissions: ["view_payments"],
                },
            },
            {
                path: "/ledgers",
                name: "ledgers",
                component: () =>
                    import("@/views/landlord/pos/ledgers/Ledger.vue"),
                meta: {
                    permissions: ["view_ledgers"],
                },
            },
            {
                path: "/dealers",
                name: "dealers",
                component: () =>
                    import("@/views/landlord/pos/partners/dealers/Dealer.vue"),
                meta: {
                    permissions: ["view_dealers"],
                },
            },
            {
                path: "/retailers",
                name: "retailers",
                component: () =>
                    import(
                        "@/views/landlord/pos/partners/retailers/Retailer.vue"
                        ),
                meta: {
                    permissions: ["view_retailers"],
                },
            },
            {
                path: "/b2b",
                name: "b2b",
                component: () =>
                    import("@/views/landlord/pos/partners/b2b/B2b.vue"),
                meta: {
                    permissions: ["view_b2b"],
                },
            },
            {
                path: "/franchises",
                name: "franchises",
                component: () =>
                    import(
                        "@/views/landlord/pos/partners/franchises/Franchise.vue"
                        ),
                meta: {
                    permissions: ["view_franchises"],
                },
            },
            {
                path: "/partners",
                name: "partners",
                component: () =>
                    import(
                        "@/views/landlord/pos/partners/Partner.vue"
                        ),
                meta: {
                    permissions: ["view_partners"],
                },
            },
            {
                path: "expenses",
                name: "expenses",
                component: () =>
                    import("@/views/landlord/pos/expenses/Expense.vue"),
                meta: {
                    permissions: ["view_expenses"],
                },
            },
            {
                path: "create-expense",
                name: "createExpense",
                component: () =>
                    import("@/views/landlord/pos/expenses/CreateExpense.vue"),
                meta: {
                    permissions: ["create_expenses"],
                },
            },
            {
                path: "edit-expenses/:id",
                name: "editExpenses",
                props: true,
                component: () =>
                    import("@/views/landlord/pos/expenses/EditExpense.vue"),
                meta: {
                    permissions: ["update_expenses"],
                },
            },
            {
                path: "expense-voucher/:id",
                name: "expenseVoucher",
                props: true,
                component: () =>
                    import("@/views/landlord/pos/expenses/ExpenseVoucher.vue"),
                meta: {
                    permissions: ["view_expense_voucher"],
                },
            },
            {
                path: "incomes",
                name: "incomes",
                component: () =>
                    import("@/views/landlord/pos/income/Income.vue"),
                meta: {
                    permissions: ["view_incomes"],
                },
            },
            {
                path: "create-income",
                name: "createIncome",
                component: () =>
                    import("@/views/landlord/pos/income/CreateIncome.vue"),
                meta: {
                    permissions: ["create_incomes"],
                },
            },
            {
                path: "edit-income/:id",
                name: "editIncome",
                props: true,
                component: () =>
                    import("@/views/landlord/pos/income/EditIncome.vue"),
                meta: {
                    permissions: ["update_incomes"],
                },
            },
            {
                path: "/expense-categories",
                name: "expenseCategories",
                component: () =>
                    import(
                        "@/views/landlord/pos/expenses/categories/ExpenseCategory.vue"
                        ),
                meta: {
                    permissions: ["view_expense_categories"],
                },
            },
            {
                path: "/create-expense-categories",
                name: "createExpenseCategories",
                component: () =>
                    import(
                        "@/views/landlord/pos/expenses/categories/CreateExpenseCategory.vue"
                        ),
                meta: {
                    permissions: ["create_expense_categories"],
                },
            },
            {
                path: "/edit-expense-category/:id",
                name: "editExpenseCategory",
                props: true,
                component: () =>
                    import(
                        "@/views/landlord/pos/expenses/categories/EditExpenseCategory.vue"
                        ),
                meta: {
                    permissions: ["create_expense_categories"],
                },
            },
            {
                path: "/income-categories",
                name: "incomeCategories",
                component: () =>
                    import(
                        "@/views/landlord/pos/income/category/IncomeCategory.vue"
                        ),
                meta: {
                    permissions: ["view_income_categories"],
                },
            },
            {
                path: "/create-income-categories",
                name: "createIncomeCategory",
                component: () =>
                    import(
                        "@/views/landlord/pos/income/category/CreateIncomeCategory.vue"
                        ),
                meta: {
                    permissions: ["create_income_categories"],
                },
            },
            {
                path: "/edit-income-category/:id",
                name: "editIncomeCategory",
                props: true,
                component: () =>
                    import(
                        "@/views/landlord/pos/income/category/EditIncomeCategory.vue"
                        ),
                meta: {
                    permissions: ["update_income_categories"],
                },
            },
            {
                path: "warehouses",
                name: "warehouses",
                component: () =>
                    import("@/views/landlord/warehouses/Warehouse.vue"),
                meta: {
                    hasPermission: () => true,
                },
            },
            {
                path: "/adjustments",
                name: "adjustments",
                component: () =>
                    import("@/views/landlord/adjustments/Adjustment.vue"),
                meta: {
                    hasPermission: () => true,
                },
            },
            {
                path: "/stock-reports",
                name: "stock-reports",
                component: () =>
                    import("@/views/landlord/pos/reports/StockReport.vue"),
                meta: {
                    permissions: ["view_stock_reports"],
                },
            },
            {
                path: "/expense-reports",
                name: "expense-reports",
                component: () =>
                    import("@/views/landlord/pos/reports/ExpenseReport.vue"),
                meta: {
                    permissions: ["view_expense_reports"],
                },
            },
            {
                path: "/profit-loss-reports",
                name: "profit-loss-reports",
                component: () => import("@/views/landlord/pos/reports/ProfitLossReport.vue"),
                meta: {
                    permissions: ["view_profit_loss_reports"],
                },
            },
            {
                path: "/sale-reports",
                name: "sale-reports",
                component: () =>
                    import("@/views/landlord/pos/reports/SaleReport.vue"),
                meta: {
                    permissions: ["view_sale_reports"],
                },
            },
            {
                path: "/sale-return-reports",
                name: "sale-return-reports",
                component: () =>
                    import("@/views/landlord/pos/reports/SaleReturnReport.vue"),
                meta: {
                    permissions: ["view_sale_return_reports"],
                },
            },
            {
                path: "/purchase-reports",
                name: "purchase-reports",
                component: () =>
                    import("@/views/landlord/pos/reports/PurchaseReport.vue"),
                meta: {
                    permissions: ["view_purchase_reports"],
                },
            },
            {
                path: "/stock-transfer-reports",
                name: "stock-transfer-reports",
                component: () =>
                    import("@/views/landlord/pos/reports/StockTransferReport.vue"),
                meta: {
                    permissions: ["view_stock_transfer_reports"],
                },
            },
            {
                path: "/damage-product-reports",
                name: "damage-product-reports",
                component: () =>
                    import("@/views/landlord/pos/reports/DamageProductReport.vue"),
                meta: {
                    permissions: ["view_damage_product_reports"],
                },
            },
            {
                path: "/cash-transfer-reports",
                name: "cash-transfer-reports",
                component: () =>
                    import("@/views/landlord/pos/reports/CashTransferReport.vue"),
                meta: {
                    permissions: ["view_cash_transfer_reports"],
                },
            },
            {
                path: "/purchase-return-reports",
                name: "purchase-return-reports",
                component: () =>
                    import(
                        "@/views/landlord/pos/reports/PurchaseReturnReport.vue"
                        ),
                meta: {
                    permissions: ["view_purchase_return_reports"],
                },
            },
            {
                path: "/transaction-reports",
                name: "transaction-reports",
                component: () =>
                    import("@/views/landlord/pos/statement/Transaction.vue"),
                meta: {
                    permissions: ["view_transaction_reports"],
                },
            },
            {
                path: "/deposit-report",
                name: "depositReport",
                component: () =>
                    import(
                        "@/views/landlord/pos/reports/DepositReport.vue"
                        ),
                meta: {
                    permissions: ["view_deposit_reports"],
                },
            },
            {
                path: "/withdrawal-report",
                name: "withdrawalReport",
                component: () =>
                    import(
                        "@/views/landlord/pos/reports/WithdrawalReport.vue"
                        ),
                meta: {
                    permissions: ["view_withdrawal_reports"],
                },
            },
            {
                path: "/brand-sale-reports",
                name: "brandSaleReports",
                component: () =>
                    import(
                        "@/views/landlord/pos/reports/brandSale/BrandSaleReport.vue"
                        ),
                meta: {
                    permissions: ["view_brand_sale_reports"],
                },
            },
            {
                path: "/category-sale-reports",
                name: "categorySaleReports",
                component: () =>
                    import(
                        "@/views/landlord/pos/reports/categorySale/CategorySaleReport.vue"
                        ),
                meta: {
                    permissions: ["view_category_sale_reports"],
                },
            },
            {
                path: "/sale-by-product-reports",
                name: "saleByProductReports",
                component: () =>
                    import(
                        "@/views/landlord/pos/reports/saleByProduct/SaleByProductReport.vue"
                        ),
                meta: {
                    permissions: ["view_sale_by_product_reports"],
                },
            },
            {
                path: "/profit-loss-statistic",
                name: "profitLossStatistic",
                component: () =>
                    import(
                        "@/views/landlord/pos/reports/profitLoss/Statistics.vue"
                        ),
                meta: {
                    permissions: ["view_profit_loss_statistics"],
                },
            },
            {
                path: "/supplier-reports",
                name: "supplier-reports",
                component: () =>
                    import("@/views/landlord/pos/reports/SupplierReport.vue"),
                meta: {
                    permissions: ["view_supplier_reports"],
                },
            },
            {
                path: "/supplier-report-details/:id",
                name: "supplierReportDetails",
                props: true,
                component: () =>
                    import(
                        "@/views/landlord/pos/reports/SupplierReportDetail.vue"
                        ),
                meta: {
                    permissions: [
                        "view_supplier_purchase_reports",
                        "view_supplier_purchase_return_reports",
                        "view_supplier_purchase_transaction_reports",
                        "view_supplier_purchase_return_transaction_reports",
                        "view_supplier_ledger_reports",
                    ],
                },
            },
            {
                path: "/contact-history/:id",
                name: "contactHistory",
                props: true,
                component: () =>
                    import(
                        "@/views/landlord/pos/people/contact/ContactHistory.vue"
                        ),
                meta: {
                    permissions: [
                        "view_sales",
                        "view_sale_returns",
                        "view_purchases",
                        "view_purchase_returns",
                        "view_orders",
                        "view_telesales",
                        "view_order_confirmations",
                    ],
                },
            },
            {
                path: "/ledger-details/:id",
                name: "ledgerDetails",
                props: true,
                component: () => import("@/views/landlord/pos/reports/customer/LedgerDetail.vue"),
                meta: {
                    permissions: ["view_customer_ledger_reports"],
                },
               
            },

            // {
            //     path: "/customer-report-details/:id/",
            //     name: "customerReportDetail",
            //     props: true,
            //     component: () => import("@/views/landlord/pos/reports/customer/CustomerReport.vue"),
            //     meta: {
            //         permissions: ["view_customer_ledger_reports"],
            //     },
            // },
            {
                path: "create-adjustment",
                name: "createAdjustment",
                component: () =>
                    import("@/views/landlord/adjustments/CreateAdjustment.vue"),
                meta: {
                    hasPermission: () => true,
                },
            },
            {
                path: "/sales-transactions",
                name: "salesTransactions",
                component: () =>
                    import(
                        "@/views/landlord/pos/transaction/SaleTransaction.vue"
                        ),
                meta: {
                    permissions: ["view_sale_transactions"],
                },
            },
            {
                path: "/sales-return-transactions",
                name: "salesReturnTransactions",
                component: () =>
                    import(
                        "@/views/landlord/pos/transaction/SaleReturnTransaction.vue"
                        ),
                meta: {
                    permissions: ["view_sale_return_transactions"],
                },
            },
            {
                path: "purchase-transactions",
                name: "purchaseTransactions",
                component: () =>
                    import(
                        "@/views/landlord/pos/transaction/PurchaseTransaction.vue"
                        ),
                meta: {
                    permissions: ["view_purchase_transactions"],
                },
            },
            {
                path: "/purchase-return-transactions",
                name: "purchaseReturnTransactions",
                component: () =>
                    import(
                        "@/views/landlord/pos/transaction/PurchaseReturnTransaction.vue"
                        ),
                meta: {
                    permissions: ["view_purchase_return_transactions"],
                },
            },
            {
                path: "/expenses-transactions",
                name: "expensesTransactions",
                component: () =>
                    import(
                        "@/views/landlord/pos/transaction/ExpenseTransaction.vue"
                        ),
                meta: {
                    permissions: ["view_expense_transactions"],
                },
            },
            {
                path: "/stock-transfers",
                name: "stockTransfers",
                component: () =>
                    import("@/views/landlord/pos/stockTransfer/StockTransfer.vue"),
                meta: {
                   permissions: ["view_stock_transfers"],
                },
            },
            {
                path: "/stock-transfers/create",
                name: "createStockTransfer",
                component: () =>
                    import("@/views/landlord/pos/stockTransfer/CreateStockTransfer.vue"),
                meta: {
                    permissions: ["create_stock_transfers"],
                },
            },
            {
                path: "/stock-transfers/:id",
                name: "viewStockTransfer",
                props: true,
                component: () =>
                    import("@/views/landlord/pos/stockTransfer/ViewStockTransfer.vue"),
                meta: {
                    permissions: ["view_stock_transfers"],
                },
            },
            {
                path: "/damage-products",
                name: "damageProducts",
                component: () =>
                    import("@/views/landlord/pos/damageProduct/DamageProduct.vue"),
                meta: {
                   permissions: ["view_damage_products"],
                },
            },
            {
                path: "/damage-products/create",
                name: "createDamageProduct",
                component: () =>
                    import("@/views/landlord/pos/damageProduct/CreateDamageProduct.vue"),
                meta: {
                    permissions: ["create_damage_products"],
                },
            },
            {
                path: "/damage-products/:id",
                name: "viewDamageProduct",
                props: true,
                component: () =>
                    import("@/views/landlord/pos/damageProduct/ViewDamageProduct.vue"),
                meta: {
                    permissions: ["view_damage_products"],
                },
            },
            {
                path: "/cash-transfers",
                name: "cashTransfers",
                component: () =>
                    import("@/views/landlord/pos/cashTransfer/CashTransfer.vue"),
                meta: {
                    permissions: ["view_cash_transfers"],
                },
            },
            {
                path: "/cash-transfers/create",
                name: "createCashTransfer",
                component: () =>
                    import("@/views/landlord/pos/cashTransfer/CreateCashTransfer.vue"),
                meta: {
                    permissions: ["create_cash_transfers"],
                },
            },
            {
                path: "/cash-transfers/:id",
                name: "viewCashTransfer",
                props: true,
                component: () =>
                    import("@/views/landlord/pos/cashTransfer/ViewCashTransfer.vue"),
                meta: {
                    permissions: ["view_cash_transfers"],
                },
            },
            {
                path: "/stock-statement",
                name: "stockStatement",
                component: () =>
                    import("@/views/landlord/pos/statement/StockStatement.vue"),
                meta: {
                    permissions: ["view_stock_statements"],
                },
            },
            {
                path: "/sale-statement",
                name: "saleStatement",
                component: () =>
                    import("@/views/landlord/pos/statement/SaleStatement.vue"),
                meta: {
                    permissions: ["view_sale_statements"],
                },
            },
            {
                path: "/purchase-statement",
                name: "purchaseStatement",
                component: () =>
                    import(
                        "@/views/landlord/pos/statement/PurchaseStatement.vue"
                        ),
                meta: {
                    permissions: ["view_purchase_statements"],
                },
            },
            {
                path: "/purchase-return-statement",
                name: "purchaseReturnStatement",
                component: () =>
                    import(
                        "@/views/landlord/pos/statement/PurchaseReturnStatement.vue"
                        ),
                meta: {
                    permissions: ["view_purchase_return_statements"],
                },
            },
            {
                path: "/sale-return-statement",
                name: "saleReturnStatement",
                component: () =>
                    import(
                        "@/views/landlord/pos/statement/SaleReturnStatement.vue"
                        ),
                meta: {
                    permissions: ["view_sale_return_statements"],
                },
            },
            {
                path: "/expense-statement",
                name: "expensesStatement",
                component: () =>
                    import(
                        "@/views/landlord/pos/statement/ExpenseStatement.vue"
                        ),
                meta: {
                    permissions: ["view_expense_statements"],
                },
            },
            {
                path: "/withdrawal-statement",
                name: "withdrawalStatement",
                component: () =>
                    import(
                        "@/views/landlord/pos/statement/WithdrawalStatement.vue"
                        ),
                meta: {
                    permissions: ["view_withdrawal_statements"],
                },
            },
            {
                path: "/deposit-statement",
                name: "depositStatement",
                component: () =>
                    import(
                        "@/views/landlord/pos/statement/DepositStatement.vue"
                        ),
                meta: {
                    permissions: ["view_deposit_statements"],
                },
            },
            {
                path: "/daily-closing",
                name: "dailyClosing",
                component: () =>
                    import(
                        "@/views/landlord/pos/statement/DailyClosing.vue"
                        ),
                meta: {
                    permissions: ["view_daily_closings"],
                },
            },

            {
                path: "/daily-closing-balance",
                name: "dailyClosingBalance",
                component: () =>
                    import(
                        "@/views/landlord/pos/statement/DailyClosingBalance.vue"
                        ),
                meta: {
                    permissions: ["view_daily_closing_balance"],
                },
            },

            {
                path: "/daily-closing-profit-loss",
                name: "dailyClosingProfitLoss",
                component: () =>
                    import(
                        "@/views/landlord/pos/statement/DailyClosingProfitLoss.vue"
                        ),
                meta: {
                    permissions: ["view_daily_closing_profit_loss"],
                },
            },

        
              {
                path: "/balance-sheets",
                name: "balanceSheets",
                component: () =>
                    import(
                        "@/views/landlord/pos/statement/BalanceSheet.vue"
                        ),
                meta: {
                    permissions: ["view_balance_sheets"],
                },
            },

         
            {
                path: "/settings",
                redirect: "/settings/general",
            },
            {
                path: "/settings/general",
                name: "settings.general",
                component: () => import("@/views/landlord/setting/general/GeneralSetting.vue"),
                meta: { permissions: ["view_settings"] },
            },
            {
                path: "/settings/payment-method",
                name: "settings.paymentMethod",
                component: () => import("@/views/landlord/setting/paymentMethod/PaymentMethod.vue"),
                meta: { permissions: ["view_payment_methods"] },
            },
            {
                path: "/settings/shop",
                name: "settings.shop",
                component: () => import("@/views/landlord/setting/shop/Shop.vue"),
                meta: { permissions: ["view_shops"] },
            },
            {
                path: "/settings/ecommerce",
                name: "settings.ecommerce",
                component: () => import("@/views/landlord/setting/ecommerce/Ecommerce.vue"),
                meta: { permissions: ["view_ecommerces"] },
            },
            {
                path: '/payrun-settings',
                name: 'payrun-settings',
                component: () => import('@/views/landlord/setting/payrun/PayrunSettings.vue'),
                meta: {
                    permissions: ['view_payrun_settings', 'default_payrun_types'],
                },
            },
            {
                path: '/leave-settings',
                name: 'leave-settings',
                component: () => import('@/views/landlord/setting/leave/LeaveSettings.vue'),
                meta: {
                    permissions: ['view_leave_settings'],
                },
            },
            {
                path: '/attendance-settings',
                name: 'attendance-settings',
                component: () => import('@/views/landlord/setting/attendance/AttendanceSettings.vue'),
                meta: {
                    permissions: ['view_attendance_settings'],
                },
            },
            {
                path: '/delivery-charge-settings',
                name: 'delivery-charge-settings',
                component: () => import('@/views/landlord/setting/deliveryCharge/DeliveryChargeSettings.vue'),
                meta: {
                    permissions: ['view_delivery_charge_settings'],
                },
            },
            {
                path: '/area-shipping-charges',
                name: 'area-shipping-charges',
                component: () => import('@/views/landlord/setting/deliveryCharge/AreaShippingCharges.vue'),
                meta: {
                    permissions: ['view_area_shipping_charges'],
                },
            },
            {
                path: 'policy-settings/salamat',
                name: 'salamat-policy-settings',
                component: () => import('@/views/landlord/setting/policy/SalamatPolicy.vue'),
                meta: {
                    title: 'salamat_policy_settings',
                    breadcrumb: 'salamat_policy_settings',
                    permission: ['view_policy_settings']
                }
            },
            {
                path: 'policy-settings/tekzo',
                name: 'tekzo-policy-settings',
                component: () => import('@/views/landlord/setting/policy/TekzoPolicy.vue'),
                meta: {
                    title: 'tekzo_policy_settings',
                    breadcrumb: 'tekzo_policy_settings',
                    permission: ['view_policy_settings']
                }
            },
            {
                path: "/shops/details/:id",
                name: "shopDetails",
                props: true,
                component: () =>
                    import("@/views/landlord/shopSetting/Index.vue"),
                meta: {
                    permissions: true,
                },
            },
            {
                path: "all-notifications",
                name: "AllNotification",
                component: () =>
                    import("@//views/common/notification/AllNotification.vue"),
            },
            {
                path: "/templates",
                name: "templates",
                component: () =>
                    import("@/views/landlord/template/Template.vue"),
                meta: {
                    permissions: ["landlord_view_email_template_types"],
                },
            },
            {
                path: "/investment-categories",
                name: "investmentCategories",
                component: () =>
                    import("@/views/landlord/pos/investment/category/Category.vue"),
                meta: {
                    permissions: ["view_investment_categories"],
                },
            },
            {
                path: "/create-investment-categories",
                name: "investmentCreateCategories",
                component: () => import("@/views/landlord/pos/investment/category/Create.vue"),
                meta: {
                    permissions: ["create_investment_categories"],
                },
            },
            {
                path: "/edit-investment-category/:id",
                props: true,
                name: "investmentUpdateCategories",
                component: () => import("@/views/landlord/pos/investment/category/Update.vue"),
                meta: {
                    permissions: ["update_investment_categories"],
                },
            },
            {
                path: "/investments",
                name: "investments",
                component: () => import("@/views/landlord/pos/investment/Investment.vue"),
                meta: {
                    permissions: ["view_investments"],
                },
            },
            {
                path: "/create-investments",
                name: "createInvestments",
                component: () => import("@/views/landlord/pos/investment/Create.vue"),
                meta: {
                    permissions: ["create_investments"],
                },
            },
            {
                path: "/edit-investments/:id",
                props: true,
                name: "updateInvestments",
                component: () => import("@/views/landlord/pos/investment/Update.vue"),
                meta: {
                    permissions: ["update_investments"],
                },
            },
            {
                path: "/directors",
                name: "directors",
                component: () => import("@/views/landlord/pos/director/Director.vue"),
                meta: {
                    permissions: ["create_investments"],
                },
            },
            {
                path: "/create-directors",
                name: "createDirectors",
                component: () => import("@/views/landlord/pos/director/Create.vue"),
                meta: {
                    permissions: ["create_investments"],
                },
            },
            {
                path: "/director-profile/:id",
                props: true,
                name: "directorProfile",
                component: () => import("@/views/landlord/pos/director/DirectorProfile.vue"),
                meta: {
                    permissions: ["create_investments"],
                },
            },
            {
                path: "/edit-directors/:id",
                props: true,
                name: "updateDirectors",
                component: () => import("@/views/landlord/pos/director/EditDirector.vue"),
                meta: {
                    permissions: ["update_investments"],
                },
            },
            {
                path: "/cares",
                name: "cares",
                component: () => import("@/views/landlord/Ecommerce/care/Care.vue"),
                meta: {
                    permissions: true,
                },
            },
            {
                path: "/create-care",
                name: "createCare",
                component: () => import("@/views/landlord/Ecommerce/care/CreateCare.vue"),
                meta: {
                    permissions: true,
                },
            },
            {
                path: "/edit-care/:id",
                props: true,
                name: "editCare",
                component: () => import("@/views/landlord/Ecommerce/care/EditCare.vue"),
                meta: {
                    permissions: true,
                },
            },
        ],
    },
];
