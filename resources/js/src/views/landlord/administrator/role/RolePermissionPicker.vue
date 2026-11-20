<script setup>
import { ref, computed, markRaw } from "vue";
import {
    Search, Check, Cart, Group, UserBadgeCheck,
    Wallet, ShopFourTiles, StatsReport, Settings, Folder, Xmark, Home, Package,
    ShoppingBag, Truck, DollarCircle, Bank, GraphUp, Suitcase, Calendar, ClipboardCheck,
    Barcode,
    ProfileCircle
} from "@iconoir/vue";

const props = defineProps({
    permissions: {
        type: Object,
        default: () => ({}),
    },
    modelValue: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(["update:modelValue"]);

const searchQuery = ref("");
const activeModule = ref(null);

/* ── Group name -> category mapping ──────────────────────────────────
 * Mirrors the sidebar menu 1:1 (same grouping and order as the sidebar
 * dropdowns in LandlordSidebar.vue and the ordering the backend returns
 * from RoleController::permission()) so admins can find a permission by
 * matching what they already see in the navigation.
 */
const CATEGORY_MAP = {
    dashboards: "dashboard",

    products: "products", pre_owneds_products: "products", categories: "products",
    brands: "products", pages: "products", attributes: "products",
    specification_groups: "products", product_specifications: "products",
    barcodes: "barcodes", barcodes: "barcodes",

    purchases: "purchases", purchase_returns: "purchases",

    sales: "sales", sale_returns: "sales",

    couriers: "couriers",

    stock_transfers: "stock_cash_transfer", cash_transfers: "stock_cash_transfer",

    customers: "people", online_contacts: "people", suppliers: "people",

    payments: "payments", payment_records: "payments",

    expenses: "expenses", expense_categories: "expenses",

    incomes: "incomes", income_categories: "incomes",

    investments: "investments", investment_categories: "investments",

    directors: "directors", directors: "directors",


    dealers: "partners", retailers: "partners", b2b: "partners",
    franchises: "partners", partners: "partners",

    seo: "seo_orders", orders: "seo_orders",

    reels_videos: "ecommerce",
    elites: "ecommerce",
    popup_dialogs: "ecommerce",
    cares: "ecommerce",
    medias: "ecommerce",
    offers: "ecommerce",

    coupons: "ecommerce",
    news: "ecommerce",
    blogs: "ecommerce",
    sliders: "ecommerce",
    gift_products: "ecommerce",
    warranties: "ecommerce",
    product_verifies: "ecommerce",
    our_teams: "ecommerce",
    reviews: "ecommerce",
    emi_banks: "ecommerce",
    policy_settings: "ecommerce",

    careers: "careers",

    faq_categories: "faqs", faqs: "faqs",

    outlets: "outlets",

    hrm_employees: "hrm_employee", hrm_designations: "hrm_employee",
    hrm_employee_statuses: "hrm_employee", hrm_departments: "hrm_employee",

    hrm_attendance: "hrm_attendance", hrm_attendances: "hrm_attendance",

    hrm_leaves: "hrm_leaves",

    hrm_assets: "hrm_assets",

    hrm_payrun: "hrm_payroll", payroll: "hrm_payroll",

    hrm_holidays: "hrm_other", hrm_events: "hrm_other", hrm_announcements: "hrm_other",

    ledgers: "ledger",

    reports: "reports", report_downloads: "reports",

    closing_statements: "closing_statement",

    users: "users_roles", roles: "users_roles",

    settings: "settings", payment_methods: "settings", shops: "settings", ecommerces: "settings",
};

const CATEGORIES = [
    { key: "dashboard", label: "Dashboard", icon: markRaw(Home) },
    { key: "products", label: "Products", icon: markRaw(Package) },
    { key: "barcodes", label: "Barcodes", icon: markRaw(Barcode) },
    { key: "purchases", label: "Purchases", icon: markRaw(Cart) },
    { key: "sales", label: "Sales", icon: markRaw(ShoppingBag) },
    { key: "couriers", label: "Couriers", icon: markRaw(Truck) },
    { key: "stock_cash_transfer", label: "Stock & Cash Transfer", icon: markRaw(Truck) },
    { key: "people", label: "People", icon: markRaw(Group) },
    { key: "payments", label: "Payments", icon: markRaw(DollarCircle) },
    { key: "expenses", label: "Expenses", icon: markRaw(Wallet) },
    { key: "incomes", label: "Incomes", icon: markRaw(Bank) },
    { key: "investments", label: "Investments", icon: markRaw(GraphUp) },
    { key: "partners", label: "Partners", icon: markRaw(Suitcase) },
    { key: "directors", label: "Directors", icon: markRaw(ProfileCircle) },
    { key: "seo_orders", label: "SEO & Orders", icon: markRaw(Search) },
    { key: "ecommerce", label: "Ecommerce", icon: markRaw(ShopFourTiles) },
    { key: "careers", label: "Careers", icon: markRaw(Suitcase) },
    { key: "faqs", label: "FAQs", icon: markRaw(Folder) },
    { key: "outlets", label: "Outlets", icon: markRaw(ShopFourTiles) },
    { key: "hrm_employee", label: "HRM: Employee", icon: markRaw(UserBadgeCheck) },
    { key: "hrm_attendance", label: "HRM: Attendance", icon: markRaw(Calendar) },
    { key: "hrm_leaves", label: "HRM: Leaves", icon: markRaw(Calendar) },
    { key: "hrm_assets", label: "HRM: Assets", icon: markRaw(Package) },
    { key: "hrm_payroll", label: "HRM: Payroll", icon: markRaw(Wallet) },
    { key: "hrm_other", label: "HRM: Holidays, Events & Announcements", icon: markRaw(Calendar) },
    { key: "ledger", label: "Ledger", icon: markRaw(Bank) },
    { key: "reports", label: "Reports", icon: markRaw(StatsReport) },
    { key: "closing_statement", label: "Daily Closing Statement", icon: markRaw(ClipboardCheck) },
    { key: "users_roles", label: "Users & Roles", icon: markRaw(Group) },
    { key: "settings", label: "Settings", icon: markRaw(Settings) },
    { key: "other", label: "Other", icon: markRaw(Folder) },
];

const prettyLabel = (groupName) => groupName
    .split("_")
    .map((w) => w.charAt(0).toUpperCase() + w.slice(1))
    .join(" ");

/* ── Build categories -> modules structure from the raw grouped data ── */
const categorizedGroups = computed(() => {
    const groupNames = Object.keys(props.permissions || {});
    const byCategory = {};

    groupNames.forEach((groupName) => {
        const categoryKey = CATEGORY_MAP[groupName] || "other";
        if (!byCategory[categoryKey]) byCategory[categoryKey] = [];
        byCategory[categoryKey].push(groupName);
    });

    const categorized = CATEGORIES
        .map((category) => ({
            ...category,
            modules: byCategory[category.key] || [],
        }))
        .filter((category) => category.modules.length);

    if (!activeModule.value && categorized.length) {
        activeModule.value = categorized[0].modules[0] || null;
    }

    return categorized;
});

/* ── Selection helpers ─────────────────────────────────────────────── */
const permissionLength = computed(() => {
    let i = 0;
    for (const groupName in props.permissions) {
        i += props.permissions[groupName].length;
    }
    return i;
});

const selectedCount = computed(() => props.modelValue.length);

const isPermissionChecked = (id) => props.modelValue.includes(id);

const togglePermission = (id) => {
    const selected = [...props.modelValue];
    const index = selected.indexOf(id);
    if (index === -1) selected.push(id);
    else selected.splice(index, 1);
    emit("update:modelValue", selected);
};

const moduleSelectedCount = (groupName) => {
    const perms = props.permissions[groupName] || [];
    return perms.filter((p) => isPermissionChecked(p.id)).length;
};

const isModuleFullySelected = (groupName) => {
    const perms = props.permissions[groupName] || [];
    return perms.length > 0 && perms.every((p) => isPermissionChecked(p.id));
};

const toggleModule = (groupName) => {
    const perms = props.permissions[groupName] || [];
    const ids = perms.map((p) => p.id);
    if (isModuleFullySelected(groupName)) {
        emit("update:modelValue", props.modelValue.filter((id) => !ids.includes(id)));
    } else {
        const selected = [...props.modelValue];
        ids.forEach((id) => {
            if (!selected.includes(id)) selected.push(id);
        });
        emit("update:modelValue", selected);
    }
};

const categorySelectedCount = (category) => {
    return category.modules.reduce((sum, groupName) => sum + moduleSelectedCount(groupName), 0);
};

const categoryTotalCount = (category) => {
    return category.modules.reduce((sum, groupName) => sum + (props.permissions[groupName]?.length || 0), 0);
};

const selectAll = () => {
    const all = [];
    for (const groupName in props.permissions) {
        props.permissions[groupName].forEach((p) => all.push(p.id));
    }
    emit("update:modelValue", all);
};

const clearAll = () => {
    emit("update:modelValue", []);
};

const selectModule = (groupName) => {
    activeModule.value = groupName;
    searchQuery.value = "";
};

/* ── Search across module names and permission labels ───────────────── */
const isSearching = computed(() => searchQuery.value.trim().length > 0);

const searchResults = computed(() => {
    if (!isSearching.value) return [];
    const query = searchQuery.value.trim().toLowerCase();
    const results = [];
    for (const groupName in props.permissions) {
        const moduleMatches = prettyLabel(groupName).toLowerCase().includes(query);
        props.permissions[groupName].forEach((permission) => {
            if (moduleMatches || permission.label.toLowerCase().includes(query)) {
                results.push({ groupName, permission });
            }
        });
    }
    return results;
});

const visibleCategories = computed(() => {
    if (!isSearching.value) return categorizedGroups.value;
    const matchedGroups = new Set(searchResults.value.map((r) => r.groupName));
    return categorizedGroups.value
        .map((category) => ({
            ...category,
            modules: category.modules.filter((g) => matchedGroups.has(g)),
        }))
        .filter((category) => category.modules.length);
});

const activeModulePermissions = computed(() => {
    if (!activeModule.value) return [];
    return props.permissions[activeModule.value] || [];
});
</script>

<template>
    <div class="permission-picker">
        <!-- Summary bar -->
        <div class="picker-summary">
            <div class="rail-search">
                <Search width="16" height="16" />
                <input type="text" v-model="searchQuery" :placeholder="$t('search_permissions_hint')" />
                <Xmark v-if="searchQuery" width="14" height="14" class="clear-search" @click="searchQuery = ''" />
            </div>
            <div class="summary-count">
                <strong>{{ selectedCount }}</strong> / {{ permissionLength }} {{ $t('total_permissions') }}
            </div>
            <div class="summary-actions">
                <button type="button" class="btn btn-sm btn-outline-primary" @click="selectAll">
                    {{ $t('select_all_permission') }}
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary" @click="clearAll">
                    {{ $t('clear_all') }}
                </button>
            </div>
        </div>

        <div class="picker-body">
            <!-- Left rail: every category and module always visible, no expand/collapse -->
            <div class="picker-rail">
                <div class="rail-categories">
                    <div v-for="category in visibleCategories" :key="category.key" class="rail-category">
                        <div class="category-header">
                            <component :is="category.icon" width="18" height="18" class="category-icon" />
                            <span class="category-label">{{ category.label }}</span>
                            <span class="category-count">{{ categorySelectedCount(category) }}/{{
                                categoryTotalCount(category) }}</span>
                        </div>

                        <div class="category-modules">
                            <button v-for="groupName in category.modules" :key="groupName" type="button"
                                class="module-row" :class="{ active: activeModule === groupName && !isSearching }"
                                @click="selectModule(groupName)">
                                <span class="module-label">{{ prettyLabel(groupName) }}</span>
                                <span class="module-count" :class="{ full: isModuleFullySelected(groupName) }">
                                    {{ moduleSelectedCount(groupName) }}/{{ permissions[groupName]?.length || 0 }}
                                </span>
                            </button>
                        </div>
                    </div>

                    <div v-if="!visibleCategories.length" class="rail-empty">
                        {{ $t('no_matching_permissions') }}
                    </div>
                </div>
            </div>

            <!-- Right pane -->
            <div class="picker-pane">
                <template v-if="isSearching">
                    <div class="pane-header">
                        <h6 class="mb-0">{{ $t('search_results') }} ({{ searchResults.length }})</h6>
                    </div>
                    <div class="permission-grid">
                        <label v-for="({ groupName, permission }) in searchResults" :key="permission.id"
                            class="permission-card" :class="{ selected: isPermissionChecked(permission.id) }">
                            <input type="checkbox" class="visually-hidden" :checked="isPermissionChecked(permission.id)"
                                @change="togglePermission(permission.id)">
                            <span class="permission-label">{{ permission.label }}</span>
                            <span class="permission-module">{{ prettyLabel(groupName) }}</span>
                            <span class="check-icon" :class="{ checkmark: isPermissionChecked(permission.id) }">
                                <Check width="12" height="12" />
                            </span>
                        </label>
                    </div>
                </template>

                <template v-else-if="activeModule">
                    <div class="pane-header">
                        <h6 class="mb-0">{{ prettyLabel(activeModule) }}</h6>
                        <button type="button" class="btn btn-sm btn-outline-primary"
                            @click="toggleModule(activeModule)">
                            {{ isModuleFullySelected(activeModule) ? $t('deselect_all') : $t('select_all') }}
                        </button>
                    </div>
                    <div class="permission-grid">
                        <label v-for="permission in activeModulePermissions" :key="permission.id"
                            class="permission-card" :class="{ selected: isPermissionChecked(permission.id) }">
                            <input type="checkbox" class="visually-hidden" :checked="isPermissionChecked(permission.id)"
                                @change="togglePermission(permission.id)">
                            <span class="permission-label">{{ permission.label }}</span>
                            <span class="check-icon" :class="{ checkmark: isPermissionChecked(permission.id) }">
                                <Check width="12" height="12" />
                            </span>
                        </label>
                    </div>
                </template>

                <div v-else class="pane-empty">
                    {{ $t('choose_a_module_hint') }}
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped lang="scss">
.permission-picker {
    display: flex;
    flex-direction: column;
    height: calc(100vh - 260px);
    min-height: 480px;
    border: 1px solid var(--border-color);
    border-radius: 10px;
    background: var(--white);
    overflow: hidden;
}

.picker-summary {
    display: flex;
    align-items: center;
    gap: 24px;
    padding: 12px 20px;
    border-bottom: 1px solid var(--border-color);
    flex-shrink: 0;

    .rail-search {
        display: flex;
        align-items: center;
        gap: 8px;
        width: 280px;
        padding: 6px 12px;
        border: 1px solid var(--border-color);
        border-radius: 8px;
        color: var(--secondary-font-color, #6b7280);

        input {
            border: none;
            outline: none;
            flex: 1;
            background: transparent;
            color: var(--font-color);
            font-size: 14px;
        }

        .clear-search {
            cursor: pointer;
            opacity: 0.6;

            &:hover {
                opacity: 1;
            }
        }
    }

    .summary-count {
        font-size: 14px;
        color: var(--secondary-font-color, #6b7280);
        white-space: nowrap;

        strong {
            color: var(--primary);
            font-size: 18px;
        }
    }

    .summary-actions {
        display: flex;
        gap: 8px;
        margin-left: auto;
    }
}

.picker-body {
    flex: 1 1 auto;
    display: flex;
    min-height: 0;
}

.picker-rail {
    width: 320px;
    flex-shrink: 0;
    border-right: 1px solid var(--border-color);
    display: flex;
    flex-direction: column;
    min-height: 0;
}

.rail-categories {
    flex: 1 1 auto;
    overflow-y: auto;
    padding: 8px;
}

.rail-empty,
.pane-empty {
    padding: 40px 16px;
    text-align: center;
    color: var(--secondary-font-color, #6b7280);
    font-size: 14px;
}

.rail-category {
    margin-bottom: 4px;
    border-radius: 8px;
    overflow: hidden;
}

.category-header {
    width: 100%;
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 12px;

    .category-icon {
        color: var(--primary);
        flex-shrink: 0;
    }

    .category-label {
        font-weight: 600;
        font-size: 14px;
        color: var(--font-color);
        flex: 1;
    }

    .category-count {
        font-size: 12px;
        color: var(--secondary-font-color, #6b7280);
        font-variant-numeric: tabular-nums;
    }
}

.category-modules {
    padding: 2px 8px 8px 40px;
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.module-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    padding: 7px 10px;
    background: transparent;
    border: none;
    border-radius: 6px;
    text-align: left;
    cursor: pointer;
    font-size: 13px;
    color: var(--font-color);
    transition: background 0.15s ease;

    &:hover {
        background: var(--dashboard-bg, #f7f8fa);
    }

    &.active {
        background: var(--primary);
        color: var(--white);

        .module-count {
            color: var(--white);
        }
    }

    .module-count {
        font-size: 11px;
        color: var(--secondary-font-color, #6b7280);
        font-variant-numeric: tabular-nums;

        &.full {
            font-weight: 700;
        }
    }
}

.picker-pane {
    flex: 1 1 auto;
    display: flex;
    flex-direction: column;
    min-height: 0;
    padding: 20px 24px;
    overflow-y: auto;
}

.pane-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 16px;
    flex-shrink: 0;
}

.permission-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
    gap: 12px;
    align-content: start;
}

.permission-card {
    position: relative;
    display: flex;
    flex-direction: column;
    gap: 2px;
    padding: 12px 36px 12px 14px;
    border: 1px solid var(--border-color);
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s ease;

    &:hover {
        border-color: var(--primary);
    }

    &.selected {
        border-color: var(--primary);
        background: var(--dashboard-bg, #f7f8fa);
    }

    .permission-label {
        font-size: 13px;
        font-weight: 500;
        color: var(--font-color);
    }

    .permission-module {
        font-size: 11px;
        color: var(--secondary-font-color, #6b7280);
    }

    .check-icon {
        position: absolute;
        top: 10px;
        right: 10px;
        width: 18px;
        height: 18px;
        border-radius: 50%;
        background-color: var(--dashboard-bg, #e5e7eb);
        display: flex;
        justify-content: center;
        align-items: center;
        flex-shrink: 0;

        svg {
            opacity: 0;
        }

        &.checkmark {
            background-color: var(--primary);
            color: var(--white);

            svg {
                opacity: 1;
            }
        }
    }
}
</style>
