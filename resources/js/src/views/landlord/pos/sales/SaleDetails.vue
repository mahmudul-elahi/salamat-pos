<script setup>
import { onMounted, ref } from "vue";
import { useToast } from "vue-toastification";
import { SALE_INVOICE_DATA } from "@/services/endpoints/landlord.js";
import Axios from "@/services/axios/index.js";

const props = defineProps({ id: {} });
const toast = useToast();
const preloader = ref(false);
const invoice = ref(null);

const fmt = (n, d = 2) => Number(n || 0).toLocaleString('en-IN', {
    minimumFractionDigits: d,
    maximumFractionDigits: d,
});
const pad = (n) => String(n).padStart(2, '0');
const ucfirst = (s) => s ? s.charAt(0).toUpperCase() + s.slice(1) : '';

const loadInvoiceData = () => {
    preloader.value = true;
    Axios.get(`${SALE_INVOICE_DATA}/${props.id}`)
        .then(({ data }) => { invoice.value = data; })
        .catch(() => toast.error("Failed to load invoice data."))
        .finally(() => { preloader.value = false; });
};

const printInvoice = () => {
    const el = document.getElementById('sale-invoice-print');
    if (!el) return;

    const css = `
        @page { size: A4 portrait; margin-top: 30mm; margin-bottom: 35mm; margin-left: 15mm; margin-right: 15mm; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 11px; color: #333; line-height: 1.4; }
        .clearfix::after { content: ""; display: table; clear: both; }
        .meta-container { width: 100%; margin-bottom: 25px; }
        .bill-to { float: left; width: 60%; }
        .bill-title { font-size: 13px; margin-bottom: 8px; color: #555; font-weight: 600; }
        .bill-table { border-collapse: collapse; }
        .bill-table td { padding: 2px 0; font-size: 12px; vertical-align: top; }
        .label-col { width: 65px; font-weight: 600; }
        .sep-col { width: 15px; text-align: center; }
        .invoice-info { float: right; width: 40%; text-align: right; }
        .invoice-heading { font-size: 30px; font-weight: 500; margin-bottom: 5px; color: #000; text-transform: uppercase; }
        .info-row { font-size: 12px; margin-bottom: 3px; }
        .barcode-area { margin-top: 10px; text-align: right; }
        .barcode-img { height: 30px; width: 150px; }
        .orange-divider {
            height: 3px; background-color: #f39233 !important;
            -webkit-print-color-adjust: exact; print-color-adjust: exact;
            width: 100%; margin-left: 0; margin-bottom: 10px; clear: both; display: block;
        }
        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .items-table thead th {
            background-color: #dce1e5 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact;
            padding: 10px; text-align: left; font-weight: 600; color: #444;
        }
        .items-table tbody td { padding: 12px 10px; border-bottom: 1px solid #eee; vertical-align: top; }
        .product-name { font-weight: bold; font-size: 12px; margin-bottom: 4px; }
        .item-badge { display: inline-block; padding: 1px 8px; border: 1px solid #ccc; border-radius: 10px; font-size: 10px; color: #555; margin-right: 5px; }
        .warranty { font-size: 10px; color: #777; margin-top: 5px; }
        .product-img-box { float: left; width: 42px; height: 42px; border-radius: 5px; margin-right: 10px; object-fit: cover; }
        .summary-wrapper {
            background-color: #e5e7eb !important; -webkit-print-color-adjust: exact; print-color-adjust: exact;
            border-radius: 15px; padding: 20px; width: 95%; box-sizing: border-box;
        }
        .summary-left { float: left; width: 58%; }
        .summary-right { float: right; width: 40%; }
        .summary-table { width: 100%; border-collapse: collapse; }
        .summary-table td { padding: 4px 0; font-size: 12px; }
        .s-label { width: 155px; font-weight: 600; vertical-align: top; }
        .sr-label { text-align: left; white-space: nowrap; padding-right: 16px; vertical-align: top; }
        .sr-value { text-align: right; white-space: nowrap; font-variant-numeric: tabular-nums; }
        .total-mrp-row td { font-size: 15px; font-weight: 800; }
        .footer-msg { margin-top: 50px; text-align: center; font-size: 13px; font-style: italic; color: #444; clear: both; }
    `;

    const win = window.open('', '_blank');
    win.document.write(`<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Invoice ${invoice.value?.sale?.full_invoice_number || ''}</title>
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

onMounted(() => loadInvoiceData());
</script>

<template>
    <div class="content-wrapper">
        <app-breadcrumb :page-title="$t('sale_details_preview')">
            <template #breadcrumb-actions>
                <button v-if="invoice" class="btn btn-primary btn-sm" @click="printInvoice">
                    <i class="bi bi-printer me-1"></i> Print Invoice
                </button>
            </template>
        </app-breadcrumb>

        <div class="d-flex justify-content-center py-5" v-if="preloader">
            <app-loader></app-loader>
        </div>

        <div v-else-if="invoice" id="sale-invoice-print" class="sale-invoice">
            <!-- Bill To + Invoice Info -->
            <div class="meta-container clearfix">
                <div class="bill-to">
                    <h3 class="bill-title">Bill To</h3>
                    <table class="bill-table">
                        <tr>
                            <td class="label-col">Name</td>
                            <td class="sep-col">:</td>
                            <td>{{ invoice?.customer?.name || '-' }}</td>
                        </tr>
                        <tr>
                            <td class="label-col">Business</td>
                            <td class="sep-col">:</td>
                            <td>{{ invoice?.customer?.company_name || '-' }}</td>
                        </tr>
                        <tr>
                            <td class="label-col">Phone</td>
                            <td class="sep-col">:</td>
                            <td>{{ invoice?.customer?.phone || '-' }}</td>
                        </tr>
                        <tr>
                            <td class="label-col">Email</td>
                            <td class="sep-col">:</td>
                            <td>{{ invoice?.customer?.email || '-' }}</td>
                        </tr>
                        <tr v-if="invoice?.customer?.address">
                            <td class="label-col">Address</td>
                            <td class="sep-col">:</td>
                            <td>{{ invoice.customer.address }}</td>
                        </tr>
                    </table>
                </div>
                <div class="invoice-info">
                    <h1 class="invoice-heading">INVOICE</h1>
                    <div class="info-row"><b>Date :</b> {{ invoice.sale.date }}</div>
                    <div class="info-row"><b>Tracking No :</b></div>
                    <div class="barcode-area">
                        <img class="barcode-img" :src="invoice.barcode" alt="barcode">
                        <div style="font-size: 10px; margin-top: 3px;">{{ invoice.sale.full_invoice_number }}</div>
                    </div>
                </div>
            </div>

            <div class="orange-divider"></div>

            <!-- Items Table -->
            <table class="items-table">
                <thead>
                    <tr>
                        <th style="width: 30px;">#</th>
                        <th>Product</th>
                        <th style="width: 90px; text-align: center;">Serial Number</th>
                        <th style="width: 50px; text-align: center;">Qty</th>
                        <th style="width: 70px;">MRP</th>
                        <th style="width: 70px;">Discount</th>
                        <th style="width: 80px;">Unit Price</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(row, idx) in invoice.items" :key="idx">
                        <td style="font-weight: bold;">{{ pad(idx + 1) }}</td>
                        <td class="clearfix">
                            <img v-if="row.product_image && invoice.items_count < 10" :src="row.product_image"
                                class="product-img-box">
                            <div style="float: left; width: 80%;">
                                <div class="product-name">{{ row.product_name }}</div>
                                <span v-for="attr in row.attributes" :key="attr" class="item-badge">{{ attr }}</span>
                                <div v-if="row.warranty_day" class="warranty">
                                    Warranty Days: {{ row.warranty_day }}
                                </div>
                                <div v-if="row.care_names && row.care_names.length" class="warranty">
                                    Product Care: {{ row.care_names.join(', ') }} (৳ {{ fmt(row.care_amount) }})
                                </div>
                            </div>
                        </td>
                        <td>
                            <div v-for="(line, li) in row.lines" :key="li">{{ line.serial_number }}</div>
                        </td>
                        <td style="text-align: center;">{{ row.total_qty }}</td>
                        <td>
                            <div v-for="(line, li) in row.lines" :key="li">{{ fmt(line.unit_price) }}</div>
                        </td>
                        <td>
                            <div v-for="(line, li) in row.lines" :key="li">{{ fmt(line.discount_amount) }}</div>
                        </td>
                        <td>
                            <div v-for="(line, li) in row.lines" :key="li"
                                :style="line.is_exchange ? 'color: red;' : ''">{{
                                    fmt(line.line_total, 0) }}</div>
                        </td>
                    </tr>
                </tbody>
            </table>

            <!-- Summary -->
            <div class="summary-wrapper clearfix">
                <div class="summary-left">
                    <table class="summary-table">
                        <tr>
                            <td class="s-label">Total Quantity</td>
                            <td>: {{ invoice.total_quantity }} PCS</td>
                        </tr>
                        <tr>
                            <td class="s-label">
                                Payment Method &<br>({{ ucfirst(invoice.sale.status) }})
                            </td>
                            <td v-if="invoice.payment_summary.length === 0">: ৳ 0.00</td>
                            <td v-else>
                                <div v-for="(p, pi) in invoice.payment_summary" :key="pi">
                                    : ৳ {{ fmt(p.amount) }} ({{ p.method }})
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td class="s-label">
                                Additional Charge
                                <template v-if="invoice.sale.additional_charge_name">
                                    <br>({{ invoice.sale.additional_charge_name }})
                                </template>
                            </td>
                            <td>: ৳ {{ fmt(invoice.sale.additional_charge) }}</td>
                        </tr>
                        <tr v-if="Number(invoice.total_care_charge) > 0">
                            <td class="s-label">Total Care Charge</td>
                            <td>: ৳ {{ fmt(invoice.total_care_charge) }}</td>
                        </tr>
                        <tr>
                            <td class="s-label">Shipping Charge</td>
                            <td>: ৳ {{ fmt(invoice.sale.shipping_cost) }}</td>
                        </tr>
                        <tr>
                            <td class="s-label">Order Tax</td>
                            <td>: ৳ {{ fmt(invoice.sale.order_tax) }}</td>
                        </tr>
                    </table>
                </div>
                <div class="summary-right">
                    <table class="summary-table">
                        <tr class="total-mrp-row">
                            <td class="sr-label">Total MRP :</td>
                            <td class="sr-value">৳ {{ fmt(invoice.total_unit_price) }}</td>
                        </tr>
                        <tr>
                            <td class="sr-label" style="font-weight: 600;">Coupon Amount:</td>
                            <td class="sr-value">৳ {{ fmt(invoice.coupon_amount) }}</td>
                        </tr>
                        <tr>
                            <td class="sr-label" style="font-weight: 600;">Total Discount (-) :</td>
                            <td class="sr-value">৳ {{ fmt(invoice.total_discount) }}</td>
                        </tr>
                        <tr>
                            <td class="sr-label" style="font-weight: 600;">Total Balance (=) :</td>
                            <td class="sr-value" style="font-weight: 600;">৳ {{ fmt(invoice.sale.grand_total) }}</td>
                        </tr>
                        <tr>
                            <td class="sr-label" style="font-weight: bold; padding-top: 10px;">Paid :</td>
                            <td class="sr-value" style="font-weight: 600; padding-top: 10px;">৳ {{
                                fmt(invoice.received_amount)
                            }}</td>
                        </tr>
                        <tr>
                            <td class="sr-label" style="font-weight: bold;">Due :</td>
                            <td class="sr-value" style="font-weight: 600;">৳ {{ fmt(invoice.due_amount) }}</td>
                        </tr>
                    </table>
                </div>
            </div>

            <div class="footer-msg">Thank You For your Purchase!</div>
        </div>
    </div>
</template>


<style scoped lang="scss">
.sale-invoice {
    width: 210mm;
    font-family: 'Arial', sans-serif;
    font-size: 11px;
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

/* Bill To / Invoice Info */
.meta-container {
    width: 100%;
    margin-bottom: 25px;
}

.bill-to {
    float: left;
    width: 60%;
}

.bill-title {
    font-size: 13px;
    margin-bottom: 8px;
    color: #555;
    font-weight: 600;
}

.bill-table {
    border-collapse: collapse;

    td {
        padding: 2px 0;
        font-size: 12px;
        vertical-align: top;
    }
}

.label-col {
    width: 65px;
    font-weight: 600;
}

.sep-col {
    width: 15px;
    text-align: center;
}

.invoice-info {
    float: right;
    width: 40%;
    text-align: right;
}

.invoice-heading {
    font-size: 30px;
    font-weight: 500;
    margin-bottom: 5px;
    color: #000;
    text-transform: uppercase;
}

.info-row {
    font-size: 12px;
    margin-bottom: 3px;
}

.barcode-area {
    margin-top: 10px;
    text-align: right;
}

.barcode-img {
    height: 30px;
    width: 150px;
}

/* Divider */
.orange-divider {
    height: 3px;
    background-color: #f39233;
    width: calc(100% + 30mm);
    margin-left: -15mm;
    margin-bottom: 10px;
    clear: both;
    display: block;
}

/* Items Table */
.items-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 20px;

    thead th {
        background-color: #dce1e5;
        padding: 10px;
        text-align: left;
        font-weight: 600;
        color: #444;
    }

    tbody td {
        padding: 12px 10px;
        border-bottom: 1px solid #eee;
        vertical-align: top;
    }
}

.product-name {
    font-weight: bold;
    font-size: 12px;
    margin-bottom: 4px;
}

.item-badge {
    display: inline-block;
    padding: 1px 8px;
    border: 1px solid #ccc;
    border-radius: 10px;
    font-size: 10px;
    color: #555;
    margin-right: 5px;
}

.warranty {
    font-size: 10px;
    color: #777;
    margin-top: 5px;
}

.product-img-box {
    float: left;
    width: 42px;
    height: 42px;
    border-radius: 5px;
    margin-right: 10px;
    object-fit: cover;
}

/* Summary */
.summary-wrapper {
    //background-color: #e5e7eb;
    //border-radius: 15px;
    padding: 20px;
    width: 100%;
    //box-sizing: border-box;
}

.summary-left {
    float: left;
    width: 50%;
}

.summary-right {
    float: right;
    width: 50%;
}

.summary-table {
    width: 100%;
    border-collapse: collapse;
    border: none;

    td {
        padding: 4px 0;
        font-size: 12px;
        border: none;
    }
}

.s-label {
    width: 155px;
    font-weight: 600;
    vertical-align: top;
}

.sr-label {
    text-align: left;
    white-space: nowrap;
    padding-right: 16px;
    vertical-align: top;
}

.sr-value {
    text-align: right;
    white-space: nowrap;
    font-variant-numeric: tabular-nums;
}

.total-mrp-row td {
    font-size: 15px;
    font-weight: 800;
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
