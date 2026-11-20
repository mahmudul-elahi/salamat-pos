<script setup>
import { h, ref, markRaw } from "vue";
import { useI18n } from "vue-i18n";
import PageList from "@/views/landlord/Ecommerce/SEO/Salamat/PageList.vue";
import ProductList from "@/views/landlord/Ecommerce/SEO/Salamat/ProductList.vue";
import CategoriesList from "@/views/landlord/Ecommerce/SEO/Salamat/CategoriesList.vue";
import BrandList from "@/views/landlord/Ecommerce/SEO/Salamat/BrandList.vue";
import { GoogleDocs, BoxIso, Suggestion, Shield } from "@iconoir/vue";
import usePermission from "@/composable/permission/usePermission.js";

const { canAccess } = usePermission();
const { t } = useI18n();
const tabs = ref([
    {
        icon: markRaw(GoogleDocs),
        title: t("pages"),
        showHeader: true,
        key: 'pages',
        component: h(PageList),
        permission: canAccess('view_pages')
    },
    {
        icon: markRaw(BoxIso),
        title: t("products"),
        showHeader: true,
        key: 'products',
        component: h(ProductList),
        permission: canAccess('view_products')
    },  
    {
        icon: markRaw(Suggestion),
        title: t("categories"),
        showHeader: true,
        key: 'categories',
        component: h(CategoriesList),
        permission: canAccess('view_categories')
    },
    {
        icon: markRaw(Shield),
        title: t("brands"),
        showHeader: true,
        key: 'brands',
        component: h(BrandList),
        permission: canAccess('view_brands')
    }
])
</script>

<template>
    <div class="content-wrapper">
        <app-breadcrumb :page-title="$t('salamat_pages')" />

        <app-tab id="page-tab" aria-label="vertical" :tabs="tabs" />

    </div>

</template>

<style scoped lang="scss"></style>
