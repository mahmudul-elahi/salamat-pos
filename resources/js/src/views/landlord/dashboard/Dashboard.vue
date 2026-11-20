<script setup>
import Statistic from "./Statistic.vue";
import ShortStatistic from "./ShortStatistic.vue";
import PurchaseSaleOverview from "./PurchaseSaleOverview.vue";
import PaymentReceiveSentOverview from "./PaymentReceiveSentOverview.vue";
import PurchaseSaleReturnOverview from "./PurchaseSaleReturnOverview.vue";
import TopProductOverview from "./TopProductOverview.vue";
import TopCustomerOverview from "./TopCustomerOverview.vue";
import usePermission from "@/composable/permission/usePermission.js";

const { canAccess } = usePermission();

</script>

<template>
    <div class="content-wrapper">
        <app-breadcrumb :page-title="$t('welcome_to_dashboard')" />
        <Statistic v-if="canAccess('view_dashboard_statistics')" />
        <!-- Chart section -->
        <div class="row g-4" v-if="canAccess('view_dashboard_purchases_and_sales_graph') ||
            canAccess('view_dashboard_top_sell_products_graph') ||
            canAccess('view_dashboard_top_sell_customer_graph') ||
            canAccess('view_dashboard_purchase_sale_and_return_overviews') ||
            canAccess('view_dashboard_sent_and_received_graph')">
            <div class="col-xl-8" v-if="canAccess('view_dashboard_purchases_and_sales_graph')">
                <purchase-sale-overview />
            </div>
            <div class="col-xl-4" v-if="canAccess('view_dashboard_top_sell_products_graph')">
                <top-product-overview />
            </div>
            <div class="col-xl-8" v-if="canAccess('view_dashboard_purchase_sale_and_return_overviews')">
                <purchase-sale-return-overview />
            </div>
            <div class="col-xl-4 short-cards-wrapper" v-if="canAccess('view_dashboard_statistics')">
                <short-statistic />
            </div>    
            <div class="col-xl-8" v-if="canAccess('view_dashboard_sent_and_received_graph')">
                <payment-receive-sent-overview />
            </div>
            <div class="col-xl-4" v-if="canAccess('view_dashboard_top_sell_customer_graph')">
                <top-customer-overview />
            </div>

        </div>
    </div>
</template>

<style scoped lang="scss"></style>
