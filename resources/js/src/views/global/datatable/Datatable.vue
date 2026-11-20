<script setup>
import { ref, watch, computed, onMounted, onBeforeMount, onBeforeUnmount } from "vue";
import { objectToQueryString, queryStringToObject } from "@/utils/queryHelper.js";
import { numberWithCurrencySymbol } from "@/utils/helper.js";
import useEmitter from "@/composable/emitter/useEmitter.js";
import { debounce as _debounce } from "lodash";
import { useCoreAppFunction } from "@/views/global/helpers/CoreLibrary";
import Paginate from "@/views/global/datatable/Paginate.vue";
import EmptyMessage from "@/views/global/datatable/EmptyMessage.vue";
import DropdownAction from "@/views/global/datatable/DropdownAction.vue";
import Axios from "@/services/axios";
import { Download, Filter, Upload, ViewGrid, List, Search, CheckCircle, Circle, Table2Columns } from "@iconoir/vue";
import { useToast } from "vue-toastification";

const toast = useToast()

const isMobile = ref(window.innerWidth <= 767)
const handleResize = () => { isMobile.value = window.innerWidth <= 767 }

let activeRequest = null; // AbortController for the current in-flight request

const props = defineProps({
    id: {
        required: true,
        type: String,
    },
    options: {
        required: true,
        type: Object,
    },
    addButtonText: {
        type: String,
        required: false
    },
    buttonVisible: {
        type: Boolean,
        required: false,
        default: true
    }
});


const emit = defineEmits(["afterRowSelect", "action", "buttonAction", "filter-change"]);

const emitter = useEmitter();
const loading = ref(false);
const dataSetList = ref([]);
const filterValues = ref({});
const activePaginationPage = ref(1);
const totalRow = ref(0);
const searchValue = ref("");
const gridView = ref(false);
const getSearchValue = _debounce(() => {
    if (searchValue.value) {
        // Searching — go to page 1 internally but keep URL page intact
        activePaginationPage.value = 1;
    } else {
        // Search cleared — restore page from URL
        const urlPage = queryStringToObject().page;
        activePaginationPage.value = urlPage ? Number(urlPage) : 1;
    }
    getServerData();
}, 500);
const selectedRows = ref([]);
const grandTotal = ref({});

const { isUndefined, getAppUrl } = useCoreAppFunction();
const rowSelectEnable = computed(
    () =>
        !isUndefined(props.options.enableRowSelect) &&
        props.options.enableRowSelect
);

// ── Column show/hide ──────────────────────────────────────────────
const COLUMN_VISIBILITY_STORAGE_KEY = `datatable-hidden-columns-${props.id}`;
const loadHiddenColumnKeys = () => {
    try {
        const saved = JSON.parse(localStorage.getItem(COLUMN_VISIBILITY_STORAGE_KEY));
        if (Array.isArray(saved)) return saved;
    } catch (e) {
        // ignore malformed storage value
    }
    return [];
};
const hiddenColumnKeys = ref(loadHiddenColumnKeys());
const persistHiddenColumnKeys = () => {
    localStorage.setItem(COLUMN_VISIBILITY_STORAGE_KEY, JSON.stringify(hiddenColumnKeys.value));
};
const isColumnVisible = (column) =>
    column.permission !== false && !hiddenColumnKeys.value.includes(column.key);
const toggleableColumns = computed(() =>
    (props.options.columns || []).filter((column) => column.key && column.title && column.permission !== false)
);
const showColumnToggle = computed(() =>
    props.options.columnToggle !== false && toggleableColumns.value.length > 1
);
const toggleColumnVisibility = (key) => {
    const index = hiddenColumnKeys.value.indexOf(key);
    if (index > -1) {
        hiddenColumnKeys.value.splice(index, 1);
    } else {
        hiddenColumnKeys.value.push(key);
    }
    persistHiddenColumnKeys();
};
const showAllColumns = () => {
    hiddenColumnKeys.value = [];
    persistHiddenColumnKeys();
};
// ────────────────────────────────────────────────────────────────────

const isAnyAccountableColumns = computed(() =>
    props.options.columns
        ? props.options.columns.filter(
            (column) => isColumnVisible(column) && column.accountAble
        ).length
        : 0
);
const windowWidth = ref(window.innerWidth);


