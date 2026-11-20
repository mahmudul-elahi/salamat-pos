import {defineStore} from "pinia";
import {ref} from "vue";
import Axios from "@/services/axios/index.js";
import {PUBLIC_SETTINGS} from "@/services/endpoints/app.js";

export const useSettingStore = defineStore('setting', () => {
    const settings = ref([])

    function fetchSettings() {
        return Axios.get(PUBLIC_SETTINGS).then(({data}) => {
            settings.value = data
        })
    }

    return {settings, fetchSettings}
})
