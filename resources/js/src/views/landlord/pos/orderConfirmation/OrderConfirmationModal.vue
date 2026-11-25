<script setup>
import { computed, nextTick, onMounted, ref } from "vue";
import { Plus, Trash } from "@iconoir/vue";
import { useSubmitForm } from "@/composable/submit/useSubmitForm.js";
import Axios from "@/services/axios/index.js";
import CustomerModal from "@/views/landlord/pos/people/customers/CustomerModal.vue";
import {
    ORDER_CONFIRMATIONS,
    SELECTED_PRODUCT,
    SELECTED_PRODUCT_ATTRIBUTE,
    SELECTED_SELLERS,
    SELECTED_TELESALE_CUSTOMERS,
    SELECTED_ADVANCE_MEDIAS,
    SELECTED_DELIVERY_STATUSES,
    SELECTED_DELIVERY_BYS,
    CUSTOMERS,
} from "@/services/endpoints/landlord.js";

const props = defineProps({ modalId: String, tableId: String, selectedUrl: String });
const emit = defineEmits(["close"]);

const { preloader, pageLoader, formData, errors, save, closeModal } = useSubmitForm(props, emit, "no");

// Where the order arrived from — the whole point of this list.
const sourceMediaOptions = [
    { id: "Facebook", name: "Facebook" },
    { id: "WhatsApp", name: "WhatsApp" },
    { id: "TikTok", name: "TikTok" },
    { id: "Instagram", name: "Instagram" },
    { id: "Messenger", name: "Messenger" },
    { id: "Website", name: "Website" },
    { id: "Phone Call", name: "Phone Call" },
    { id: "Other", name: "Other" },
];

const orderTypeOptions = [
    { id: "online", name: "Online" },
    { id: "offline", name: "Offline" },
];

const hydrating = ref(false);

// An order can carry several products, so the lines are edited as a repeatable
// list. New rows are always a catalogue product - free-text entry has been
// removed. A handful of orders saved before that removal still carry a
// legacyCustom row with no product_id; those are shown read-only so editing
// an old order doesn't silently wipe the product name it was saved with.
const items = ref([]);

const blankItem = () => ({
    product_id: null,
    product_variant_id: null,
    custom_product: "",
    quantity: 1,
    unit_price: 0,
    legacyCustom: false,
    variantOptions: [],
    variantLoading: false,
});

const addItem = () => items.value.push(blankItem());

const removeItem = (index) => {
    items.value.splice(index, 1);

    // The form must always post at least one line.
    if (items.value.length === 0) addItem();
};

// Fetches the variant list for whichever product a row currently has, without
// touching the row's already-chosen variant - used both after picking a new
// product and while hydrating an existing row for edit.
const loadVariantOptions = async (item, productId) => {
    item.variantOptions = [];
    if (!productId) return;
    item.variantLoading = true;
    try {
        const { data } = await Axios.get(`${SELECTED_PRODUCT_ATTRIBUTE}/${productId}`);
        item.variantOptions = Array.isArray(data) ? data : (data?.data ?? []);
    } catch (e) {
        console.error(e);
    } finally {
        item.variantLoading = false;
    }
};

// A fresh product pick starts the variant choice over - a variant from the
// previous product would otherwise silently stay selected underneath it.
const onProductPicked = async (item, option) => {
    item.product_variant_id = null;
    await loadVariantOptions(item, option?.id);

    // Most products only carry one variant, so there is nothing to actually
    // choose - picking it automatically saves that extra click every time.
    if (item.variantOptions.length === 1) {
        item.product_variant_id = item.variantOptions[0].id;
        onVariantPicked(item, item.variantOptions[0]);
    }
};

// The price is specific to the variant (storage/color tiers commonly differ
// in price), so it prefills from whichever one gets picked. It is the price
// after any active offer or variant discount, same as the storefront; the
// regular price is kept to show the discount under the field.
const onVariantPicked = (item, variant) => {
    item.regular_price = variant?.max_retail_price != null ? Number(variant.max_retail_price) : null;

    const sellingPrice = variant?.selling_price ?? variant?.max_retail_price;
    if (sellingPrice != null) {
        item.unit_price = sellingPrice;
    }
};

