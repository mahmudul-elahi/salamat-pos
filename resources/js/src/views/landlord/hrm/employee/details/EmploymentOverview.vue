<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRoute } from 'vue-router';
import Axios from '@/services/axios/index.js';
import { HRM_EMPLOYEES } from '@/services/endpoints/hrm.js';

const route = useRoute();
const employeeId = route.params.id;

const profile = ref(null);
const loading = ref(true);

const fetchProfile = async () => {
    try {
        const { data } = await Axios.get(`${HRM_EMPLOYEES}/${employeeId}/profile`);
        profile.value = data;
    } finally {
        loading.value = false;
    }
};

onMounted(fetchProfile);

const statusStyle = computed(() => {
    const color = profile.value?.employment_status_color;
    if (!color) return {};
    return {
        backgroundColor: color + '22',
        color: color,
        borderColor: color + '55',
    };
});

const formatDate = (dateStr) => {
    if (!dateStr) return '—';
    return new Date(dateStr).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
};

const overviewItems = computed(() => [
    {
        label: 'Job Title',
        value: profile.value?.job_title ?? '—',
        iconClass: 'ov-icon-title',
        icon: 'briefcase',
    },
    {
        label: 'Joining Date',
        value: formatDate(profile.value?.joining_date),
        iconClass: 'ov-icon-date',
        icon: 'calendar',
    },
    {
        label: 'Job Level',
        value: profile.value?.job_level ?? '—',
        iconClass: 'ov-icon-level',
        icon: 'level',
    },
]);
</script>

<template>
    <div>
        <div class="detail-section-header">
            <h6 class="detail-section-title">Employment Overview</h6>
            <p class="detail-section-sub">Current employment and position details</p>
        </div>

        <app-loader v-if="loading" />

        <div v-else class="ov-grid">
            <!-- Joining Date -->
            <div class="ov-card">
                <div class="ov-card-icon ov-icon-date">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                        <line x1="16" y1="2" x2="16" y2="6"/>
                        <line x1="8" y1="2" x2="8" y2="6"/>
                        <line x1="3" y1="10" x2="21" y2="10"/>
                    </svg>
                </div>
                <div class="ov-card-body">
                    <p class="ov-card-label">Joining Date</p>
                    <p class="ov-card-value">{{ formatDate(profile?.joining_date) }}</p>
                </div>
            </div>

            <!-- Employment Status -->
            <div class="ov-card">
                <div class="ov-card-icon ov-icon-status">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M22 11.08V12a10 10 0 11-5.93-9.14"/>
                        <polyline points="22 4 12 14.01 9 11.01"/>
                    </svg>
                </div>
                <div class="ov-card-body">
                    <p class="ov-card-label">Employment Status</p>
                    <p class="ov-card-value">
                        <span v-if="profile?.employment_status" class="ov-status-badge" :style="statusStyle">
                            {{ profile.employment_status }}
                        </span>
                        <span v-else>—</span>
                    </p>
                </div>
            </div>

            <!-- Department -->
            <div class="ov-card">
                <div class="ov-card-icon ov-icon-dept">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/>
                        <polyline points="9 22 9 12 15 12 15 22"/>
                    </svg>
                </div>
                <div class="ov-card-body">
                    <p class="ov-card-label">Department</p>
                    <p class="ov-card-value">{{ profile?.department ?? '—' }}</p>
                </div>
            </div>

            <!-- Designation -->
            <div class="ov-card">
                <div class="ov-card-icon ov-icon-desig">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="8" r="4"/>
                        <path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/>
                    </svg>
                </div>
                <div class="ov-card-body">
                    <p class="ov-card-label">Designation</p>
                    <p class="ov-card-value">{{ profile?.designation ?? '—' }}</p>
                </div>
            </div>

            <!-- Role -->
            <div class="ov-card">
                <div class="ov-card-icon ov-icon-role">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 2l3 6.3L22 9.3l-5 4.9 1.2 6.8L12 18l-6.2 3 1.2-6.8L2 9.3l7-.7z"/>
                    </svg>
                </div>
                <div class="ov-card-body">
                    <p class="ov-card-label">Role</p>
                    <p class="ov-card-value">
                        <span v-if="profile?.role" class="ov-role-badge">{{ profile.role }}</span>
                        <span v-else>—</span>
                    </p>
                </div>
            </div>

            <!-- Tenants -->
            <div class="ov-card ov-card-full">
                <div class="ov-card-icon ov-icon-tenant">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/>
                        <rect x="9" y="14" width="6" height="8"/>
                    </svg>
                </div>
                <div class="ov-card-body">
                    <p class="ov-card-label">Assigned Shops / Tenants</p>
                    <div class="ov-tenant-list" v-if="profile?.tenants?.length">
                        <span v-for="tenant in profile.tenants" :key="tenant" class="ov-tenant-badge">
                            {{ tenant }}
                        </span>
                    </div>
                    <p v-else class="ov-card-value">—</p>
                </div>
            </div>

        </div>
    </div>
