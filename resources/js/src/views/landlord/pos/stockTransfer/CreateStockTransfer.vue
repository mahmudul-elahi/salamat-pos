<script setup>
import { ref, watch, computed, nextTick } from 'vue';
import { Trash } from '@iconoir/vue';
import {
    STOCK_TRANSFERS,
    STOCK_TRANSFER_SEARCH_SERIAL,
    SELECTED_WAREHOUSES,
} from '@/services/endpoints/landlord.js';
import Axios from '@/services/axios/index.js';
import { useToast } from 'vue-toastification';
import router from '@/router/index.js';
import { serverDateFormat } from "@/utils/helper.js";

const toast = useToast();
const preloader = ref(false);
const errors = ref({});

const formData = ref({
    from_tenant_id: null,
    to_tenant_id: null,
    transfer_date: new Date(),
    note: '',
});

// Serial search
const searchQuery = ref('');
const searchResults = ref([]);
const searchLoading = ref(false);
const searchInput = ref(null);

const focusSearch = () => nextTick(() => searchInput.value?.focus());

const searchSerials = async () => {
    if (!searchQuery.value.trim() || !formData.value.from_tenant_id) {
        searchResults.value = [];
        return;
    }
    searchLoading.value = true;
    try {
        const { data } = await Axios.get(STOCK_TRANSFER_SEARCH_SERIAL, {
            params: {
                query: searchQuery.value,
                from_tenant_id: formData.value.from_tenant_id,
            },
        });
        const results = Array.isArray(data) ? data : [];
        if (results.length === 1) {
            addSerial(results[0]);
        } else {
            searchResults.value = results;
        }
    } catch {
        searchResults.value = [];
    } finally {
        searchLoading.value = false;
    }
};

watch(searchQuery, searchSerials);

// Clear items when from_tenant changes
watch(() => formData.value.from_tenant_id, () => {
    items.value = [];
    searchQuery.value = '';
    searchResults.value = [];
});

// Items list
const items = ref([]);

const addSerial = (serial) => {
    const exists = items.value.some(i => i.product_serial_id === serial.product_serial_id);
    if (exists) {
        toast.warning('This serial is already added.');
    } else {
        items.value.push({ ...serial });
    }
    searchQuery.value = '';
    searchResults.value = [];
    focusSearch();
};

const removeItem = (index) => items.value.splice(index, 1);

const totalItems = computed(() => items.value.length);

const submit = async () => {
    if (!formData.value.from_tenant_id || !formData.value.to_tenant_id) {
        toast.warning('Please select both From and To shops.');
        return;
    }
    if (items.value.length === 0) {
        toast.warning('Please add at least one serial.');
        return;
    }

    errors.value = {};
    preloader.value = true;
    try {
        const { data } = await Axios.post(STOCK_TRANSFERS, {
            ...formData.value,
            transfer_date: serverDateFormat(formData.value.transfer_date),
            items: items.value.map(i => ({
                product_serial_id: i.product_serial_id,
                product_id: i.product_id,
                product_variant_id: i.product_variant_id,
            })),
        });
        formData.value = {
            from_tenant_id: null,
            to_tenant_id: null,
            transfer_date: new Date(),
            note: '',
        }
        items.value = [];
        searchQuery.value = '';
        searchResults.value = [];
        toast.success('Stock transfer created successfully.');
    } catch (e) {
        if (e.response?.status === 422) {
            errors.value = e.response.data.errors;
        } else {
            toast.error(response?.data?.message ?? 'Failed to create transfer.');
        }
    } finally {
        preloader.value = false;
    }
};
</script>

