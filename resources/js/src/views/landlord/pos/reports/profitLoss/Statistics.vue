<script setup lang="ts">
import { onBeforeMount, ref, computed } from "vue";
import { PROFIT_LOSS_STATISTICS } from "@/services/endpoints/landlord.js";
import {
    ArrowEmailForward,
    Cart,
    CartPlus,
    Dollar,
    Filter,
    ArrowDownLeft,
    ArrowUpRight,
} from "@iconoir/vue";
import CardSkeleton from "@/views/common/skeleton/CardSkeleton.vue";
import Axios from "@/services/axios/index.js";
import { numberWithCurrencySymbol } from "@/utils/helper";
import DropdownMenuFilter from "@/views/common/dropdown-menu/DropdownMenuFilter.vue";

const preLoader = ref(false);
const statistics = ref(<any>{});
const selectedFilter = ref(6);
const getStatistics = async (filterType = 6) => {
    preLoader.value = true;
    selectedFilter.value = filterType;
    await Axios.get(`${PROFIT_LOSS_STATISTICS}/${filterType}`)
        .then(({ data }) => {
            statistics.value = data;


        })
        .finally(() => (preLoader.value = false));
};

onBeforeMount(() => {
    getStatistics();
});

// Define cards with both amount + count fields
const cards = computed(() => [
    {
        label: "sales",
        amountField: "total_sale_amount",
        countField: "total_sale_count",
        icon: Cart,
        formula: "",
    },
    {
        label: "purchases",
        amountField: "total_purchases_amount",
        countField: "total_purchases_count",
        icon: CartPlus,
        formula: "",
    },
    {
        label: "sale_return",
        amountField: "total_sale_return_amount",
        countField: "total_sale_return_count",
        icon: ArrowEmailForward,
        formula: "",
    },
    {
        label: "purchase_return",
        amountField: "total_purchases_return_amount",
        countField: "total_purchases_return_count",
        icon: ArrowEmailForward,
        formula: "",
    },
    {
        label: "revenue",
        amountField: "revenue",
        countField: null,
        icon: Dollar,
        formula: "Revenue = Sales + Purchase Returns",
    },
    {
        label: "payment_received",
        amountField: "payment_received",
        countField: null,
        icon: ArrowDownLeft,
        formula: "Sales + Purchase Returns",
    },
    {
        label: "payment_sent",
        amountField: "payment_sent",
        countField: null,
        icon: ArrowUpRight,
        formula: "Purchases + Sale Returns + Expenses",
    },
    {
        label: "payment_net",
        amountField: "payment_net",
        countField: null,
        icon: Dollar,
        formula: "Payment Received - Payment Sent",
    },
    {
        label: "profit_loss",
        amountField: "profit_loss.net_profit_loss",
        countField: null,
        icon: Dollar,
        formula: "(Sales + Purchase Returns) - (Purchases + Sale Returns + Expenses)",
    },
]);

</script>

<template>
    <div class="content-wrapper">
        <app-breadcrumb :page-title="$t('profit_loss_report')">
            <template #breadcrumb-actions>
                <div class="dropdown">
                    <button
                        class="btn btn-white search-and-filter-btn"
                        type="button"
                        data-bs-toggle="dropdown"
                        aria-expanded="false">
                        <Filter />
                        {{ $t("filter") }}
                    </button>
                    <dropdown-menu-filter
                        :selected="selectedFilter"
                        @applyFilter="getStatistics($event)" />
                </div>
            </template>
        </app-breadcrumb>
        <card-skeleton v-if="preLoader" />

        <div class="row g-4 mb-4" v-else>
            <div class="col-xl-3 col-md-6" v-for="(item, index) in cards" :key="index">
                <div class="card card-icon-bg card-icon-bg-primary o-hidden mb-30 text-center h-100">
                    <div class="card-body d-flex gap-2 align-items-center">
                        <component :is="item.icon" />
                        <div class="content text-start">
                            <p class="para">
                                {{ $t(item.label) }}
                                <small v-if="item.countField" class="text-muted">
                                    ({{ statistics[item.countField] ?? 0 }})
                                </small>

                                <!-- Profit/Loss Badge -->
                                <span
                                    v-if="item.label === 'profit_loss'"
                                    class="badge-square-light"
                                    :class="statistics.profit_loss?.status === 'profit' ? 'bg-success' : 'bg-danger'"
                                    style="margin-left: 5px;">
                                    {{ statistics.profit_loss?.status ?? '' }}
                                </span>
                            </p>

                            <p class="highlight-para mb-0">
                                {{numberWithCurrencySymbol(
                                    item.amountField.includes('.')
                                        ? item.amountField.split('.').reduce((o, k) => o?.[k], statistics) ?? 0
                                        : statistics[item.amountField] ?? 0
                                ) }}
                            </p>
                            <small class="text-muted d-block">{{ item.formula }}</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped lang="scss"></style>
