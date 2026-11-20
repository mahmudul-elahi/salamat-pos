<script setup>
import { ref, computed } from "vue";
import { useI18n } from "vue-i18n";
import { useToast } from "vue-toastification";
import { Pause, Play } from "@iconoir/vue";
import Axios from "@/services/axios";
import { HRM_ATTENDANCES } from "@/services/endpoints/hrm.js";
import { useTimezone } from "@/utils/useTimezone.js";

const props = defineProps({
    modalId: { type: String, default: 'break-modal' },
    todayStatus: { type: Object, default: () => ({}) },
});

const emit = defineEmits(['close', 'success']);

const { t } = useI18n();
const toast = useToast();
const { formatTime } = useTimezone();

const submitting      = ref(false);
const selectedBreakId = ref(null);

const onBreak            = computed(() => props.todayStatus?.on_break ?? false);
const availableBreakTimes = computed(() => props.todayStatus?.available_break_times ?? []);
const breaksTaken        = computed(() => props.todayStatus?.breaks_today ?? []);

const submit = () => {
    submitting.value = true;

    const url     = onBreak.value ? `${HRM_ATTENDANCES}/end-break` : `${HRM_ATTENDANCES}/start-break`;
    const payload = onBreak.value ? {} : { break_time_id: selectedBreakId.value };

    Axios.post(url, payload)
        .then(({ data }) => {
            toast.success(data.message);
            emit('success');
            emit('close');
        })
        .catch(({ response }) => {
            toast.error(response?.data?.message ?? 'Something went wrong');
        })
        .finally(() => {
            submitting.value = false;
        });
};
</script>

<template>
    <app-modal :modal-id="modalId" modal-size="default"
        :title="onBreak ? $t('end_break') : $t('take_break')"
        :save-button-label="onBreak ? $t('end_break') : $t('start_break')"
        :preloader="submitting"
        @submit="submit" @close="$emit('close')">
        <template #body>

            <!-- Currently on break -->
            <div v-if="onBreak" class="text-center py-3">
                <div class="mb-3 text-warning">
                    <Pause width="48" height="48" />
                </div>
                <p class="fw-semibold mb-1">{{ $t('you_are_on_break') }}</p>
                <p class="text-muted small mb-0">
                    {{ todayStatus.break_name }} — {{ $t('started_at') }} {{ formatTime(todayStatus.break_started_at) }}
                </p>
            </div>

            <!-- Select break time -->
            <div v-else>
                <p class="text-muted small mb-3">{{ $t('select_break_time') }}</p>

                <div v-if="availableBreakTimes.length" class="d-flex flex-column gap-2 mb-3">
                    <label v-for="bt in availableBreakTimes" :key="bt.id"
                        class="d-flex align-items-center gap-3 p-3 border rounded cursor-pointer"
                        :class="selectedBreakId === bt.id ? 'border-primary bg-primary-subtle' : ''"
                        style="cursor:pointer">
                        <input type="radio" :value="bt.id" v-model="selectedBreakId" class="form-check-input mt-0" />
                        <div>
                            <p class="mb-0 fw-semibold">{{ bt.name }}</p>
                            <small class="text-muted">{{ bt.duration }}</small>
                        </div>
                    </label>
                </div>
                <div v-else class="alert alert-warning py-2">
                    {{ $t('no_break_times_available') }}
                </div>
            </div>

            <!-- Breaks taken today -->
            <div v-if="breaksTaken.length" class="mt-3 border-top pt-3">
                <p class="text-muted small fw-semibold mb-2">{{ $t('breaks_today') }}</p>
                <div v-for="(b, i) in breaksTaken" :key="i"
                    class="d-flex justify-content-between small text-muted mb-1">
                    <span>{{ b.name }} ({{ formatTime(b.started_at) }} – {{ b.ended_at ? formatTime(b.ended_at) : '...' }})</span>
                    <span class="fw-semibold">{{ b.duration }}</span>
                </div>
            </div>

        </template>
    </app-modal>
</template>

<style scoped lang="scss"></style>
