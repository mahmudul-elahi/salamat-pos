<script setup>
import { computed } from "vue"
import Avatar from "@/views/global/avatar/Avatar.vue";

const props = defineProps({
    rowData: {}
})

const profilePicture = computed(() => {
    if (props.rowData?.tenant?.setting?.value) {
        return props.rowData?.tenant.setting?.value
    }
})

</script>

<template>
    <div class="d-flex align-items-center">
        <avatar :image="profilePicture" :name="props.rowData?.tenant?.name" active="success" />
        <div class="name mx-2 fw-semibold">
            {{ props.rowData?.tenant?.name }}
        </div>
    </div>

</template>
