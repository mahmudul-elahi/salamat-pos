<script setup>
import { computed } from "vue"
import Avatar from "@/views/global/avatar/Avatar.vue";
import usePermission from "@/composable/permission/usePermission.js";

const props = defineProps({
    rowData: {}
})

const { canAccess } = usePermission();

const profilePicture = computed(() => {
    if (props.rowData?.profile_picture) {
        return props.rowData.profile_picture
    }
})

</script>

<template>
    <div class="d-flex align-items-center">
        <avatar :image="profilePicture" :name="rowData.name" active="success" />
        <div class="name mx-2 fw-semibold" v-if="canAccess('view_customer_ledger_reports')">
            <router-link :to="{ name: 'ledgerDetails', params: { id: rowData.id } }" class="text-success">
                {{ rowData?.name }}
            </router-link>
        </div>
        <div class="name mx-2 fw-semibold" v-else>
            {{ rowData?.name }}
        </div>
    </div>

</template>
