<script setup>
import { ref, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import Axios from '@/services/axios/index.js';
import { HRM_EMPLOYEES } from '@/services/endpoints/hrm.js';
import { useToast } from 'vue-toastification';
import { User, Phone, Mail, EditPencil, AtSign, Heart } from '@iconoir/vue';

const route      = useRoute();
const toast      = useToast();
const employeeId = route.params.id;

const loading   = ref(true);
const showModal = ref(false);
const saving    = ref(false);
const errors    = ref({});

const contacts = ref({
    contact_name: null, contact_email: null,
    emergency_name: null, emergency_relation: null,
    emergency_phone: null, emergency_email: null,
});
const form = ref({ ...contacts.value });

const fetchContacts = async () => {
    loading.value = true;
    try {
        const { data } = await Axios.get(`${HRM_EMPLOYEES}/${employeeId}/contacts`);
        contacts.value = data;
    } catch {
        toast.error('Failed to load contact information.');
    } finally {
        loading.value = false;
    }
};

onMounted(fetchContacts);

const openModal = () => {
    form.value   = { ...contacts.value };
    errors.value = {};
    showModal.value = true;
};
const closeModal = () => { showModal.value = false; };

const save = async () => {
    errors.value = {};
    try {
        saving.value = true;
        const { data } = await Axios.post(`${HRM_EMPLOYEES}/${employeeId}/contacts`, form.value);
        toast.success(data.message);
        contacts.value  = { ...form.value };
        showModal.value = false;
    } catch ({ response }) {
        if (response?.status === 422) errors.value = response.data.errors ?? {};
        else toast.error(response?.data?.message ?? 'Something went wrong.');
    } finally {
        saving.value = false;
    }
};
</script>

<template>
    <div>
        <!-- Section header -->
        <div class="ci-page-header">
            <div class="ci-page-header-text">
                <h6 class="ci-page-title">Contact Information</h6>
                <p class="ci-page-sub">Manage employee and emergency contact details</p>
            </div>
            <button class="ci-edit-btn" @click="openModal">
                <EditPencil />
                Edit Contacts
            </button>
        </div>

        <app-loader v-if="loading" />

        <div v-else class="row g-4 align-items-stretch">

            <!-- ── Person Contact card ── -->
            <div class="col-12 col-lg-6 d-flex">
                <div class="ci-card ci-card--person w-100">
                    <div class="ci-card-top"></div>
                    <div class="ci-card-header">
                        <div class="ci-avatar ci-avatar--person">
                            <User />
                        </div>
                        <div>
                            <p class="ci-card-title">Person Contact</p>
                            <p class="ci-card-subtitle">Primary contact information</p>
                        </div>
                    </div>

                    <div class="ci-rows">
                        <div class="ci-row">
                            <div class="ci-row-label">
                                <span class="ci-row-icon ci-row-icon--blue"><User /></span>
                                Full Name
                            </div>
                            <div class="ci-row-value">
                                <span v-if="contacts.contact_name" class="ci-value-text">{{ contacts.contact_name }}</span>
                                <span v-else class="ci-value-empty">Not provided</span>
                            </div>
                        </div>

                        <div class="ci-row">
                            <div class="ci-row-label">
                                <span class="ci-row-icon ci-row-icon--violet"><AtSign /></span>
                                Email Address
                            </div>
                            <div class="ci-row-value">
                                <a v-if="contacts.contact_email" :href="`mailto:${contacts.contact_email}`" class="ci-value-link ci-value-link--blue">
                                    <Mail />{{ contacts.contact_email }}
                                </a>
                                <span v-else class="ci-value-empty">Not provided</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ── Emergency Contact card ── -->
            <div class="col-12 col-lg-6 d-flex">
                <div class="ci-card ci-card--emergency w-100">
                    <div class="ci-card-top ci-card-top--emergency"></div>
                    <div class="ci-card-header">
                        <div class="ci-avatar ci-avatar--emergency">
                            <Heart />
                        </div>
                        <div>
                            <p class="ci-card-title">Emergency Contact</p>
                            <p class="ci-card-subtitle">Contact in case of emergency</p>
                        </div>
                    </div>

                    <div class="ci-rows">
                        <div class="ci-row">
                            <div class="ci-row-label">
                                <span class="ci-row-icon ci-row-icon--rose"><User /></span>
                                Full Name
                            </div>
                            <div class="ci-row-value">
                                <span v-if="contacts.emergency_name" class="ci-value-text">{{ contacts.emergency_name }}</span>
                                <span v-else class="ci-value-empty">Not provided</span>
                            </div>
                        </div>

                        <div class="ci-row">
                            <div class="ci-row-label">
                                <span class="ci-row-icon ci-row-icon--orange"><Heart /></span>
                                Relation
                            </div>
                            <div class="ci-row-value">
                                <span v-if="contacts.emergency_relation" class="ci-value-badge">{{ contacts.emergency_relation }}</span>
                                <span v-else class="ci-value-empty">Not provided</span>
                            </div>
                        </div>

                        <div class="ci-row">
                            <div class="ci-row-label">
                                <span class="ci-row-icon ci-row-icon--green"><Phone /></span>
                                Phone Number
                            </div>
                            <div class="ci-row-value">
                                <a v-if="contacts.emergency_phone" :href="`tel:${contacts.emergency_phone}`" class="ci-value-link ci-value-link--green">
                                    <Phone />{{ contacts.emergency_phone }}
                                </a>
                                <span v-else class="ci-value-empty">Not provided</span>
                            </div>
                        </div>

                        <div class="ci-row ci-row--last">
                            <div class="ci-row-label">
                                <span class="ci-row-icon ci-row-icon--violet"><AtSign /></span>
                                Email Address
                            </div>
                            <div class="ci-row-value">
                                <a v-if="contacts.emergency_email" :href="`mailto:${contacts.emergency_email}`" class="ci-value-link ci-value-link--blue">
                                    <Mail />{{ contacts.emergency_email }}
                                </a>
                                <span v-else class="ci-value-empty">Not provided</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- ── Edit Modal ── -->
        <app-modal
            v-if="showModal"
            modal-id="employee-contact-modal"
            title="Edit Contact Information"
            modal-size="large"
            :scrollable="true"
            :preloader="saving"
            save-button-label="Save Changes"
            @submit="save"
            @close="closeModal"
        >
            <template #body>
                <div class="row g-4">

                    <div class="col-12">
                        <div class="ci-modal-section">
                            <div class="ci-modal-section-dot ci-modal-section-dot--blue"></div>
                            <span class="ci-modal-section-title">Person Contact</span>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <app-input id="mc_contact_name" type="text" label="Full Name"
                            placeholder="Enter full name"
                            v-model="form.contact_name" :errors="$errors(errors, 'contact_name')" />
                    </div>
                    <div class="col-md-6">
                        <app-input id="mc_contact_email" type="email" label="Email Address"
                            placeholder="Enter email address"
                            v-model="form.contact_email" :errors="$errors(errors, 'contact_email')" />
                    </div>

                    <div class="col-12"><div class="ci-modal-divider"></div></div>

                    <div class="col-12">
                        <div class="ci-modal-section">
                            <div class="ci-modal-section-dot ci-modal-section-dot--rose"></div>
                            <span class="ci-modal-section-title">Emergency Contact</span>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <app-input id="mc_emergency_name" type="text" label="Full Name"
                            placeholder="Enter emergency contact name"
                            v-model="form.emergency_name" :errors="$errors(errors, 'emergency_name')" />
                    </div>
                    <div class="col-md-6">
                        <app-input id="mc_emergency_relation" type="text" label="Relation"
                            placeholder="e.g. Father, Spouse, Sibling"
                            v-model="form.emergency_relation" :errors="$errors(errors, 'emergency_relation')" />
                    </div>
                    <div class="col-md-6">
                        <app-input id="mc_emergency_phone" type="text" label="Phone Number"
                            placeholder="Enter phone number"
                            v-model="form.emergency_phone" :errors="$errors(errors, 'emergency_phone')" />
                    </div>
                    <div class="col-md-6">
                        <app-input id="mc_emergency_email" type="email" label="Email Address"
                            placeholder="Enter email address"
                            v-model="form.emergency_email" :errors="$errors(errors, 'emergency_email')" />
                    </div>

                </div>
            </template>
        </app-modal>
    </div>
</template>

<style scoped lang="scss">
/* ── Page header ──────────────────────────────── */
.ci-page-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding-bottom: 20px;
    border-bottom: 1px solid var(--border-color);
    margin-bottom: 28px;
    gap: 16px;
}
.ci-page-title {
    font-size: 15px;
    font-weight: 700;
    color: var(--font-color);
    margin: 0 0 4px;
}
.ci-page-sub {
    font-size: 13px;
    color: var(--secondary-font-color, #6b7280);
    margin: 0;
}
.ci-edit-btn {
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
    transition: opacity 0.15s, box-shadow 0.15s;
    box-shadow: 0 4px 12px rgba(43,135,104,0.25);
    svg { width: 15px; height: 15px; }
    &:hover { opacity: 0.9; box-shadow: 0 6px 18px rgba(43,135,104,0.35); }
}

/* ── Card ─────────────────────────────────────── */
.ci-card {
    background: var(--white);
    border: 1px solid var(--border-color);
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.04);
    transition: box-shadow 0.2s, transform 0.2s;
    &:hover {
        box-shadow: 0 8px 24px rgba(0,0,0,0.08);
        transform: translateY(-2px);
    }
}

