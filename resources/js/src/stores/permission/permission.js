import { defineStore } from 'pinia'
import {computed, ref} from 'vue'

export const usePermissionStore = defineStore('permission', () => {
    const permissions = ref({})

    const setPermissions = (payload) => {
        permissions.value = payload || {}
    }

    const resetPermissions = () => {
        permissions.value = {}
    }

    const getPermissions = computed(() => permissions.value)

    return {
        permissions,
        setPermissions,
        resetPermissions,
        getPermissions
    }
})
