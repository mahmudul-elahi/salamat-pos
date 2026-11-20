<script setup>
import { ref, watch } from "vue";
import { useSubmitForm } from "@/composable/submit/useSubmitForm.js";
import { HRM_BREAK_TIMES } from "@/services/endpoints/hrm.js";

const props = defineProps({ modalId: String, tableId: String, selectedUrl: String });
const emit = defineEmits(["close"]);

const { preloader, pageLoader, formData, errors, save, closeModal } = useSubmitForm(props, emit);

formData.value = { name: null, duration: null };

const hours = ref(0);
const minutes = ref(0);

// When editing, parse existing "Xh Ym" string into hours/minutes
watch(formData, (val) => {
    if (val?.duration) {
        const match = val.duration.match(/(\d+)h\s*(\d+)m/);
        if (match) {
            hours.value = parseInt(match[1]);
            minutes.value = parseInt(match[2]);
        }
    }
}, { immediate: true });

// Keep formData.duration in sync
watch([hours, minutes], ([h, m]) => {
    formData.value.duration = `${h}h ${m}m`;
}, { immediate: true });

const submit = () => save(props.selectedUrl ? props.selectedUrl : HRM_BREAK_TIMES, formData.value);
</script>

<template>
    <app-modal :modal-id="modalId" modal-size="default"
        :title="selectedUrl ? $t('update_break_time') : $t('add_break_time')"
        :preloader="preloader" @submit="submit" @close="closeModal">
        <template #body>
            <app-loader v-if="pageLoader" />
            <form v-else>
                <div class="row g-4">
                    <div class="col-md-12">
                        <app-input id="name" type="text" :label="$t('name')" label-required
                            :placeholder="$t('enter_break_time_name')"
                            v-model="formData.name" :errors="$errors(errors, 'name')" />
                    </div>

                    <div class="col-md-12">
                        <label class="form-label mb-2">
                            {{ $t('duration') }} <span class="text-danger">*</span>
                        </label>
                        <div class="d-flex align-items-center gap-2">
                            <div class="d-flex align-items-center gap-1">
                                <input
                                    type="number" min="0" max="23"
                                    class="form-control text-center"
                                    style="width: 72px"
                                    v-model.number="hours"
                                />
                                <span class="text-muted fw-semibold">h</span>
                            </div>
                            <span class="text-muted fw-bold">:</span>
                            <div class="d-flex align-items-center gap-1">
                                <input
                                    type="number" min="0" max="59"
                                    class="form-control text-center"
                                    style="width: 72px"
                                    v-model.number="minutes"
                                />
                                <span class="text-muted fw-semibold">m</span>
                            </div>
                            <span class="badge bg-light text-dark border ms-1 fs-6 px-3 py-2">
                                {{ hours }}h {{ minutes }}m
                            </span>
                        </div>
                        <div v-if="$errors(errors, 'duration').length" class="text-danger small mt-1">
                            {{ $errors(errors, 'duration')[0] }}
                        </div>
                    </div>
                </div>
            </form>
        </template>
    </app-modal>
</template>

<style scoped lang="scss"></style>
