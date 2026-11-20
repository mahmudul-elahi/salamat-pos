import './bootstrap';
import {createApp, h} from "vue";
import {createPinia} from 'pinia'
import router from "@/router/index.js";
import i18n from "@/i18n";
import mitt from "mitt";

const emitter = mitt();

// Validation Errors
import ValidationErrors from "@/utils/validationErrors.js";

// Directives
import clickOutside from '@/directives/clickOutside';
// Plugins
import progressbar from "@/plugins/progressbar/index";

//Toaster
import Toast from "vue-toastification";
import "vue-toastification/dist/index.css";
import { IconoirProvider } from '@iconoir/vue';
const toastOptions = {
    position: "top-center",
};

// Progressbar configuration
const progressbarOptions = {
    color: "#6DCC4C",
    gradient: "linear-gradient(to left, #6DCC4C, #6DCC4C)",
    failedColor: "#ed213a",
    failedGradient: "linear-gradient(to left, #ed213a, #93291e)",
    thickness: "2px",
    transition: {
        speed: "0.2s",
        opacity: "0.6s",
        termination: 300,
    },
    autoRevert: true,
    location: "top",
    inverse: false,
};

import App from "@/App.vue";

// import global components
import {defineGlobalComponents} from "@/views/global/global-components.js";
//create Pinia once
const pinia = createPinia();

const app = createApp({
    render: () => h(IconoirProvider, null, { default: () => h(App) }),
});
defineGlobalComponents(app);



app.config.globalProperties.emitter = emitter;
app.config.globalProperties.$errors = ValidationErrors;
app.directive("click-outside", clickOutside)
    .use(progressbar, progressbarOptions)
    .use(i18n)
    .use(Toast, toastOptions)
    .use(pinia)
    .use(router)
    .mount("#app");

