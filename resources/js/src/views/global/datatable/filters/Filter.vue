<script setup>
import CheckBoxFilter from "@/views/global/datatable/filters/CheckBoxFilter.vue";
import SingleSelectFilter from "@/views/global/datatable/filters/SingleSelectFilter.vue";
import AdvanceSearchSelect from "@/views/global/datatable/filters/AdvanceSearchSelect.vue";
import MultiSelectFilter from "@/views/global/datatable/filters/MultiSelectFilter.vue";
import DateRangeFilter from "@/views/global/datatable/filters/DateRangeFilter.vue";
import YearRangeFilter from "@/views/global/datatable/filters/YearRangeFilter.vue";
import TextFilter from "@/views/global/datatable/filters/TextFilter.vue";

const props = defineProps({
    filters: {
        type: Array,
        required: true,
    },
    filterOptions: {
        type: Array,
        default: function () {
            return [
                {id: 1, name: "Option 1"},
                {id: 2, name: "Option 2"},
                {id: 3, name: "Option 3"},
                {id: 4, name: "Option 4"},
                {id: 5, name: "Option 5"},
            ];
        },
    },
});

const emit = defineEmits(["applyFilter"]);

const applyFilter = (values) => {
    emit("applyFilter", values);
};
</script>

<template>
    <div class="filters px-3 py-3 d-flex gap-2">
        <template v-for="(filter, index) in filters" :key="index">
            <check-box-filter
                v-if="
                    (filter.type.toLowerCase() === 'checkbox') &
                    filter.permission
                "
                :filter-key="filter.key"
                :label="filter.label"
                :filter-options="
                    filter.options ? filter.options : filterOptions
                "
                :active="filter.active"
                @applyFilter="applyFilter"
            />

            <single-select-filter
                v-if="
                    (filter.type.toLowerCase() === 'search-select') &
                    filter.permission
                "
                :filter-key="filter.key"
                :label="filter.label"
                :filter-options="
                    filter.options ? filter.options : filterOptions
                "
                :active="filter.active"
                :show-search="filter.showSearch"
                @applyFilter="applyFilter"
            />

            <advance-search-select
                v-if="
                    (filter.type.toLowerCase() === 'advance-search-select') &
                    filter.permission
                "
                :filter-key="filter.key"
                :label="filter.label"
                :fetch-url="filter.fetchUrl"
                :list-key-field="filter.listKeyField"
                :list-value-field="filter.listValueField"
                :loading-length="filter.rowPerLoad"
                :active="filter.active"
                :show-search="filter.showSearch"
                @applyFilter="applyFilter"
            />

            <multi-select-filter
                v-if="
                    (filter.type.toLowerCase() === 'multi-select-filter') &
                    filter.permission
                "
                :filter-key="filter.key"
                :label="filter.label"
                :fetch-url="filter.fetchUrl"
                :list-key-field="filter.listKeyField"
                :list-value-field="filter.listValueField"
                :loading-length="filter.rowPerLoad"
                :active="filter.active"
                :show-search="filter.showSearch"
                @applyFilter="applyFilter"
            />

            <date-range-filter
                v-if="
                    filter.type.toLowerCase() === 'date-range' &&
                    filter.permission
                "
                :filter-key="filter.key"
                :active="filter.active"
                :placeholder="filter.placeholder"
                @applyFilter="applyFilter"
            />
            <year-range-filter
                v-if="filter.type.toLowerCase() === 'year-range' && filter.permission"
                :filter-key="filter.key"
                :active="filter.active"
                :placeholder="filter.placeholder"
                year-picker
                @applyFilter="applyFilter"
            />

            <text-filter
                v-if="filter.type.toLowerCase() === 'text' && filter.permission"
                :filter-key="filter.key"
                :label="filter.label"
                :active="filter.active"
                :placeholder="filter.placeholder"
                @applyFilter="applyFilter"
            />
        </template>
    </div>

</template>

<style scoped lang="scss">

</style>
