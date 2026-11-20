<script setup>
import { ref, computed, watch, onMounted } from "vue";
import { useSubmitForm } from "@/composable/submit/useSubmitForm.js";
import { HRM_LEAVE_REQUESTS, HRM_SUPPORT_LEAVE_TYPES, HRM_SUPPORT_EMPLOYEE_LEAVE_ALLOWANCES } from "@/services/endpoints/hrm.js";
import { useProfileStore } from "@/stores/profile/profile.js";
import Axios from "@/services/axios";
import { serverDateFormat } from "@/utils/helper";

const props = defineProps({ modalId: String, tableId: String, selectedUrl: String });
const emit = defineEmits(["close"]);

const { preloader, pageLoader, formData, errors, closeModal, afterSuccess, afterError, afterFinalResponse } =
    useSubmitForm(props, emit);

const attachmentFiles = ref([]);
const existingMediaItems = ref([]);

formData.value = {
    leave_type_id: null,
    leave_duration: 'single_day',
    note: null,
    date: null,
    start_date: null,
    end_date: null,
    start_time: null,
    end_time: null,
    attachments: [],
};

watch(() => pageLoader.value, (loading) => {
    if (!loading && props.selectedUrl) {
        const mediaItems = Array.isArray(formData.value.attachments) ? formData.value.attachments : [];
        existingMediaItems.value = mediaItems.filter(m => m && m.id);
        formData.value.attachments = existingMediaItems.value.map(m => m.url).filter(Boolean);
    }
});

const isSingleDay = computed(() => formData.value.leave_duration === 'single_day');
const isMultiDay = computed(() => formData.value.leave_duration === 'multi_day');
const isHours = computed(() => formData.value.leave_duration === 'hours');

const leaveDurationOptions = [
    { id: 'single_day', name: 'Single Day' },
    { id: 'multi_day', name: 'Multi Day' },
    { id: 'hours', name: 'Hours' },
];

const onFilesChange = (files) => {
    attachmentFiles.value = files;
};

// ── Leave Availability ────────────────────────────────────────────────────────
const profileStore = useProfileStore();
const leaveAllowances = ref([]);
const allowancesLoading = ref(false);

const fetchAllowances = () => {
    const userId = profileStore.profileData?.id;
    if (!userId) return;
    allowancesLoading.value = true;
    Axios.get(HRM_SUPPORT_EMPLOYEE_LEAVE_ALLOWANCES(userId))
        .then(({ data }) => { leaveAllowances.value = Array.isArray(data) ? data : []; })
        .catch(() => { leaveAllowances.value = []; })
        .finally(() => { allowancesLoading.value = false; });
};

onMounted(fetchAllowances);
// ─────────────────────────────────────────────────────────────────────────────

const submit = () => {
    errors.value = {};
    preloader.value = true;
    console.log("formData.value", formData.value);

    const fd = new FormData();
    fd.append('leave_type_id', formData.value.leave_type_id ?? '');
    fd.append('leave_duration', formData.value.leave_duration ?? '');
    fd.append('note', formData.value.note ?? '');

    if (isSingleDay.value || isHours.value) {
        fd.append('date', formData.value.date ? serverDateFormat(formData.value.date) : '');
    }
    if (isMultiDay.value) {
        fd.append('start_date', formData.value.start_date ? serverDateFormat(formData.value.start_date) : '');
        fd.append('end_date', formData.value.end_date ? serverDateFormat(formData.value.end_date) : '');
    }
    if (isHours.value) {
        fd.append('start_time', formData.value.start_time ?? '');
        fd.append('end_time', formData.value.end_time ?? '');
    }

    // find removed existing media by comparing current URLs with original
    const currentUrls = (attachmentFiles.value || []).filter(f => typeof f === 'string');
    existingMediaItems.value
        .filter(m => !currentUrls.includes(m.url))
        .forEach(m => fd.append('remove_attachments[]', m.id));

    // new File objects only
    const newFiles = (attachmentFiles.value || []).filter(f => f instanceof File);
    newFiles.forEach(file => fd.append('attachments[]', file));

    const url = props.selectedUrl ? props.selectedUrl : HRM_LEAVE_REQUESTS;
    if (props.selectedUrl) fd.append('_method', 'PATCH');

    Axios.post(url, fd, {
        headers: {
            'Content-Type': 'multipart/form-data'
        }
    })
        .then(res => afterSuccess(res))
        .catch(({ response }) => afterError(response))
        .finally(() => afterFinalResponse());
};
</script>

