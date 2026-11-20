<script setup>
import { computed } from "vue"
import Avatar from "@/views/global/avatar/Avatar.vue";

const props = defineProps({
    rowData: {}
})

const profilePicture = computed(() => {
    if (props.rowData?.user?.profile_photo) {
        return props.rowData.user.profile_photo
    }
})

</script>

<template>
    <div class="d-flex align-items-center">
        <avatar :image="profilePicture" :name="rowData.name" active="success" />
        <div class="mx-2">
            <div class="name fw-semibold">
                {{ rowData.user?.name }}
            </div>
            <div class="name text-muted small">
                {{ rowData.invoice_number }}
            </div>
        </div>
    </div>

</template>