<template>
    <div class="content-wrapper">
        <app-breadcrumb page-title="Create Stock Transfer" />

        <div class="card">
            <div class="card-body">

                <!-- Header row -->
                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <app-input type="advance-search-select" id="from_tenant" label="From Shop *"
                            list-value-field="name" v-model="formData.from_tenant_id" :loading-length="10"
                            :fetch-url="SELECTED_WAREHOUSES" choose-label="Select source shop"
                            :errors="$errors(errors, 'from_tenant_id')" />
                    </div>
                    <div class="col-md-3">
                        <app-input type="advance-search-select" id="to_tenant" label="To Shop *" list-value-field="name"
                            v-model="formData.to_tenant_id" :loading-length="10" :fetch-url="SELECTED_WAREHOUSES"
                            choose-label="Select destination shop" :errors="$errors(errors, 'to_tenant_id')" />
                    </div>
                    <div class="col-md-3">
                        <app-input type="date" id="transfer_date" label="Transfer Date *"
                            v-model="formData.transfer_date" :errors="$errors(errors, 'transfer_date')"
                            :enable-time-picker="false" auto-apply />
                    </div>
                    <div class="col-md-3">
                        <app-input type="textarea" id="note" label="Note" v-model="formData.note"
                            :errors="$errors(errors, 'note')" />
                    </div>
                </div>

                <!-- Serial search -->
                <div class="mb-3">
                    <div style="font-size:0.8rem;font-weight:600;color:#495057;margin-bottom:6px;">
                        Search by Serial Number
                        <span v-if="!formData.from_tenant_id"
                            style="font-weight:400;color:#adb5bd;margin-left:6px;">(Select source shop first)</span>
                    </div>
                    <div class="position-relative" style="max-width:480px;">
                        <div class="d-flex align-items-center gap-2 px-3">
                            <!-- <Search style="width:16px;height:16px;color:#adb5bd;flex-shrink:0;" /> -->
                            <app-input ref="searchInput" id="search" v-model="searchQuery" type="text"
                                placeholder="Type serial number..." :disabled="!formData.from_tenant_id" />
                        </div>

                        <!-- Dropdown results -->
                        <div v-if="searchResults.length"
                            style="position:absolute;top:44px;left:0;right:0;background:#fff;border:1px solid #dee2e6;border-radius:8px;z-index:100;box-shadow:0 4px 16px rgba(0,0,0,0.1);max-height:300px;overflow-y:auto;">
                            <div v-for="s in searchResults" :key="s.product_serial_id"
                                class="d-flex align-items-center justify-content-between px-3 py-2"
                                style="cursor:pointer;border-bottom:1px solid #f1f3f5;font-size:0.82rem;transition:background 0.1s;"
                                @mouseenter="$event.currentTarget.style.background = '#f8f9fa'"
                                @mouseleave="$event.currentTarget.style.background = 'transparent'"
                                @click="addSerial(s)">
                                <div>
                                    <div style="font-weight:700;color:#212529;font-family:monospace;font-size:0.88rem;">
                                        {{ s.serial_number }}
                                    </div>
                                    <div style="color:#868e96;font-size:0.75rem;margin-top:1px;">
                                        {{ s.product_name }}
                                        <span v-if="s.sku"> · {{ s.sku }}</span>
                                        <span v-if="s.attributes && s.attributes !== 'Default'"> · {{ s.attributes
                                        }}</span>
                                    </div>
                                </div>
                                <div v-if="s.max_retail_price"
                                    style="text-align:right;flex-shrink:0;margin-left:12px;font-size:0.78rem;color:#495057;">
                                    MRP: {{ s.max_retail_price }}
                                </div>
                            </div>
                        </div>

                        <div v-if="searchQuery && !searchLoading && searchResults.length === 0 && formData.from_tenant_id"
                            class="st-no-result">
                            No available serial found for "{{ searchQuery }}"
                        </div>
                    </div>
                </div>

                <!-- Items table -->
                <div v-if="items.length" class="mb-4">
                    <div class="st-section-title">
                        Transfer Serials ({{ totalItems }} item{{ totalItems > 1 ? 's' : '' }})
                    </div>
                    <div class="st-table-wrap">

                        <!-- Desktop table -->
                        <div class="table-responsive d-none d-md-block">
                            <table class="table table-sm mb-0 st-table">
                                <thead>
                                    <tr>
                                        <th class="st-th">#</th>
                                        <th class="st-th">Serial No.</th>
                                        <th class="st-th">Product</th>
                                        <th class="st-th">Variant</th>
                                        <th class="st-th"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(item, idx) in items" :key="idx" class="st-tr">
                                        <td class="st-td st-muted">{{ idx + 1 }}</td>
                                        <td class="st-td st-serial">{{ item.serial_number }}</td>
                                        <td class="st-td st-bold">{{ item.product_name }}</td>
                                        <td class="st-td">
                                            <span v-if="item.attributes && item.attributes !== 'Default'"
                                                class="st-badge">
                                                {{ item.attributes }}
                                            </span>
                                        </td>
                                        <td class="st-td">
                                            <button @click="removeItem(idx)" class="st-delete-btn">
                                                <Trash style="width:13px;height:13px;" />
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Mobile stacked cards -->
                        <div class="d-md-none">
                            <div v-for="(item, idx) in items" :key="idx" class="st-mobile-row">
                                <div class="st-mobile-field">
                                    <span class="st-mobile-label">#</span>
                                    <span class="st-muted">{{ idx + 1 }}</span>
                                </div>
                                <div class="st-mobile-field">
                                    <span class="st-mobile-label">Serial No.</span>
                                    <span class="st-serial">{{ item.serial_number }}</span>
                                </div>
                                <div class="st-mobile-field">
                                    <span class="st-mobile-label">Product</span>
                                    <span class="st-bold">{{ item.product_name }}</span>
                                </div>
                                <div v-if="item.attributes && item.attributes !== 'Default'" class="st-mobile-field">
                                    <span class="st-mobile-label">Variant</span>
                                    <span class="st-badge">{{ item.attributes }}</span>
                                </div>
                                <div class="d-flex justify-content-end mt-2">
                                    <button @click="removeItem(idx)" class="st-delete-btn">
                                        <Trash style="width:13px;height:13px;" />
                                    </button>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- Empty state -->
                <div v-else class="text-center py-5 text-muted mb-4">
                    <div style="font-size:2rem;">📦</div>
                    <div class="mt-2" style="font-size:0.88rem;">Search serial numbers to add items</div>
                </div>

                <!-- Footer buttons -->
                <div class="d-flex justify-content-end gap-2 mt-2">
                    <button class="btn btn-light btn-sm px-4"
                        @click="router.push({ name: 'stockTransfers' })">Cancel</button>
                    <button class="btn btn-primary btn-sm px-5" @click="submit" :disabled="preloader">
                        <span v-if="preloader" class="spinner-border spinner-border-sm me-1"></span>
                        Create Transfer
                    </button>
                </div>

            </div>
        </div>
    </div>
