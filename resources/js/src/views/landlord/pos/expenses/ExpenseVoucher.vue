<script setup>
import { onMounted, ref } from "vue";
import { useToast } from "vue-toastification";
import { EXPENSES } from "@/services/endpoints/landlord.js";
import { formatDateToLocal } from "@/utils/helper.js";
import Axios from "@/services/axios/index.js";
import router from "@/router/index.js";
import { NavArrowLeft, Printer } from "@iconoir/vue";

const props = defineProps({ id: {} });
const toast = useToast();
const preloader = ref(false);
const voucher = ref(null);

const fmt = (n) => Number(n || 0).toLocaleString('en-IN', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
});

const loadVoucherData = () => {
    preloader.value = true;
    Axios.get(`${EXPENSES}/${props.id}/voucher`)
        .then(({ data }) => { voucher.value = data; })
        .catch(() => toast.error("Failed to load expense voucher."))
        .finally(() => { preloader.value = false; });
};

const printVoucher = () => {
    const el = document.getElementById('expense-voucher-print');
    if (!el) return;

    const css = `
        @page { size: A4 portrait; margin-top: 30mm; margin-bottom: 35mm; margin-left: 15mm; margin-right: 15mm; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 13px; color: #333; line-height: 1.4; }
        .clearfix::after { content: ""; display: table; clear: both; }
        .header-container { width: 100%; margin-bottom: 25px; }
        .company-info { float: left; width: 60%; }
        .brand-text { font-size: 18px; font-weight: bold; color: #00a09d; margin-bottom: 8px; }
        .info-row { font-size: 12px; margin-bottom: 3px; }
        .voucher-info { float: right; width: 40%; text-align: right; }
        .voucher-heading { font-size: 26px; font-weight: 500; margin-bottom: 5px; color: #000; text-transform: uppercase; }
        .orange-divider {
            height: 3px; background-color: #f39233 !important;
            -webkit-print-color-adjust: exact; print-color-adjust: exact;
            width: 100%; margin-bottom: 20px; clear: both; display: block;
        }
        .content-container { width: 100%; margin-bottom: 20px; }
        .details-box {
            float: left; width: 65%;
            background-color: #f4f6f9 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact;
            border-radius: 15px; padding: 15px 20px; box-sizing: border-box;
        }
        .details-table { width: 100%; border-collapse: collapse; }
        .details-table td { padding: 10px 0; border-bottom: 1px solid #dee2e6; font-size: 12px; vertical-align: top; }
        .details-table tr:last-child td { border-bottom: none; }
        .label-col { width: 130px; font-weight: 600; }
        .sep-col { width: 15px; text-align: center; }
        .amount-box {
            float: right; width: 30%; text-align: center;
            background-color: #f4f6f9 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact;
            border-radius: 15px; padding: 15px; box-sizing: border-box;
        }
        .amount-label { margin-bottom: 15px; }
        .amount-value { border-top: 1px solid #ccc; padding-top: 15px; font-size: 18px; font-weight: bold; }
        .footer-container {
            clear: both; width: 100%; margin-top: 15px;
            background-color: #dee2e6 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact;
            border-radius: 15px; padding: 20px; box-sizing: border-box;
        }
        .footer-left { float: left; width: 65%; font-size: 12px; }
        .footer-right { float: right; width: 30%; text-align: right; }
        .total-row { font-size: 15px; font-weight: 800; }
        .signature-container { width: 100%; margin-top: 70px; }
        .signature-box { float: left; width: 35%; text-align: center; }
        .signature-box-right { float: right; }
        .signature-line { border-top: 1px solid #333; margin-bottom: 6px; }
        .signature-label { font-size: 12px; color: #444; }
        .footer-msg { margin-top: 50px; text-align: center; font-size: 13px; font-style: italic; color: #444; clear: both; }
    `;

    const win = window.open('', '_blank');
    win.document.write(`<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Expense Voucher ${voucher.value?.invoice_number || ''}</title>
<style>${css}</style>
</head>
<body>
${el.innerHTML}
</body>
</html>`);
    win.document.close();
    win.focus();
    setTimeout(() => { win.print(); win.close(); }, 400);
};

onMounted(() => loadVoucherData());
</script>

