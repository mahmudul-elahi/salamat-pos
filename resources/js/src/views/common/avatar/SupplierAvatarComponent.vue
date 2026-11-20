<script setup>
import {computed} from "vue"
import Avatar from "@/views/global/avatar/Avatar.vue";
import usePermission from "@/composable/permission/usePermission.js";

const {canAccess} = usePermission();
const props = defineProps({
    rowData: Object
})

const profilePicture = computed(() => {
    return props.rowData?.profile_picture || null
})
</script>

<template>
    <div class="d-flex align-items-center p-2">
        <avatar
            :image="profilePicture"
            :name="rowData.name"
            size="45"
            active="success"/>

        <div class="ms-3">
            <div class="fw-semibold  mb-1">
                <template v-if="canAccess('view_supplier_purchase_reports') ||
                    canAccess('view_supplier_purchase_return_reports') ||
                    canAccess('view_supplier_purchase_transaction_reports') ||
                    canAccess('view_supplier_purchase_return_transaction_reports') ||
                    canAccess('view_supplier_ledger_reports')
                ">
                    <router-link
                        :to="{ name: 'supplierReportDetails', params: { id: rowData.id } }"
                        class="brand-color">
                        {{ rowData.name }}
                    </router-link>
                </template>

                <template v-else>
                    <span class="text-muted">{{ rowData.name }}</span>
                </template>
            </div>
            <div class="text-muted small">
                {{ rowData.company_name }}
            </div>
            <div class="text-muted small">
                {{ rowData.phone }}
            </div>
        </div>
    </div>
</template>

<style scoped></style>
