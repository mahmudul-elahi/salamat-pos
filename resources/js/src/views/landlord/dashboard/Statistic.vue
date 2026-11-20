<script setup lang="ts">
import {onBeforeMount, ref} from "vue";
import {DASHBOARD_STATISTICS} from "@/services/endpoints/landlord.js";
import { ArrowEmailForward, Cart, CartPlus } from "@iconoir/vue";
import CardSkeleton from "@/views/common/skeleton/CardSkeleton.vue";
import Axios from "@/services/axios/index.js";
import { numberWithCurrencySymbol } from "@/utils/helper";
const preLoader = ref(false);
const statistics = ref();
const getStatistics = () => {
    preLoader.value = true;
    Axios.get(DASHBOARD_STATISTICS)
        .then(({data}) => {
            statistics.value = data;
        }).finally(() => preLoader.value = false)
};

onBeforeMount(() => {
    getStatistics();

})
</script>

<template>
    <card-skeleton v-if="preLoader"/>
    <div class="row g-4 mb-4" v-else>
        <div class="col-xl-3 col-md-6">
            <div class="card card-icon-bg card-icon-bg-primary o-hidden mb-30 text-center">
                <div class="card-body d-flex gap-2">
                    <Cart />
                    <div class="content">
                        <p class="para">{{ $t('sales') }}</p>
                        <p class="highlight-para">
                            {{ getStatistics ? numberWithCurrencySymbol(statistics.total_sale_amount) : '-' }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-icon-bg card-icon-bg-primary o-hidden mb-30 text-center">
                <div class="card-body d-flex gap-2">
                    <CartPlus />
                    <div class="content">
                        <p class="para">{{ $t('purchases') }}</p>
                        <p class="highlight-para">
                            {{ getStatistics ? numberWithCurrencySymbol(statistics.total_purchases_amount) : '-' }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-icon-bg card-icon-bg-primary o-hidden mb-30 text-center">
                <div class="card-body d-flex gap-2">
                    <ArrowEmailForward />
                    <div class="content">
                        <p class="para">{{ $t('sale_return') }}</p>
                        <p class="highlight-para">
                            {{ getStatistics ? numberWithCurrencySymbol(statistics.total_sale_return_amount) : '-' }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-icon-bg card-icon-bg-primary o-hidden mb-30 text-center">
                <div class="card-body d-flex gap-2">
                    <span class="rotate">
                        <ArrowEmailForward />
                    </span>
                    <div class="content">
                        <p class="para">{{ $t('purchase_return') }}</p>
                        <p class="highlight-para">
                            {{ getStatistics ? numberWithCurrencySymbol(statistics.total_purchases_return_amount) : '-' }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped lang="scss">

</style>