const updateWindowWidth = () => {
    windowWidth.value = window.innerWidth;
};
const IsAccountableColumnsFilter = computed(() => {
    return props.options.columns.filter(isColumnVisible);
});

// deep: true - rows are pushed/spliced into the array in place
// (selectedRows.push(row) / selectedRows.splice(...)), not reassigned, so a
// shallow watch on the ref never sees the change and afterRowSelect never
// fires for individual row clicks (only for the whole-array reassignments
// like clearAllSelection() or the header select-all/none toggle).
watch(selectedRows, () => {
    afterRowSelection();
}, { deep: true });

const clearAllSelection = () => {
    selectedRows.value = [];
};
const removeFromSelection = (row) => {
    let index = selectedRows.value.indexOf(row);
    selectedRows.value.splice(index, 1);
};

const isSelected = (row) => {
    return selectedRows.value.includes(row);
};
const afterRowSelection = () => {
    emit("afterRowSelect", {
        rows: selectedRows.value,
    });
};
const getServerData = () => {
    // Cancel any in-flight request before starting a new one
    if (activeRequest) activeRequest.abort();
    activeRequest = new AbortController();

    let URL = props.options.url,
        filter = filterValues.value,
        requestData = {};
    filter.page = activePaginationPage.value;
    filter.per_page = filterValues.value.per_page || 10;
    filter.filter = filterValues.value.filter || "close";
    filter.search = searchValue.value;

    if (filterValues.value.date) {
        filter.date =
            typeof filterValues.value.date == "string"
                ? JSON.parse(filterValues.value.date)
                : filterValues.value.date;
    }

    let query = objectToQueryString(filter),
        pageTitle = document.title;
    requestData.params = filter;
    requestData.signal = activeRequest.signal;
    // Don't change URL while searching or filtering — page stays visible in address bar
    const isFiltering = filterValues.value.filter === 'open';
    if (!searchValue.value && !isFiltering) {
        history.replaceState({ ...history.state }, pageTitle, `?${query}`);
    }
    loading.value = true;
    Axios.get(URL, requestData)
        .then((response) => {
            dataSetList.value = response.data;
            totalRow.value = response.data.total;
            grandTotal.value = response.data.grand_total;
            selectedRows.value = [];
        }).catch((err) => {
            // Ignore abort errors — they're intentional cancellations
            if (err?.code === 'ERR_CANCELED' || err?.name === 'CanceledError') return;
            toast.error(err?.response?.data?.message ?? 'Something went wrong');
        }).finally(() => (loading.value = false));
};

const getPaginateData = (page) => {
    activePaginationPage.value = page;
    getServerData();
};

const reloadDataTable = () => {
    emitter.on("reload-" + props.id, (value = true) => {
        if (value) {
            let queryObj = queryStringToObject();
            if (queryObj.page) {
                if (dataSetList.value.data?.length - 1 > 0) {
                    activePaginationPage.value = Number(queryObj.page);
                } else {
                    activePaginationPage.value =
                        Number(queryObj.page) === 1
                            ? Number(queryObj.page)
                            : Number(queryObj.page - 1);
                }
            }

            getServerData();
        }
    });
};

const bothAction = (row, action, index) => {
    emit("action", row, action, index);
};

const getButtonValue = (row, column) => {
    emit("action", row, column, true);
};
const applyFilter = (values) => {
    filterValues.value = { ...filterValues.value, ...values };

    let o = Object.fromEntries(
        Object.entries(filterValues.value).filter(([_, v]) => !!v)
    );
    const hasActiveFilter = Object.keys(o).length > 3;
    filterValues.value.filter = hasActiveFilter ? "open" : "close";

    if (hasActiveFilter) {
        activePaginationPage.value = 1;
    } else {
        const urlPage = queryStringToObject().page;
        activePaginationPage.value = urlPage ? Number(urlPage) : 1;
    }

    emit('filter-change', filterValues.value);
    getServerData();
};
const initState = () => {
    let queryObj = queryStringToObject();
    if (queryObj.search) searchValue.value = queryObj.search;
    if (queryObj.page) activePaginationPage.value = Number(queryObj.page);
    filterValues.value = { ...queryObj };
    for (const [key, value] of Object.entries(queryObj)) {
        let filter = props.options?.filters?.find((item) => item.key === key);
        if (filter) filter.active = value;
    }
    getServerData();
};

