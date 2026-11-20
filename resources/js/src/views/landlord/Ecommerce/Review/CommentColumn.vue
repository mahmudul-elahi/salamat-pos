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
        `ul[aria-labelledby='#comment${props.rowData.id}']`
    );
    element?.classList.remove("show");
};
</script>

<template>
    <div class="dropdown d-inline-flex" v-if="rowData.comment">
        <a
            :href="`#comment${rowData.id}`"
            role="button"
            :id="`#comment${rowData.id}`"
            data-bs-toggle="dropdown"
            data-bs-auto-close="outside"
            aria-expanded="false"
            aria-label="View comment">
            <div class="view-icon">
                <Eye class="brand-color"/>
            </div>
        </a>
        <ul
            class="dropdown-menu dropdown-menu-end note-container py-0 custom-scrollbar"
            :aria-labelledby="`#comment${rowData.id}`"
            @click.stop="() => false">
            <div class="card card-body shadow-none">
                <div class="card-title py-1 pb-1">
                    {{ $t("comment") }}
                </div>
                <div class="note-details mb-3">
                    <span v-html="rowData.comment"></span>
                </div>
                <div class="d-flex justify-content-end">
                    <button
                        @click="closeNote"
                        class="btn btn-primary"
                        data-bs-toggle="collapse"
                        :data-bs-target="`#comment${rowData.id}`"
                        role="button"
                        aria-expanded="false"
                        :aria-controls="`comment${rowData.id}`">
                        {{ $t("close") }}
                    </button>
                </div>
            </div>
        </ul>
    </div>
    <div v-else>-</div>
</template>

<style scoped lang="scss"></style>