<template>
    <app-modal :modal-id="modalId" modal-size="large"
        :title="selectedUrl ? $t('update_leave_request') : $t('add_leave_request')" :preloader="preloader"
        @submit="submit" @close="closeModal">
        <template #body>
            <app-loader v-if="pageLoader" />
            <form v-else>
                <div class="row g-4">

                    <!-- Leave Availability -->
                    <div class="col-md-12">
                        <p class="lra-heading">Leave Availability</p>

                        <div v-if="allowancesLoading" class="lra-skeleton">
                            <div class="lra-skeleton-pill" v-for="n in 3" :key="n"></div>
                        </div>

                        <div v-else-if="leaveAllowances.length" class="lra-grid">
                            <div v-for="leave in leaveAllowances" :key="leave.id" class="lra-card">
                                <div class="lra-card-name">{{ leave.name }}</div>
                                <div class="lra-card-numbers">
                                    <span class="lra-avail" :class="leave.availability === 0 ? 'lra-avail--zero' : ''">
                                        {{ leave.availability }}
                                    </span>
                                    <span class="lra-sep">/</span>
                                    <span class="lra-total">{{ leave.allowance }}</span>
                                </div>
                                <div class="lra-card-sub">
                                    <span>Remaining</span>
                                    <span class="lra-taken">{{ leave.taken }} taken</span>
                                </div>
                                <div class="lra-bar-wrap">
                                    <div class="lra-bar-fill"
                                        :style="{ width: leave.allowance > 0 ? (leave.availability / leave.allowance * 100) + '%' : '0%' }"
                                        :class="leave.availability === 0 ? 'lra-bar-fill--zero' : ''">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div v-else class="lra-empty">
                            Please contact your administrator for leave availability.
                        </div>
                    </div>

                    <!-- Leave Type -->
                    <div class="col-md-12">
                        <app-input type="advance-search-select" id="leave_type_id" :label="$t('leave_type')"
                            label-required list-value-field="name" :loading-length="10" :fetch-url="HRM_SUPPORT_LEAVE_TYPES"
                            :choose-label="$t('choose_leave_type')" v-model="formData.leave_type_id"
                            :errors="$errors(errors, 'leave_type_id')" />
                    </div>

                    <!-- Leave Duration -->
                    <div class="col-md-12">
                        <app-input id="leave_duration" type="select" :label="$t('leave_duration')" label-required
                            :options="leaveDurationOptions" list-value-field="name" :selected-disable="false"
                            v-model="formData.leave_duration" :errors="$errors(errors, 'leave_duration')" />
                    </div>

                    <!-- Single Day -->
                    <template v-if="isSingleDay">
                        <div class="col-md-12">
                            <app-input id="date" type="date" :label="$t('date')" label-required v-model="formData.date"
                                :enable-time-picker="false" auto-apply :placeholder="$t('select_date')"
                                :errors="$errors(errors, 'date')" />
                        </div>
                    </template>

                    <!-- Multi Day -->
                    <template v-if="isMultiDay">
                        <div class="col-md-6">
                            <app-input id="start_date" type="date" :label="$t('start_date')" label-required
                                v-model="formData.start_date" :enable-time-picker="false" auto-apply
                                :errors="$errors(errors, 'start_date')" />
                        </div>
                        <div class="col-md-6">
                            <app-input id="end_date" type="date" :label="$t('end_date')" label-required
                                v-model="formData.end_date" :enable-time-picker="false" auto-apply
                                :errors="$errors(errors, 'end_date')" />
                        </div>
                    </template>

                    <!-- Hours -->
                    <template v-if="isHours">
                        <div class="col-md-12">
                            <app-input id="hours_date" type="date" :label="$t('date')" label-required
                                v-model="formData.date" :enable-time-picker="false" auto-apply
                                :errors="$errors(errors, 'date')" />
                        </div>
                        <div class="col-md-6">
                            <label class="form-label mb-2">
                                {{ $t('start_time') }} <span class="text-danger">*</span>
                            </label>
                            <input type="time" class="form-control" v-model="formData.start_time" />
                            <small v-if="$errors(errors, 'start_time')" class="text-danger mt-1 d-block">
                                {{ $errors(errors, 'start_time') }}
                            </small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label mb-2">
                                {{ $t('end_time') }} <span class="text-danger">*</span>
                            </label>
                            <input type="time" class="form-control" v-model="formData.end_time" />
                            <small v-if="$errors(errors, 'end_time')" class="text-danger mt-1 d-block">
                                {{ $errors(errors, 'end_time') }}
                            </small>
                        </div>
                    </template>

                    <!-- Note -->
                    <div class="col-md-12">
                        <app-input id="note" type="textarea" :label="$t('note')" :placeholder="$t('write_note')"
                            v-model="formData.note" :errors="$errors(errors, 'note')" />
                    </div>

                    <!-- Attachments -->
                    <div class="col-md-12">
                        <label class="form-label mb-2">{{ $t('attachment') }}</label>
                        <app-input type="multi-image-uploader" id="attachments"
                            :initial-files="formData.attachments ?? []" @getFiles="onFilesChange"
                            :errors="$errors(errors, 'attachments')" />
                        <small v-if="$errors(errors, 'attachments')" class="text-danger mt-1 d-block">
                            {{ $errors(errors, 'attachments') }}
                        </small>
                    </div>

                </div>
            </form>
        </template>
    </app-modal>
