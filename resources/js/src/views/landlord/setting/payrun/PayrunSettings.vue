<script setup>
import { h, ref, markRaw } from "vue";
import { useI18n } from "vue-i18n";
import usePermission from "@/composable/permission/usePermission.js";
import DefaultPayrun from "@/views/landlord/setting/payrun/DefaultPayrun.vue";
import ManageAudience from "@/views/landlord/setting/payrun/ManageAudience.vue";
import { Coins, ShieldCheck } from "@iconoir/vue";

const { canAccess } = usePermission();
const { t } = useI18n();

const tabs = ref([
    {
        icon: markRaw(Coins),
        title: t('default_payrun'),
        showHeader: true,
        key: 'default_payrun',
        component: h(DefaultPayrun),
        permission: canAccess('default_payrun_types'),
    },
    {
        icon: markRaw(ShieldCheck),
        title: t('manage_audience'),
        showHeader: true,
        key: 'manage_audience',
        component: h(ManageAudience),
        permission: canAccess('view_payrun_settings'),
    },
]);
</script>

<template>
    <div class="content-wrapper">
        <app-breadcrumb :page-title="$t('payrun_settings')" />
        <app-tab id="payrun-setting-tab" aria-label="vertical" :tabs="tabs" />
    </div>
</template>

<style scoped lang="scss"></style>
