<script setup>
import { useSubmitForm } from "@/composable/submit/useSubmitForm.js";
import { HRM_ATTENDANCE_REQUESTS } from "@/services/endpoints/hrm.js";
import { serverDateFormat } from "@/utils/helper";
import { Clock } from "@iconoir/vue";

const props = defineProps({ modalId: String, tableId: String, selectedUrl: String });
const emit = defineEmits(["close"]);

const { preloader, pageLoader, formData, errors, save, closeModal } = useSubmitForm(props, emit);

formData.value = {
    date: null,
    in_time: null,
    out_time: null,
    note: null,
};

const submit = () => {
    const payload = {
        date: formData.value.date ? serverDateFormat(formData.value.date) : null,
        in_time: formData.value.in_time || null,
        out_time: formData.value.out_time || null,
        note: formData.value.note,
    };

    save(props.selectedUrl ? props.selectedUrl : HRM_ATTENDANCE_REQUESTS, payload);
};
</script>

<template>
    <app-modal :modal-id="modalId" modal-size="large"
        :title="selectedUrl ? $t('update_attendance_request') : $t('add_attendance_request')" :preloader="preloader"
        @submit="submit" @close="closeModal">
        <template #body>
            <app-loader v-if="pageLoader" />
            <form v-else>
                <div class="row g-4">
                    <div class="col-md-12">
                        <app-input id="date" type="date" :label="$t('date')" label-required v-model="formData.date"
                            :enable-time-picker="false" auto-apply :placeholder="$t('select_date')"
                            :max-date="new Date()" :errors="$errors(errors, 'date')" />
                    </div>

                    <div class="col-md-6">
                        <label class="form-label mb-2">{{ $t('punch_in') }}</label>
                        <div class="time-field"
                            :class="{ 'time-field--filled': formData.in_time, 'time-field--invalid': $errors(errors, 'in_time') }">
                            <span class="time-field__icon">
                                <Clock width="18" height="18" />
                            </span>
                            <input type="time" class="time-field__input" v-model="formData.in_time" />
                        </div>
                        <small v-if="$errors(errors, 'in_time')" class="text-danger mt-1 d-block">
                            {{ $errors(errors, 'in_time') }}
                        </small>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label mb-2">{{ $t('punch_out') }}</label>
                        <div class="time-field"
                            :class="{ 'time-field--filled': formData.out_time, 'time-field--invalid': $errors(errors, 'out_time') }">
                            <span class="time-field__icon">
                                <Clock width="18" height="18" />
                            </span>
                            <input type="time" class="time-field__input" v-model="formData.out_time" />
                        </div>
                        <small v-if="$errors(errors, 'out_time')" class="text-danger mt-1 d-block">
                            {{ $errors(errors, 'out_time') }}
                        </small>
                    </div>
                    <small class="text-muted d-block px-2">{{ $t('attendance_request_time_hint') }}</small>

                    <div class="col-md-12">
                        <app-input id="note" type="textarea" :label="$t('note')" :placeholder="$t('write_note')"
                            v-model="formData.note" :errors="$errors(errors, 'note')" />
                    </div>
                </div>
            </form>
        </template>
    </app-modal>
</template>

<style scoped lang="scss">
.time-field {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 0 14px;
    height: 46px;
    border-radius: 12px;
    border: 1.5px solid var(--border-color, #e5e7eb);
    background: var(--white, #fff);
    transition: border-color 0.18s ease, box-shadow 0.18s ease, background 0.18s ease;

    &:hover {
        border-color: #a5b4fc;
    }

    &:focus-within {
        border-color: #6366f1;
        box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.12);
    }

    &--filled {
        background: rgba(99, 102, 241, 0.04);
    }

    &--invalid {
        border-color: #ef4444;

        &:focus-within {
            box-shadow: 0 0 0 4px rgba(239, 68, 68, 0.12);
        }
    }
}

.time-field__icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    color: #6366f1;
}

.time-field__input {
    flex: 1;
    border: none;
    outline: none;
    background: transparent;
    height: 100%;
    font-size: 0.92rem;
    font-weight: 600;
    color: var(--font-color);
    font-variant-numeric: tabular-nums;

    &::-webkit-calendar-picker-indicator {
        cursor: pointer;
        opacity: 0.55;
        filter: invert(0.4) sepia(1) saturate(4) hue-rotate(210deg);
        border-radius: 6px;
        padding: 4px;
        transition: opacity 0.15s ease, background 0.15s ease;

        &:hover {
            opacity: 1;
            background: rgba(99, 102, 241, 0.1);
        }
    }
}

:global([theme="dark"]) {
    .time-field {
        background: #1e2330;
        border-color: #2a3045;

        &:hover {
            border-color: #4b4fd4;
        }

        &--filled {
            background: rgba(99, 102, 241, 0.08);
        }
    }

    .time-field__input {
        color: #e5e7eb;

        &::-webkit-calendar-picker-indicator {
            filter: invert(0.8) sepia(1) saturate(4) hue-rotate(210deg);
        }
    }
}
</style>
