<script setup>
import { ref, computed, onMounted } from "vue";
import { debounce as _debounce } from "lodash";
import Observer from "@/views/global/observer/Observer.vue";
import Axios from "@/services/axios";
import { uniqBy } from "lodash";
import { useToast } from "vue-toastification";
import { NavArrowDown, Xmark, Search, CheckCircle } from "@iconoir/vue";
const props = defineProps({
    data: {},
    value: {},
});

const emit = defineEmits(["advanceMultiSelect"]);
const toast = useToast();
const options = ref([]);
const search = ref("");
const currentPage = ref(1);
const multiSelectValue = ref([]);
const loadingLength = ref(props.data.loadingLength);
const getSearchValue = _debounce(() => {
    afterSearch();
}, 500);
const totalRow = ref(0);
const preloader = ref(false);
const initialOption = ref([]);

const dropDownId = computed(() => `${props.data.id}-multi-select`);

const isSelected = (item) => {
    return (
        multiSelectValue.value.findIndex(
            (e) => e[props.data.listKeyField] == item
        ) !== -1
    );
};
const getOptionsByClick = () => {
    if (
        (props.value && props.value.length === options.value.length) ||
        options.value.length === 0
    ) {
        getOptions();
    }
};
const loadMore = () => {
    currentPage.value++;
    getOptions();
};
const afterSearch = () => {
    currentPage.value = 1;
    options.value = [...initialOption.value];
    getOptions();
};

const getInitialOption = () => {
    if (!props.data.fetchUrl) {
        toast.error("You should give fetch-url in advance search");
        return;
    }

    return Axios.get(props.data.fetchUrl, {
        params: {
            [props.data.queryAttributeName]: props.value.toString(),
        },
    }).then((res) => {
        initialOption.value = [...res.data.data];
        multiSelectValue.value = [...initialOption.value];
        options.value = [...initialOption.value];
        return res;
    });
};
const getOptions = async () => {
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
        if (props.value) {
            options.value = [
                ...options.value,
                ...res.data.data.filter((item) => {
                    return !initialOption.value.find(
                        (e) => e[props.data.listKeyField] == item[props.data.listKeyField]
                    );
                }),
            ];
        } else options.value = [...options.value, ...res.data.data];
        totalRow.value = res.data.total;
        preloader.value = false;
    });
};

const fetchData = () => {
    if (options.value.length < totalRow.value && !preloader.value) {
        loadMore();
    }
};
const changeSelectFilter = (option) => {
    let elementIndex = multiSelectValue.value.findIndex(
        (e) => e[props.data.listKeyField] == option[props.data.listKeyField]
    );
    if (elementIndex === -1) multiSelectValue.value.push(option);
    else multiSelectValue.value.splice(elementIndex, 1);
    sendEvent();
    removeDropdownShow();
};

const deleteItems = (e, item) => {
    e.stopPropagation();
    changeSelectFilter(item);
};

const clearFilter = (e) => {
    search.value = "";
    multiSelectValue.value = [];
    sendEvent();
    afterSearch();
    e.stopPropagation();
    removeDropdownShow();
};

const sendEvent = () => {
    emit(
        "advanceMultiSelect",
        multiSelectValue.value.map((e) => e[props.data.listKeyField])
    );
};

const removeDropdownShow = () => {
    let dropDownMenu = document.getElementById(dropDownId.value),
        button = document.getElementById(`advanceMultiSelect-${dropDownId.value}`);
    dropDownMenu.classList.remove("show");
    button.classList.remove("show");
};

onMounted(() => {
    if (props.data.preLoadedOptions?.length) {
        initialOption.value = [...props.data.preLoadedOptions];
        multiSelectValue.value = [...props.data.preLoadedOptions];
        options.value = [...props.data.preLoadedOptions];
        return;
    }
    if (props.value && props.value.length > 0) {
        getInitialOption();
    }
});
</script>

<template>
    <div class="dropdown search-select-filter keep-dropdown-open">
        <div v-if="data.showType === 'input-field'" :id="`advanceMultiSelect-${dropDownId}`" data-bs-toggle="dropdown"
            aria-expanded="false" class="multi-select-control dropdown-toggle cursor-pointer"
            @click="getOptionsByClick">
            <div class="d-flex flex-wrap align-items-center">
                <template v-if="multiSelectValue.length">
                    <span @click.prevent="$event.stopPropagation()" v-for="(item, index) in multiSelectValue"
                        :key="index" class="badge-square-light bg-primary badge-responsive">
                        {{ item[data.listValueField] }}
                        <a href="#" class="multi-select-btn" @click.prevent="deleteItems($event, item)">
                            <Xmark />
                        </a>
                    </span>
                </template>
                <span v-else>{{ data.chooseLabel }}</span>
            </div>
            <span v-if="!multiSelectValue.length">
                <span class="close-search">
                    <NavArrowDown />
                </span>
            </span>
        </div>
        <button v-else class="btn btn-white flex-shrink-0 search-and-filter-btn"
            :class="{ 'bg-primary': multiSelectValue.length }" type="button" :id="`advanceMultiSelect-${dropDownId}`"
            data-bs-toggle="dropdown" aria-expanded="false">
            {{ data.chooseLabel }}
            <span v-if="multiSelectValue.length" @click="clearFilter($event)" class="cursor-pointer ms-2">
                <Xmark />
            </span>

        </button>
        <ul class="dropdown-menu" :class="{ 'has-selections': multiSelectValue.length }" :id="dropDownId"
            :aria-labelledby="`advanceMultiSelect-${dropDownId}`">
            <li class="search-wrapper" @click.stop="() => 0">
                <div class="search-input">
                    <span class="search-icon">
                        <Search />
                    </span>
                    <input v-model="search" @input="getSearchValue" placeholder="Search" class="form-control" />
                </div>
            </li>

            <li class="filter-options-wrapper" :id="`options-${dropDownId}`">
                <ul class="p-0 m-0">
                    <li v-for="(option, index) in uniqBy(options, 'id')" :key="index">
                        <a href="#" class="dropdown-item text-capitalize d-inline-flex justify-content-between"
                            :class="{ 'bg-base mb-2': isSelected(option[data.listKeyField]) }"
                            @click.prevent="changeSelectFilter(option)">
                            {{ option[data.listValueField] }}
                            <span v-if="isSelected(option[data.listKeyField])" class="cursor-pointer ms-2">
                                <CheckCircle />
                            </span>

                        </a>
                    </li>
                    <li v-if="preloader">
                        <app-loader class="py-4 w-100" />
                    </li>
                    <observer @intersect="fetchData" />
                </ul>
            </li>
        </ul>
    </div>
</template>

<style scoped lang="scss">
.filter-options-wrapper {
    max-height: 250px;
    overflow-y: auto;
}

.dropdown-menu {
    min-width: 280px;

    // selected items এর উপর ভিত্তি করে height বাড়বে
    &.has-selections {
        .filter-options-wrapper {
            max-height: 180px; // selected items জায়গা নেওয়ার পর বাকি height
        }
    }
}

.multi-select-control {
    min-height: 38px;
    height: auto; // fixed height সরিয়ে auto করুন
    padding: 6px 10px;

    .badge-responsive {
        margin: 2px;
    }
}
</style>