/* gradient top bar */
.ci-card-top {
    height: 4px;
    background: linear-gradient(90deg, #3b82f6 0%, rgba(59,130,246,0.25) 100%);
}
.ci-card-top--emergency {
    background: linear-gradient(90deg, #f43f5e 0%, rgba(244,63,94,0.25) 100%);
}

.ci-card-header {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 20px 22px 16px;
    border-bottom: 1px solid var(--border-color);
}

/* ── Avatar ───────────────────────────────────── */
.ci-avatar {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    svg { width: 22px; height: 22px; }

    &--person {
        background: linear-gradient(135deg, rgba(59,130,246,0.12) 0%, rgba(59,130,246,0.22) 100%);
        box-shadow: 0 0 0 6px rgba(59,130,246,0.06);
        svg { color: #3b82f6; }
    }
    &--emergency {
        background: linear-gradient(135deg, rgba(244,63,94,0.12) 0%, rgba(244,63,94,0.22) 100%);
        box-shadow: 0 0 0 6px rgba(244,63,94,0.06);
        svg { color: #f43f5e; }
    }
}

.ci-card-title {
    font-size: 14px;
    font-weight: 700;
    color: var(--font-color);
    margin: 0 0 3px;
    line-height: 1.2;
}
.ci-card-subtitle {
    font-size: 12px;
    color: var(--secondary-font-color, #6b7280);
    margin: 0;
}

/* ── Field rows ───────────────────────────────── */
.ci-rows {
    padding: 6px 0;
}

.ci-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 13px 22px;
    border-bottom: 1px solid var(--border-color);
    transition: background 0.15s;

    &:hover { background: var(--dashboard-bg); }
    &--last  { border-bottom: none; }
}

.ci-row-label {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
    font-weight: 500;
    color: var(--secondary-font-color, #6b7280);
    white-space: nowrap;
    flex-shrink: 0;
    min-width: 130px;
}

.ci-row-icon {
    width: 28px;
    height: 28px;
    border-radius: 7px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    svg { width: 13px; height: 13px; }

    &--blue   { background: rgba(59,130,246,0.10); svg { color: #3b82f6; } }
    &--violet { background: rgba(139,92,246,0.10); svg { color: #7c3aed; } }
    &--rose   { background: rgba(244,63,94,0.10);  svg { color: #f43f5e; } }
    &--orange { background: rgba(249,115,22,0.10); svg { color: #ea580c; } }
    &--green  { background: rgba(34,197,94,0.10);  svg { color: #16a34a; } }
}

.ci-row-value {
    flex: 1;
    display: flex;
    justify-content: flex-end;
}

.ci-value-text {
    font-size: 13px;
    font-weight: 600;
    color: var(--font-color);
}

.ci-value-empty {
    font-size: 13px;
    font-weight: 400;
    color: var(--border-color);
    font-style: italic;
}

.ci-value-badge {
    display: inline-flex;
    align-items: center;
    font-size: 12px;
    font-weight: 600;
    padding: 3px 10px;
    border-radius: 20px;
    background: rgba(249,115,22,0.08);
    color: #ea580c;
    border: 1px solid rgba(249,115,22,0.18);
}

.ci-value-link {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    font-size: 13px;
    font-weight: 600;
    text-decoration: none;
    border-radius: 8px;
    padding: 4px 10px;
    transition: background 0.15s;
    svg { width: 13px; height: 13px; flex-shrink: 0; }

    &--blue  { color: #3b82f6; background: rgba(59,130,246,0.06);  &:hover { background: rgba(59,130,246,0.12); } }
    &--green { color: #16a34a; background: rgba(34,197,94,0.06);   &:hover { background: rgba(34,197,94,0.12); } }
}

/* ── Modal section labels ─────────────────────── */
.ci-modal-section {
    display: flex;
    align-items: center;
    gap: 10px;
}
.ci-modal-section-dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    flex-shrink: 0;
    &--blue { background: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,0.2); }
    &--rose { background: #f43f5e; box-shadow: 0 0 0 3px rgba(244,63,94,0.2); }
}
.ci-modal-section-title {
    font-size: 12px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.6px;
    color: var(--font-color);
}
.ci-modal-divider {
    height: 1px;
    background: var(--border-color);
}
</style>
