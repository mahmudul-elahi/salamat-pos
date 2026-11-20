<script setup>
import { provide } from 'vue';
import PolicyTabs from "./PolicyTabs.vue";

// Provide Tenant ID for Tekzo (3)
provide('tenantId', 4);
</script>

<template>
    <PolicyTabs />
</template>