</template>

<style scoped>
.st-section-title {
    font-size: 0.8rem;
    font-weight: 600;
    color: var(--font-color);
    margin-bottom: 8px;
}

.st-table-wrap {
    border: 1px solid var(--border-color);
    border-radius: 10px;
    overflow: hidden;
}

.st-table {
    font-size: 0.83rem;
    --bs-table-bg: var(--white);
    --bs-table-border-color: var(--border-color);
    --bs-table-color: var(--font-color);
    --bs-table-striped-bg: var(--base-color);
    --bs-table-hover-bg: var(--base-color);
}

.st-th {
    font-size: 0.72rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.4px;
    color: var(--table-th-font-color);
    background-color: var(--base-color);
    padding: 10px 14px;
}

.st-tr {
    border-top: 1px solid var(--border-color);
    transition: background 0.1s;
}

.st-tr:hover td {
    background-color: var(--base-color) !important;
}

.st-td {
    padding: 10px 14px;
    color: var(--font-color);
    background-color: var(--white);
}

.st-muted {
    color: var(--table-th-font-color);
}

.st-bold {
    font-weight: 600;
}

.st-serial {
    font-family: monospace;
    font-weight: 700;
    color: #1c7ed6;
    font-size: 0.88rem;
}

.st-badge {
    padding: 1px 8px;
    border-radius: 12px;
    background: var(--base-color);
    color: var(--font-color);
    font-size: 0.73rem;
}

.st-no-result {
    position: absolute;
    top: 44px;
    left: 0;
    right: 0;
    background: var(--white);
    border: 1px solid var(--border-color);
    border-radius: 8px;
    z-index: 100;
    padding: 14px 16px;
    font-size: 0.83rem;
    color: var(--table-th-font-color);
}

.st-delete-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 28px;
    height: 28px;
    border-radius: 6px;
    background: #fff0f0;
    border: 1px solid #ffd0d0;
    color: #e03131;
    cursor: pointer;
}

.st-delete-btn:hover {
    background: #ffe3e3;
}

.st-mobile-row {
    padding: 12px 16px;
    border-top: 1px solid var(--border-color);
    background-color: var(--white);
    font-size: 0.83rem;
}

.st-mobile-field {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 4px 0;
}

.st-mobile-label {
    font-weight: 600;
    color: var(--table-th-font-color);
    flex-shrink: 0;
    margin-right: 12px;
}
</style>