</template>

<style scoped lang="scss">
.detail-section-header {
    padding-bottom: 18px;
    border-bottom: 1px solid var(--border-color);
    margin-bottom: 24px;
}
.detail-section-title {
    font-size: 15px;
    font-weight: 700;
    color: var(--font-color);
    margin: 0 0 4px;
}
.detail-section-sub {
    font-size: 13px;
    color: var(--secondary-font-color, #6b7280);
    margin: 0;
}

.ov-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 16px;

    @media (max-width: 576px) {
        grid-template-columns: 1fr;
    }
}

.ov-card {
    display: flex;
    align-items: flex-start;
    gap: 16px;
    background: var(--dashboard-bg);
    border-radius: 14px;
    padding: 20px;
    transition: box-shadow 0.2s ease;

    &:hover {
        box-shadow: 0 4px 16px rgba(0,0,0,0.06);
    }
}

.ov-card-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;

    svg {
        width: 22px;
        height: 22px;
    }

    &.ov-icon-title {
        background: rgba(43, 135, 104, 0.1);
        color: var(--primary);
    }
    &.ov-icon-date {
        background: rgba(99, 102, 241, 0.1);
        color: #4f46e5;
    }
    &.ov-icon-level {
        background: rgba(245, 158, 11, 0.1);
        color: #d97706;
    }
    &.ov-icon-status {
        background: rgba(34, 197, 94, 0.1);
        color: #16a34a;
    }
    &.ov-icon-dept {
        background: rgba(14, 165, 233, 0.1);
        color: #0284c7;
    }
    &.ov-icon-desig {
        background: rgba(239, 68, 68, 0.1);
        color: #dc2626;
    }
}

.ov-card-body {
    flex: 1;
    min-width: 0;
}

.ov-card-label {
    font-size: 11px;
    font-weight: 500;
    color: var(--secondary-font-color, #6b7280);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin: 0 0 6px;
}

.ov-card-value {
    font-size: 15px;
    font-weight: 600;
    color: var(--font-color);
    margin: 0;
    word-break: break-word;
}

.ov-status-badge {
    display: inline-block;
    padding: 4px 14px;
    border-radius: 20px;
    font-size: 13px;
    font-weight: 600;
    border: 1px solid transparent;
}

.ov-icon-role {
    background: rgba(139, 92, 246, 0.1);
    color: #7c3aed;
}

.ov-role-badge {
    display: inline-block;
    padding: 3px 12px;
    border-radius: 20px;
    font-size: 13px;
    font-weight: 600;
    background: rgba(139, 92, 246, 0.1);
    color: #7c3aed;
}

.ov-icon-tenant {
    background: rgba(20, 184, 166, 0.1);
    color: #0d9488;
}

.ov-card-full {
    grid-column: 1 / -1;
}

.ov-tenant-list {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    margin-top: 2px;
}

.ov-tenant-badge {
    display: inline-block;
    padding: 3px 12px;
    border-radius: 20px;
    font-size: 13px;
    font-weight: 500;
    background: rgba(20, 184, 166, 0.1);
    color: #0d9488;
    border: 1px solid rgba(20, 184, 166, 0.2);
}
</style>
