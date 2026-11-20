<script setup>
import { computed } from "vue"
import Avatar from "@/views/global/avatar/Avatar.vue";

const props = defineProps({
    rowData: {}
})

const profilePicture = computed(() => {
    if (props.rowData?.seller?.profile_picture) {
        return props.rowData.seller.profile_picture
    }
})

</script>

<template>
    <div class="d-flex align-items-center">
        <avatar :image="profilePicture" :name="rowData.seller?.name" active="success" />
        <div class="name mx-2 fw-semibold">
            {{ rowData.seller?.name ?? '-' }}
        </div>
    </div>

</template>
