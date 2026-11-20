<script setup>
import { computed } from "vue"
import Avatar from "@/views/global/avatar/Avatar.vue";

const props = defineProps({
    rowData: {}
})

const profilePicture = computed(() => {
    if (props.rowData?.director?.profile_photo) {
        return props.rowData.director.profile_photo
    }
})

</script>

<template>
    <div class="d-flex align-items-center">
        <avatar :image="profilePicture" :name="rowData.name" active="success" />
        <div class="name mx-2 fw-semibold">
            {{ rowData.name }}
        </div>
    </div>

</template>
