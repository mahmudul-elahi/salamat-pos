<script setup>
import { markRaw, ref } from "vue";
import { useI18n } from "vue-i18n";
import usePermission from "@/composable/permission/usePermission.js";

// Policy Components
import WarrantyPolicy from "@/views/landlord/setting/policy/WarrantyPolicy.vue";
import PrivacyPolicy from "@/views/landlord/setting/policy/PrivacyPolicy.vue";
import TermsConditionPolicy from "@/views/landlord/setting/policy/TermsConditionPolicy.vue";
import RefundPolicy from "@/views/landlord/setting/policy/RefundPolicy.vue";
import ReturnPolicy from "@/views/landlord/setting/policy/ReturnPolicy.vue";
import ExchangePolicy from "@/views/landlord/setting/policy/ExchangePolicy.vue";
import DeliveryPolicy from "@/views/landlord/setting/policy/DeliveryPolicy.vue";
import EmiPaymentPolicy from "@/views/landlord/setting/policy/EmiPaymentPolicy.vue";
import PreOrderPolicy from "@/views/landlord/setting/policy/PreOrderPolicy.vue";
import TekzoCarePolicy from "@/views/landlord/setting/policy/TekzoCarePolicy.vue";

import {
    Wrench,
    ShieldCheck,
    Lock,
    Refresh,
    Package,
    CoinsSwap,
    Truck,
    Page
} from "@iconoir/vue";

const { canAccess } = usePermission();
const { t } = useI18n();

const tabs = ref([
    {
        icon: markRaw(Wrench),
        title: t("warranty_policy"),
        showHeader: true,
        key: 'warranty_policy',
        component: WarrantyPolicy,
        permission: canAccess('view_policy_settings')
    },
    {
        icon: markRaw(Lock),
        title: t("privacy_policy"),
        showHeader: true,
        key: 'privacy_policy',
        component: PrivacyPolicy,
        permission: canAccess('view_policy_settings')
    },
    {
        icon: markRaw(Page),
        title: t("terms_condition"),
        showHeader: true,
        key: 'terms_condition',
        component: TermsConditionPolicy,
        permission: canAccess('view_policy_settings')
    },
    {
        icon: markRaw(Refresh),
        title: t("refund_policy"),
        showHeader: true,
        key: 'refund_policy',
        component: RefundPolicy,
        permission: canAccess('view_policy_settings')
    },
    {
        icon: markRaw(Refresh),
        title: t("return_policy"),
        showHeader: true,
        key: 'return_policy',
        component: ReturnPolicy,
        permission: canAccess('view_policy_settings')
    },
    {
        icon: markRaw(CoinsSwap),
        title: t("exchange_policy"),
        showHeader: true,
        key: 'exchange_policy',
        component: ExchangePolicy,
        permission: canAccess('view_policy_settings')
    },
    {
        icon: markRaw(Truck),
        title: t("delivery_policy"),
        showHeader: true,
        key: 'delivery_policy',
        component: DeliveryPolicy,
        permission: canAccess('view_policy_settings')
    },
    {
        icon: markRaw(ShieldCheck),
        title: t("emi_payment_policy"),
        showHeader: true,
        key: 'emi_payment_policy',
        component: EmiPaymentPolicy,
        permission: canAccess('view_policy_settings')
    },
    {
        icon: markRaw(Package),
        title: t("preorder_policy"),
        showHeader: true,
        key: 'preorder_policy',
        component: PreOrderPolicy,
        permission: canAccess('view_policy_settings')
    },
    {
        icon: markRaw(Package),
        title: t("care_policy"),
        showHeader: true,
        key: 'care_policy',
        component: TekzoCarePolicy,
        permission: canAccess('view_policy_settings')
    },
]);
</script>

<template>
    <div class="content-wrapper">
        <app-breadcrumb :page-title="$t('policy_settings')" />
        <app-tab id="setting-tab" aria-label="vertical" :tabs="tabs" />
    </div>
</template>
