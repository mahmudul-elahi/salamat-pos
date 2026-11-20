<script setup>
import { computed } from "vue"
import Avatar from "@/views/global/avatar/Avatar.vue";

const props = defineProps({
    rowData: {}
})

const profilePicture = computed(() => {
    if (props.rowData?.product_image) {
        return props.rowData.product_image
    }
})

</script>

<template>
    <div class="d-flex align-items-center">
        <avatar :image="profilePicture" :name="rowData.product_name" active="success" />
        <div class="mx-2 d-flex flex-column align-items-start">
            <div class="fw-semibold">{{ rowData?.product_name }}</div>
            <div class="badge bg-success badge-square-light ms-0">{{ rowData.attribute_value }}</div>
        </div>
    </div>

</template>
