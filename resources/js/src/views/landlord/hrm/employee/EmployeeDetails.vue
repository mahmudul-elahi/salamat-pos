<script setup>
import { ref, onMounted, markRaw } from 'vue';
import { useRoute } from 'vue-router';
import Axios from '@/services/axios/index.js';
import { HRM_EMPLOYEES } from '@/services/endpoints/hrm.js';
import { useToast } from 'vue-toastification';
import { User, Phone, MapPin, Suitcase, Calendar, LogIn, LogOut, Clock, Attachment, StatsReport, ClipboardCheck, BookmarkBook, MoneySquare, ShieldCheck, Camera } from '@iconoir/vue';
import usePermission from "@/composable/permission/usePermission.js";

import PersonalInformation from './details/PersonalInformation.vue';
import ContactInformation from './details/ContactInformation.vue';
import AddressInformation from './details/AddressInformation.vue';
import EmploymentOverview from './details/EmploymentOverview.vue';
import AttendanceOverview from './details/AttendanceOverview.vue';
import AttendanceTab from './details/AttendanceTab.vue';
import Leave from './details/Leave.vue';
import LeaveAllowances from './details/LeaveAllowances.vue';
import Documents from './details/Documents.vue';
import JobHistory from './details/JobHistory.vue';
import Payroll from './details/Payroll.vue';

const { canAccess } = usePermission();
const route = useRoute();
const toast = useToast();
const employeeId = route.params.id;

const summary = ref(null);
const headerLoading = ref(true);
const avatarInput = ref(null);
const avatarUploading = ref(false);

const fetchSummary = async () => {
    try {
        const { data } = await Axios.get(`${HRM_EMPLOYEES}/${employeeId}/summary`);
        summary.value = data;
    } catch {
        // silent
    } finally {
        headerLoading.value = false;
    }
};

const onAvatarChange = async (e) => {
    const file = e.target.files?.[0];
    if (!file) return;

    const form = new FormData();
    form.append('profile_picture', file);

    try {
        avatarUploading.value = true;
        const { data } = await Axios.post(`${HRM_EMPLOYEES}/${employeeId}/avatar`, form, {
            headers: {
                'Content-Type': 'multipart/form-data',
            }
        });
        summary.value.profile_picture = data.result?.profile_picture;
        toast.success(data.message);
    } catch ({ response }) {
        toast.error(response?.data?.message ?? 'Upload failed.');
    } finally {
        avatarUploading.value = false;
        avatarInput.value.value = '';
    }
};

onMounted(fetchSummary);

const tabs = ref([
    {
        title: 'Personal Information',
        key: 'personal_info',
        component: markRaw(PersonalInformation),
        icon: markRaw(User),
        showHeader: false,
        permission: canAccess('profile_employees')
    },
    {
        title: 'Contact Information',
        key: 'contact_info',
        component: markRaw(ContactInformation),
        icon: markRaw(Phone),
        showHeader: false,
        permission: canAccess('view_employees_contacts')
    },
    {
        title: 'Address Information',
        key: 'address_info',
        component: markRaw(AddressInformation),
        icon: markRaw(MapPin),
        showHeader: false,
        permission: canAccess('view_employees_contact_address')
    },
    {
        title: 'Employment Overview',
        key: 'employment_overview',
        component: markRaw(EmploymentOverview),
        icon: markRaw(Suitcase),
        showHeader: false,
        permission: canAccess('profile_employees')
    },
    {
        title: 'Attendance',
        key: 'attendance',
        component: markRaw(AttendanceTab),
        icon: markRaw(ClipboardCheck),
        showHeader: false,
        permission: canAccess('view_attendances') || canAccess('attendance_stats_employees')
    },
    {
        title: 'Attendance Calendar View',
        key: 'attendances',
        component: markRaw(AttendanceOverview),
        icon: markRaw(Calendar),
        showHeader: false,
        permission: canAccess('attendance_calendar_employees') || canAccess('attendance_stats_employees')
    },
    {
        title: 'Leave Allowances',
        key: 'leave_allowances',
        component: markRaw(LeaveAllowances),
        icon: markRaw(ShieldCheck),
        showHeader: false,
        permission: canAccess('leave_allowances_employees')
    },
    {
        title: 'Leave',
        key: 'leave',
        component: markRaw(Leave),
        icon: markRaw(BookmarkBook),
        showHeader: false,
        permission: canAccess('leave_summary_employees')
    },
    {
        title: 'Payroll',
        key: 'payroll',
        component: markRaw(Payroll),
        icon: markRaw(MoneySquare),
        showHeader: false,
        permission: canAccess('view_payslips')
    },
    {
        title: 'Job History',
        key: 'job_history',
        component: markRaw(JobHistory),
        icon: markRaw(StatsReport),
        showHeader: false,
        permission: canAccess('job_history_employees')
    },
    {
        title: 'Documents',
        key: 'documents',
        component: markRaw(Documents),
        icon: markRaw(Attachment),
        showHeader: false,
        permission: canAccess('view_employee_documents')
    },
]);
</script>

