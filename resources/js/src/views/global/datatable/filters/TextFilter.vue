<script setup>
import { Xmark } from "@iconoir/vue";
import { ref, computed, onMounted } from "vue";

const props = defineProps({
    label: {},
    filterKey: {},
    active: {},
    placeholder: {},
});

const emit = defineEmits(["applyFilter"]);

const isApplied = ref(false);
const inputValue = ref("");

const dropDownId = computed(() => `${props.filterKey}-${props.label}`);

const changeFilter = () => {
    let filterObj = {};
    filterObj[props.filterKey] = inputValue.value;
    emit("applyFilter", filterObj);

    const dropDownMenu = document.getElementById(dropDownId.value);
    const button = document.getElementById(`searchAndSelectFilter-${dropDownId.value}`);
    const dropdownContainer = button?.closest(".dropdown");

    dropDownMenu?.classList.remove("show");
    button?.classList.remove("show");
    dropdownContainer?.classList.remove("show");
};

const clearFilter = () => {
    inputValue.value = "";
    isApplied.value = false;
    changeFilter();
};

const applyFilter = () => {
    isApplied.value = !!inputValue.value;
    changeFilter();
};

onMounted(() => {
    if (props.active) {
        inputValue.value = props.active;
        isApplied.value = true;
    }
});
</script>

<template>
    <div class="filter search-select-filter">
        <div class="dropdown keep-dropdown-open">
            <button class="btn btn-white flex-shrink-0 search-and-filter-btn custom-filter-btn"
                :class="{ 'bg-brand': isApplied }" type="button" :id="`searchAndSelectFilter-${dropDownId}`"
                data-bs-toggle="dropdown" aria-expanded="false">
                {{ label }}
                <span v-if="isApplied" @click="clearFilter($event)" class="cursor-pointer ms-2">
                    <Xmark />
                </span>
            </button>
            <div class="dropdown-menu custom-scrollbar overflow-auto" :id="dropDownId"
                :aria-labelledby="`searchAndSelectFilter-${dropDownId}`">
                <div class="p-4">
                    <input type="text" class="form-control" :placeholder="placeholder || label" v-model="inputValue"
                        @keyup.enter="applyFilter" @click.stop />

                    <div class="d-flex align-items-center justify-content-between mt-4">
                        <button type="button" class="btn btn-sm btn-secondary" @click.prevent="clearFilter">
                            {{ $t("cancel") }}
                        </button>
                        <button type="button" class="btn btn-sm btn-primary" @click.prevent="applyFilter">
                            {{ $t("apply") }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped lang="scss"></style>
