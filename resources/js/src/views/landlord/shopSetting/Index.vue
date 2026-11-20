<script setup>
import {h, ref, markRaw} from "vue";
import {useI18n} from "vue-i18n";
import GeneralSetting from "@/views/landlord/shopSetting/general/GeneralSetting.vue";
import usePermission from "@/composable/permission/usePermission.js";
import { Wrench} from "@iconoir/vue";

const {canAccess} = usePermission();
const {t} = useI18n();
const props = defineProps({
    id: {}
})
const tabs = ref([
    {
        icon: markRaw(Wrench),
        title: t("general_setting"),
        showHeader: true,
        key: 'general_setting',
        component: h(GeneralSetting,{ shopId: props.id }),
        permission: true
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
