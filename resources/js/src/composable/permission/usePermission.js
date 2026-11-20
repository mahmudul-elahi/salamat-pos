import {computed} from 'vue'
import {usePermissionStore} from '@/stores/permission/permission.js'

export default function usePermission() {
    const permissionStore = usePermissionStore()
    const getPermissions = computed(() => permissionStore.getPermissions)

    const canAccess = (permission) => {
        if (getPermissions.value?.is_app_admin) return true
        return !!getPermissions.value?.[permission];
    }

    const hasAnyPermission = (permissions = []) => {
        if (getPermissions.value?.is_app_admin) return true;
        return permissions.some(permission => !!getPermissions.value?.[permission]);
    };


    const isSupperAdmin = () => {
        return !!getPermissions.value?.is_app_admin
    }

    const isEmployee = () => {
        return !!getPermissions.value?.is_employee
    }

    const hasMultipleShops = () => {
        return (getPermissions.value?.tenant_ids ?? []).length > 1
    }

    const canFilterByShop = () => {
        return isSupperAdmin()
    }

    const storePermissions = (permissionList) => {
        permissionStore.setPermissions(permissionList)
    }

    const removePermissions = () => {
        permissionStore.resetPermissions()
    }

    return {
        storePermissions,
        removePermissions,
        canAccess,
        hasAnyPermission,
        isSupperAdmin,
        isEmployee,
        hasMultipleShops,
        canFilterByShop,
    }
}