const discountHint = (item) => {
    const discount = (Number(item.regular_price) || 0) - (Number(item.unit_price) || 0);

    return discount > 0 ? `Regular ${money(item.regular_price)} − ${money(discount)} off` : '';
};

const lineTotal = (item) => (Number(item.quantity) || 0) * (Number(item.unit_price) || 0);

const sellingPrice = computed(() => items.value.reduce((sum, item) => sum + lineTotal(item), 0));

const grandTotal = computed(() => sellingPrice.value + (Number(formData.value.shipping_charge) || 0));

const money = (value) => Number(value || 0).toLocaleString('en-US', { minimumFractionDigits: 2 });

// Name and mobile live on the contact, so they are never typed here — the
// server reads them off the picked contact when the order is saved.
const isCustomerModal = ref(false);
const customerPickerKey = ref(0);

// Only prefill a blank address — a delivery address for this order may
// deliberately differ from the one on the contact. The customer's full
// address (street, area, thana, district, postcode, country) comes from the server.
const prefillAddress = async (contactId, fallbackAddress) => {
    if (!contactId || formData.value.address) return;

    let address = fallbackAddress ?? "";
    try {
        const { data } = await Axios.get(`${CUSTOMERS}/${contactId}`);
        address = data.invoice_address || address;
    } catch {
        // Keep the street address the picker already gave us.
    }

    if (!formData.value.address) {
        formData.value.address = address;
    }
};

const selectCustomer = (contact) => {
    if (!contact) return;

    prefillAddress(contact.id, contact.address);
};

// A customer added through the "+" is selected straight away, so the agent
// never has to go find the record they just created.
const onCustomerCreated = (customer) => {
    if (!customer?.id) return;

    formData.value.contact_id = customer.id;

    prefillAddress(customer.id, customer.address);

    // The picker resolves its label once, on mount — bump the key so it
    // remounts and picks up the id we just set.
    customerPickerKey.value++;
};

const submit = () => {
    save(props.selectedUrl ? props.selectedUrl : ORDER_CONFIRMATIONS, {
        date: formData.value.date || "",
        confirmation_date: formData.value.confirmation_date || "",
        contact_id: formData.value.contact_id || null,
        address: formData.value.address || "",
        source_media: formData.value.source_media || "",
        order_type: formData.value.order_type || "online",
        items: items.value.map((item) => ({
            product_id: item.legacyCustom ? null : item.product_id || null,
            product_variant_id: item.legacyCustom ? null : item.product_variant_id || null,
            custom_product: item.legacyCustom ? item.custom_product || "" : "",
            quantity: Number(item.quantity) || 0,
            unit_price: Number(item.unit_price) || 0,
        })),
        order_person_id: formData.value.order_person_id || null,
        advance_media_id: formData.value.advance_media_id || null,
        advance_amount: formData.value.advance_amount ?? 0,
        selling_price: formData.value.selling_price ?? 0,
        shipping_charge: formData.value.shipping_charge ?? 0,
        cod: formData.value.cod ?? 0,
        note: formData.value.note || "",
        delivery_status_id: formData.value.delivery_status_id || null,
        invoice_no: formData.value.invoice_no || "",
        delivery_by_id: formData.value.delivery_by_id || null,
        track_url: formData.value.track_url || "",
        delivery_note: formData.value.delivery_note || "",
    });
};

