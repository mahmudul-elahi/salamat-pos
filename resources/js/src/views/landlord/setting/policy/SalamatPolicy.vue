<script setup>
import { provide } from 'vue';
import PolicyTabs from "./PolicyTabs.vue";

// Provide Tenant ID for Salamat (4)
provide('tenantId', 3);
</script>

<template>
    <PolicyTabs />
</template>
