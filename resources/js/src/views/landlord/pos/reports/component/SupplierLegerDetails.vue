<script setup>
import {onMounted, ref} from "vue";
import {useToast} from "vue-toastification";
import {SUPPLIER_LEDGER_REPORTS_PDF} from "@/services/endpoints/landlord.js";
import Axios from "@/services/axios/index.js";

const props = defineProps({
    id: {},
});
const invoiceURL = ref(null);
const toast = useToast()
const preloader = ref(false);

const purchaseDetailsPreview = () => {
     preloader.value = true
    Axios.get(`${SUPPLIER_LEDGER_REPORTS_PDF}/${props.id}?preview=preview`, {responseType: 'arraybuffer'}).then(response => {
        const blob = new Blob([response.data], {type: 'application/pdf'});

        invoiceURL.value = window.URL.createObjectURL(blob);
    }).catch(error => {
        toast.error("Supplier Ledger Details preview failed. Please try again later.");
    }).finally(() => preloader.value = false);
}

onMounted(() => {
    purchaseDetailsPreview()
})
</script>

<template>
    <div class="content-wrapper">
        <app-breadcrumb :page-title="$t('purchase_details_preview')"/>
        <div class="center-loader d-flex justify-content-center"  v-if="preloader">
            <app-loader></app-loader>
        </div>
        <iframe v-else :src="invoiceURL" width="100%" height="750px"></iframe>
    </div>
</template>

<style scoped lang="scss">

</style>
