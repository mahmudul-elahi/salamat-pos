<script setup>
import { ref, computed, watch, onMounted, onBeforeUnmount, nextTick } from "vue";
import { useI18n } from "vue-i18n";
import { NavArrowLeft, NavArrowRight, Download } from "@iconoir/vue";
import Axios from "@/services/axios";
import { HRM_ATTENDANCE_REPORT, HRM_DEPARTMENTS } from "@/services/endpoints/hrm.js";

const { t } = useI18n();

const monthNames = [
    'January', 'February', 'March', 'April', 'May', 'June',
    'July', 'August', 'September', 'October', 'November', 'December',
];

const today = new Date();
const month = ref(today.getMonth() + 1);
const year = ref(today.getFullYear());
const departmentId = ref(null);

const loading = ref(false);
const report = ref({ days: [], employees: [], daily_totals: {}, summary: {} });

const years = computed(() => {
    const current = today.getFullYear();
    return Array.from({ length: 11 }, (_, i) => current - 5 + i);
});

const cellClass = {
    P: 'bg-success-subtle text-success',
    A: 'bg-danger-subtle text-danger',
    L: 'bg-info-subtle text-info',
    H: 'bg-warning-subtle text-warning',
    '-': 'text-muted',
};

const prevMonth = () => {
    if (month.value === 1) {
        month.value = 12;
        year.value -= 1;
    } else {
        month.value -= 1;
    }
};

const nextMonth = () => {
    if (month.value === 12) {
        month.value = 1;
        year.value += 1;
    } else {
        month.value += 1;
    }
};

const fetchReport = () => {
    loading.value = true;

    Axios.get(HRM_ATTENDANCE_REPORT, {
        params: {
            month: month.value,
            year: year.value,
            department_id: departmentId.value || undefined,
        },
    })
        .then(({ data }) => {
            report.value = data;
        })
        .finally(() => {
            loading.value = false;
        });
};

const exportCsv = () => {
    const header = ['Name of Attendee', ...report.value.days.map((d) => `${d.day} ${d.name}`), 'Total'];
    const rows = report.value.employees.map((employee) => [
        employee.name,
        ...report.value.days.map((d) => employee.days[d.day] ?? ''),
        employee.total_present,
    ]);
    const totalsRow = ['Total', ...report.value.days.map((d) => report.value.daily_totals[d.day] ?? 0), ''];

    const csv = [header, ...rows, totalsRow]
        .map((row) => row.map((cell) => `"${cell}"`).join(','))
        .join('\n');

    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = `daily-attendance-record-${year.value}-${month.value}.csv`;
    link.click();
    URL.revokeObjectURL(url);
};

watch([month, year, departmentId], fetchReport);
onMounted(fetchReport);

// Row 2 of the header ("day of month" columns) needs its sticky `top`
// set to row 1's rendered height, since row 1's rowspan cells (name/total)
// already span both rows and can't tell CSS how tall row 1 alone is.
const theadRow1 = ref(null);
const row1Height = ref(0);

const measureRow1Height = () => {
    if (theadRow1.value) {
        row1Height.value = theadRow1.value.offsetHeight;
    }
};

watch(loading, (isLoading) => {
    if (!isLoading) {
        nextTick(measureRow1Height);
    }
});

onMounted(() => {
    window.addEventListener('resize', measureRow1Height);
});

onBeforeUnmount(() => {
    window.removeEventListener('resize', measureRow1Height);
});
</script>

