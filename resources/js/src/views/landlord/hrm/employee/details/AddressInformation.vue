<script setup>
import { ref, watch, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import Axios from '@/services/axios/index.js';
import { HRM_EMPLOYEES } from '@/services/endpoints/hrm.js';
import {
    SELECTED_COUNTRIES,
    SELECTED_DISTRICTS,
    SELECTED_AREAS,
} from '@/services/endpoints/landlord.js';
import { useToast } from 'vue-toastification';
import { Home, MapPin, Globe, City, EditPencil, Check } from '@iconoir/vue';

const route      = useRoute();
const toast      = useToast();
const employeeId = route.params.id;

const loading   = ref(true);
const showModal = ref(false);
const saving    = ref(false);
const errors    = ref({});
const sameAsResidential = ref(false);

const empty = () => ({
    residential_address:     null,
    residential_country:     null,
    residential_country_id:  null,
    residential_district:    null,
    residential_district_id: null,
    residential_area_id:     null,
    residential_thana:       null,
    residential_postal_code: null,
    permanent_address:       null,
    permanent_country:       null,
    permanent_country_id:    null,
    permanent_district:      null,
    permanent_district_id:   null,
    permanent_area_id:       null,
    permanent_thana:         null,
    permanent_postal_code:   null,
});

const address = ref(empty());
const form    = ref(empty());

// Dynamic area fetch URLs
const resAreaFetchUrl = ref(null);
const perAreaFetchUrl = ref(null);

/* ── Fetch ───────────────────────────────────── */
onMounted(async () => {
    try {
        const { data } = await Axios.get(`${HRM_EMPLOYEES}/${employeeId}/contacts/address`);
        address.value = { ...empty(), ...data };
    } catch {
        toast.error('Failed to load address info.');
    } finally {
        loading.value = false;
    }
});

/* ── Modal ───────────────────────────────────── */
const openModal = async () => {
    form.value              = { ...address.value };
    errors.value            = {};
    sameAsResidential.value = false;

    // Restore area fetch URLs for edit mode
    if (form.value.residential_district_id) {
        resAreaFetchUrl.value = SELECTED_AREAS(form.value.residential_district_id);
    }
    if (form.value.permanent_district_id) {
        perAreaFetchUrl.value = SELECTED_AREAS(form.value.permanent_district_id);
    }

    showModal.value = true;
};
const closeModal = () => { showModal.value = false; };

/* ── District / Area cascade — Residential ── */
const onResDistrictChange = (event) => {
    form.value.residential_district    = event?.name ?? null;
    form.value.residential_district_id = event?.id   ?? null;
    form.value.residential_area_id     = null;
    form.value.residential_thana       = null;
    form.value.residential_postal_code = null;
    resAreaFetchUrl.value = event?.id ? SELECTED_AREAS(event.id) : null;
};

const onResAreaChange = (event) => {
    if (event) {
        form.value.residential_thana       = event.thana    ?? null;
        form.value.residential_postal_code = event.postcode ?? null;
    }
};

const onResCountryChange = (event) => {
    form.value.residential_country = event?.name ?? null;
};

/* ── District / Area cascade — Permanent ── */
const onPerDistrictChange = (event) => {
    form.value.permanent_district    = event?.name ?? null;
    form.value.permanent_district_id = event?.id   ?? null;
    form.value.permanent_area_id     = null;
    form.value.permanent_thana       = null;
    form.value.permanent_postal_code = null;
    perAreaFetchUrl.value = event?.id ? SELECTED_AREAS(event.id) : null;
};

const onPerAreaChange = (event) => {
    if (event) {
        form.value.permanent_thana       = event.thana    ?? null;
        form.value.permanent_postal_code = event.postcode ?? null;
    }
};

const onPerCountryChange = (event) => {
    form.value.permanent_country = event?.name ?? null;
};

/* ── Same-as-residential toggle ── */
watch(sameAsResidential, (val) => {
    if (val) {
        form.value.permanent_address     = form.value.residential_address;
        form.value.permanent_country     = form.value.residential_country;
        form.value.permanent_country_id  = form.value.residential_country_id;
        form.value.permanent_district    = form.value.residential_district;
        form.value.permanent_district_id = form.value.residential_district_id;
        form.value.permanent_area_id     = form.value.residential_area_id;
        form.value.permanent_thana       = form.value.residential_thana;
        form.value.permanent_postal_code = form.value.residential_postal_code;
        perAreaFetchUrl.value            = resAreaFetchUrl.value;
    }
});

/* ── Save ────────────────────────────────────── */
const save = async () => {
    errors.value = {};
    try {
        saving.value = true;
        const { data } = await Axios.post(
            `${HRM_EMPLOYEES}/${employeeId}/contacts/address`,
            form.value
        );
        toast.success(data.message);
        address.value   = { ...form.value };
        showModal.value = false;
    } catch ({ response }) {
        if (response?.status === 422) errors.value = response.data.errors ?? {};
        else toast.error(response?.data?.message ?? 'Something went wrong.');
    } finally {
        saving.value = false;
    }
};

/* ── Helpers ─────────────────────────────────── */
const hasData = (prefix) =>
    address.value[`${prefix}_address`] ||
    address.value[`${prefix}_country`] ||
    address.value[`${prefix}_district`] ||
    address.value[`${prefix}_thana`] ||
    address.value[`${prefix}_postal_code`];
</script>

<template>
    <div>
        <!-- Page header -->
        <div class="ai-page-header">
            <div>
                <h6 class="ai-page-title">Address Information</h6>
                <p class="ai-page-sub">Manage residential and permanent address details</p>
            </div>
            <button class="ai-edit-btn" @click="openModal">
                <EditPencil /> Edit Address
            </button>
        </div>

        <app-loader v-if="loading" />

        <div v-else class="row g-4 align-items-stretch">

            <!-- ── Residential Address card ── -->
            <div class="col-12 col-lg-6 d-flex">
                <div class="ai-card ai-card--residential w-100">
                    <div class="ai-card-bar ai-card-bar--green"></div>

                    <div class="ai-card-header">
                        <div class="ai-avatar ai-avatar--green">
                            <Home />
                        </div>
                        <div>
                            <p class="ai-card-title">Residential Address</p>
                            <p class="ai-card-subtitle">Current living address</p>
                        </div>
                        <span v-if="hasData('residential')" class="ai-filled-badge ai-filled-badge--green">Filled</span>
                        <span v-else class="ai-filled-badge ai-filled-badge--empty">Empty</span>
                    </div>

                    <div v-if="hasData('residential')" class="ai-fields">
                        <div v-if="address.residential_address" class="ai-field-row">
                            <div class="ai-field-icon ai-field-icon--green"><MapPin /></div>
                            <div class="ai-field-body">
                                <span class="ai-field-label">Address</span>
                                <span class="ai-field-value">{{ address.residential_address }}</span>
                            </div>
                        </div>
                        <div v-if="address.residential_country" class="ai-field-row">
                            <div class="ai-field-icon ai-field-icon--blue"><Globe /></div>
                            <div class="ai-field-body">
                                <span class="ai-field-label">Country</span>
                                <span class="ai-field-value">{{ address.residential_country }}</span>
                            </div>
                        </div>
                        <div v-if="address.residential_district" class="ai-field-row">
                            <div class="ai-field-icon ai-field-icon--orange"><MapPin /></div>
                            <div class="ai-field-body">
                                <span class="ai-field-label">District</span>
                                <span class="ai-field-value">{{ address.residential_district }}</span>
                            </div>
                        </div>
                        <div v-if="address.residential_thana" class="ai-field-row">
                            <div class="ai-field-icon ai-field-icon--violet"><City /></div>
                            <div class="ai-field-body">
                                <span class="ai-field-label">Thana</span>
                                <span class="ai-field-value">{{ address.residential_thana }}</span>
                            </div>
                        </div>
                        <div v-if="address.residential_postal_code" class="ai-field-row ai-field-row--last">
                            <div class="ai-field-icon ai-field-icon--violet"><City /></div>
                            <div class="ai-field-body">
                                <span class="ai-field-label">Postal Code</span>
                                <span class="ai-field-value">{{ address.residential_postal_code }}</span>
                            </div>
                        </div>
                    </div>

                    <div v-else class="ai-empty">
                        <Home class="ai-empty-icon" />
                        <p>No residential address added yet.</p>
                    </div>
                </div>
            </div>

            <!-- ── Permanent Address card ── -->
            <div class="col-12 col-lg-6 d-flex">
                <div class="ai-card ai-card--permanent w-100">
                    <div class="ai-card-bar ai-card-bar--indigo"></div>

                    <div class="ai-card-header">
                        <div class="ai-avatar ai-avatar--indigo">
                            <MapPin />
                        </div>
                        <div>
                            <p class="ai-card-title">Permanent Address</p>
                            <p class="ai-card-subtitle">Official registered address</p>
                        </div>
                        <span v-if="hasData('permanent')" class="ai-filled-badge ai-filled-badge--indigo">Filled</span>
                        <span v-else class="ai-filled-badge ai-filled-badge--empty">Empty</span>
                    </div>

                    <div v-if="hasData('permanent')" class="ai-fields">
                        <div v-if="address.permanent_address" class="ai-field-row">
                            <div class="ai-field-icon ai-field-icon--indigo"><MapPin /></div>
                            <div class="ai-field-body">
                                <span class="ai-field-label">Address</span>
                                <span class="ai-field-value">{{ address.permanent_address }}</span>
                            </div>
                        </div>
                        <div v-if="address.permanent_country" class="ai-field-row">
                            <div class="ai-field-icon ai-field-icon--blue"><Globe /></div>
                            <div class="ai-field-body">
                                <span class="ai-field-label">Country</span>
                                <span class="ai-field-value">{{ address.permanent_country }}</span>
                            </div>
                        </div>
                        <div v-if="address.permanent_district" class="ai-field-row">
                            <div class="ai-field-icon ai-field-icon--orange"><MapPin /></div>
                            <div class="ai-field-body">
                                <span class="ai-field-label">District</span>
                                <span class="ai-field-value">{{ address.permanent_district }}</span>
                            </div>
                        </div>
                        <div v-if="address.permanent_thana" class="ai-field-row">
                            <div class="ai-field-icon ai-field-icon--violet"><City /></div>
                            <div class="ai-field-body">
                                <span class="ai-field-label">Thana</span>
                                <span class="ai-field-value">{{ address.permanent_thana }}</span>
                            </div>
                        </div>
                        <div v-if="address.permanent_postal_code" class="ai-field-row ai-field-row--last">
                            <div class="ai-field-icon ai-field-icon--violet"><City /></div>
                            <div class="ai-field-body">
                                <span class="ai-field-label">Postal Code</span>
                                <span class="ai-field-value">{{ address.permanent_postal_code }}</span>
                            </div>
                        </div>
                    </div>

                    <div v-else class="ai-empty">
                        <MapPin class="ai-empty-icon" />
                        <p>No permanent address added yet.</p>
                    </div>
                </div>
            </div>

        </div>

        <!-- ── Edit Modal ── -->
        <app-modal
            v-if="showModal"
            modal-id="employee-address-modal"
            title="Edit Address Information"
            modal-size="large"
            :scrollable="true"
            :preloader="saving"
            save-button-label="Save Changes"
            @submit="save"
            @close="closeModal"
        >
            <template #body>
                <div class="row g-4">

                    <!-- ── Residential section ── -->
                    <div class="col-12">
                        <div class="ai-modal-section-head ai-modal-section-head--green">
                            <div class="ai-modal-section-icon ai-modal-section-icon--green"><Home /></div>
                            <div>
                                <p class="ai-modal-section-title">Residential Address</p>
                                <p class="ai-modal-section-sub">Current living address</p>
                            </div>
                        </div>
                    </div>

                    <!-- Country -->
                    <div class="col-md-6">
                        <app-input type="advance-search-select" id="res_country_id"
                            label="Country" list-value-field="name" list-key-field="id"
                            :loading-length="10" :fetch-url="SELECTED_COUNTRIES"
                            choose-label="Choose a Country"
                            v-model="form.residential_country_id"
                            :errors="$errors(errors, 'residential_country_id')"
                            @searchSelect="onResCountryChange($event)" />
                    </div>

                    <!-- District -->
                    <div class="col-md-6">
                        <app-input type="advance-search-select" id="res_district_id"
                            label="District" list-value-field="name" list-key-field="id"
                            :loading-length="10" :fetch-url="SELECTED_DISTRICTS"
                            choose-label="Select District"
                            v-model="form.residential_district_id"
                            :errors="$errors(errors, 'residential_district_id')"
                            @searchSelect="onResDistrictChange($event)" />
                    </div>

                    <!-- Area -->
                    <div class="col-md-6" :style="!form.residential_district_id ? 'pointer-events:none;opacity:.55' : ''">
                        <app-input type="advance-search-select" id="res_area_id"
                            label="Area" list-value-field="name" list-key-field="id"
                            :loading-length="10" :fetch-url="resAreaFetchUrl"
                            choose-label="Select Area" :selected-disable="false"
                            v-model="form.residential_area_id"
                            :errors="$errors(errors, 'residential_area_id')"
                            @searchSelect="onResAreaChange($event)" />
                    </div>

                    <!-- Thana (auto) -->
                    <div class="col-md-6">
                        <app-input id="res_thana" type="text" label="Thana" readonly
                            placeholder="Auto-filled from Area"
                            v-model="form.residential_thana"
                            :errors="$errors(errors, 'residential_thana')" />
                    </div>

                    <!-- Postal Code (auto) -->
                    <div class="col-md-6">
                        <app-input id="res_postal" type="text" label="Postal Code" readonly
                            placeholder="Auto-filled from Area"
                            v-model="form.residential_postal_code"
                            :errors="$errors(errors, 'residential_postal_code')" />
                    </div>

                    <!-- Street Address -->
                    <div class="col-md-6">
                        <label class="form-label">Street Address</label>
                        <textarea v-model="form.residential_address"
                            class="form-control ai-textarea" rows="3"
                            placeholder="Enter full residential address"></textarea>
                    </div>

                    <!-- ── Permanent section ── -->
                    <div class="col-12 mt-2">
                        <div class="ai-modal-section-head ai-modal-section-head--indigo">
                            <div class="ai-modal-section-icon ai-modal-section-icon--indigo"><MapPin /></div>
                            <div class="flex-grow-1">
                                <p class="ai-modal-section-title">Permanent Address</p>
                                <p class="ai-modal-section-sub">Official registered address</p>
                            </div>
                            <label class="ai-same-toggle">
                                <div class="ai-toggle-track" :class="{ 'ai-toggle-track--on': sameAsResidential }"
                                    @click="sameAsResidential = !sameAsResidential">
                                    <div class="ai-toggle-thumb">
                                        <Check v-if="sameAsResidential" />
                                    </div>
                                </div>
                                <span>Same as Residential</span>
                            </label>
                        </div>
                    </div>

                    <div :style="sameAsResidential ? 'pointer-events:none;opacity:.6' : ''"
                        class="col-12">
                        <div class="row g-4">

                            <!-- Country -->
                            <div class="col-md-6">
                                <app-input type="advance-search-select" id="per_country_id"
                                    label="Country" list-value-field="name" list-key-field="id"
                                    :loading-length="10" :fetch-url="SELECTED_COUNTRIES"
                                    choose-label="Choose a Country"
                                    v-model="form.permanent_country_id"
                                    :errors="$errors(errors, 'permanent_country_id')"
                                    @searchSelect="onPerCountryChange($event)" />
                            </div>

                            <!-- District -->
                            <div class="col-md-6">
                                <app-input type="advance-search-select" id="per_district_id"
                                    label="District" list-value-field="name" list-key-field="id"
                                    :loading-length="10" :fetch-url="SELECTED_DISTRICTS"
                                    choose-label="Select District"
                                    v-model="form.permanent_district_id"
                                    :errors="$errors(errors, 'permanent_district_id')"
                                    @searchSelect="onPerDistrictChange($event)" />
                            </div>

                            <!-- Area -->
                            <div class="col-md-6"
                                :style="!form.permanent_district_id ? 'pointer-events:none;opacity:.55' : ''">
                                <app-input type="advance-search-select" id="per_area_id"
                                    label="Area" list-value-field="name" list-key-field="id"
                                    :loading-length="10" :fetch-url="perAreaFetchUrl"
                                    choose-label="Select Area" :selected-disable="false"
                                    v-model="form.permanent_area_id"
                                    :errors="$errors(errors, 'permanent_area_id')"
                                    @searchSelect="onPerAreaChange($event)" />
                            </div>

                            <!-- Thana (auto) -->
                            <div class="col-md-6">
                                <app-input id="per_thana" type="text" label="Thana" readonly
                                    placeholder="Auto-filled from Area"
                                    v-model="form.permanent_thana"
                                    :errors="$errors(errors, 'permanent_thana')" />
                            </div>

                            <!-- Postal Code (auto) -->
                            <div class="col-md-6">
                                <app-input id="per_postal" type="text" label="Postal Code" readonly
                                    placeholder="Auto-filled from Area"
                                    v-model="form.permanent_postal_code"
                                    :errors="$errors(errors, 'permanent_postal_code')" />
                            </div>

                            <!-- Street Address -->
                            <div class="col-md-6">
                                <label class="form-label">Street Address</label>
                                <textarea v-model="form.permanent_address"
                                    class="form-control ai-textarea" rows="3"
                                    placeholder="Enter full permanent address"></textarea>
                            </div>

                        </div>
                    </div>

                </div>
            </template>
        </app-modal>
    </div>
</template>

<style scoped lang="scss">
/* ── Page header ──────────────────────────────── */
.ai-page-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding-bottom: 20px;
    border-bottom: 1px solid var(--border-color);
    margin-bottom: 28px;
    gap: 16px;
}
.ai-page-title {
    font-size: 15px;
    font-weight: 700;
    color: var(--font-color);
    margin: 0 0 4px;
}
.ai-page-sub {
    font-size: 13px;
    color: var(--secondary-font-color, #6b7280);
    margin: 0;
}
.ai-edit-btn {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    padding: 9px 18px;
    background: var(--primary);
    color: #fff;
    border: none;
    border-radius: 10px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    white-space: nowrap;
    box-shadow: 0 4px 12px rgba(43,135,104,0.25);
    transition: opacity 0.15s, box-shadow 0.15s;
    svg { width: 15px; height: 15px; }
    &:hover { opacity: 0.9; box-shadow: 0 6px 18px rgba(43,135,104,0.35); }
}

/* ── Cards ────────────────────────────────────── */
.ai-card {
    background: var(--white);
    border: 1px solid var(--border-color);
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.04);
    transition: box-shadow 0.2s, transform 0.2s;
    display: flex;
    flex-direction: column;
    &:hover {
        box-shadow: 0 8px 24px rgba(0,0,0,0.08);
        transform: translateY(-2px);
    }
}

