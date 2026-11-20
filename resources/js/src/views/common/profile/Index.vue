<script setup>
import { h, markRaw, ref } from "vue"
import { useI18n } from 'vue-i18n';
import MyProfile from "@/views/common/profile/MyProfile.vue";
import PasswordChange from "@/views/common/profile/PasswordChange.vue";
import AvatarChange from "@/views/common/profile/AvatarChange.vue";
import RegionSettings from "@/views/common/profile/RegionSettings.vue";
import { User, MediaImage, Lock, Globe } from "@iconoir/vue";

const { t } = useI18n();
const tabs = ref([
    // {
    //     title: t('personal_info'),
    //     key: 'personal_info',
    //     component: h(MyProfile),
    //     icon: markRaw(User)
    // },
    {
        title: t('avatar_change'),
        key: 'avatar_change',
        component: h(AvatarChange),
        icon: markRaw(MediaImage),
    },
    {
        title: t('password_change'),
        key: 'password_change',
        component: h(PasswordChange),
        icon: markRaw(Lock),
    },
    {
        title: 'Region',
        key: 'region',
        component: h(RegionSettings),
        icon: markRaw(Globe),
    },
])
</script>

<template>
    <div class="content-wrapper">
        <app-breadcrumb :page-title="$t('profile')" />

        <app-tab id="profile-tab" :tabs="tabs" aria-label="horizontal" type="horizontal" />

    </div>
</template>

<style scoped lang="scss"></style>
