<script setup>
import { ref, markRaw, h } from "vue";
import { useI18n } from "vue-i18n";
import { WARRANTY_CLAIMS } from "@/services/endpoints/Ecommerce.js";
import { Check, XmarkCircle } from "@iconoir/vue";
import { formatDateToLocal } from "@/utils/helper.js";
import usePermission from "@/composable/permission/usePermission.js";
import useWarrantyClaimStatus from "@/composable/ecommerce/useWarrantyClaimStatus.js";
import OrderStatusModal from "@/views/landlord/Ecommerce/Order/Component/OrderStatusModal.vue";
import ProfitLossProductColumn from "@/views/landlord/pos/reports/ProfitLossProductColumn.vue";

const { t } = useI18n();
const { canAccess } = usePermission();

const tableId = ref("warranty-claim-table");

const options = ref({
    url: WARRANTY_CLAIMS,
    pageSize: 10,
    rowLimit: 10,
    orderBy: "desc",
    showGridView: false,
    actionType: "dropdown",
    paginationType: "pagination",
    enableRowSelect: false,
    enableBulkSelect: false,
    showSearch: true,
    showFilter: false,
    columns: [
        {
            title: t("imei_sl_number"),
            type: "text",
            key: "serial_number",
        },
        {
            title: t("invoice_number"),
            type: "text",
            key: "invoice_number",
        },
        {
            title: t("product_name"),
            type: "component",
            key: "product",
            componentName: h(ProfitLossProductColumn),
        },
        {
            title: t("reason_of_claim"),
            type: "text",
            key: "reason",
        },
        {
            title: t("warranty_day"),
            type: "object",
            key: "warranty_day",
            modifier: (warranty_day) => (warranty_day ? `${warranty_day} days` : "-"),
        },
        {
            title: t("warranty"),
            type: "custom-html",
            key: "is_within_warranty",
            modifier: (isWithinWarranty) => {
                return isWithinWarranty
                    ? `<span class="badge-square-light bg-success">Within Warranty</span>`
                    : `<span class="badge-square-light bg-danger">Expired</span>`;
            },
        },
        {
            title: t("created_at"),
            type: "object",
            key: "created_at",
            modifier: (created_at) => (created_at ? formatDateToLocal(created_at) : "-"),
        },
        {
            title: t("status"),
            type: "custom-html",
            key: "status",
            modifier: (status) => {
                return `<span class="badge-square-light bg-${status.color}">${status.name}</span>`;
            },
        },
        {
            title: t("action"),
            type: "action",
            align: "right",
            permission: canAccess("status_update_warranty_claims"),
        },
    ],
    filters: [],
    actions: [
        {
            title: t("approved"),
            type: "approved",
            icon: markRaw(Check),
            modifier: (row) => canAccess("status_update_warranty_claims") && row.status.name !== "Approved",
        },
        {
            title: t("rejected"),
            type: "rejected",
            icon: markRaw(XmarkCircle),
            modifier: (row) => canAccess("status_update_warranty_claims") && row.status.name !== "Rejected",
        },
    ],
});

const {
    showStatusModal,
    modalData,
    preloader: statusPreloader,
    openStatusModal,
    updateWarrantyClaimStatus,
    closeStatusModal,
} = useWarrantyClaimStatus(tableId);

const actionTiger = (row, action) => {
    switch (action.type) {
        case "approved":
            openStatusModal("approved", row);
            break;
        case "rejected":
            openStatusModal("rejected", row);
            break;
    }
};
</script>

<template>
    <div class="content-wrapper">
        <app-breadcrumb :page-title="$t('warranty_claim')" />

        <app-datatable :id="tableId" :options="options" @action="actionTiger" />

        <order-status-modal v-if="showStatusModal" :modal-id="`warranty-claim-${modalData.type}-modal`"
            :modal-class="modalData.type === 'approved' ? 'text-success' : 'text-danger'" :icon="modalData.icon"
            :title="modalData.title" :message="modalData.message" :table-id="tableId" :preloader="statusPreloader"
            @confirm="updateWarrantyClaimStatus" @cancel="closeStatusModal" />
    </div>
</template>

<style scoped lang="scss"></style>
