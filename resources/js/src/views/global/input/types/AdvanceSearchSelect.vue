<script setup>
import { ref, computed, onBeforeMount, watch } from "vue";
import { debounce as _debounce } from "lodash";
import Axios from "@/services/axios";
import { useCoreAppFunction } from "@/views/global/helpers/CoreLibrary";
import { XmarkCircle, NavArrowDown, Check, Search, Xmark } from "@iconoir/vue";
import { useToast } from "vue-toastification";

const toast = useToast();
const props = defineProps({
    data: {},
    value: {}
});

const emit = defineEmits(["searchSelect"]);
const { isUndefined } = useCoreAppFunction();

const options = ref([]);
const search = ref("");
const currentPage = ref(1);
const loadingLength = ref(props.data.loadingLength);
const getSearchValue = _debounce(() => {
    emit("searchValue", search.value);
    afterSearch();
}, 500);
const totalRow = ref(0);
const preloader = ref(false);
const selectedOption = ref({});
const optionsLoaded = ref(false);

const dropDownId = computed(() => `${props.data.id}-multi-select`);

const getOptionsByClick = () => {
    if (!optionsLoaded.value) {
        getOptions(null);
    }
};

const loadMore = () => {
    currentPage.value++;
    getOptions();
};

const afterSearch = () => {
    currentPage.value = 1;
    getOptions(null);
};

const getInitialOption = () => {
    if (!props.data.fetchUrl) {
        toast.error("You should give fetch-url in advance search");
        return;
    }
    Axios.get(props.data.fetchUrl, {
        params: {
            [props.data.queryAttributeName]: props.value,
        },
    }).then((res) => {
        const items = Array.isArray(res.data.data) ? res.data.data : [];
        const match = items.find(
            (item) => item && props.value == item[props.data.listKeyField]
        );

        // The selected value may no longer exist (e.g. a deleted category),
        // so never store undefined in the options array.
        selectedOption.value = match ?? {};
        options.value = match ? [match] : [];
        optionsLoaded.value = false;
    });
};

const mergeOptions = (items, append) => {
    const optionsByKey = new Map();
    const keyField = props.data.listKeyField;

    if (append) {
        options.value
            .filter(Boolean)
            .forEach((option) => optionsByKey.set(option[keyField], option));
    }

    items
        .filter(Boolean)
        .forEach((option) => optionsByKey.set(option[keyField], option));

    options.value = [...optionsByKey.values()];
};

const getOptions = (getType = "loadmore") => {
    preloader.value = true;
    if (!props.data.fetchUrl) {
        toast.error("You should give fetch-url in advance search");
        return;
    }
    let url = props.data.fetchUrl,
        requestData = {};
    requestData.params = {
        search: search.value,
        page: currentPage.value,
        per_page: loadingLength.value,
    };
    Axios.get(url, requestData).then((res) => {
        const items = Array.isArray(res.data.data) ? res.data.data : [];
        mergeOptions(items, getType === "loadmore");
        totalRow.value = res.data.total;
        optionsLoaded.value = true;
        preloader.value = false;
    });
};

const changeSelectFilter = (option) => {
    selectedOption.value = option;
    emit("searchSelect", option);
};

const clearOption = (e) => {
    clearFilter(e);
};

const clearFilter = (e) => {
    search.value = "";
    selectedOption.value = {};
    emit("searchSelect", "");
    currentPage.value = 1;
    getOptions(null);
    e.stopPropagation();
};

const dropdownScroll = ref();

// Watch the ref — scroll listener বসবে শুধু তখনই যখন dropdown DOM-এ attach হবে
watch(dropdownScroll, (newVal) => {
    if (newVal) {
        newVal.addEventListener("scroll", () => {
            if (
                newVal.scrollTop + newVal.clientHeight >=
                newVal.scrollHeight - 5
            ) {
                if (options.value.length < totalRow.value && !preloader.value) {
                    loadMore();
                }
            }
        });
    }
});

onBeforeMount(() => {
    if (props.value && props.data.showType !== "filter") {
        getInitialOption();
    }
});
</script>

<template>
    <div class="dropdown search-select-filter">
        <div
            v-if="data.showType === 'input-field'"
            class="multi-select-control dropdown-toggle d-flex justify-content-between align-items-center cursor-pointer text-truncate"
            :id="`advanceSearch-${dropDownId}`"
            data-bs-toggle="dropdown"
            aria-expanded="false"
            role="button"
            :aria-disabled="!!(value && data.selectedDisable)"
            :disabled="!!(value && data.selectedDisable)"
            @click="getOptionsByClick">
            <span>
                {{
                    selectedOption[data.listValueField] && value
                        ? selectedOption[data.listValueField]
                        : data.chooseLabel
                }}</span>

            <div
                v-if="
                    !isUndefined(value) &&
                    !isUndefined(selectedOption) &&
                    Object.keys(selectedOption).length > 0 &&
                    data.selectedDisable
                "
                class="close-search"
                @click="clearOption($event)">
                <XmarkCircle />
            </div>
            <span v-else>
                <span class="close-search">
                    <NavArrowDown />
                </span>
            </span>
        </div>
        <button
            v-else
            class="btn btn-white flex-shrink-0 search-and-filter-btn custom-filter-btn"
            :class="{ 'bg-brand': value }"
            type="button"
            :id="`advanceSearch-${dropDownId}`"
            data-bs-toggle="dropdown"
            aria-expanded="false"
            @click="getOptionsByClick">
            {{ data.chooseLabel }}
            <span
                v-if="value"
                @click="clearFilter($event)"
                class="cursor-pointer ms-2">
                <Xmark />
            </span>
        </button>
        <ul
            class="dropdown-menu custom-scrollbar overflow-auto"
            :id="dropDownId"
            :aria-labelledby="`advanceSearch-${dropDownId}`">
            <li class="search-wrapper" @click.stop="() => 0">
                <div class="search-input">
                    <span class="search-icon">
                        <Search />
                    </span>
                    <input
                        type="text"
                        autocomplete="off"
                        name="search"
                        v-model="search"
                        @input="getSearchValue"
                        :placeholder="$t('search')"
                        class="form-control" />
                </div>
            </li>

            <li
                class="filter-options-wrapper custom-scrollbar"
                :id="`options-${dropDownId}`"
                ref="dropdownScroll">
                <ul class="p-0 m-0">
                    <li v-for="(option, index) in options" :key="index">
                        <a
                            href="#"
                            class="dropdown-item text-capitalize d-inline-flex justify-content-between"
                            :class="{
                                'bg-base': value == option[data.listKeyField],
                            }"
                            @click.prevent="changeSelectFilter(option)">
                            <slot name="option" :item="option">
                                {{ option[data.listValueField] }}
                            </slot>
                            <span v-if="value == option[data.listKeyField]" class="ms-2">
                                <Check />
                            </span>
                        </a>
                    </li>
                    <li v-if="preloader">
                        <app-loader class="py-4 w-100" />
                    </li>
                </ul>
            </li>
        </ul>
    </div>
</template>

<style scoped lang="scss"></style>
