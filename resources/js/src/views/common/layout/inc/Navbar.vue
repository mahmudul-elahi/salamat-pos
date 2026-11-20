<script setup>
import { ref, watch, onMounted, onBeforeMount, onBeforeUnmount } from "vue";
import NotificationDropdown from "@/views/common/layout/inc/navdropdown/NotificationDropdown.vue";
import LanguageDropdown from "@/views/common/layout/inc/navdropdown/LanguageDropdown.vue";
import ProfileDropdown from "@/views/common/layout/inc/navdropdown/ProfileDropdown.vue";
import PunchAttendance from "@/views/common/layout/inc/navdropdown/PunchAttendance.vue";
import ShopSwitcher from "@/views/common/layout/inc/navdropdown/ShopSwitcher.vue";
import { useNavbarStore } from '@/stores/layouts/navbar.js';
import { FastArrowLeft, FastArrowRight, HalfMoon, HomeSimple, SunLight } from "@iconoir/vue";
import usePermission from "@/composable/permission/usePermission.js";

const { hasMultipleShops } = usePermission();

const props = defineProps({
    profileData: {},
});

const navbarStore = useNavbarStore();

const languageList = [
    {
        title: "English",
        key: "en",
    }
];

// Default to 'open' sidebar
const sidebarStatus = ref("open");
const toggleSidebar = (status) => {
    if (status === "open") {
        sidebarStatus.value = "collapsed";
        document.querySelector("body").classList.add("sidebar-icon-only");
    } else {
        sidebarStatus.value = "open";
        document.querySelector("body").classList.remove("sidebar-icon-only");
    }

    navbarStore.setSidebarStatus(sidebarStatus.value);
};

const sidebarOffCanvas = (e) => {
    e.stopPropagation();
    document.querySelector(".sidebar-off-canvas").classList.toggle("active");

    navbarStore.setSidebarStatus('open');
};

const darkMode = ref(false);
const toggleDarkMode = () => {
    darkMode.value = !darkMode.value;
    navbarStore.$state.darkMode = Boolean(darkMode.value);
};

// Watcher for changes
watch([darkMode, sidebarStatus], ([NewDarkMode, NewSidebarStatus]) => {
    const htmlElement = document.documentElement;

    // Dark mode
    if (NewDarkMode) {
        localStorage.setItem("theme", "dark");
        htmlElement.setAttribute("theme", "dark");
    } else {
        localStorage.setItem("theme", "light");
        htmlElement.setAttribute("theme", "light");
    }

    // Sidebar status
    if (NewSidebarStatus === "open") {
        localStorage.setItem("sidebarStatus", "open");
        htmlElement.setAttribute("sidebarStatus", "open");
    } else {
        localStorage.setItem("sidebarStatus", "collapsed");
        htmlElement.setAttribute("sidebarStatus", "collapsed");
    }
});

const handleScroll = () => {
    const header = document.querySelector('.header-top');
    if (window.scrollY > 200) {
        header.classList.add('shadow-active');
    } else {
        header.classList.remove('shadow-active');
    }
};
onBeforeMount(() => {
    const htmlElement = document.documentElement;
    const direction = localStorage.getItem("direction");
    if (direction) {
        htmlElement.setAttribute("dir", direction);
        htmlElement.setAttribute("class", direction);
    }
});

onMounted(() => {
    window.addEventListener('scroll', handleScroll);
    const htmlElement = document.documentElement;
    const theme = localStorage.getItem("theme");
    const getSidebarStatus = localStorage.getItem("sidebarStatus");

    // Check theme
    if (theme === "dark") {
        htmlElement.setAttribute("theme", "dark");
        darkMode.value = true;
        navbarStore.$state.darkMode = Boolean(darkMode.value);
    } else {
        htmlElement.setAttribute("theme", "light");
        darkMode.value = false;
        navbarStore.$state.darkMode = Boolean(darkMode.value);
    }

    // Sidebar status: Default to 'open' if not set
    if (getSidebarStatus === "open" || !getSidebarStatus) {
        sidebarStatus.value = "open";
        htmlElement.setAttribute("sidebarStatus", "open");
        document.querySelector("body").classList.remove("sidebar-icon-only");
    } else {
        sidebarStatus.value = "collapsed";
        htmlElement.setAttribute("sidebarStatus", "collapsed");
        document.querySelector("body").classList.add("sidebar-icon-only");
    }

    navbarStore.setSidebarStatus(sidebarStatus.value);
});

onBeforeUnmount(() => {
    window.removeEventListener('scroll', handleScroll);
});
</script>

<template>
    <header class="header-top">
        <nav class="navbar-wrapper" id="navbar-top">
            <div class="navbar-menu-wrapper">
                <div class="navbar-menu-list">
                    <ul class="navbar-menu">
                        <li class="nav-item d-none d-lg-block sidebar-toggle-button">
                            <button id="sidebar-toggle-button" aria-label="Toggle Sidebar"
                                class="nav-link nav-toggle-side-btn"
                                :class="{ 'is-collapsed': sidebarStatus === 'collapsed' }"
                                @click.prevent="toggleSidebar(sidebarStatus === 'open' ? 'open' : 'collapsed')">
                                <component :is="sidebarStatus === 'open' ? FastArrowLeft : FastArrowRight" />
                            </button>

                        </li>
                        <li class="nav-item d-block d-lg-none">
                            <a href="#" class="nav-link" @click.prevent="sidebarOffCanvas" aria-label="Toggle Sidebar">
                                <FastArrowRight />
                            </a>
                        </li>
                    </ul>
                </div>
                <div class="navbar-right-items">
                    <ul class="navbar-right-menu">
                        <punch-attendance v-if="props.profileData?.is_employee" />
                        <shop-switcher v-if="hasMultipleShops()" />
                        <li class="nav-item">
                            <div class="divider"></div>
                        </li>
                        <li class="nav-item">
                            <a href="#" class="nav-link" @click.prevent="toggleDarkMode" aria-label="Dark Mode">
                                <div v-if="darkMode" class="nav-toggle-icon">
                                    <SunLight />
                                </div>
                                <div v-else class="nav-toggle-icon">
                                    <HalfMoon />
                                </div>
                            </a>
                        </li>
                        <li class="nav-item">
                            <div class="divider"></div>
                        </li>
                        <li class="nav-item">
                            <ul class="d-flex multi-list-wrapper">
                                <notification-dropdown />
                                <language-dropdown :language="languageList" />
                            </ul>
                        </li>
                        <li class="nav-item">
                            <div class="divider"></div>
                        </li>
                        <profile-dropdown :profile-data="profileData" />
                    </ul>
                </div>
            </div>
        </nav>
    </header>
</template>

<style scoped lang="scss"></style>
