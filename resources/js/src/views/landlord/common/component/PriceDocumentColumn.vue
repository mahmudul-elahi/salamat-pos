<script setup>
import { Attachment } from "@iconoir/vue";

defineProps({
    tableId: [String],
    rowData: {
        type: Object,
        required: true,
    },
});
</script>

<template>
    <a v-if="rowData.price_document_url" :href="rowData.price_document_url" target="_blank" rel="noopener noreferrer"
        :aria-label="$t('price_document')" :title="$t('price_document')">
        <div class="view-icon">
            <Attachment class="brand-color" />
        </div>
    </a>
    <div v-else>-</div>
</template>

<style scoped lang="scss"></style>
