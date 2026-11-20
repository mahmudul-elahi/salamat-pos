import {createRouter, createWebHistory} from "vue-router";

// Route groups
import appRoutes from "@/router/appRoutes.js";
import appLandlord from "@/router/landlordRoutes.js";
import appEcommerce from "@/router/eCommerceRoutes.js";
import hrmRoutes from "@/router/hrmRoutes.js";

// Stores
import {useAppStore} from "@/stores/app/index.js";
import {useProfileStore} from "@/stores/profile/profile.js";
import {useSettingStore} from "@/stores/setting/setting.js";
import {storeToRefs} from "pinia";

// Utilities and composable
import {getToken} from "@/utils/token.js";
import usePermission from "@/composable/permission/usePermission.js";
import PosRoutes from "./PosRoutes";

// Combine all routes
const routes = [...appRoutes, ...appLandlord, ...appEcommerce, ...PosRoutes, ...hrmRoutes];

// Create router instance
const router = createRouter({
    history: createWebHistory(),
    routes,
    linkActiveClass: "active",
});

// Global navigation guard
router.beforeEach(async (to, from, next) => {
    const token = getToken();

    const appStore = useAppStore();
    const {isLoaded} = storeToRefs(appStore);
     const settingStore = useSettingStore();
    const profileStore = useProfileStore();

    if (!isLoaded.value) {
         await settingStore.fetchSettings();

        if (token) {
            const permissions = await profileStore.fetchMyProfile();
        }

        appStore.setIsLoaded(true);
    }

    const requiredPermissions = to.meta?.permissions ?? [];
    if (requiredPermissions.length > 0) {
        const {hasAnyPermission} = usePermission();
        const hasPermission = hasAnyPermission(requiredPermissions);

        if (!hasPermission) {
            return next({name: token ? "accessForbidden" : "login"});
        }
    }

    return next();
});

export default router;
