<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { Trash, EditPencil, Plus, Check, XmarkCircleSolid } from "@iconoir/vue";
import Axios from "@/services/axios/index.js";
import { useToast } from "vue-toastification";
import usePermission from "@/composable/permission/usePermission.js";

const props = defineProps({
    modalId: String,
    product: Object,
});

const { canAccess } = usePermission();

const emit = defineEmits(['close']);
const toast = useToast();

const loading = ref(false);
const specs = ref([]);
const groups = ref([]);

// Add form
const showAddForm = ref(false);
const addForm = ref({ parent_id: null, sub_id: null, details: '' });
const addLoading = ref(false);

// Edit state
const editingId = ref(null);
const editForm = ref({ parent_id: null, sub_id: null, details: '' });
const editLoading = ref(false);

const deleteLoadingId = ref(null);

// ── Sub-group options ─────────────────────────────────────────
const addSubGroups = computed(() => {
    if (!addForm.value.parent_id) return [];
    return groups.value.find(g => g.id === addForm.value.parent_id)
        ?.sub_specification_groups ?? [];
});

const editSubGroups = computed(() => {
    if (!editForm.value.parent_id) return [];
    return groups.value.find(g => g.id === editForm.value.parent_id)
        ?.sub_specification_groups ?? [];
});

// ── Grouped display ───────────────────────────────────────────
const groupedSpecs = computed(() => {
    const map = {};
    specs.value.forEach(s => {
        const key = s.parent?.name ?? 'General';
        if (!map[key]) map[key] = [];
        map[key].push(s);
    });
    return map;
});

// ── Load ──────────────────────────────────────────────────────
const load = async () => {
    loading.value = true;
    try {
        const res = await Axios.get(`admin/landlord/products/${props.product.id}/specifications`);
        specs.value = res.data.specs;
        groups.value = res.data.groups;
    } catch {
        toast.error('Failed to load specifications.');
    } finally {
        loading.value = false;
    }
};
onMounted(load);

// ── Add ───────────────────────────────────────────────────────
const openAddForm = () => {
    addForm.value = { parent_id: null, sub_id: null, details: '' };
    showAddForm.value = true;
};

// Clear sub when parent changes (add form only)
watch(() => addForm.value.parent_id, () => {
    addForm.value.sub_id = null;
});

const submitAdd = async () => {
    if (!addForm.value.parent_id || !addForm.value.details.trim()) {
        toast.warning('Group and details are required.');
        return;
    }
    addLoading.value = true;
    try {
        const res = await Axios.post(
            `admin/landlord/products/${props.product.id}/specifications`,
            addForm.value
        );
        specs.value.push(res.data.spec);
        showAddForm.value = false;
        toast.success('Specification added.');
    } catch (e) {
        toast.error(e.response?.data?.message ?? 'Failed to add.');
    } finally {
        addLoading.value = false;
    }
};

// ── Edit ──────────────────────────────────────────────────────
// Flag: skip sub reset when initialising edit
let _skipSubReset = false;

watch(() => editForm.value.parent_id, () => {
    if (_skipSubReset) { _skipSubReset = false; return; }
    editForm.value.sub_id = null;
});

const startEdit = (spec) => {
    _skipSubReset = true;
    editingId.value = spec.id;
    editForm.value = {
        parent_id: spec.parent?.id ?? null,
        sub_id: spec.sub?.id ?? null,
        details: spec.details,
    };
};

const cancelEdit = () => { editingId.value = null; };

const submitEdit = async (spec) => {
    if (!editForm.value.parent_id || !editForm.value.details.trim()) {
        toast.warning('Group and details are required.');
        return;
    }
    editLoading.value = true;
    try {
        const res = await Axios.put(
            `admin/landlord/products/${props.product.id}/specifications/${spec.id}`,
            editForm.value
        );
        const idx = specs.value.findIndex(s => s.id === spec.id);
        if (idx !== -1) specs.value[idx] = res.data.spec;
        editingId.value = null;
        toast.success('Specification updated.');
    } catch (e) {
        toast.error(e.response?.data?.message ?? 'Failed to update.');
    } finally {
        editLoading.value = false;
    }
};

// ── Delete ────────────────────────────────────────────────────
const deleteSpec = async (spec) => {
    deleteLoadingId.value = spec.id;
    try {
        await Axios.delete(
            `admin/landlord/products/${props.product.id}/specifications/${spec.id}`
        );
        specs.value = specs.value.filter(s => s.id !== spec.id);
        toast.success('Specification deleted.');
    } catch (e) {
        toast.error(e.response?.data?.message ?? 'Failed to delete.');
    } finally {
        deleteLoadingId.value = null;
    }
};
</script>