</template>

<style scoped lang="scss">
/* ── Leave availability section ───────────────────────────────────────────── */
.lra-heading {
    font-size: 13px;
    font-weight: 700;
    color: var(--font-color);
    margin: 0 0 10px;
    letter-spacing: 0.02em;
    text-transform: uppercase;
}

.lra-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
    gap: 10px;
}

.lra-card {
    background: var(--white);
    border: 1px solid var(--border-color);
    border-radius: 12px;
    padding: 14px 14px 10px;
}

.lra-card-name {
    font-size: 11px;
    font-weight: 600;
    color: var(--secondary-font-color, #6b7280);
    text-transform: uppercase;
    letter-spacing: 0.04em;
    margin-bottom: 6px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.lra-card-numbers {
    display: flex;
    align-items: baseline;
    gap: 2px;
    margin-bottom: 2px;
}

.lra-avail {
    font-size: 26px;
    font-weight: 800;
    color: var(--primary-color, #4f46e5);
    line-height: 1;

    &.lra-avail--zero {
        color: var(--danger-color, #dc2626);
    }
}

.lra-sep {
    font-size: 16px;
    color: var(--secondary-font-color, #9ca3af);
    margin: 0 1px;
}

.lra-total {
    font-size: 16px;
    font-weight: 600;
    color: var(--secondary-font-color, #6b7280);
}

.lra-card-sub {
    display: flex;
    justify-content: space-between;
    font-size: 11px;
    color: var(--secondary-font-color, #9ca3af);
    margin-bottom: 8px;
}

.lra-taken {
    font-weight: 500;
}

.lra-bar-wrap {
    height: 4px;
    background: var(--border-color);
    border-radius: 99px;
    overflow: hidden;
}

.lra-bar-fill {
    height: 100%;
    background: var(--primary-color, #4f46e5);
    border-radius: 99px;
    transition: width 0.4s ease;

    &.lra-bar-fill--zero {
        background: var(--danger-color, #dc2626);
    }
}

/* ── Loading skeleton ─────────────────────────────────────────────────────── */
.lra-skeleton {
    display: flex;
    gap: 10px;
}

.lra-skeleton-pill {
    flex: 1;
    height: 90px;
    border-radius: 12px;
    background: linear-gradient(90deg, var(--border-color) 25%, color-mix(in srgb, var(--border-color) 60%, transparent) 50%, var(--border-color) 75%);
    background-size: 200% 100%;
    animation: lra-shimmer 1.4s infinite;
}

@keyframes lra-shimmer {
    0%   { background-position: 200% 0; }
    100% { background-position: -200% 0; }
}

/* ── Empty state ──────────────────────────────────────────────────────────── */
.lra-empty {
    font-size: 13px;
    color: var(--secondary-font-color, #6b7280);
    background: var(--white);
    border: 1px solid var(--border-color);
    border-radius: 10px;
    padding: 14px 16px;
}
</style>
