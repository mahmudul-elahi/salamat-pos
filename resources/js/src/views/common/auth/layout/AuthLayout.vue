<script setup>
import {ref, onMounted} from "vue"
import {storeToRefs} from "pinia";
import {useSettingStore} from "@/stores/setting/setting.js";
const settingStore = useSettingStore();
const {settings} = storeToRefs(settingStore);
const darkMode = ref(false)
onMounted(() => {
    let htmlElement = document.documentElement,
        theme = localStorage.getItem('theme')
    if (theme === 'dark') {
        htmlElement.setAttribute('theme', 'dark');
        darkMode.value = true;
    } else {
        htmlElement.setAttribute('theme', 'light');
        darkMode.value = false;
    }
})

</script>

<template>
    <div class="auth-page-wrapper flex">
        <div class="left-content" :style="'background-image: url('+ settings.company_banner +')'"></div>
        <div class="right-content">
            <div class="auth-form my-5">
                <div class="brand">
                    <img :src="settings.company_logo"
                         class="img-fluid" alt="App logo">
                </div>
                <slot name="auth-content"></slot>
            </div>
        </div>
    </div>
</template>

<style scoped lang="scss">
</style>
