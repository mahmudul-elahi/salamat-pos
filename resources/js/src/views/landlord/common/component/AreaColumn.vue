<script setup>
const props = defineProps({
    tableId: [String],
    rowData: {
        type: Object,
        required: true,
    },
});
import {Eye} from "@iconoir/vue";

const closeNote = () => {
    let element = document.querySelector(
        `ul[aria-labelledby='#area${props.rowData.customer_id || props.rowData.supplier_id}']`
    );
    element?.classList.remove("show");
};
</script>

<template>
    <div class="dropdown d-inline-flex" v-if="rowData.customer_address || rowData.supplier_address">
        <a
            :href="`#area${rowData.customer_id || rowData.supplier_id}`"
            role="button"
            :id="`#area${rowData.customer_id || rowData.supplier_id}`"
            data-bs-toggle="dropdown"
            data-bs-auto-close="outside"
            aria-expanded="false"
            :aria-label="rowData.customer_id || rowData.supplier_id">
            <div class="view-icon">
                <Eye class="brand-color"/>
            </div>
        </a>
        <ul
            class="dropdown-menu dropdown-menu-end note-container py-0 custom-scrollbar"
            :aria-labelledby="`#area${rowData.customer_id || rowData.supplier_id}`"
            @click.stop="() => false">
            <div class="card card-body shadow-none">
                <div class="card-title py-1 pb-1">
                    {{ $t("area") }}
                </div>
                <div class="note-details mb-3">
                    <span v-html="rowData.customer_address || rowData.supplier_address || ''"></span>
                </div>
                <div class="d-flex justify-content-end">
                    <button
                        @click="closeNote"
                        class="btn btn-primary"
                        data-bs-toggle="collapse"
                        :data-bs-target="`#area${rowData.customer_id || rowData.supplier_id}`"
                        role="button"
                        aria-expanded="false"
                        :aria-controls="`note${rowData.customer_id || rowData.supplier_id}`">
                        {{ $t("close") }}
                    </button>
                </div>
            </div>
        </ul>
    </div>
    <div v-else>-</div>
</template>

<style scoped lang="scss"></style>
