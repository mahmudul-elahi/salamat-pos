<script setup>
import { ref, onMounted, computed, h } from "vue";
import { useI18n } from "vue-i18n";
import usePermission from "@/composable/permission/usePermission.js";
import {
    SELECTED_LEDGER_CONTACTS,
    CUSTOMER_LEDGER_REPORTS,
    SELECTED_SHOPS,
    CUSTOMER_LEDGER_REPORTS_PDF,
} from "@/services/endpoints/landlord.js";
import { formatDateToLocal, numberWithCurrencySymbol } from "@/utils/helper";
import Axios from "@/services/axios/index.js";
import { useToast } from "vue-toastification";
import Avatar from "@/views/global/avatar/Avatar.vue";
import LedgerPaymentMethodColumn from "@/views/landlord/common/component/LedgerPaymentMethodColumn.vue";



const { canAccess, isSupperAdmin, canFilterByShop } = usePermission();

const { t } = useI18n();
const props = defineProps({
    id: {
        type: [String, Number],
        required: true
    }
})

const toast = useToast();
const contact = ref(null);


const preloader = ref(false);
const ledgerContacts = async () => {
    try {
        preloader.value = true;
        await Axios.get(`${SELECTED_LEDGER_CONTACTS}/${props.id}`).then((res) => {
            contact.value = res.data;
        });
    } catch {
        console.log('error');
    } finally {
        preloader.value = false;
    }
}

onMounted(async () => {
    await ledgerContacts();
});
const currentFilters = ref({});
const tableId = ref('customer-ledger-reports-table')


const options = ref({
    url: `${CUSTOMER_LEDGER_REPORTS}/${props.id}`,
    pageSize: 5,
    rowLimit: 10,
    orderBy: 'desc',
    showGridView: false,
    actionType: 'dropdown',
    paginationType: 'pagination',
    enableRowSelect: false,
    enableBulkSelect: false,
    showSearch: true,
    showFilter: isSupperAdmin(),
    boxShadow: false,
    hideTotal: true,
    columns: [
        {
            title: t('date'),
            type: 'object',
            key: 'date',
            modifier: (date => formatDateToLocal(date))
        },
        {
            title: t('invoice_number'),
            type: 'text',
            key: 'full_invoice_number',
        },
        {
            title: t('type'),
            type: 'text',
            key: 'type',
        },
        {
            title: t('payment_method'),
            type: 'component',
            key: 'payment_method',
            componentName: h(LedgerPaymentMethodColumn),
        },
        {
            title: t('debit'),
            type: 'object',
            key: 'debit',
            accountAble: true,
            modifier: (debit => numberWithCurrencySymbol(debit))
        },
        {
            title: t('credit'),
            type: 'object',
            key: 'credit',
            accountAble: true,
            modifier: (credit => numberWithCurrencySymbol(credit))
        },
        {
            title: t('balance'),
            type: 'object',
            key: 'balance',
            accountAble: true,
            modifier: (balance => numberWithCurrencySymbol(balance))
        },

    ],
    filters: [
        {
            label: t('warehouse_or_store_name'),
            type: 'advance-search-select',
            key: 'shop',
            listKeyField: 'id',
            listValueField: 'name',
            rowPerLoad: 10,
            fetchUrl: `${SELECTED_SHOPS}`,
            permission: canFilterByShop(),
        },
    ],
    actions: [
    ],
})

const actionTiger = (row, action) => {

}


const onFilterChange = (filters) => {
    currentFilters.value = filters;
};

const downloadLedger = async () => {
    try {
        const payload = {
            shop: currentFilters.value.shop || '',
            contactId: props.id,
        };

        const response = await Axios.get(
            `${CUSTOMER_LEDGER_REPORTS_PDF}/${props.id}`,
            {
                params: payload,
                responseType: "blob"
            }
        );

        // ✅ Open PDF
        const file = new Blob([response.data], { type: "application/pdf" });
        const fileURL = URL.createObjectURL(file);
        window.open(fileURL);

    } catch (error) {
        if (error.response && error.response.data instanceof Blob) {
            const reader = new FileReader();

            reader.onload = () => {
                try {
                    const errorData = JSON.parse(reader.result);
                    toast.error(errorData.message || 'Something went wrong');
                } catch {
                    toast.error('Failed to download ledger');
                }
            };

            reader.readAsText(error.response.data);
        } else {
            toast.error(error?.response?.data?.message || 'Something went wrong');
        }
    }
};


</script>

