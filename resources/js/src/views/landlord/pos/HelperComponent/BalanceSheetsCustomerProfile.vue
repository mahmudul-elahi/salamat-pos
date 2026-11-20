<script setup>
import { computed } from "vue"
import Avatar from "@/views/global/avatar/Avatar.vue";

const props = defineProps({
    rowData: {}
})

const profilePicture = computed(() => {
    if (props.rowData?.profile_picture) {
        return props.rowData.profile_picture
    }
})

</script>

<template>
    <div class="d-flex align-items-center">
        <avatar :image="profilePicture" :name="rowData?.name" active="success" />
        <div class="name mx-2 fw-semibold">
            <router-link :to="{ name: 'ledgerDetails', params: { id: rowData.id } }" class="text-success">
                {{ rowData?.name }}
            </router-link>
        </div>
    </div>

</template>