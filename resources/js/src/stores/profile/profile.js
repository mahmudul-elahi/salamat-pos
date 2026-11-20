import {defineStore} from "pinia";
import Axios from "@/services/axios/index.js";
import {MY_PROFILE} from "@/services/endpoints/app.js";
import {ref} from 'vue'
import {usePermissionStore} from "@/stores/permission/permission.js";

export const useProfileStore = defineStore('profile', () => {
    const profileData = ref({});
    const pageLoader = ref(false);

    async function fetchMyProfile() {
        pageLoader.value = true;
        try {
            const {data} = await Axios.get(MY_PROFILE);
            profileData.value = data?.result;

            const permissionStore = usePermissionStore();
            const permissionData = data?.result?.permissions;

            if (permissionData) {
                permissionStore.setPermissions(permissionData);
            } else {
                permissionStore.setPermissions({});
            }
        }  finally {
            pageLoader.value = false;
        }
    }

    return {
        profileData,
        pageLoader,
        fetchMyProfile
    };
})
