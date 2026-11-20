<script setup>
import { ref, watch, computed, nextTick } from 'vue';
import { Trash } from '@iconoir/vue';
import {
    DAMAGE_PRODUCTS,
    STOCK_TRANSFER_SEARCH_SERIAL,
} from '@/services/endpoints/landlord.js';
import Axios from '@/services/axios/index.js';
import { useToast } from 'vue-toastification';
import router from '@/router/index.js';
import { serverDateFormat } from "@/utils/helper.js";

const toast = useToast();
const preloader = ref(false);
const errors = ref({});

const formData = ref({
    tenant_id: null,
    damage_date: new Date(),
    note: '',
});

// Filled in once a scanned serial's shop is auto-detected. There's no shop
// dropdown on this page - the shop is only ever known by scanning a serial.
const shopOption = ref(null);

// Serial search
const searchQuery = ref('');
const searchResults = ref([]);
const searchLoading = ref(false);
const searchInput = ref(null);

// app-input (FormInput.vue) doesn't expose focus() itself - for a
// type="text" input its root element IS the <input>, so $el reaches it.
const focusSearch = () => nextTick(() => searchInput.value?.$el?.focus());

const searchSerials = async () => {
    if (!searchQuery.value.trim()) {
        searchResults.value = [];
        return;
    }
    searchLoading.value = true;
    try {
        // Shared with Stock Transfer's serial picker. When a shop is already
        // selected we scope the search to it (from_tenant_id); otherwise we
        // omit it so the backend searches across all shops and reports back
        // which shop holds each serial - that's what lets a barcode scan
        // auto-select the shop instead of requiring it to be picked first.
        const { data } = await Axios.get(STOCK_TRANSFER_SEARCH_SERIAL, {
            params: {
                query: searchQuery.value,
                from_tenant_id: formData.value.tenant_id || undefined,
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

// Items list
const items = ref([]);

const addSerial = (serial) => {
    const exists = items.value.some(i => i.product_serial_id === serial.product_serial_id);
    if (exists) {
        toast.warning('This serial is already added.');
        searchQuery.value = '';
        searchResults.value = [];
        focusSearch();
        return;
    }
    if (!formData.value.tenant_id && serial.tenant_id) {
        shopOption.value = { id: serial.tenant_id, name: serial.tenant_name };
        formData.value.tenant_id = serial.tenant_id;
    }
    items.value.push({ ...serial });
    searchQuery.value = '';
    searchResults.value = [];
    focusSearch();
};

const removeItem = (index) => items.value.splice(index, 1);

// Lets the user abandon the current shop/items and scan a serial from a
// different shop instead of being stuck once the first one is detected.
const resetShop = () => {
    formData.value.tenant_id = null;
    shopOption.value = null;
    items.value = [];
    searchQuery.value = '';
    searchResults.value = [];
    focusSearch();
};

const totalItems = computed(() => items.value.length);

const submit = async () => {
    if (!formData.value.tenant_id) {
        toast.warning('Please scan a serial number first.');
        return;
    }
    if (items.value.length === 0) {
        toast.warning('Please add at least one serial.');
        return;
    }

    errors.value = {};
    preloader.value = true;
    try {
        await Axios.post(DAMAGE_PRODUCTS, {
            ...formData.value,
            damage_date: serverDateFormat(formData.value.damage_date),
            items: items.value.map(i => ({
                product_serial_id: i.product_serial_id,
                product_id: i.product_id,
                product_variant_id: i.product_variant_id,
            })),
        });
        formData.value = {
            tenant_id: null,
            damage_date: new Date(),
            note: '',
        }
        shopOption.value = null;
        items.value = [];
        searchQuery.value = '';
        searchResults.value = [];
        toast.success('Damage product recorded successfully.');
        router.push({ name: 'damageProducts' });
    } catch (e) {
        if (e.response?.status === 422) {
            errors.value = e.response.data.errors;
        } else {
            toast.error(e.response?.data?.message ?? 'Failed to record damage.');
        }
    } finally {
        preloader.value = false;
    }
};
</script>

<template>
    <div class="content-wrapper">
        <app-breadcrumb page-title="Record Damage Product" />

        <div class="card">
            <div class="card-body">

                <!-- Header row -->
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <label class="form-label">Shop *</label>
                        <div class="d-flex align-items-center gap-2">
                            <span v-if="shopOption" class="dp-shop-text">{{ shopOption.name }}</span>
                            <span v-else class="dp-shop-text dp-muted">Scan a serial to detect shop</span>
                            <button v-if="shopOption" type="button" class="dp-shop-reset" @click="resetShop">
                                Change
                            </button>
                        </div>
                        <div v-if="$errors(errors, 'tenant_id')" class="text-danger" style="font-size:0.78rem;margin-top:2px;">
                            {{ $errors(errors, 'tenant_id') }}
                        </div>
                    </div>
                    <div class="col-md-4">
                        <app-input type="date" id="damage_date" label="Damage Date *"
                            v-model="formData.damage_date" :errors="$errors(errors, 'damage_date')"
                            :enable-time-picker="false" auto-apply />
                    </div>
                    <div class="col-md-4">
                        <app-input type="textarea" id="note" label="Note / Reason" v-model="formData.note"
                            :errors="$errors(errors, 'note')" />
                    </div>
                </div>

                <!-- Serial search -->
                <div class="mb-3">
                    <div style="font-size:0.8rem;font-weight:600;color:#495057;margin-bottom:6px;">
                        Search by Serial Number
                        <span v-if="!formData.tenant_id"
                            style="font-weight:400;color:#adb5bd;margin-left:6px;">(Shop will be auto-selected from the scan)</span>
                    </div>
                    <div class="position-relative" style="max-width:480px;">
                        <div class="d-flex align-items-center gap-2 px-3">
                            <app-input ref="searchInput" id="search" v-model="searchQuery" type="text"
                                placeholder="Scan or type serial number..." />
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
                                        <span v-if="!formData.tenant_id && s.tenant_name"> · {{ s.tenant_name }}</span>
                                    </div>
                                </div>
                                <div v-if="s.max_retail_price"
                                    style="text-align:right;flex-shrink:0;margin-left:12px;font-size:0.78rem;color:#495057;">
                                    MRP: {{ s.max_retail_price }}
                                </div>
                            </div>
                        </div>

                        <div v-if="searchQuery && !searchLoading && searchResults.length === 0"
                            class="dp-no-result">
                            No available serial found for "{{ searchQuery }}"
                        </div>
                    </div>
                </div>

                <!-- Items table -->
                <div v-if="items.length" class="mb-4">
                    <div class="dp-section-title">
                        Damaged Serials ({{ totalItems }} item{{ totalItems > 1 ? 's' : '' }})
                    </div>
                    <div class="dp-table-wrap">

                        <!-- Desktop table -->
                        <div class="table-responsive d-none d-md-block">
                            <table class="table table-sm mb-0 dp-table">
                                <thead>
                                    <tr>
                                        <th class="dp-th">#</th>
                                        <th class="dp-th">Serial No.</th>
                                        <th class="dp-th">Product</th>
                                        <th class="dp-th">Variant</th>
                                        <th class="dp-th"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(item, idx) in items" :key="idx" class="dp-tr">
                                        <td class="dp-td dp-muted">{{ idx + 1 }}</td>
                                        <td class="dp-td dp-serial">{{ item.serial_number }}</td>
                                        <td class="dp-td dp-bold">{{ item.product_name }}</td>
                                        <td class="dp-td">
                                            <span v-if="item.attributes && item.attributes !== 'Default'"
                                                class="dp-badge">
                                                {{ item.attributes }}
                                            </span>
                                        </td>
                                        <td class="dp-td">
                                            <button @click="removeItem(idx)" class="dp-delete-btn">
                                                <Trash style="width:13px;height:13px;" />
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Mobile stacked cards -->
                        <div class="d-md-none">
                            <div v-for="(item, idx) in items" :key="idx" class="dp-mobile-row">
                                <div class="dp-mobile-field">
                                    <span class="dp-mobile-label">#</span>
                                    <span class="dp-muted">{{ idx + 1 }}</span>
                                </div>
                                <div class="dp-mobile-field">
                                    <span class="dp-mobile-label">Serial No.</span>
                                    <span class="dp-serial">{{ item.serial_number }}</span>
                                </div>
                                <div class="dp-mobile-field">
                                    <span class="dp-mobile-label">Product</span>
                                    <span class="dp-bold">{{ item.product_name }}</span>
                                </div>
                                <div v-if="item.attributes && item.attributes !== 'Default'" class="dp-mobile-field">
                                    <span class="dp-mobile-label">Variant</span>
                                    <span class="dp-badge">{{ item.attributes }}</span>
                                </div>
                                <div class="d-flex justify-content-end mt-2">
                                    <button @click="removeItem(idx)" class="dp-delete-btn">
                                        <Trash style="width:13px;height:13px;" />
                                    </button>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- Empty state -->
                <div v-else class="text-center py-5 text-muted mb-4">
                    <div style="font-size:2rem;">🔨</div>
                    <div class="mt-2" style="font-size:0.88rem;">Search serial numbers to add damaged items</div>
                </div>

                <!-- Footer buttons -->
                <div class="d-flex justify-content-end gap-2 mt-2">
                    <button class="btn btn-light btn-sm px-4"
                        @click="router.push({ name: 'damageProducts' })">Cancel</button>
                    <button class="btn btn-primary btn-sm px-5" @click="submit" :disabled="preloader">
                        <span v-if="preloader" class="spinner-border spinner-border-sm me-1"></span>
                        Record Damage
                    </button>
                </div>

            </div>
        </div>
    </div>
</template>

<style scoped>
.dp-section-title {
    font-size: 0.8rem;
    font-weight: 600;
    color: var(--font-color);
    margin-bottom: 8px;
}

.dp-table-wrap {
    border: 1px solid var(--border-color);
    border-radius: 10px;
    overflow: hidden;
}

.dp-table {
    font-size: 0.83rem;
    --bs-table-bg: var(--white);
    --bs-table-border-color: var(--border-color);
    --bs-table-color: var(--font-color);
    --bs-table-striped-bg: var(--base-color);
    --bs-table-hover-bg: var(--base-color);
}

.dp-th {
    font-size: 0.72rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.4px;
    color: var(--table-th-font-color);
    background-color: var(--base-color);
    padding: 10px 14px;
}

.dp-tr {
    border-top: 1px solid var(--border-color);
    transition: background 0.1s;
}

.dp-tr:hover td {
    background-color: var(--base-color) !important;
}

.dp-td {
    padding: 10px 14px;
    color: var(--font-color);
    background-color: var(--white);
}

.dp-muted {
    color: var(--table-th-font-color);
}

.dp-bold {
    font-weight: 600;
}

.dp-serial {
    font-family: monospace;
    font-weight: 700;
    color: #1c7ed6;
    font-size: 0.88rem;
}

.dp-badge {
    padding: 1px 8px;
    border-radius: 12px;
    background: var(--base-color);
    color: var(--font-color);
    font-size: 0.73rem;
}

.dp-no-result {
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

.dp-delete-btn {
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

.dp-delete-btn:hover {
    background: #ffe3e3;
}

.dp-mobile-row {
    padding: 12px 16px;
    border-top: 1px solid var(--border-color);
    background-color: var(--white);
    font-size: 0.83rem;
}

.dp-mobile-field {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 4px 0;
}

.dp-mobile-label {
    font-weight: 600;
    color: var(--table-th-font-color);
    flex-shrink: 0;
    margin-right: 12px;
}

.dp-shop-text {
    font-size: 0.9rem;
    font-weight: 600;
    color: var(--font-color);
    padding: 6px 0;
}

.dp-shop-text.dp-muted {
    font-weight: 400;
    color: var(--table-th-font-color);
}

.dp-shop-reset {
    font-size: 0.76rem;
    font-weight: 600;
    color: #1c7ed6;
    background: none;
    border: none;
    padding: 0;
    cursor: pointer;
}

.dp-shop-reset:hover {
    text-decoration: underline;
}
</style>