<template>
    <div class="content-wrapper">
        <app-breadcrumb :page-title="$t('attendance_report')" />

        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
            <div class="d-flex align-items-center gap-2">
                <button type="button" class="btn btn-light border" @click="prevMonth">
                    <NavArrowLeft />
                </button>
                <span class="btn btn-light border px-4">{{ monthNames[month - 1] }}</span>
                <button type="button" class="btn btn-light border" @click="nextMonth">
                    <NavArrowRight />
                </button>

                <select class="form-select" style="width: auto" v-model.number="year">
                    <option v-for="y in years" :key="y" :value="y">{{ y }}</option>
                </select>
            </div>

            <button type="button" class="btn btn-primary d-flex align-items-center gap-2" @click="exportCsv">
                <Download width="18" height="18" /> {{ $t('export') }}
            </button>
        </div>

        <div class="card border-0 rounded-default mb-3">
            <div class="card-body d-flex justify-content-between align-items-start flex-wrap gap-3">
                <div style="min-width: 260px">
                    <label class="form-label text-muted small text-uppercase">{{ $t('program') }}</label>
                    <app-input type="advance-search-select" id="department_id" list-value-field="name"
                        :loading-length="10" :fetch-url="HRM_DEPARTMENTS" :choose-label="$t('choose_department')"
                        v-model="departmentId" />
                    <p class="text-muted small mb-0 mt-2">
                        {{ $t('month_year') }}: <strong>{{ monthNames[month - 1] }} {{ year }}</strong>
                    </p>
                </div>
                <h5 class="fw-bold text-uppercase mb-0">{{ $t('daily_attendance_record') }}</h5>
            </div>
        </div>

        <div class="card border-0 rounded-default">
            <div class="card-body px-4 py-3 d-flex flex-wrap gap-4 border-bottom">
                <span class="d-flex align-items-center gap-2">
                    <span class="legend-dot bg-success-subtle text-success">P</span> {{ $t('present') }}
                </span>
                <span class="d-flex align-items-center gap-2">
                    <span class="legend-dot bg-danger-subtle text-danger">A</span> {{ $t('absent') }}
                </span>
                <span class="d-flex align-items-center gap-2">
                    <span class="legend-dot bg-info-subtle text-info">L</span> {{ $t('leave') }}
                </span>
                <span class="d-flex align-items-center gap-2">
                    <span class="legend-dot bg-warning-subtle text-warning">H</span> {{ $t('holiday') }}
                </span>
                <span class="d-flex align-items-center gap-2">
                    <span class="late-dot-legend"></span> {{ $t('late') }}
                </span>
                <span class="d-flex align-items-center gap-2">
                    <span class="legend-dot text-muted">—</span> {{ $t('non_working_day') }}
                </span>
            </div>

            <app-loader v-if="loading" />
            <div v-else class="table-responsive">
                <table class="table align-middle mb-0 attendance-report-table">
                    <thead>
                        <tr ref="theadRow1">
                            <th rowspan="2" class="name-col">{{ $t('name_of_attendee') }}</th>
                            <th :colspan="report.days.length" class="text-center">{{ $t('day_of_month') }}</th>
                            <th rowspan="2" class="text-center">{{ $t('total') }}</th>
                        </tr>
                        <tr>
                            <th v-for="d in report.days" :key="d.day" class="text-center day-col"
                                :style="{ top: row1Height + 'px' }">
                                <div class="fw-bold">{{ d.day }}</div>
                                <div class="text-muted" style="font-size:0.7rem;font-weight:400">{{ d.name }}</div>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(employee, index) in report.employees" :key="employee.id">
                            <td class="name-col">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="text-muted" style="min-width:18px">{{ index + 1 }}.</span>
                                    <img v-if="employee.profile_picture" :src="employee.profile_picture"
                                        :alt="employee.name" class="emp-avatar-img">
                                    <div v-else class="emp-avatar-initials">
                                        {{ (employee.name ?? '?').split(' ').map(w => w[0]).slice(0, 2).join('').toUpperCase() }}
                                    </div>
                                    <div>
                                        <strong>{{ employee.name }}</strong>
                                        <div class="text-muted small">{{ employee.department }}</div>
                                    </div>
                                </div>
                            </td>
                            <td v-for="d in report.days" :key="d.day" class="text-center day-col">
                                <div class="position-relative d-inline-block">
                                    <span class="status-pill" :class="cellClass[employee.days[d.day]]">
                                        {{ employee.days[d.day] }}
                                    </span>
                                    <span v-if="employee.late_days?.includes(d.day)"
                                        class="late-dot" title="Late"></span>
                                </div>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-light text-dark border">{{ employee.total_present }}</span>
                            </td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr>
                            <th class="name-col">{{ $t('total') }}</th>
                            <th v-for="d in report.days" :key="d.day" class="text-center day-col">
                                {{ report.daily_totals[d.day] ?? 0 }}
                            </th>
                            <th></th>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div class="card-body d-flex flex-wrap gap-3 border-top">
                <span class="badge rounded-pill bg-success-subtle text-success px-3 py-2">
                    {{ report.summary.present ?? 0 }} {{ $t('present').toUpperCase() }}
                </span>
                <span class="badge rounded-pill bg-danger-subtle text-danger px-3 py-2">
                    {{ report.summary.absent ?? 0 }} {{ $t('absent').toUpperCase() }}
                </span>
                <span class="badge rounded-pill bg-info-subtle text-info px-3 py-2">
                    {{ report.summary.leave ?? 0 }} {{ $t('leave').toUpperCase() }}
                </span>
                <span class="badge rounded-pill bg-warning-subtle text-warning px-3 py-2">
                    {{ report.summary.holiday ?? 0 }} {{ $t('holiday').toUpperCase() }}
                </span>
                <span class="badge rounded-pill px-3 py-2" style="background:#fff3e0;color:#f97316;border:1px solid #f97316">
                    {{ report.summary.late ?? 0 }} {{ $t('late').toUpperCase() }}
                </span>
                <span class="badge rounded-pill bg-light text-dark border px-3 py-2">
                    {{ report.summary.employees ?? 0 }} {{ $t('employees').toUpperCase() }}
                </span>
            </div>
        </div>
    </div>
</template>

<style scoped lang="scss">
.table-responsive {
    max-height: 65vh;
    overflow-y: auto;
}

.attendance-report-table thead th {
    position: sticky;
    top: 0;
    background: #fff;
    z-index: 2;
}

.attendance-report-table thead .name-col {
    z-index: 3;
}

.name-col {
    min-width: 220px;
    position: sticky;
    left: 0;
    background: #fff;
    z-index: 1;
}

.day-col {
    min-width: 42px;
}

.status-pill {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 26px;
    height: 26px;
    border-radius: 50%;
    font-weight: 700;
    font-size: 0.8rem;
}

.legend-dot {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 22px;
    height: 22px;
    border-radius: 50%;
    font-weight: 700;
    font-size: 0.75rem;
}

.emp-avatar-img {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    object-fit: cover;
    flex-shrink: 0;
    border: 2px solid #e2e8f0;
}

.emp-avatar-initials {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: linear-gradient(135deg, #4f46e5, #6366f1);
    color: #fff;
    font-size: 0.7rem;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.late-dot {
    position: absolute;
    top: -4px;
    right: -4px;
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background: #f97316;
    border: 2px solid #fff;
    box-shadow: 0 0 0 1px #f97316;
}

.late-dot-legend {
    display: inline-block;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background: #f97316;
    border: 2px solid #fff;
    box-shadow: 0 0 0 1px #f97316;
    flex-shrink: 0;
}
</style>