<template>
    <div class="content-wrapper">
        <app-breadcrumb :page-title="summary?.name ?? $t('employee_details')" />

        <!-- Premium Employee Header -->
        <div class="employee-profile-header mb-4">
            <!-- Skeleton while loading -->
            <div v-if="headerLoading" class="d-flex align-items-center gap-4 p-2">
                <span class="placeholder-glow">
                    <span class="placeholder rounded-circle" style="width:80px;height:80px;display:block;"></span>
                </span>
                <div class="placeholder-glow flex-grow-1">
                    <span class="placeholder col-3 d-block mb-2" style="height:20px;border-radius:6px;"></span>
                    <span class="placeholder col-2 d-block" style="height:16px;border-radius:20px;"></span>
                </div>
            </div>

            <div v-else class="emp-header-inner">
                <!-- Identity block -->
                <div class="emp-identity">
                    <div class="emp-avatar-wrap">
                        <img v-if="summary?.profile_picture" :src="summary.profile_picture" :alt="summary.name"
                            class="emp-avatar-img" />
                        <div v-else class="emp-avatar-fallback">
                            <User />
                        </div>
                        <span class="emp-online-dot"></span>
                        <button type="button" class="emp-avatar-upload-btn" :class="{ 'is-uploading': avatarUploading }"
                            :disabled="avatarUploading" @click="avatarInput.click()" title="Change profile picture">
                            <span v-if="avatarUploading" class="emp-avatar-spinner"></span>
                            <Camera v-else />
                        </button>
                        <input ref="avatarInput" type="file" accept="image/jpeg,image/png,image/jpg,image/svg+xml"
                            class="d-none" @change="onAvatarChange" />
                    </div>
                    <div class="emp-identity-info">
                        <h5 class="emp-full-name">{{ summary?.name ?? '—' }}</h5>
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <span class="emp-id-badge">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="2" y="5" width="20" height="14" rx="2" />
                                    <line x1="2" y1="10" x2="22" y2="10" />
                                </svg>
                                {{ summary?.employee_code ?? 'N/A' }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Divider -->
                <div class="emp-header-divider"></div>

                <!-- Stats row -->
                <div class="emp-stats-row">
                    <div class="emp-stat-card">
                        <div class="emp-stat-icon-wrap emp-stat-checkin">
                            <LogIn />
                        </div>
                        <div class="emp-stat-info">
                            <span class="emp-stat-lbl">Check In</span>
                            <span class="emp-stat-val">{{ summary?.last_check_in ?? '—' }}</span>
                        </div>
                    </div>

                    <div class="emp-stat-card">
                        <div class="emp-stat-icon-wrap emp-stat-checkout">
                            <LogOut />
                        </div>
                        <div class="emp-stat-info">
                            <span class="emp-stat-lbl">Check Out</span>
                            <span class="emp-stat-val">{{ summary?.last_check_out ?? '—' }}</span>
                        </div>
                    </div>

                    <div class="emp-stat-card">
                        <div class="emp-stat-icon-wrap emp-stat-shift">
                            <Calendar />
                        </div>
                        <div class="emp-stat-info">
                            <span class="emp-stat-lbl">Work Shift</span>
                            <span class="emp-stat-val">{{ summary?.work_shift ?? '—' }}</span>
                        </div>
                    </div>

                    <div class="emp-stat-card">
                        <div class="emp-stat-icon-wrap emp-stat-duration">
                            <Clock />
                        </div>
                        <div class="emp-stat-info">
                            <span class="emp-stat-lbl">Today Duration</span>
                            <span class="emp-stat-val">{{ summary?.today_work_duration ?? '—' }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Vertical Tab Component -->
        <app-tab id="employee-detail-tab" :tabs="tabs" type="vertical" aria-label="vertical" />
    </div>
</template>

<style scoped lang="scss">
/* ── Header Card ─────────────────────────────────── */
.employee-profile-header {
    background: var(--white);
    border-radius: 16px;
    box-shadow: var(--box-shadow);
    padding: 24px 28px;
    overflow: hidden;
    position: relative;

    &::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, var(--primary) 0%, rgba(43, 135, 104, 0.4) 100%);
    }
}

