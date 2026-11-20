<script setup>
import { computed } from "vue"
import Avatar from "@/views/global/avatar/Avatar.vue";

const props = defineProps({
    tableId: {},
    rowData: {}
})

const profilePicture = computed(() => {
    if (props.rowData.profile_picture) {
        return props.rowData.profile_picture
    }
})

</script>

<template>
    <div class="d-flex align-items-center">
        <avatar :image="profilePicture" :name="rowData?.customer?.name" active="success" />
        <div class="d-flex flex-column gap-1">
            <div class="name mx-2">
                <router-link :to="{ name: 'orderDetails', params: { id: rowData.id } }" class="brand-color">
                    {{ rowData?.customer?.name }}
                </router-link>
            </div>
            <div class="mx-2 text-muted">
                {{ rowData?.customer?.phone }}
            </div>
            <div class="mx-2 text-muted">
                {{ rowData?.created_at }}
            </div>
        </div>

    </div>

</template>