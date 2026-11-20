<template>
    <div class="content-wrapper">
        <app-breadcrumb :page-title="$t('area_shipping_charges')" />

        <div class="area-charge-note">
            {{ $t('area_shipping_charges_note') }}
        </div>

        <app-datatable :id="tableId" :options="options" :button-visible="false" @action="actionTiger" />

        <area-shipping-charge-modal v-if="isModalActive" modal-id="area-shipping-charge-modal" :table-id="tableId"
            :area="selectedArea" @close="isModalActive = false" />
    </div>
</template>

<script setup>
import { ref, markRaw } from "vue";
import { useI18n } from "vue-i18n";
import { Edit } from "@iconoir/vue";
import usePermission from "@/composable/permission/usePermission.js";
import { LANDLORD_AREA_SHIPPING_CHARGES, SELECTED_DISTRICTS } from "@/services/endpoints/landlord.js";
import { numberWithCurrencySymbol } from "@/utils/helper.js";
import AreaShippingChargeModal from "@/views/landlord/setting/deliveryCharge/AreaShippingChargeModal.vue";

const { t } = useI18n();
const { canAccess } = usePermission();
const tableId = ref('area-shipping-charges-table');
const isModalActive = ref(false);
const selectedArea = ref(null);

const options = ref({
    url: LANDLORD_AREA_SHIPPING_CHARGES,
    pageSize: 10,
    rowLimit: 10,
    showGridView: false,
    actionType: 'dropdown',
    paginationType: 'pagination',
    enableRowSelect: false,
    enableBulkSelect: false,
    showSearch: true,
    showFilter: true,
    filters: [
        {
            label: t('district'),
            type: 'advance-search-select',
            key: 'new_district_id',
            fetchUrl: SELECTED_DISTRICTS,
            listKeyField: 'id',
            listValueField: 'name',
            showSearch: true,
            permission: true,
        },
    ],
    columns: [
        {
            title: t('district'),
            type: 'object',
            key: 'district',
            modifier: (district) => district?.name ?? '-',
        },
        {
            title: t('area'),
            type: 'text',
            key: 'name',
        },
        {
            title: t('thana'),
            type: 'text',
            key: 'thana',
        },
        {
            title: t('postcode'),
            type: 'text',
            key: 'postcode',
        },
        {
            title: t('shipping_charge'),
            type: 'custom-html',
            key: 'shipping_charge',
            modifier: (value) => {
                if (value === null || value === undefined) {
                    return `<span class="badge-square-light bg-info">${t('default')}</span>`;
                }
                if (Number(value) === 0) {
                    return `<span class="badge-square-light bg-success">${t('free')}</span>`;
                }
                return `<span class="badge-square-light bg-primary">${numberWithCurrencySymbol(value)}</span>`;
            },
        },
        {
            title: t('action'),
            type: 'action',
            align: 'right',
            permission: canAccess('update_area_shipping_charges'),
        },
    ],
    actions: [
        {
            title: t('edit'),
            type: 'edit',
            icon: markRaw(Edit),
            modifier: () => canAccess('update_area_shipping_charges'),
        },
    ],
});

const actionTiger = (row, action) => {
    if (action.type === 'edit') {
        selectedArea.value = row;
        isModalActive.value = true;
    }
};
</script>

<style scoped lang="scss">
.area-charge-note {
    font-size: 0.8rem;
    color: var(--secondary-font-color, #6b7280);
    background: var(--dashboard-bg);
    border: 1px solid var(--border-color);
    border-radius: 10px;
    padding: 12px 16px;
    margin-bottom: 16px;
}
</style>