<template>
    <div class="content-wrapper">
        <app-breadcrumb page-title="Expense Voucher">
            <template #breadcrumb-actions>
                <div class="d-flex align-items-center gap-2">
                    <button class="btn btn-light btn-sm d-flex align-items-center gap-1"
                        @click="router.push('/expenses')">
                        <NavArrowLeft style="width:15px;height:15px;" /> Back
                    </button>
                    <button v-if="voucher" class="btn btn-primary btn-sm d-flex align-items-center gap-1"
                        @click="printVoucher">
                        <Printer style="width:15px;height:15px;" /> Print Voucher
                    </button>
                </div>
            </template>
        </app-breadcrumb>

        <div class="d-flex justify-content-center py-5" v-if="preloader">
            <app-loader></app-loader>
        </div>

        <div v-else-if="voucher" id="expense-voucher-print" class="expense-voucher">
            <div class="header-container clearfix">
                <div class="company-info">
                    <img v-if="voucher.tenant_settings?.company_logo" :src="voucher.tenant_settings.company_logo"
                        style="max-width:130px;height:auto;display:block;margin-bottom:10px;">
                    <div v-else class="brand-text">{{ voucher.tenant_settings?.company_name || 'TEKZO' }}</div>

                    <div class="info-row"><b>Shop :</b> {{ voucher.tenant || '-' }}</div>
                    <div class="info-row"><b>Category :</b> {{ voucher.category || '-' }}</div>
                    <div class="info-row"><b>Created By :</b> {{ voucher.created_by || '-' }}</div>
                </div>
                <div class="voucher-info">
                    <h1 class="voucher-heading">Expense Voucher</h1>
                    <div class="info-row"><b>Date :</b> {{ formatDateToLocal(voucher.date) }}</div>
                    <div class="info-row"><b>Voucher No :</b> {{ voucher.invoice_number }}</div>
                </div>
            </div>

            <div class="orange-divider"></div>

            <div class="content-container clearfix">
                <div class="details-box">
                    <table class="details-table">
                        <tr>
                            <td class="label-col">Title</td>
                            <td class="sep-col">:</td>
                            <td>{{ voucher.title || '-' }}</td>
                        </tr>
                        <tr>
                            <td class="label-col">Payment Method</td>
                            <td class="sep-col">:</td>
                            <td>{{ voucher.payment_method || '-' }}</td>
                        </tr>
                        <tr>
                            <td class="label-col">Note</td>
                            <td class="sep-col">:</td>
                            <td>{{ voucher.note || '-' }}</td>
                        </tr>
                    </table>
                </div>

                <div class="amount-box">
                    <div class="amount-label">Amount</div>
                    <div class="amount-value">৳ {{ fmt(voucher.amount) }}</div>
                </div>
            </div>

            <div class="footer-container clearfix">
                <div class="footer-left">
                    Paid via {{ voucher.payment_method || '-' }} : ৳ {{ fmt(voucher.amount) }}
                </div>
                <div class="footer-right">
                    <div class="total-row">Total: ৳ {{ fmt(voucher.amount) }}</div>
                </div>
            </div>

            <div class="signature-container clearfix">
                <div class="signature-box">
                    <div class="signature-line"></div>
                    <div class="signature-label">Prepared By</div>
                </div>
                <div class="signature-box signature-box-right">
                    <div class="signature-line"></div>
                    <div class="signature-label">Approved By</div>
                </div>
            </div>

            <div class="footer-msg">Thank You!</div>
        </div>
    </div>
</template>

<style scoped lang="scss">
.expense-voucher {
    width: 210mm;
    font-family: 'Arial', sans-serif;
    font-size: 13px;
    color: #333;
    background: #fff;
    line-height: 1.4;
    padding: 20px 15mm;
    margin: 20px auto;
    box-shadow: 0 2px 12px rgba(0, 0, 0, .08);
    box-sizing: border-box;
}

.clearfix::after {
    content: "";
    display: table;
    clear: both;
}

.header-container {
    width: 100%;
    margin-bottom: 25px;
}

.company-info {
    float: left;
    width: 60%;
}

.brand-text {
    font-size: 18px;
    font-weight: bold;
    color: #00a09d;
    margin-bottom: 8px;
}

.info-row {
    font-size: 12px;
    margin-bottom: 3px;
}

.voucher-info {
    float: right;
    width: 40%;
    text-align: right;
}

.voucher-heading {
    font-size: 26px;
    font-weight: 500;
    margin-bottom: 5px;
    color: #000;
    text-transform: uppercase;
}

.orange-divider {
    height: 3px;
    background-color: #f39233;
    width: calc(100% + 30mm);
    margin-left: -15mm;
    margin-bottom: 20px;
    clear: both;
    display: block;
}

.content-container {
    width: 100%;
    margin-bottom: 20px;
}

.details-box {
    float: left;
    width: 65%;
    background-color: #f4f6f9;
    border-radius: 15px;
    padding: 15px 20px;
    box-sizing: border-box;
}

.details-table {
    width: 100%;
    border-collapse: collapse;

    td {
        padding: 10px 0;
        border-bottom: 1px solid #dee2e6;
        font-size: 12px;
        vertical-align: top;
    }

    tr:last-child td {
        border-bottom: none;
    }
}

.label-col {
    width: 130px;
    font-weight: 600;
}

.sep-col {
    width: 15px;
    text-align: center;
}

.amount-box {
    float: right;
    width: 30%;
    text-align: center;
    background-color: #f4f6f9;
    border-radius: 15px;
    padding: 15px;
    box-sizing: border-box;
}

.amount-label {
    margin-bottom: 15px;
}

.amount-value {
    border-top: 1px solid #ccc;
    padding-top: 15px;
    font-size: 18px;
    font-weight: bold;
}

.footer-container {
    clear: both;
    width: 100%;
    margin-top: 15px;
    background-color: #dee2e6;
    border-radius: 15px;
    padding: 20px;
    box-sizing: border-box;
}

.footer-left {
    float: left;
    width: 65%;
    font-size: 12px;
}

.footer-right {
    float: right;
    width: 30%;
    text-align: right;
}

.total-row {
    font-size: 15px;
    font-weight: 800;
}

.signature-container {
    width: 100%;
    margin-top: 70px;
}

.signature-box {
    float: left;
    width: 35%;
    text-align: center;
}

.signature-box-right {
    float: right;
}

.signature-line {
    border-top: 1px solid #333;
    margin-bottom: 6px;
}

.signature-label {
    font-size: 12px;
    color: #444;
}

.footer-msg {
    margin-top: 50px;
    text-align: center;
    font-size: 13px;
    font-style: italic;
    color: #444;
    clear: both;
}
</style>