const columnTotal = (column) => {
    let isNotAccountAble = isNaN(dataSetList.value.data[0][column.key]);
    if (isNotAccountAble) {
        return "";
    } else {
        return dataSetList.value.data.reduce(
            (total, current) => (total + Number(current[column.key])),
            0
        );
    }
};
const lastNonAccountableIndex = computed(() => {
    const cols = IsAccountableColumnsFilter.value;
    let lastIndex = -1;

    for (let i = 0; i < cols.length; i++) {
        if (cols[i].accountAble) break;
        lastIndex = i;
    }

    return lastIndex;
});

const showCustomLabel = (columnIndex) => {
    return columnIndex === lastNonAccountableIndex.value;
};

const perPageValues = ref([10, 30, 50, 100, 200]);
const getByPerPage = (e) => {
    activePaginationPage.value = 1;
    filterValues.value.per_page = parseInt(e.currentTarget.value);
    getServerData();
};
const toPage = ref(1);
const goToPage = (e) => {
    toPage.value = parseInt(e.currentTarget.value);
    updatePage();
};
const updatePage = _debounce(() => {
    if (
        toPage.value &&
        toPage.value > 0 &&
        toPage.value <= dataSetList.value.last_page
    ) {
        activePaginationPage.value = toPage.value;
        getServerData();
    }
}, 500);

const updateQuery = () => {
    if ((filterValues.value.filter = "open")) {
        filterValues.value.filter = "close";
    }

    let query = objectToQueryString(filterValues.value),
        pageTitle = document.title;
    history.replaceState({ ...history.state }, pageTitle, `?${query}`);
};

const buttonAction = () => {
    emit("buttonAction");
}
const outerWrapperClass = computed(() => {
    const hasAnyLeftButton =
        props.options?.showGridView ||
        props.options?.showFilter ||
        (!isUndefined(props.options?.exportData) && props.options?.exportPermission) ||
        !isUndefined(props.options?.importData) ||
        showColumnToggle.value;

    return hasAnyLeftButton ? 'd-flex gap-3 flex-wrap' : '';
});
onMounted(() => {
    reloadDataTable();
    window.addEventListener('resize', updateWindowWidth);
    window.addEventListener('resize', handleResize);
    updateWindowWidth();
});

onBeforeMount(() => {
    initState();
});
onBeforeUnmount(() => {
    if (activeRequest) activeRequest.abort();
    emitter.off("reload-" + props.id);
    window.removeEventListener('resize', updateWindowWidth);
    window.removeEventListener('resize', handleResize);
});
</script>

