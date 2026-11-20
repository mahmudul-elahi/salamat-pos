<script setup>
import { Eye } from "@iconoir/vue";
import useEmitter from "@/composable/emitter/useEmitter";

const props = defineProps({
    tableId: [String],
    rowData: {
        type: Object,
        required: true,
    },
});

const emitter = useEmitter();

// The column only has room for a summary, so the eye opens the same detail
// modal the action menu does. Component columns can't reach the page's
// handlers directly, so it goes through the shared emitter.
const view = () => emitter.emit(`view-order-${props.tableId}`, props.rowData.id);

const itemCount = () => props.rowData.items?.length ?? 0;
</script>

<template>
    <div class="d-inline-flex align-items-center gap-2 text-nowrap">
        <span>{{ rowData.product_name || '-' }}</span>

        <a v-if="itemCount() > 0" href="#" role="button" :aria-label="$t('view_details')"
            :title="$t('view_details')" @click.prevent="view">
            <div class="view-icon">
                <Eye class="brand-color" />
            </div>
        </a>
    </div>
</template>

<style scoped lang="scss"></style>