.emp-header-inner {
    display: flex;
    align-items: center;
    gap: 28px;
    flex-wrap: wrap;
}

/* ── Identity ─────────────────────────────────────── */
.emp-identity {
    display: flex;
    align-items: center;
    gap: 18px;
    flex-shrink: 0;
}

.emp-avatar-wrap {
    position: relative;
    flex-shrink: 0;
}

.emp-avatar-img {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    object-fit: cover;
    border: 3px solid var(--primary);
    box-shadow: 0 0 0 5px rgba(43, 135, 104, 0.12);
}

.emp-avatar-fallback {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: linear-gradient(135deg, rgba(43, 135, 104, 0.08) 0%, rgba(43, 135, 104, 0.18) 100%);
    border: 3px solid var(--primary);
    box-shadow: 0 0 0 5px rgba(43, 135, 104, 0.12);
    display: flex;
    align-items: center;
    justify-content: center;

    svg {
        width: 34px;
        height: 34px;
        color: var(--primary);
        stroke-width: 1.5;
    }
}

.emp-online-dot {
    position: absolute;
    bottom: 4px;
    right: 4px;
    width: 14px;
    height: 14px;
    background: #22c55e;
    border-radius: 50%;
    border: 2px solid var(--white);
}

.emp-avatar-upload-btn {
    position: absolute;
    inset: 0;
    border-radius: 50%;
    background: rgba(0, 0, 0, 0.45);
    border: none;
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    cursor: pointer;
    transition: opacity 0.2s ease;

    svg {
        width: 22px;
        height: 22px;
        color: #fff;
        stroke-width: 1.8;
    }

    &.is-uploading {
        opacity: 1;
    }
}

.emp-avatar-wrap:hover .emp-avatar-upload-btn {
    opacity: 1;
}

.emp-avatar-spinner {
    width: 20px;
    height: 20px;
    border: 2px solid rgba(255, 255, 255, 0.4);
    border-top-color: #fff;
    border-radius: 50%;
    animation: emp-spin 0.7s linear infinite;
}

@keyframes emp-spin {
    to {
        transform: rotate(360deg);
    }
}

.emp-identity-info {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.emp-full-name {
    font-size: 1.15rem;
    font-weight: 700;
    color: var(--font-color);
    margin: 0;
    line-height: 1.2;
}

.emp-id-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    background: rgba(43, 135, 104, 0.08);
    color: var(--primary);
    border: 1px solid rgba(43, 135, 104, 0.2);
    font-size: 12px;
    font-weight: 600;
    padding: 4px 10px;
    border-radius: 20px;
    letter-spacing: 0.4px;

    svg {
        flex-shrink: 0;
    }
}

/* ── Divider ─────────────────────────────────────── */
.emp-header-divider {
    width: 1px;
    height: 60px;
    background: var(--border-color);
    flex-shrink: 0;

    @media (max-width: 768px) {
        display: none;
    }
}

/* ── Stats ───────────────────────────────────────── */
.emp-stats-row {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
    flex: 1;
}

.emp-stat-card {
    display: flex;
    align-items: center;
    gap: 12px;
    background: var(--dashboard-bg);
    border-radius: 12px;
    padding: 14px 18px;
    flex: 1 1 140px;
    min-width: 140px;
    transition: box-shadow 0.2s ease;

    &:hover {
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06);
    }
}

.emp-stat-icon-wrap {
    width: 42px;
    height: 42px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;

    svg {
        width: 20px;
        height: 20px;
        stroke-width: 1.8;
    }

    &.emp-stat-checkin {
        background: rgba(34, 197, 94, 0.12);

        svg {
            color: #16a34a;
        }
    }

    &.emp-stat-checkout {
        background: rgba(239, 68, 68, 0.12);

        svg {
            color: #dc2626;
        }
    }

    &.emp-stat-shift {
        background: rgba(99, 102, 241, 0.12);

        svg {
            color: #4f46e5;
        }
    }

    &.emp-stat-duration {
        background: rgba(245, 158, 11, 0.12);

        svg {
            color: #d97706;
        }
    }
}

.emp-stat-info {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.emp-stat-lbl {
    font-size: 11px;
    font-weight: 500;
    color: var(--secondary-font-color, #6b7280);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    line-height: 1;
}

.emp-stat-val {
    font-size: 14px;
    font-weight: 700;
    color: var(--font-color);
    line-height: 1.3;
    margin-top: 3px;
}

@media (max-width: 768px) {
    .emp-header-inner {
        flex-direction: column;
        align-items: flex-start;
    }

    .emp-stats-row {
        width: 100%;
    }
}
</style>
