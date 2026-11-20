<script setup>
import { h, ref, markRaw } from 'vue';
import { useI18n } from 'vue-i18n';
import usePermission from '@/composable/permission/usePermission.js';
import AttendanceDefinitions from '@/views/landlord/setting/attendance/AttendanceDefinitions.vue';
import AttendanceGeolocation from '@/views/landlord/setting/attendance/AttendanceGeolocation.vue';
import AttendanceIpRestriction from '@/views/landlord/setting/attendance/AttendanceIpRestriction.vue';
import { ControlSlider, MapPin, ShieldCheck } from '@iconoir/vue';

const { canAccess } = usePermission();
const { t } = useI18n();

const tabs = ref([
    {
        icon: markRaw(ControlSlider),
        title: 'Definitions',
        showHeader: true,
        key: 'definitions',
        component: h(AttendanceDefinitions),
        permission: canAccess('view_attendance_settings'),
    },
    {
        icon: markRaw(MapPin),
        title: 'Geolocation & IP',
        showHeader: true,
        key: 'geolocation',
        component: h(AttendanceGeolocation),
        permission: canAccess('view_attendance_settings'),
    },
    // {
    //     icon: markRaw(ShieldCheck),
    //     title: 'Ip Restriction',
    //     showHeader: true,
    //     key: 'ip_restriction',
    //     component: h(AttendanceIpRestriction),
    //     permission: canAccess('view_attendance_settings'),
    // },
]);
</script>

<template>
    <div class="content-wrapper">
        <app-breadcrumb page-title="Attendance Settings" />
        <app-tab id="attendance-setting-tab" aria-label="vertical" :tabs="tabs" />
    </div>
</template>

<style scoped lang="scss"></style>
