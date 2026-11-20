<script setup>
import { computed } from "vue";
import Avatar from "@/views/global/avatar/Avatar.vue";

const props = defineProps({
  rowData: Object
});

const profilePicture = computed(() => {
  return props.rowData?.contact?.profile_picture || null;
});

</script>

<template>
  <div class="d-flex align-items-center p-2">
    <avatar :image="profilePicture" :name="rowData?.contact?.name" size="45" active="success" />

    <div class="ms-3">
      <div class="fw-semibold mb-1">
        {{ rowData?.contact?.name }} <br>
        {{ rowData?.contact?.business_name }}
      </div>
    </div>
  </div>
</template>

<style scoped></style>