<template>
    <app-modal :modal-id="modalId" modal-size="extra-large" :preloader="loading" title="Product Specifications"
        @close="emit('close')" :show-footer="false">
        <template v-slot:body>

            <!-- Product name bar -->
            <div class="mb-4 px-3 py-2 rounded-2 d-flex align-items-center justify-content-between"
                style="background:#f8f9fa; border:1px solid #e9ecef;">
                <div>
                    <span style="font-size:0.75rem; color:#888;">Product: </span>
                    <span style="font-weight:600; font-size:0.9rem; color:#222;">{{ product?.product_name }}</span>
                </div>
                <button v-if="!showAddForm && canAccess('create_product_specifications')"
                    class="btn btn-primary btn-sm d-flex align-items-center gap-1 px-3" @click="openAddForm">
                    <Plus style="width:14px; height:14px;" /> Add Specification
                </button>
            </div>

            <!-- Add form -->
            <div v-if="showAddForm" class="mb-4 p-3 rounded-2" style="background:#f0f7ff; border:1px solid #c5dff8;">
                <div class="fw-semibold mb-3" style="font-size:0.85rem; color:#1864ab;">+ New Specification</div>
                <div class="row g-2">
                    <div class="col-md-4">
                        <app-input type="select" id="add_parent_group" label="Group *" show-search :options="groups"
                            list-value-field="name" :choose-label="'Select group'" v-model="addForm.parent_id" />
                    </div>
                    <div class="col-md-3">
                        <app-input type="select" show-search id="add_sub_group" label="Sub Group"
                            :options="addSubGroups" list-value-field="name" :choose-label="'None'"
                            :disabled="!addForm.parent_id || addSubGroups.length === 0" v-model="addForm.sub_id" />
                    </div>
                    <div class="col-md-5">
                        <app-input type="text" id="add_details" label="Details *" placeholder="e.g. 6.1 inches"
                            v-model="addForm.details" @keydown.enter="submitAdd" />
                    </div>
                </div>
                <div class="d-flex gap-2 mt-3">
                    <button class="btn btn-success btn-sm px-4" @click="submitAdd" :disabled="addLoading">
                        <span v-if="addLoading" class="spinner-border spinner-border-sm me-1"></span>
                        Save
                    </button>
                    <button class="btn btn-light btn-sm px-3" @click="showAddForm = false">Cancel</button>
                </div>
            </div>

            <!-- Empty state -->
            <div v-if="!loading && specs.length === 0 && !showAddForm" class="text-center py-5 text-muted">
                <div style="font-size:2rem;">📋</div>
                <div class="mt-2" style="font-size:0.9rem;">No specifications yet.</div>
                <button class="btn btn-primary btn-sm mt-3" @click="openAddForm">Add First Specification</button>
            </div>

            <!-- Grouped spec list -->
            <div v-for="(items, groupName) in groupedSpecs" :key="groupName" class="mb-4">

                <!-- Group header -->
                <div class="px-3 py-2 mb-1 rounded-2 d-flex align-items-center gap-2"
                    style="background:#eef2ff; border-left:3px solid #4263eb;">
                    <span
                        style="font-weight:700; font-size:0.8rem; color:#3451c7; text-transform:uppercase; letter-spacing:0.4px;">
                        {{ groupName }}
                    </span>
                    <span style="font-size:0.75rem; color:#7c8db0;">({{ items.length }})</span>
                </div>

                <div v-for="spec in items" :key="spec.id">

                    <!-- View row -->
                    <div v-if="editingId !== spec.id"
                        class="d-flex align-items-center justify-content-between px-3 py-2"
                        style="border-bottom:1px solid #f2f4f8; transition:background 0.12s;"
                        @mouseenter="$event.currentTarget.style.background = '#fafbff'"
                        @mouseleave="$event.currentTarget.style.background = 'transparent'">
                        <div class="d-flex gap-3 flex-grow-1">
                            <div
                                style="min-width:160px; font-size:0.83rem; font-weight:600; color:#495057; flex-shrink:0;">
                                {{ spec.sub?.name || '—' }}
                            </div>
                            <div style="font-size:0.83rem; color:#333; flex:1;">{{ spec.details }}</div>
                        </div>
                        <div class="d-flex gap-2 ms-3 flex-shrink-0"
                            v-if="canAccess('update_product_specifications') || canAccess('delete_product_specifications')">
                            <button v-if="canAccess('update_product_specifications')" @click="startEdit(spec)"
                                title="Edit" class="d-flex align-items-center justify-content-center"
                                style="width:28px;height:28px;border-radius:6px;background:#e8f4fd;border:1px solid #bee3f8;color:#1c7ed6;cursor:pointer;transition:all 0.15s;"
                                @mouseenter="$event.currentTarget.style.background = '#d0ebff'"
                                @mouseleave="$event.currentTarget.style.background = '#e8f4fd'">
                                <EditPencil style="width:13px;height:13px;" />
                            </button>
                            <button v-if="canAccess('delete_product_specifications')" @click="deleteSpec(spec)"
                                title="Delete" :disabled="deleteLoadingId === spec.id"
                                class="d-flex align-items-center justify-content-center"
                                style="width:28px;height:28px;border-radius:6px;background:#fff0f0;border:1px solid #ffd0d0;color:#e03131;cursor:pointer;transition:all 0.15s;"
                                @mouseenter="$event.currentTarget.style.background = '#ffe3e3'; $event.currentTarget.style.borderColor = '#fa5252'"
                                @mouseleave="$event.currentTarget.style.background = '#fff0f0'; $event.currentTarget.style.borderColor = '#ffd0d0'">
                                <span v-if="deleteLoadingId === spec.id" class="spinner-border"
                                    style="width:11px;height:11px;border-width:2px;"></span>
                                <Trash v-else style="width:13px;height:13px;" />
                            </button>
                        </div>
                    </div>

                    <!-- Inline edit row -->
                    <div v-else class="px-3 py-3"
                        style="background:#fffbf0;border-bottom:1px solid #ffe8a1;border-left:3px solid #f59f00;">
                        <div class="row g-2 align-items-end">
                            <div class="col-md-3">
                                <app-input type="select" id="edit_parent_group" label="Group *" :options="groups"
                                    list-value-field="name" :choose-label="'Select group'"
                                    v-model="editForm.parent_id" />
                            </div>
                            <div class="col-md-3">
                                <app-input type="select" id="edit_sub_group" label="Sub Group" :options="editSubGroups"
                                    list-value-field="name" :choose-label="'None'"
                                    :disabled="!editForm.parent_id || editSubGroups.length === 0"
                                    v-model="editForm.sub_id" />
                            </div>
                            <div class="col-md-4">
                                <app-input type="text" id="edit_details" label="Details *" v-model="editForm.details"
                                    @keydown.enter="submitEdit(spec)" @keydown.esc="cancelEdit" />
                            </div>
                            <div class="col-md-2 d-flex gap-2 align-items-end pb-1">
                                <button @click="submitEdit(spec)" :disabled="editLoading" title="Save"
                                    class="d-flex align-items-center justify-content-center"
                                    style="width:36px;height:36px;border-radius:8px;background:#d3f9d8;border:1px solid #8ce99a;color:#2f9e44;cursor:pointer;transition:all 0.15s;flex-shrink:0;"
                                    @mouseenter="$event.currentTarget.style.background = '#b2f2bb'"
                                    @mouseleave="$event.currentTarget.style.background = '#d3f9d8'">
                                    <span v-if="editLoading" class="spinner-border"
                                        style="width:13px;height:13px;border-width:2px;"></span>
                                    <Check v-else style="width:15px;height:15px;" />
                                </button>
                                <button @click="cancelEdit" title="Cancel"
                                    class="d-flex align-items-center justify-content-center"
                                    style="width:36px;height:36px;border-radius:8px;background:#fff0f0;border:1px solid #ffd0d0;color:#e03131;cursor:pointer;transition:all 0.15s;flex-shrink:0;"
                                    @mouseenter="$event.currentTarget.style.background = '#ffe3e3'; $event.currentTarget.style.borderColor = '#fa5252'"
                                    @mouseleave="$event.currentTarget.style.background = '#fff0f0'; $event.currentTarget.style.borderColor = '#ffd0d0'">
                                    <XmarkCircleSolid style="width:15px;height:15px;" />
                                </button>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

        </template>

        <template #footer>
            <div class="d-flex justify-content-end">
                <button class="btn btn-primary btn-sm" @click="emit('close')">Cancel</button>
            </div>
        </template>
    </app-modal>
</template>