const getEditData = async () => {
    pageLoader.value = true;
    try {
        const { data } = await Axios.get(props.selectedUrl);

        hydrating.value = true;
        formData.value = {
            ...data,
            // The list formats dates for display; the inputs need them raw.
            date: data.date_raw ?? "",
            confirmation_date: data.confirmation_date_raw ?? "",
        };
        items.value = (data.items ?? []).map((item) => ({
            product_id: item.product_id,
            product_variant_id: item.product_variant_id,
            custom_product: item.custom_product ?? "",
            quantity: Number(item.quantity),
            unit_price: Number(item.unit_price),
            legacyCustom: !item.product_id && !!item.custom_product,
            variantOptions: [],
            variantLoading: false,
        }));

        // An order saved before this list existed would leave the form with no
        // rows at all, and nothing to type into.
        if (items.value.length === 0) addItem();

        // Populate each row's variant dropdown so its already-picked variant
        // shows up selected rather than the row looking unset.
        await Promise.all(
            items.value
                .filter((item) => item.product_id)
                .map((item) => loadVariantOptions(item, item.product_id))
        );

        await nextTick();
        hydrating.value = false;
    } finally {
        pageLoader.value = false;
    }
};

onMounted(() => {
    if (props.selectedUrl) {
        getEditData();
    } else {
        formData.value = {
            date: new Date().toISOString().slice(0, 10),
            order_type: "online",
            advance_amount: 0,
            cod: 0,
        };
        addItem();
    }
});
</script>

