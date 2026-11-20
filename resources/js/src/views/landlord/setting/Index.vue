<script setup>
import {h, ref, markRaw} from "vue";
import {useI18n} from "vue-i18n";
import GeneralSetting from "@/views/landlord/setting/general/GeneralSetting.vue";
import PaymentMethod from "@/views/landlord/setting/paymentMethod/PaymentMethod.vue";
import Shop from "@/views/landlord/setting/shop/Shop.vue";
import Ecommerce from "@/views/landlord/setting/ecommerce/Ecommerce.vue";
import {Wrench, CreditCard} from "@iconoir/vue";
import usePermission from "@/composable/permission/usePermission.js";

const {canAccess} = usePermission();
const {t} = useI18n();
const tabs = ref([
    {
        icon: markRaw(Wrench),
        title: t("general_setting"),
        showHeader: true,
        key: 'general_setting',
        component: h(GeneralSetting),
        permission: canAccess('view_settings')
    },
    {
        icon: markRaw(CreditCard),
        title: t("payment_method"),
        showHeader: true,
        key: 'payment_method',
        component: h(PaymentMethod),
        permission: canAccess('view_payment_methods'),
        headerButton: {
            label: t('add_payment_method'),
            class: 'btn btn-primary',
            permission: canAccess('create_payment_methods')
        }
    },
    {
        icon: markRaw(CreditCard),
        title: t("shop"),
        showHeader: true,
        key: 'shop',
        component: h(Shop),
        permission: canAccess('view_shops'),
        headerButton: {
            label: t('add_shop'),
            class: 'btn btn-primary',
            permission: canAccess('create_shops')
        }
    },
    {
        icon: markRaw(CreditCard),
        title: t("ecommerce"),
        showHeader: true,
        key: 'ecommerce',
        component: h(Ecommerce),
        permission: canAccess('view_ecommerces'),
        headerButton: {
            label: t('add_ecommerce'),
            class: 'btn btn-primary',
            permission: canAccess('create_ecommerces')
        }
    },
])
</script>

<template>
    <div class="content-wrapper">
        <app-breadcrumb :page-title="$t('settings')"/>

        <app-tab id="setting-tab" aria-label="vertical" :tabs="tabs"/>

    </div>

</template>

<style scoped lang="scss">

</style>
