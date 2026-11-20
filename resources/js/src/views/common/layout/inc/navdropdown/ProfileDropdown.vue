<script setup>
import {computed} from "vue";
import {Dropdown} from "bootstrap";
import {urlGenerator} from "@/utils/urlGenerator.js";
import Axios from "@/services/axios/index.js";
import {LOGOUT} from "@/services/endpoints/app.js";
import {useAuth} from "@/services/auth/index.js";
import {Group, LogOut} from "@iconoir/vue";
import {useSettingStore} from "@/stores/setting/setting.js";

const props = defineProps({
    profileData: {},
})

const profilePicture = computed(() => {
    const profileData = props.profileData;
    return profileData.profile_picture ?? urlGenerator('assets/images/placeholder/avatar.svg')
})
const settingStore = useSettingStore();
const {logout} = useAuth();
const handleLogout = () => {

    Axios.post(LOGOUT).then(async () => {
        closeDropdown();

        logout();
        await settingStore.fetchSettings();
    });
};
// Method to close the dropdown
const closeDropdown = () => {
    const dropdownElement = document.getElementById('dropdownMyProfileLink');
    const dropdown = Dropdown.getInstance(dropdownElement);
    if (dropdown) {
        dropdown.hide();
    }
};
</script>

<template>
    <li class="nav-item nav-profile dropdown" @click.stop>
        <a class="nav-link dropdown-toggle border-left py-2" href="#" role="button" id="dropdownMyProfileLink"
           data-bs-toggle="dropdown" aria-expanded="false">
            <div class="d-flex align-items-center flex-row-reverse gap-2">
                <div class="profile-skeleton-wrapper">
                    <div class="nav-profile-img">
                        <img :src="profilePicture" alt="profile-picture" class="rounded-circle"/>
                    </div>
                    <div class="nav-profile-text user-name">
                        <p class="mb-0 title">{{ profileData.name }}</p>
                        <p class="mb-0 mini-title">
                            <span>{{ profileData.role_name }}</span>
                        </p>
                    </div>
                </div>
            </div>

        </a>
        <div class="dropdown-menu dropdown-profile custom-scrollbar" aria-labelledby="dropdownMyProfileLink">
            <div class="dropdown-header">
                <h4 class="title">{{ $t('profile') }}</h4>
            </div>
            <div class="profile-body">
                <div class="dropdown-scroll">
                    <div class="user-info">
                        <div class="user-img">
                            <img :src="profilePicture" alt="profile-picture">
                        </div>
                        <div class="user-name">
                            <h4 class="name">{{ profileData.name }}</h4>
                            <p class="description"><span class="badge bg-secondary">{{ profileData.role_name }}</span>
                            </p>
                        </div>
                    </div>
                    <div class="profile-divider"></div>
                    <p class="text-span">{{ $t('manage') }}</p>
                    <div class="dropdown-item">
                        <router-link to="/profiles" @click="closeDropdown">
                    <span>
                        <Group/>
                        <span class="d-inline-block">{{ $t('my_profile') }}</span>
                    </span>
                        </router-link>
                    </div>
                    <div class="profile-divider"></div>
                    <div class="d-grid mb-3">
                        <button class="btn btn-primary gap-5" type="button" @click.prevent="handleLogout">
                            <LogOut/>
                            {{ $t('logout') }}
                        </button>
                    </div>

                </div>
            </div>

        </div>
    </li>
</template>


<style scoped lang="scss"></style>