<template>
    <app-modal :modal-id="modalId" modal-size="extra-large"
        :title="selectedUrl ? $t('update_order') : $t('add_order')" :preloader="preloader" @submit="submit"
        @close="closeModal">
        <template #body>
            <app-loader v-if="pageLoader" />
            <form v-else @submit.prevent="submit">
                <div class="row g-4">
                    <div class="col-12">
                        <h6 class="title mb-0">{{ $t('customer') }}</h6>
                    </div>

                    <div class="col-xl-3 col-md-6">
                        <app-input type="date" id="date" :label="$t('date')" label-required v-model="formData.date"
                            auto-apply :enable-time-picker="false" :errors="$errors(errors, 'date')" />
                    </div>

                    <div class="col-xl-3 col-md-6">
                        <app-input type="date" id="confirmation_date" :label="$t('confirmation_date')"
                            v-model="formData.confirmation_date" auto-apply :enable-time-picker="false"
                            :errors="$errors(errors, 'confirmation_date')" />
                    </div>

                    <div class="col-xl-6 col-md-6">
                        <label class="form-label">
                            {{ $t('customer') }} <span class="text-danger">*</span>
                        </label>
                        <div class="d-flex gap-2 align-items-start">
                            <div class="flex-grow-1">
                                <!-- Remounted on :key so a customer created via -->
                                <!-- the "+" resolves its label straight away. -->
                                <app-input :key="customerPickerKey" type="advance-search-select" id="contact_id"
                                    list-key-field="id" list-value-field="name" :loading-length="10"
                                    :fetch-url="SELECTED_TELESALE_CUSTOMERS" v-model="formData.contact_id"
                                    :choose-label="$t('choose_a_customer')" 
                                    @searchSelect="selectCustomer($event)" :errors="$errors(errors, 'contact_id')" />
                            </div>
                            <button type="button" class="btn btn-primary flex-shrink-0"
                                :title="$t('add_customer')" @click="isCustomerModal = true">
                                <Plus />
                            </button>
                        </div>
                    </div>

                    <!-- Order Media and Address share a line. -->
                    <div class="col-xl-3 col-md-6">
                        <app-input type="select" id="source_media" :label="$t('source_media')" list-value-field="name"
                            :options="sourceMediaOptions" :selected-disable="false" v-model="formData.source_media"
                            :choose-label="$t('choose_media')" :errors="$errors(errors, 'source_media')" />
                    </div>

                    <div class="col-xl-9 col-md-6">
                        <app-input type="textarea" id="address" :label="$t('address')" label-required
                            :placeholder="$t('address')" v-model="formData.address"
                            :errors="$errors(errors, 'address')" />
                    </div>

                    <div class="col-12">
                        <h6 class="title mb-0">{{ $t('order') }}</h6>
                    </div>

                    <!-- One row per product; an order can carry several. -->
                    <div class="col-12">
                        <div v-for="(item, index) in items" :key="index" class="row g-3 align-items-end mb-3">
                            <div class="col-xl-5 col-md-12">
                                <template v-if="!item.legacyCustom">
                                    <app-input type="advance-search-select"
                                        :id="`item_product_${index}`" :label="$t('product')" label-required
                                        list-key-field="id" list-value-field="name" :loading-length="10"
                                        :fetch-url="SELECTED_PRODUCT" v-model="item.product_id"
                                        :choose-label="$t('choose_a_product')" :selected-disable="false"
                                        @searchSelect="onProductPicked(item, $event)"
                                        :errors="$errors(errors, `items.${index}.product_id`)" />

                                    <div v-if="item.product_id" class="mt-2">
                                        <app-input type="select" :id="`item_variant_${index}`" :label="$t('variant')"
                                            list-key-field="id" list-value-field="attributes"
                                            :options="item.variantOptions" v-model="item.product_variant_id"
                                            :choose-label="item.variantLoading ? $t('loading') : $t('choose_a_variant')"
                                            :selected-disable="false" @selectInput="onVariantPicked(item, $event)"
                                            :errors="$errors(errors, `items.${index}.product_variant_id`)" />
                                    </div>
                                </template>
                                <template v-else>
                                    <label class="form-label">{{ $t('product') }}</label>
                                    <div class="form-control-plaintext ps-2">{{ item.custom_product }}</div>
                                </template>
                            </div>

                            <div class="col-xl-2 col-md-4">
                                <app-input type="number" :id="`item_quantity_${index}`" :label="$t('quantity')"
                                    label-required placeholder="1" v-model="item.quantity"
                                    :errors="$errors(errors, `items.${index}.quantity`)" />
                            </div>

                            <div class="col-xl-2 col-md-4">
                                <app-input type="number" :id="`item_unit_price_${index}`" :label="$t('unit_price')"
                                    placeholder="0" v-model="item.unit_price"
                                    :errors="$errors(errors, `items.${index}.unit_price`)" />
                                <small v-if="discountHint(item)" class="text-success">{{ discountHint(item) }}</small>
                            </div>

                            <div class="col-xl-2 col-md-3">
                                <label class="form-label">{{ $t('total') }}</label>
                                <div class="form-control-plaintext ps-2">{{ money(lineTotal(item)) }}</div>
                            </div>

                            <div class="col-xl-1 col-md-1">
                                <button type="button" class="btn btn-white w-100" :title="$t('delete')"
                                    :disabled="items.length === 1" @click="removeItem(index)">
                                    <Trash />
                                </button>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center">
                            <button type="button" class="btn btn-white" @click="addItem">
                                <Plus /> {{ $t('add_product') }}
                            </button>

                            <div>
                                <span class="text-muted me-2">{{ $t('selling_price') }}</span>
                                <strong>{{ money(sellingPrice) }}</strong>
                                <span class="text-muted mx-2">+</span>
                                <span class="text-muted me-2">{{ $t('shipping_charge') }}</span>
                                <strong>{{ money(formData.shipping_charge) }}</strong>
                                <span class="text-muted mx-2">=</span>
                                <span class="text-muted me-2">{{ $t('grand_total') }}</span>
                                <strong>{{ money(grandTotal) }}</strong>
                            </div>
                        </div>

                        <div class="text-danger fs-6 mt-2" v-if="$errors(errors, 'items')">
                            {{ $errors(errors, 'items') }}
                        </div>
                    </div>

                    <div class="col-xl-4 col-md-6">
                        <app-input type="select" id="order_type" :label="$t('online_offline')" list-value-field="name"
                            :options="orderTypeOptions" :selected-disable="false" v-model="formData.order_type"
                            :choose-label="$t('online_offline')" :errors="$errors(errors, 'order_type')" />
                    </div>

                    <div class="col-xl-4 col-md-6">
                        <app-input type="advance-search-select" id="order_person_id" :label="$t('order_person')"
                            list-key-field="id" list-value-field="name" :loading-length="10"
                            :fetch-url="`${SELECTED_SELLERS}?active_only=1`" v-model="formData.order_person_id"
                            :choose-label="$t('choose_a_salesman')" :selected-disable="false"
                            :errors="$errors(errors, 'order_person_id')" />
                    </div>

                    <div class="col-xl-4 col-md-6">
                        <app-input type="advance-search-select" id="advance_media_id" :label="$t('advance_media')"
                            list-key-field="id" list-value-field="name" :loading-length="100"
                            :fetch-url="SELECTED_ADVANCE_MEDIAS" v-model="formData.advance_media_id"
                            :choose-label="$t('advance_media')" :selected-disable="false"
                            :errors="$errors(errors, 'advance_media_id')" />
                    </div>

                    <!-- Advance Amount, Shipping Charge and COD share a line. -->
                    <!-- Selling price is the sum of the lines above, shown -->
                    <!-- there, so there is nothing to type for it here. -->
                    <div class="col-xl-2 col-md-6">
                        <app-input type="number" id="advance_amount" :label="$t('advance_amount')" placeholder="0"
                            v-model="formData.advance_amount" :errors="$errors(errors, 'advance_amount')" />
                    </div>

                    <div class="col-xl-2 col-md-6">
                        <app-input type="number" id="shipping_charge" :label="$t('shipping_charge')" placeholder="0"
                            v-model="formData.shipping_charge" :errors="$errors(errors, 'shipping_charge')" />
                    </div>

                    <div class="col-xl-2 col-md-6">
                        <app-input type="number" id="cod" :label="$t('cod')" placeholder="0" v-model="formData.cod"
                            :errors="$errors(errors, 'cod')" />
                    </div>

                    <div class="col-xl-6 col-md-12">
                        <app-input type="textarea" id="note" :label="$t('note')" :placeholder="$t('note')"
                            v-model="formData.note" :errors="$errors(errors, 'note')" />
                    </div>

                    <div class="col-12">
                        <h6 class="title mb-0">{{ $t('delivery') }}</h6>
                    </div>

                    <div class="col-xl-3 col-md-6">
                        <app-input type="advance-search-select" id="delivery_status_id" :label="$t('delivery_status')"
                            list-key-field="id" list-value-field="name" :loading-length="100"
                            :fetch-url="SELECTED_DELIVERY_STATUSES" v-model="formData.delivery_status_id"
                            :choose-label="$t('delivery_status')" :selected-disable="false"
                            :errors="$errors(errors, 'delivery_status_id')" />
                    </div>

                    <div class="col-xl-3 col-md-6">
                        <app-input type="text" id="invoice_no" :label="$t('invoice_no')" :placeholder="$t('invoice_no')"
                            v-model="formData.invoice_no" :errors="$errors(errors, 'invoice_no')" />
                    </div>

                    <div class="col-xl-3 col-md-6">
                        <app-input type="advance-search-select" id="delivery_by_id" :label="$t('delivery_by')"
                            list-key-field="id" list-value-field="name" :loading-length="100"
                            :fetch-url="SELECTED_DELIVERY_BYS" v-model="formData.delivery_by_id"
                            :choose-label="$t('delivery_by')" :selected-disable="false"
                            :errors="$errors(errors, 'delivery_by_id')" />
                    </div>

                    <div class="col-xl-3 col-md-6">
                        <app-input type="text" id="track_url" :label="$t('track')" placeholder="https://..."
                            v-model="formData.track_url" :errors="$errors(errors, 'track_url')" />
                    </div>

                    <div class="col-12">
                        <app-input type="textarea" id="delivery_note" :label="$t('delivery_note')"
                            :placeholder="$t('delivery_note')" v-model="formData.delivery_note"
                            :errors="$errors(errors, 'delivery_note')" />
                    </div>
                </div>
            </form>
        </template>
    </app-modal>

    <!-- No table-id: creating from here must not reload a list behind us. -->
    <customer-modal v-if="isCustomerModal" modal-id="order-customer-modal" @created="onCustomerCreated"
        @close="isCustomerModal = false" />
</template>

<style scoped lang="scss"></style>