<template>
    <div class="content-wrapper">

        <app-loader v-if="preloader" />
        <template v-else>
            <div v-if="contact" class="contact-card">
                <div class="contact-card__avatar">
                    <avatar :image="contact.profile_picture" :name="contact.name" active="success" />
                </div>
                <div class="contact-card__body">
                    <div class="contact-card__header">
                        <div>
                            <h2 class="contact-card__name">{{ contact.name }}</h2>
                            <span v-if="contact.business_name" class="contact-card__business">{{ contact.business_name
                            }}</span>
                        </div>
                    </div>
                    <div class="contact-card__grid">
                        <div v-if="contact.phone" class="contact-card__item">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="2">
                                <path
                                    d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12 19.79 19.79 0 0 1 1.62 3.38 2 2 0 0 1 3.6 1.21h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 8.91a16 16 0 0 0 6.06 6.06l.86-.86a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 21.73 17z" />
                            </svg>
                            <span>{{ contact.phone }}</span>
                        </div>

                        <div v-if="contact.email" class="contact-card__item">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="2">
                                <rect width="20" height="16" x="2" y="4" rx="2" />
                                <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7" />
                            </svg>
                            <span>{{ contact.email }}</span>
                        </div>
                        <div v-if="contact.address" class="contact-card__item">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="2">
                                <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z" />
                                <circle cx="12" cy="10" r="3" />
                            </svg>
                            <span>{{ contact.address }}</span>
                        </div>
                        <div v-if="contact.gender" class="contact-card__item">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="8" r="5" />
                                <path d="M20 21a8 8 0 1 0-16 0" />
                            </svg>
                            <span style="text-transform: capitalize">{{ contact.gender }}</span>
                        </div>
                        <div v-if="contact.date_of_birth" class="contact-card__item">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="2">
                                <rect width="18" height="18" x="3" y="4" rx="2" ry="2" />
                                <line x1="16" x2="16" y1="2" y2="6" />
                                <line x1="8" x2="8" y1="2" y2="6" />
                                <line x1="3" x2="21" y1="10" y2="10" />
                            </svg>
                            <span>{{ formatDateToLocal(contact.date_of_birth) }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </template>

        <app-breadcrumb :page-title="$t('ledger_report')" />

        <!-- Contact Info Card -->
        <app-datatable :id="tableId" :options="options" @action="actionTiger" :add-button-text="$t('download')"
            :button-visible="canAccess('download_customer_ledger_reports')" @buttonAction="downloadLedger"
            @filter-change="onFilterChange" />
    </div>
</template>

<style scoped lang="scss">
.contact-card {
    display: flex;
    align-items: flex-start;
    gap: 1.5rem;
    background: linear-gradient(135deg, #ffffff 0%, #f8faff 100%);
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    padding: 1.75rem;
    margin-bottom: 1.5rem;
    box-shadow: 0 4px 24px rgba(99, 102, 241, 0.08), 0 1px 3px rgba(0, 0, 0, 0.06);
    position: relative;
    overflow: hidden;

    &::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 4px;
        height: 100%;
        background: linear-gradient(180deg, #6366f1, #8b5cf6);
        border-radius: 16px 0 0 16px;
    }

    &__avatar {
        flex-shrink: 0;
        width: 80px;
        height: 80px;
        border-radius: 50%;
        font-size: 1.5rem;
        font-weight: 700;
        display: flex;
        align-items: center;
        justify-content: center;
        letter-spacing: 1px;
    }

    &__body {
        flex: 1;
        min-width: 0;
    }

    &__header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 0.75rem;
        margin-bottom: 1rem;
    }

    &__name {
        font-size: 1.25rem;
        font-weight: 700;
        color: #1e293b;
        margin: 0 0 0.2rem;
        line-height: 1.3;
    }

    &__business {
        font-size: 0.85rem;
        color: #64748b;
        font-weight: 500;
    }

    &__badges {
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
    }

    &__grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
        gap: 0.6rem 1.5rem;
    }

    &__item {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        color: #475569;
        font-size: 0.875rem;

        svg {
            width: 15px;
            height: 15px;
            flex-shrink: 0;
            color: #6366f1;
        }

        span {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
    }
}

.badge {
    display: inline-flex;
    align-items: center;
    padding: 0.25rem 0.75rem;
    border-radius: 999px;
    font-size: 0.75rem;
    font-weight: 600;
    letter-spacing: 0.4px;
    text-transform: capitalize;

    &--type {
        background: #ede9fe;
        color: #6d28d9;
    }

    &--active {
        background: #dcfce7;
        color: #15803d;
    }

    &--inactive {
        background: #fee2e2;
        color: #b91c1c;
    }
}
</style>