.ai-card-bar {
    height: 4px;
    &--green  { background: linear-gradient(90deg, #22c55e 0%, rgba(34,197,94,0.25) 100%); }
    &--indigo { background: linear-gradient(90deg, #6366f1 0%, rgba(99,102,241,0.25) 100%); }
}

.ai-card-header {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 20px 22px 16px;
    border-bottom: 1px solid var(--border-color);
}

.ai-avatar {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    svg { width: 22px; height: 22px; }

    &--green {
        background: linear-gradient(135deg, rgba(34,197,94,0.12) 0%, rgba(34,197,94,0.22) 100%);
        box-shadow: 0 0 0 6px rgba(34,197,94,0.06);
        svg { color: #16a34a; }
    }
    &--indigo {
        background: linear-gradient(135deg, rgba(99,102,241,0.12) 0%, rgba(99,102,241,0.22) 100%);
        box-shadow: 0 0 0 6px rgba(99,102,241,0.06);
        svg { color: #6366f1; }
    }
}

.ai-card-title {
    font-size: 14px;
    font-weight: 700;
    color: var(--font-color);
    margin: 0 0 3px;
    line-height: 1.2;
}
.ai-card-subtitle {
    font-size: 12px;
    color: var(--secondary-font-color, #6b7280);
    margin: 0;
}

.ai-filled-badge {
    margin-left: auto;
    flex-shrink: 0;
    font-size: 11px;
    font-weight: 700;
    padding: 3px 10px;
    border-radius: 20px;
    text-transform: uppercase;
    letter-spacing: 0.4px;

    &--green  { background: rgba(34,197,94,0.10);  color: #16a34a; border: 1px solid rgba(34,197,94,0.2); }
    &--indigo { background: rgba(99,102,241,0.10); color: #6366f1; border: 1px solid rgba(99,102,241,0.2); }
    &--empty  { background: var(--dashboard-bg); color: var(--secondary-font-color, #6b7280); border: 1px solid var(--border-color); }
}

/* ── Field rows ───────────────────────────────── */
.ai-fields {
    padding: 6px 0;
    flex: 1;
}

.ai-field-row {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 13px 22px;
    border-bottom: 1px solid var(--border-color);
    transition: background 0.15s;
    &:hover { background: var(--dashboard-bg); }
    &--last  { border-bottom: none; }
}

.ai-field-icon {
    width: 30px;
    height: 30px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    margin-top: 2px;
    svg { width: 14px; height: 14px; }

    &--green  { background: rgba(34,197,94,0.10);  svg { color: #16a34a; } }
    &--blue   { background: rgba(59,130,246,0.10); svg { color: #3b82f6; } }
    &--indigo { background: rgba(99,102,241,0.10); svg { color: #6366f1; } }
    &--violet { background: rgba(139,92,246,0.10); svg { color: #7c3aed; } }
    &--orange { background: rgba(249,115,22,0.10); svg { color: #ea580c; } }
}

.ai-field-body {
    display: flex;
    flex-direction: column;
    gap: 3px;
    flex: 1;
}

.ai-field-label {
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: var(--secondary-font-color, #6b7280);
}
.ai-field-value {
    font-size: 13px;
    font-weight: 500;
    color: var(--font-color);
    line-height: 1.5;
    white-space: pre-line;
}

/* ── Empty state ──────────────────────────────── */
.ai-empty {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 40px 20px;
    text-align: center;
    color: var(--secondary-font-color, #6b7280);
    p { font-size: 13px; margin: 0; }
}
.ai-empty-icon {
    width: 40px;
    height: 40px;
    color: var(--border-color);
    margin-bottom: 10px;
    stroke-width: 1.2;
}

/* ── Modal section headers ────────────────────── */
.ai-modal-section-head {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px 16px;
    border-radius: 12px;
    border-left: 3px solid transparent;

    &--green  { background: rgba(34,197,94,0.05);  border-left-color: #22c55e; }
    &--indigo { background: rgba(99,102,241,0.05); border-left-color: #6366f1; }
}

.ai-modal-section-icon {
    width: 36px;
    height: 36px;
    border-radius: 9px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    svg { width: 17px; height: 17px; }

    &--green  { background: rgba(34,197,94,0.12);  svg { color: #16a34a; } }
    &--indigo { background: rgba(99,102,241,0.12); svg { color: #6366f1; } }
}

.ai-modal-section-title {
    font-size: 13px;
    font-weight: 700;
    color: var(--font-color);
    margin: 0 0 2px;
}
.ai-modal-section-sub {
    font-size: 11px;
    color: var(--secondary-font-color, #6b7280);
    margin: 0;
}

/* ── Same-as toggle ───────────────────────────── */
.ai-same-toggle {
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    font-size: 13px;
    font-weight: 500;
    color: var(--font-color);
    white-space: nowrap;
    margin-left: auto;
    user-select: none;
}

.ai-toggle-track {
    width: 36px;
    height: 20px;
    border-radius: 20px;
    background: var(--border-color);
    position: relative;
    cursor: pointer;
    transition: background 0.2s;
    flex-shrink: 0;
    &--on { background: var(--primary); }
}

.ai-toggle-thumb {
    position: absolute;
    top: 2px;
    left: 2px;
    width: 16px;
    height: 16px;
    border-radius: 50%;
    background: #fff;
    box-shadow: 0 1px 4px rgba(0,0,0,0.15);
    transition: transform 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;

    .ai-toggle-track--on & { transform: translateX(16px); }
    svg { width: 9px; height: 9px; color: var(--primary); }
}

/* ── Textarea ─────────────────────────────────── */
.ai-textarea {
    resize: vertical;
    min-height: 80px;
    border-radius: 10px;
    font-size: 13px;
}
</style>