<template>
    <div class="datatable-wrapper">
        <div class="filters-wrapper">
            <div class="d-flex flex-wrap gap-3 align-items-center justify-content-between res-wrapper mb-3">
                <div class="search-input flex-shrink-0" v-if="options.showSearch">
                    <span class="search-icon">
                        <Search />
                    </span>
                    <input :placeholder="$t('search')" v-model="searchValue" type="text" name="search"
                        autocomplete="off" @input="getSearchValue($event)"
                        class="form-control form-control-sm bg-card border-transparent shadow-default rounded-default" />
                </div>
                <div :class="outerWrapperClass">
                    <div class="d-flex flex-wrap align-items-start gap-3">
                        <button v-if="options.showGridView" type="button" class="btn btn-white flex-shrink-0 me-3">
                            <template v-if="!gridView">
                                <span class="me-1">
                                    <ViewGrid />
                                </span>
                                {{ $t("grid_view") }}
                            </template>
                            <template v-else>
                                <span class="me-1">
                                    <List />
                                </span>

                                {{ $t("list_view") }}
                            </template>
                        </button>
                        <div v-if="options.showFilter" @click="updateQuery">
                            <button type="button" class="btn btn-white flex-shrink-0 search-and-filter-btn"
                                data-bs-toggle="collapse" data-bs-target="#filterCollapse" aria-expanded="false"
                                aria-controls="filterCollapse">
                                <span class="me-1">
                                    <Filter />
                                </span>
                                {{ $t("filter") }}
                            </button>
                        </div>
                        <button v-if="!isUndefined(options.exportData) && options.exportPermission"
                            @click.prevent="options.exportData" type="button"
                            class="btn btn-white flex-shrink-0 search-and-filter-btn">
                            <span class="me-1">
                                <Download />
                            </span>
                            {{ $t("export") }}
                        </button>
                        <button v-if="!isUndefined(options.importData)" @click.prevent="options.importData"
                            type="button" class="btn btn-white flex-shrink-0">
                            <span class="me-1">
                                <Upload />
                            </span>
                            {{ $t("import") }}
                        </button>
                        <div v-if="showColumnToggle" class="dropdown">
                            <button type="button" class="btn btn-white flex-shrink-0 dropdown-toggle"
                                data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
                                <span class="me-1">
                                    <Table2Columns />
                                </span>
                                {{ $t("columns") }}
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end p-2 column-visibility-menu">
                                <li v-for="col in toggleableColumns" :key="col.key">
                                    <label class="dropdown-item d-flex align-items-center gap-2 mb-0">
                                        <input type="checkbox" class="form-check-input m-0"
                                            :checked="!hiddenColumnKeys.includes(col.key)"
                                            @change="toggleColumnVisibility(col.key)">
                                        <span>{{ col.title }}</span>
                                    </label>
                                </li>
                                <li>
                                    <hr class="dropdown-divider">
                                </li>
                                <li>
                                    <button type="button" class="btn btn-sm btn-primary w-100"
                                        @click="showAllColumns">
                                        {{ $t("show_all") }}
                                    </button>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <!-- Extra caller-supplied actions, rendered inline with Columns/Add -->
                    <slot name="extra-actions"></slot>
                    <!-- Reusable Button -->
                    <button v-if="!isUndefined(addButtonText) && buttonVisible" @click.prevent="buttonAction"
                        type="button" class="btn btn-primary">
                        {{ addButtonText }}
                    </button>
                </div>
            </div>
            <div class="collapse mb-3" :class="{
                show: $route.query.filter === 'open',
            }" id="filterCollapse" v-if="options.showFilter">
                <app-filter :filters="options.filters" @applyFilter="applyFilter" />
            </div>


        </div>
        <div v-if="selectedRows.length && options.enableBulkSelect" class="text-secondary text-center card-bg">
            <p class="text-primary">
                {{ $t("selected") }} {{ selectedRows.length }} {{ $t("items") }} |
                <a href="#" @click.prevent="clearAllSelection">
                    {{ $t("clear_all_selection") }}
                </a>
            </p>
        </div>
        <div class="data-wrapper">
            <div class="table-responsive position-relative rounded-default" :style="[
                !isUndefined(options.boxShadow) &&
                    options.boxShadow === false
                    ? 'box-shadow:none'
                    : 'box-shadow: var(--box-shadow)',
            ]">
                <div v-if="loading"
                    style="position:absolute;inset:0;z-index:10;background:rgba(255,255,255,.6);display:flex;align-items:center;justify-content:center;">
                    <app-loader />
                </div>
                <table class="table text-nowrap" :class="{ 'table-striped': options.stripedTable }">
                    <thead>
                        <tr>
                            <th style="width: 2rem" v-if="rowSelectEnable">
                                <span v-if="selectedRows.length && selectedRows.length === dataSetList?.data?.length"
                                    @click.prevent="selectedRows = []" class="cursor-pointer">
                                    <CheckCircle />
                                </span>
                                <span v-else @click.prevent="selectedRows = [...(dataSetList?.data ?? [])]"
                                    class="cursor-pointer">
                                    <Circle />
                                </span>
                            </th>
                            <template v-for="(manageColumn, index) in options.columns" :key="index">
                                <th v-if="isColumnVisible(manageColumn)" :class="{
                                    'text-start':
                                        manageColumn.align === 'left',
                                    'text-center':
                                        manageColumn.align === 'center',
                                    'text-end':
                                        manageColumn.align === 'right',
                                }">
                                    {{ manageColumn.title }}
                                </th>
                            </template>
                        </tr>
                    </thead>
                    <tbody>
                        <template v-if="
                            dataSetList.data && dataSetList.data.length > 0
                        ">
                            <tr v-for="(row, rowIndex) in dataSetList.data" :key="'row' + rowIndex">
                                <td v-if="rowSelectEnable">
                                    <span v-if="isSelected(row)" @click.prevent="removeFromSelection(row)"
                                        class="cursor-pointer">
                                        <CheckCircle />
                                    </span>

                                    <span v-else @click.prevent="selectedRows.push(row)" class="cursor-pointer">
                                        <Circle />
                                    </span>
                                </td>
                                <template v-for="(column, columnIndex) in options.columns">
                                    <td v-if="isColumnVisible(column)" :key="'column' + rowIndex + columnIndex"
                                        :class="{
                                            'text-start': column.align === 'left',
                                            'text-end': column.align === 'right',
                                            'text-center': column.align === 'center',
                                            'pt-0 border-top-0': options.showHeader === false && rowIndex === 0,
                                        }" :data-title="column.title">

                                        <!-- Text Type Column -->
                                        <template v-if="column.type === 'text'">
                                            <span class="table-res-text" :key="`text-${rowIndex}${columnIndex}`">
                                                {{ row[column.key] }}
                                            </span>
                                        </template>

                                        <!-- Link Type Column -->
                                        <template v-if="column.type === 'link'">
                                            <a :key="`link-${rowIndex}${columnIndex}`"
                                                :href="getAppUrl(column.modifier(row[column.key], row))">
                                                {{ row[column.key] }}
                                            </a>
                                        </template>

                                        <!-- Object Type Column -->
                                        <template v-else-if="column.type === 'object'">
                                            <span class="table-res-text" :key="`object-${rowIndex}${columnIndex}`">
                                                {{ column.modifier(row[column.key], row) }}
                                            </span>
                                        </template>

                                        <!-- Custom Component Type Column -->
                                        <template v-else-if="column.type === 'component'">
                                            <component :key="`component-${rowIndex}${columnIndex}`"
                                                :is="column.componentName" :value="row[column.key]" :row-data="row"
                                                :table-id="id" :index="columnIndex" />
                                        </template>

                                        <!-- Custom HTML Type Column -->
                                        <span v-else-if="column.type === 'custom-html'"
                                            :key="`custom-html-${rowIndex}${columnIndex}`"
                                            v-html="column.modifier(row[column.key], row)">
                                        </span>

                                        <!-- Custom Class Type Column -->
                                        <template v-else-if="column.type === 'custom-class'">
                                            <span class="table-res-text" :key="'custom-class-' + rowIndex + columnIndex"
                                                :class="column.modifier(row[column.key], row)">
                                                {{ row[column.key] }}
                                            </span>
                                        </template>

                                        <!-- Button Type Column -->
                                        <template v-else-if="column.type === 'button'">
                                            <button :key="'column-button-' + rowIndex + columnIndex"
                                                v-if="column.modifier(row[column.key], row) !== false"
                                                :class="[column.className ? column.className : 'btn btn-default']"
                                                @click="getButtonValue(row, column)">
                                                {{ column.label }}
                                                {{ column.modifier(row[column.key], row) }}
                                            </button>
                                        </template>

                                        <!-- Action Type Column -->
                                        <template v-else-if="column.type === 'action'">
                                            <dropdown-action :key="'column-action-' + rowIndex + columnIndex"
                                                v-if="options.actionType === 'dropdown'" :actions="options.actions"
                                                :row-data="row" @action="bothAction" />

                                        </template>
                                    </td>

                                </template>
                            </tr>
                        </template>

                        <template v-if="isAnyAccountableColumns && dataSetList?.data?.length">
                            <!-- Mobile view (≤ 991px) -->
                            <template v-if="isMobile">
                                <tr>
                                    <td class="datatable-td">
                                        <p class="fw-bold mb-0 text-start">{{ $t('total') }}</p>
                                    </td>
                                    <td v-for="(column, columnIndex) in IsAccountableColumnsFilter.filter(col => col.accountAble)"
                                        :key="'total-column' + columnIndex" class="datatable-td"
                                        :data-title="column.title">
                                        <span class="fw-bold">
                                            {{ numberWithCurrencySymbol(columnTotal(column)) }}
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="datatable-td">
                                        <p class="fw-bold mb-0 text-start">{{ $t('grand_total') }}</p>
                                    </td>
                                    <td v-for="(column, columnIndex) in IsAccountableColumnsFilter.filter(col => col.accountAble)"
                                        :key="'grand-total-column' + columnIndex" class="datatable-td"
                                        :data-title="column.title">
                                        <span class="fw-bold">
                                            {{ numberWithCurrencySymbol(grandTotal[column.key]) }}
                                        </span>
                                    </td>
                                </tr>
                            </template>

                            <!--  Desktop view (> 991px) -->
                            <template v-else>
                                <tr :class="[options.hideTotal ? 'd-none' : '']">
                                    <td v-if="options.enableRowSelect"></td>
                                    <td v-for="(column, columnIndex) in IsAccountableColumnsFilter"
                                        :key="'total-column' + columnIndex" class="datatable-td"
                                        :data-title="column.title">
                                        <template v-if="column.accountAble">
                                            <span class="fw-bold">
                                                {{ column.isCount ? columnTotal(column) :
                                                    numberWithCurrencySymbol(columnTotal(column)) }}
                                            </span>
                                        </template>
                                        <template v-else>
                                            <span v-if="showCustomLabel(columnIndex)" class="fw-bold text-left">
                                                {{ $t('total') }}
                                            </span>
                                        </template>
                                    </td>
                                </tr>
                                <tr>
                                    <td v-if="options.enableRowSelect"></td>
                                    <td v-for="(column, columnIndex) in IsAccountableColumnsFilter"
                                        :key="'grand-total-column' + columnIndex" class="datatable-td"
                                        :data-title="column.title">
                                        <template v-if="column.accountAble">
                                            <span class="fw-bold">
                                                {{ column.isCount ? grandTotal[column.key] :
                                                    numberWithCurrencySymbol(grandTotal[column.key]) }}
                                            </span>
                                        </template>
                                        <template v-else>
                                            <span v-if="showCustomLabel(columnIndex)" class="fw-bold text-left">
                                                {{ $t('grand_total') }}
                                            </span>
                                        </template>
                                    </td>
                                </tr>
                            </template>
                        </template>

                    </tbody>
                </table>
                <empty-message v-if="!loading && dataSetList.data && dataSetList.data.length === 0" />
            </div>
            <div class="footer-paginate d-flex gap-3 justify-content-center justify-content-lg-between align-items-center"
                v-if="totalRow > options.rowLimit && dataSetList.data.length && options.showPagination !== false">
                <div class="text-center text-sm-start">
                    {{ $t("showing") }} {{ dataSetList.from }} {{ $t("to") }}
                    {{ dataSetList.to }} {{ $t("of") }} {{ totalRow }}
                    {{ $t("entries") }}
                </div>
                <div class="right-portion text-nowrap gap-3 mt-md-0">
                    <div class="pages-infos d-flex flex-wrap text-nowrap gap-3 justify-content-center">
                        <div class="items">
                            <label for="pageInput" class="me-2">{{ $t("show_per_page") }}:</label>
                            <select class="page-input" id="pageInput" @change="getByPerPage">
                                <option v-for="(perPage, i) in perPageValues" :value="perPage" :key="i">
                                    {{ perPage }}
                                </option>
                            </select>
                        </div>
                        <div class="items">
                            <label for="goToPage" class="me-2">{{ $t("go_to_page") }}:</label>
                            <input type="number" id="goToPage" class="page-input" @input="goToPage" />
                        </div>
                        <paginate :current-page="activePaginationPage" :total-pages="dataSetList.last_page"
                            @paginate="getPaginateData" />
                    </div>
                </div>
            </div>
        </div>

    </div>
</template>

<style scoped lang="scss">
.column-visibility-menu {
    min-width: 220px;
    max-height: 320px;
    overflow-y: auto;
}
</style>
