<script setup>
import { onMounted, ref } from "vue";
import { useToast } from "vue-toastification";
import { ORDER_INVOICE_DATA } from "@/services/endpoints/Ecommerce.js";
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

const loadInvoiceData = () => {
    preloader.value = true;
    Axios.get(`${ORDER_INVOICE_DATA}/${props.id}`)
        .then(({ data }) => { invoice.value = data; })
        .catch(() => toast.error("Failed to load invoice data."))
        .finally(() => { preloader.value = false; });
};

const printInvoice = () => {
    const el = document.getElementById('order-invoice-print');
    if (!el) return;

    const css = `
        @page { size: A4 portrait; margin: 15mm; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 11px; color: #333; line-height: 1.4; }
        .clearfix::after { content: ""; display: table; clear: both; }
        .header-container { width: 100%; margin-bottom: 30px; }
        .logo-area { float: left; width: 50%; }
        .logo-img { height: 45px; width: auto; display: block; }
        .website { color: #666; font-size: 11px; margin-top: -5px; }
        .contact-info { float: right; width: 50%; text-align: right; font-size: 11px; }
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
            width: 100%; margin-bottom: 10px; clear: both; display: block;
        }
        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .items-table thead th {
            background-color: #dce1e5 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact;
            padding: 10px; text-align: left; font-weight: 600; color: #444;
        }
        .items-table tbody td { padding: 12px 10px; border-bottom: 1px solid #eee; vertical-align: top; }
        .product-name { font-weight: bold; font-size: 12px; margin-bottom: 4px; }
        .badge { display: inline-block; padding: 1px 8px; border: 1px solid #ccc; border-radius: 10px; font-size: 10px; color: #555; margin-right: 5px; }
        .warranty { font-size: 10px; color: #777; margin-top: 5px; }
        .product-img-box { float: left; width: 42px; height: 42px; border-radius: 5px; margin-right: 10px; object-fit: cover; }
        .summary-wrapper {
            background-color: #e5e7eb !important; -webkit-print-color-adjust: exact; print-color-adjust: exact;
            border-radius: 15px; padding: 20px; width: 95%;
        }
        .summary-left { float: left; width: 65%; }
        .summary-right { float: right; width: 30%; text-align: right; }
        .summary-table { width: 100%; border-collapse: collapse; }
        .summary-table td { padding: 4px 0; font-size: 12px; }
        .s-label { width: 155px; font-weight: 600; vertical-align: top; }
        .total-mrp-row td { font-size: 15px; font-weight: 800; }
        .footer-msg {
            background-color: #e5e7eb !important; -webkit-print-color-adjust: exact; print-color-adjust: exact;
            border-radius: 15px; padding: 20px 20px 20px 30px; margin-top: 20px; font-size: 11px; color: #444;
        }
        .footer-msg li { margin-bottom: 5px; }
    `;

    const win = window.open('', '_blank');
    win.document.write(`<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Invoice ${invoice.value?.order?.invoice_number || ''}</title>
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
        <app-breadcrumb :page-title="$t('order_details_preview')">
            <template #breadcrumb-actions>
                <button v-if="invoice" class="btn btn-primary btn-sm" @click="printInvoice">
                    <i class="bi bi-printer me-1"></i> Print Invoice
                </button>
            </template>
        </app-breadcrumb>

        <div class="d-flex justify-content-center py-5" v-if="preloader">
            <app-loader></app-loader>
        </div>

        <div v-else-if="invoice" id="order-invoice-print" class="order-invoice">

            <!-- Header -->
            <div class="header-container clearfix">
                <div class="logo-area">
                    <img :src="invoice.logo" alt="Logo" class="logo-img">
                    <div class="website">www.tekzo.com.bd</div>
                </div>
                <div class="contact-info">
                    <div>Phone : +8801897-715222</div>
                    <div>Mail : hello@tekzo.com.bd</div>
                </div>
            </div>

            <!-- Bill To + Invoice Info -->
            <div class="meta-container clearfix">
                <div class="bill-to">
                    <h3 class="bill-title">Bill To</h3>
                    <table class="bill-table">
                        <tr>
                            <td class="label-col">Name</td>
                            <td class="sep-col">:</td>
                            <td>{{ invoice.customer.name || '-' }}</td>
                        </tr>
                        <tr>
                            <td class="label-col">Phone</td>
                            <td class="sep-col">:</td>
                            <td>{{ invoice.customer.phone || '-' }}</td>
                        </tr>
                        <tr>
                            <td class="label-col">Email</td>
                            <td class="sep-col">:</td>
                            <td>{{ invoice.customer.email || '-' }}</td>
                        </tr>
                        <tr>
                            <td class="label-col">Address</td>
                            <td class="sep-col">:</td>
                            <td>{{ invoice.customer.address || '-' }}</td>
                        </tr>
                    </table>
                </div>
                <div class="invoice-info">
                    <h1 class="invoice-heading">INVOICE</h1>
                    <div class="info-row"><b>Date :</b> {{ invoice.order.date }}</div>
                    <div class="info-row"><b>Tracking No :</b></div>
                    <div class="barcode-area">
                        <img class="barcode-img" :src="invoice.barcode" alt="barcode">
                        <div style="font-size: 10px; margin-top: 3px;">{{ invoice.order.invoice_number }}</div>
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
                        <th style="width: 70px; text-align: center;">Quantity</th>
                        <th style="width: 90px;">MRP</th>
                        <th style="width: 80px;">Discount</th>
                        <th style="width: 90px;">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(item, idx) in invoice.items" :key="idx">
                        <td style="font-weight: bold;">{{ pad(idx + 1) }}</td>
                        <td class="clearfix">
                            <img v-if="item.product_image" :src="item.product_image" class="product-img-box">
                            <div style="float: left; width: 80%;">
                                <div class="product-name">{{ item.product_name }}</div>
                                <span v-for="attr in item.attributes" :key="attr" class="badge">{{ attr }}</span>
                                <div v-if="item.warranty_day" class="warranty">Warranty Days: {{ item.warranty_day }}</div>
                                <div v-if="item.product_care" class="warranty">Product Care: {{ item.product_care }} ×{{ item.quantity }}</div>
                                <div v-if="item.pre_owned_care" class="warranty">Product Care: {{ item.pre_owned_care }} ×{{ item.quantity }}</div>
                            </div>
                            <!-- Gift item -->
                            <div v-if="item.gift" style="clear: both; margin-top: 10px;">
                                <img :src="item.gift.product_image" class="product-img-box">
                                <div style="float: left;">
                                    <div class="product-name">Gift Item x {{ item.gift.quantity }}</div>
                                    <div class="product-name">{{ item.gift.product_name }}</div>
                                    <span v-for="ga in item.gift.attributes" :key="ga" class="badge">{{ ga }}</span>
                                </div>
                            </div>
                        </td>
                        <td style="text-align: center;">{{ item.quantity }}</td>
                        <td>{{ fmt(item.product_price) }}</td>
                        <td>{{ fmt(item.discount_amount) }}</td>
                        <td style="font-weight: bold;">{{ fmt(item.line_total, 0) }}</td>
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
                            <td class="s-label">Payment Method &<br>(Partial Payment)</td>
                            <td>: ৳ {{ fmt(invoice.order.payable_amount) }}</td>
                        </tr>
                        <tr>
                            <td class="s-label">Additional Charge (+)</td>
                            <td>: ৳ {{ fmt(invoice.total_care_charge) }}</td>
                        </tr>
                        <tr>
                            <td class="s-label">Shipping Charge (+)</td>
                            <td>: ৳ {{ fmt(invoice.order.shipping_charge) }}</td>
                        </tr>
                        <tr>
                            <td class="s-label">Order Tax (0.00%) (+)</td>
                            <td>: ৳ 0.00</td>
                        </tr>
                        <tr>
                            <td class="s-label">Point (-)</td>
                            <td>: {{ invoice.total_purchase_point }}</td>
                        </tr>
                        <tr>
                            <td class="s-label">Gift Item (-)</td>
                            <td>: {{ invoice.total_gift_quantity }} PCS</td>
                        </tr>
                    </table>
                </div>
                <div class="summary-right">
                    <table class="summary-table">
                        <tr class="total-mrp-row">
                            <td style="text-align: left;">Total MRP :</td>
                            <td>৳ {{ fmt(invoice.total_unit_price) }}</td>
                        </tr>
                        <tr>
                            <td style="text-align: left; font-weight: 600;">Coupon Amount :</td>
                            <td style="font-weight: 600;">৳ {{ fmt(invoice.order.discount_total) }}</td>
                        </tr>
                        <tr>
                            <td style="text-align: left; font-weight: 600;">Total Discount (-) :</td>
                            <td style="font-weight: 600;">৳ {{ fmt(invoice.total_discount) }}</td>
                        </tr>
                        <tr>
                            <td style="text-align: left; font-weight: 600;">Total Balance (=) :</td>
                            <td style="font-weight: 600;">৳ {{ fmt(invoice.order.grand_total) }}</td>
                        </tr>
                        <tr>
                            <td style="text-align: left; font-weight: bold; padding-top: 10px;">Paid :</td>
                            <td style="font-weight: bold; padding-top: 10px;">৳ {{ fmt(invoice.order.payable_amount) }}</td>
                        </tr>
                        <tr>
                            <td style="text-align: left; font-weight: bold;">Due :</td>
                            <td style="font-weight: bold;">৳ {{ fmt(invoice.order.due) }}</td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Footer -->
            <div class="footer-msg">
                <ul>
                    <li><b>Shipping Policy</b>: Any increase in shipping costs due to order weight increasing by 1 kg or more must be fully covered by the customer.</li>
                    <li><b>Payment Policy</b>: Orders with an advance payment will be shipped via Cash on Delivery (COD), and the customer must pay the remaining balance upon receiving the product.</li>
                    <li><b>Warranty Policy</b>: TEKZO will not cover any product warranty unless it is protected under the "TEKZO Care Protection" plan.</li>
                </ul>
            </div>

        </div>
    </div>
</template>

<style scoped lang="scss">
.order-invoice {
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

.header-container {
    width: 100%;
    margin-bottom: 30px;
}

.logo-area { float: left; width: 50%; }
.logo-img { height: 45px; width: auto; display: block; }
.website { color: #666; font-size: 11px; margin-top: -5px; }
.contact-info { float: right; width: 50%; text-align: right; font-size: 11px; }

.meta-container { width: 100%; margin-bottom: 25px; }
.bill-to { float: left; width: 60%; }
.bill-title { font-size: 13px; margin-bottom: 8px; color: #555; font-weight: 600; }
.bill-table { border-collapse: collapse; }
:deep(.bill-table td) { padding: 2px 0; font-size: 12px; vertical-align: top; }
.label-col { width: 65px; font-weight: 600; }
.sep-col { width: 15px; text-align: center; }
.invoice-info { float: right; width: 40%; text-align: right; }
.invoice-heading { font-size: 30px; font-weight: 500; margin-bottom: 5px; color: #000; text-transform: uppercase; }
.info-row { font-size: 12px; margin-bottom: 3px; }
.barcode-area { margin-top: 10px; text-align: right; }
.barcode-img { height: 30px; width: 150px; }

.orange-divider {
    height: 3px;
    background-color: #f39233;
    width: calc(100% + 30mm);
    margin-left: -15mm;
    margin-bottom: 10px;
    clear: both;
    display: block;
}

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

.product-name { font-weight: bold; font-size: 12px; margin-bottom: 4px; }
.badge {
    display: inline-block;
    padding: 1px 8px;
    border: 1px solid #ccc;
    border-radius: 10px;
    font-size: 10px;
    color: #555;
    margin-right: 5px;
}
.warranty { font-size: 10px; color: #777; margin-top: 5px; }
.product-img-box { float: left; width: 42px; height: 42px; border-radius: 5px; margin-right: 10px; object-fit: cover; }

.summary-wrapper {
    background-color: #e5e7eb;
    border-radius: 15px;
    padding: 20px;
    width: 95%;
    display: flex;
    gap: 20px;
}
.summary-left { flex: 0 0 60%; }
.summary-right { flex: 1; text-align: right; white-space: nowrap; }
.summary-table { width: 100%; border-collapse: collapse; border: none; }
:deep(.summary-table th),
:deep(.summary-table td) { padding: 4px 0; font-size: 12px; border: none; background-color: transparent !important; }
.s-label { width: 155px; font-weight: 600; vertical-align: top; }
.total-mrp-row td { font-size: 15px; font-weight: 800; }

.footer-msg {
    background-color: #e5e7eb;
    border-radius: 15px;
    padding: 20px 20px 20px 30px;
    margin-top: 20px;
    font-size: 11px;
    color: #444;

    li { margin-bottom: 5px; }
}
</style>
