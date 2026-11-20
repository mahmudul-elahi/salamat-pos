<script setup>
import { ref, computed, onMounted } from "vue";
import { useI18n } from "vue-i18n";
import { useToast } from "vue-toastification";
import { Calendar, NavArrowDown, NavArrowUp } from "@iconoir/vue";
import Axios from "@/services/axios";
import { HRM_PAYRUN_DEFAULT_PREVIEW, HRM_PAYRUN_RUN_DEFAULT } from "@/services/endpoints/hrm.js";

const props = defineProps({
    modalId: { type: String, default: 'default-payrun-modal' },
});

const emit = defineEmits(["close", "success"]);

const { t } = useI18n();
const toast = useToast();

const loading = ref(true);
const submitting = ref(false);
const showDetails = ref(false);
const note = ref('');
const preview = ref({ allowances: [], deductions: [] });

const fetchPreview = () => {
    loading.value = true;

    Axios.get(HRM_PAYRUN_DEFAULT_PREVIEW)
        .then(({ data }) => {
            preview.value = data;
        })
        .finally(() => {
            loading.value = false;
        });
};

const dateRangeLabel = computed(() => `${preview.value.start_date} - ${preview.value.end_date}`);

const considerTypeLabel = computed(() => {
    const map = { hour: 'Hour', daily_log: 'Daily_log', none: 'None' };
    return map[preview.value.consider_type] ?? preview.value.consider_type;
});

const submit = () => {
    submitting.value = true;

    Axios.post(HRM_PAYRUN_RUN_DEFAULT, { note: note.value || null })
        .then((response) => {
            toast.success(response.data.message);
            emit('success');
        })
        .catch(({ response }) => {
            toast.error(response?.data?.message ?? 'Something went wrong');
        })
        .finally(() => {
            submitting.value = false;
        });
};

onMounted(fetchPreview);
</script>

<template>
    <app-modal :modal-id="modalId" modal-size="large" :title="$t('default_payrun')"
        :save-button-label="$t('run')" :preloader="submitting" @submit="submit" @close="$emit('close')">
        <template #body>
            <app-loader v-if="loading" />
            <template v-else>
                <p class="text-warning fw-semibold mb-2">{{ $t('all_good_quick_read') }}</p>
                <p class="mb-1">{{ $t('default_payrun_intro') }}</p>
                <p class="mb-4">{{ $t('last_month_preview_note') }}</p>

                <div class="d-flex align-items-start gap-3 mb-3">
                    <div class="period-icon"><Calendar /></div>
                    <div>
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <strong>{{ dateRangeLabel }}</strong>
                            <span class="text-muted small">
                                {{ $t('includes_employees', { count: preview.employees_count }) }}
                            </span>
                        </div>
                        <p class="text-muted small mb-1">
                            {{ preview.payrun_period }} - {{ considerTypeLabel }} based
                            <span class="mx-1">|</span>
                            <router-link to="/payrun-settings" class="fw-semibold">{{ $t('default_payrun') }}</router-link>
                            - {{ $t('followed_by') }} {{ $t('default_payrun') }}
                        </p>
                        <a href="#" class="d-inline-flex align-items-center gap-1" @click.prevent="showDetails = !showDetails">
                            {{ showDetails ? $t('hide_details') : $t('show_details') }}
                            <component :is="showDetails ? NavArrowUp : NavArrowDown" width="16" height="16" />
                        </a>
                    </div>
                </div>

                <div v-if="showDetails" class="row g-4 mb-4">
                    <div class="col-md-6">
                        <div class="d-flex justify-content-between border-bottom pb-2 mb-2">
                            <strong>{{ $t('earning') }}</strong>
                            <strong>{{ preview.total_allowance_percent }} %</strong>
                        </div>
                        <div v-for="item in preview.allowances" :key="item.name" class="d-flex justify-content-between mb-1">
                            <span>{{ item.name }}</span>
                            <span>{{ item.amount }}{{ item.is_percentage ? ' %' : '' }}</span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex justify-content-between border-bottom pb-2 mb-2">
                            <strong>{{ $t('deduction') }}</strong>
                            <strong>{{ preview.total_deduction_percent }} %</strong>
                        </div>
                        <div v-for="item in preview.deductions" :key="item.name" class="d-flex justify-content-between mb-1">
                            <span>{{ item.name }}</span>
                            <span>{{ item.amount }}{{ item.is_percentage ? ' %' : '' }}</span>
                        </div>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label mb-2">{{ $t('payslip_note_optional') }}</label>
                    <textarea class="form-control" rows="3" v-model="note" :placeholder="$t('add_payslip_note')"></textarea>
                </div>

                <p class="text-muted small mb-0">
                    {{ $t('payrun_footer_note_1') }}
                    <router-link to="/payrun-settings">{{ $t('settings') }}</router-link>{{ $t('payrun_footer_note_2') }}
                    <span class="text-muted">{{ $t('add_manual_payrun') }}</span>{{ $t('payrun_footer_note_3') }}
                </p>
            </template>
        </template>
    </app-modal>
</template>

<style scoped lang="scss">
.period-icon {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: #f1f1f4;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
</style>
