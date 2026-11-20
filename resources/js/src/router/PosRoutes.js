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
                component: () => import("@/views/landlord/dashboard/Dashboard.vue"),
            },

              {
                path: "/purchase-stocks",
                name: "purchaseStocks",
                component: () => import("@/views/landlord/pos/stock/PurchasesStock.vue"),
            },

              {
                path: "/product-stock-calculation",
                name: "productStockCalculation",
                component: () => import("@/views/landlord/pos/stock/ProductStockCalculation.vue"),
            },
        ],
        },
    ]