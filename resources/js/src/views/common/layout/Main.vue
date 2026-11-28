<script setup>
import {ref} from 'vue';
import Navbar from "@/views/common/layout/inc/Navbar.vue";
import {useProfileStore} from "@/stores/profile/profile.js";
import {storeToRefs} from "pinia";
import {useSettingStore} from "@/stores/setting/setting.js";
import LandlordSidebar from "@/views/common/layout/inc/LandlordSidebar.vue";
import AiAssistantWidget from "@/views/common/layout/inc/AiAssistantWidget.vue";

// Reactive variable to track scroll position
const scrollTop = ref(0);

//
const store = useProfileStore();
const {profileData} = storeToRefs(store);
// setting

const settingStore = useSettingStore();
const {settings} = storeToRefs(settingStore);

// Scroll event handler
const handleScroll = (event) => {
    scrollTop.value = event.currentTarget.scrollTop;

    // Add or remove scroller style on the navbar based on scroll position
    const navbar = document.getElementById('navbar-top');
    if (scrollTop.value > 10) {
        navbar.classList.add('add-scroller-style');
    } else {
        navbar.classList.remove('add-scroller-style');
    }

    // Show or hide the back to top button based on scroll position
    const backToTop = document.getElementById('backToTop');
    if (scrollTop.value > 150) {
        backToTop.classList.add('view');
    }
};

// Function to remove the active class from the sidebar
const sidebarOffCanvas = () => {
    const sidebar = document.querySelector('.sidebar-off-canvas.active');
    if (sidebar) {
        sidebar.classList.remove('active');
    }
}
</script>

<template>
    <!-- Main dashboard layout container with a scroll event listener -->
    <div class="dashboard-layout position-relative"
         @scroll="handleScroll"
         id="scrollerContainer">

        <!-- Navbar component with profile data passed as a prop -->
        <navbar :profile-data="profileData"/>
        <!-- Landlord Sidebar-->
        <landlord-sidebar v-click-outside="sidebarOffCanvas" :setting-data="settings"/>

        <div class="sidebar-overlay"></div>
        <!-- Main content area where router views will be rendered -->
        <div class="main-panel">
            <router-view/>
        </div>

        <!-- AI assistant chat, available on every admin page -->
        <ai-assistant-widget/>
    </div>
</template>

<style scoped>
/* Add your component-specific styles here */
</style>
