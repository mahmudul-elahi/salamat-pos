<script setup>
import { Xmark } from "@iconoir/vue";
import { ref, computed, onMounted } from "vue";

const props = defineProps({
    label: {},
    filterKey: {},
    filterOptions: {},
    active: {},
});

const emit = defineEmits(["applyFilter"]);

const isApplied = ref(false);
const checkedValues = ref([]);

const dropDownId = computed(() => `${props.filterKey}-${props.label}`);

const changeFilter = () => {
    let filterObj = {};
    filterObj[props.filterKey] = checkedValues.value.toString();
    emit("applyFilter", filterObj);

    const dropDownMenu = document.getElementById(dropDownId.value);
    const button = document.getElementById(`searchAndSelectFilter-${dropDownId.value}`);
    const dropdownContainer = button?.closest(".dropdown");

    dropDownMenu?.classList.remove("show");
    button?.classList.remove("show");
    dropdownContainer?.classList.remove("show");
};
const clearFilter = () => {
    checkedValues.value = [];
    isApplied.value = false;
    changeFilter();
};
const applyFilter = () => {
    isApplied.value = true;
    changeFilter();
};

onMounted(() => {
    if (props.active) {
        checkedValues.value = props.active.split(",");
        isApplied.value = true;
    }
});
</script>

<template>
    <div class="filter search-select-filter">
        <div class="dropdown keep-dropdown-open">
            <button class="btn btn-white flex-shrink-0 search-and-filter-btn custom-filter-btn"
                :class="{ 'bg-brand': checkedValues.length }" type="button" :id="`searchAndSelectFilter-${dropDownId}`"
                data-bs-toggle="dropdown" aria-expanded="false">
                {{ label }}
                <span v-if="checkedValues.length" @click="clearFilter($event)" class="cursor-pointer ms-2">
                    <Xmark />
                </span>
            </button>
            <div class="dropdown-menu custom-scrollbar overflow-auto checkbox-filter-menu" :id="dropDownId"
                :aria-labelledby="`searchAndSelectFilter-${dropDownId}`">
                <div class="p-3">
                    <div class="checkbox-filter">
                        <label class="checkbox-filter-option" v-for="(option, index) in filterOptions"
                            :key="'checkbox-filter-' + index" :for="option.id" @click.stop>
                            <input class="checkbox-filter-input" type="checkbox" :value="option.id" :id="option.id"
                                v-model="checkedValues" @click.stop />
                            <span class="checkbox-filter-box">
                                <svg viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M3 8.5L6.5 12L13 4.5" stroke="currentColor" stroke-width="2"
                                        stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </span>
                            <span class="checkbox-filter-label">{{ option.name }}</span>
                        </label>
                    </div>

                    <div class="d-flex align-items-center justify-content-between checkbox-filter-footer">
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

<style scoped lang="scss">
.checkbox-filter-menu {
    min-width: max-content;
    padding: 0;
    border: none;
    border-radius: 12px;
    box-shadow: 0 12px 32px rgba(0, 0, 0, 0.12);
}

.checkbox-filter {
    display: grid;
    grid-template-columns: repeat(2, auto);
    gap: 4px 24px;
    margin-bottom: 16px;
}

.checkbox-filter-option {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 8px 10px;
    border-radius: 8px;
    cursor: pointer;
    margin: 0;
    transition: background-color 0.15s ease;

    &:hover {
        background-color: var(--dashboard-bg, #f5f6fa);
    }
}

.checkbox-filter-input {
    position: absolute;
    width: 1px;
    height: 1px;
    opacity: 0;
    pointer-events: none;
}

.checkbox-filter-box {
    flex-shrink: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 20px;
    height: 20px;
    border-radius: 6px;
    border: 1.5px solid var(--border-color, #d1d5db);
    background: var(--white, #fff);
    color: transparent;
    transition: all 0.15s ease;

    svg {
        width: 13px;
        height: 13px;
    }
}

.checkbox-filter-input:checked+.checkbox-filter-box {
    background: var(--primary, #2b8768);
    border-color: var(--primary, #2b8768);
    color: #fff;
}

.checkbox-filter-input:focus-visible+.checkbox-filter-box {
    box-shadow: 0 0 0 3px rgba(43, 135, 104, 0.2);
}

.checkbox-filter-label {
    font-size: 14px;
    color: var(--font-color, #4b5563);
    user-select: none;
    white-space: nowrap;
}

.checkbox-filter-footer {
    padding-top: 14px;
    border-top: 1px solid var(--border-color, #ececec);

    .btn {
        border-radius: 8px;
        padding: 6px 16px;
        font-size: 13px;
    }
}
</style>
