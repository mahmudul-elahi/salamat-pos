<script setup>
import { Language } from "@iconoir/vue";
import {ref} from "vue";

const props = defineProps({
    language: Array,
});

const lang = localStorage.getItem("lang");
const locale = ref(lang ? lang : "en");

const changeLang = (lang) => {
    locale.value = lang;
    localStorage.setItem("lang", lang);
    window.location.reload(); // Reload window if needed
};


</script>

<template>
    <li class="nav-item nav-language dropdown py-0" @click.stop>
        <a class="nav-link dropdown-toggle d-flex justify-content-center align-items-center nav-toggle-icon" href="#"
           id="dropdownLanguage" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Change Language">
            <!-- Display the selected flag using the flag icon plugin -->
            <span class="rounded-flag-icon">
                <Language />
            </span>
        </a>
        <div class="dropdown-menu dropdown-menu-end navbar-dropdown custom-scrollbar" aria-labelledby="ChangeLanguage">
            <a class="dropdown-item d-flex align-items-center py-1" :class="{ active: item.key === locale }" v-for="item in language" :key="item.key" href="#"
               @click.prevent="changeLang(item.key)">
                <!-- Display the flag for each language option -->
                <span class="ms-3 d-inline-block">{{ item.title }}</span>
            </a>
        </div>
    </li>
</template>

<style scoped lang="scss"></style>
