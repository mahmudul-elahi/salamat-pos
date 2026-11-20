<script setup>
import { nextTick, onMounted, ref, watch } from "vue";
import { useSubmitForm } from "@/composable/submit/useSubmitForm.js";
import Axios from "@/services/axios/index.js";
import {
    TELESALES,
    TELESALE_CHECK_NUMBER,
    SELECTED_TELESALE_RESULTS,
    SELECTED_PRODUCT,
    SELECTED_SELLERS,
    SELECTED_TELESALE_CUSTOMERS,
} from "@/services/endpoints/landlord.js";
import { serverDateFormat } from "@/utils/helper";

const props = defineProps({ modalId: String, tableId: String, selectedUrl: String });
const emit = defineEmits(["close"]);


const {
    preloader,
    pageLoader,
    formData,
    errors,
    save,
    closeModal,
} = useSubmitForm(props, emit, "no");

const mediaOptions = [
    { id: "WhatsApp", name: "WhatsApp" },
    { id: "Messenger", name: "Messenger" },
    { id: "Instagram", name: "Instagram" },
    { id: "Phone Call", name: "Phone Call" },
    { id: "IMO", name: "IMO" },
    { id: "Walk-in", name: "Walk-in" },
    { id: "Other", name: "Other" },
];

// Whether the typed number already belongs to a contact. null = not checked yet.
const customerLookup = ref(null);
const checking = ref(false);

// Callers we already hold are picked from the contact list; only genuinely new
// ones get typed in by hand.
const customerFromList = ref(true);

// Set while an existing entry is being loaded, so the mode watchers below don't
// mistake hydration for the user flipping a toggle and wipe the loaded values.
const hydrating = ref(false);

// The product is either picked from the catalogue or typed in free-hand when
// we don't stock it; this toggles which of the two inputs is shown.
const useCustomProduct = ref(false);

const checkNumber = async () => {
    const number = (formData.value.customer_number || "").trim();

    if (!number) {
        customerLookup.value = null;
        return;
    }

    checking.value = true;
    try {
        const { data } = await Axios.get(TELESALE_CHECK_NUMBER, { params: { customer_number: number } });
        customerLookup.value = data;

        // Save the agent retyping details we already hold. Blanks only — what
        // they typed for this call takes precedence.
        if (data.exists) {
            if (!formData.value.customer_name) {
                formData.value.customer_name = data.contact?.name ?? "";
            }
            if (!formData.value.address) {
                formData.value.address = data.contact?.address ?? "";
            }
        }
    } catch (e) {
        customerLookup.value = null;
    } finally {
        checking.value = false;
    }
};

// Picking from the list settles both fields at once — and the caller is known
// by definition, so no lookup round trip is needed.
const selectCustomer = (contact) => {
    if (!contact) {
        customerLookup.value = null;
        return;
    }

    formData.value.customer_name = contact.customer_name ?? "";
    formData.value.customer_number = contact.phone ?? "";

    // Only prefill a blank address — a delivery address typed for this call
    // may deliberately differ from the one on the contact.
    if (!formData.value.address) {
        formData.value.address = contact.address ?? "";
    }

    customerLookup.value = { exists: true, contact };
};

// Switching modes clears whatever the other mode filled in, so a half-typed
// number can't be submitted alongside a picked contact.
watch(customerFromList, () => {
    if (hydrating.value) return;

    formData.value.contact_id = null;
    formData.value.customer_name = "";
    formData.value.customer_number = "";
    formData.value.address = "";
    customerLookup.value = null;
});

watch(useCustomProduct, (custom) => {
    if (hydrating.value) return;

    if (custom) {
        formData.value.product_id = null;
    } else {
        formData.value.custom_product = "";
    }
});

const submit = () => {
    save(props.selectedUrl ? props.selectedUrl : TELESALES, {
        date: formData.value.date || "",
        customer_name: formData.value.customer_name || "",
        customer_number: formData.value.customer_number || "",
        address: formData.value.address || "",
        product_id: useCustomProduct.value ? null : formData.value.product_id || null,
        custom_product: useCustomProduct.value ? formData.value.custom_product || "" : "",
        media: formData.value.media || "",
        note: formData.value.note || "",
        telesale_result_id: formData.value.telesale_result_id || null,
        salesman_id: formData.value.salesman_id || null,
    });
};

const getEditData = async () => {
    pageLoader.value = true;
    try {
        const { data } = await Axios.get(props.selectedUrl);

        hydrating.value = true;
        formData.value = {
            ...data,
            // The API sends a display-formatted date ("14 Aug 2026"); the input needs it raw.
            date: serverDateFormat(data.date),
        };
        // Entries logged against a contact reopen on the dropdown; the rest,
        // including callers who were new at the time, reopen on manual entry.
        customerFromList.value = !!data.contact_id;
        useCustomProduct.value = !data.product_id && !!data.custom_product;
        customerLookup.value = { exists: data.customer_type === "existing" };

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
        formData.value.date = serverDateFormat(new Date());
    }
});
</script>

<template>
    <app-modal :modal-id="modalId" modal-size="extra-large"
        :title="selectedUrl ? $t('update_telesale') : $t('add_telesale')" :preloader="preloader" @submit="submit"
        @close="closeModal">
        <template #body>
            <app-loader v-if="pageLoader" />
            <form v-else @submit.prevent="submit">
                <div class="row g-4">
                    <!-- Row 1 — who called. The mode toggle sits directly above -->
                    <!-- the field it governs, inside the same column. -->
                    <div class="col-xl-3 col-md-6">
                        <!-- Matches the height of the toggle in the next column -->
                        <!-- so both inputs sit on the same line. Same markup as -->
                        <!-- the real one, so it can never drift out of step. -->
                        <div class="form-check mb-2 invisible d-none d-xl-block" aria-hidden="true">
                            <input class="form-check-input" type="checkbox" tabindex="-1" disabled />
                            <label class="form-check-label">&nbsp;</label>
                        </div>

                        <app-input type="date" id="date" :label="$t('date')" label-required v-model="formData.date"
                            auto-apply :enable-time-picker="false" :errors="$errors(errors, 'date')" />
                    </div>

                    <div class="col-xl-9 col-md-6">
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" id="customer_not_in_list"
                                :checked="!customerFromList" @change="customerFromList = !$event.target.checked" />
                            <label class="form-check-label" for="customer_not_in_list">
                                {{ $t('customer_not_in_system') }}
                            </label>
                        </div>

                        <!-- Known caller: one pick fills both the name and the number. -->
                        <div class="row g-4" v-if="customerFromList">
                            <div class="col-12">
                                <app-input type="advance-search-select" id="contact_id" :label="$t('customer')"
                                    label-required list-key-field="id" list-value-field="name" :loading-length="10"
                                    :fetch-url="SELECTED_TELESALE_CUSTOMERS" v-model="formData.contact_id"
                                    :choose-label="$t('choose_a_customer')" @searchSelect="selectCustomer($event)"
                                    :errors="$errors(errors, 'customer_number')" />

                                <div class="mt-1" v-if="formData.customer_number">
                                    <span class="badge-square-light bg-success">{{ $t('existing_customer') }}</span>
                                    <span class="ms-2">{{ formData.customer_number }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- New caller: typed by hand, still checked against the -->
                        <!-- contact book in case the number is already on file. -->
                        <div class="row g-4" v-else>
                            <div class="col-md-6">
                                <app-input type="text" id="customer_number" :label="$t('customer_number')"
                                    label-required :placeholder="$t('customer_number')"
                                    v-model="formData.customer_number" @blur="checkNumber"
                                    :errors="$errors(errors, 'customer_number')" />

                                <div class="mt-1" v-if="checking">
                                    <span class="badge-square-light bg-secondary">{{ $t('checking') }}…</span>
                                </div>
                                <div class="mt-1" v-else-if="customerLookup">
                                    <span v-if="customerLookup.exists" class="badge-square-light bg-success">
                                        {{ $t('existing_customer') }}
                                    </span>
                                    <span v-else class="badge-square-light bg-primary">{{ $t('new_customer') }}</span>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <app-input type="text" id="customer_name" :label="$t('customer_name')"
                                    :placeholder="$t('customer_name')" v-model="formData.customer_name"
                                    :errors="$errors(errors, 'customer_name')" />
                            </div>
                        </div>
                    </div>

                    <!-- Row 2 — what they asked for and how the call went. -->
                    <div class="col-xl-6 col-md-6">
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" id="use_custom_product"
                                v-model="useCustomProduct" />
                            <label class="form-check-label" for="use_custom_product">
                                {{ $t('product_not_in_catalogue') }}
                            </label>
                        </div>

                        <app-input v-if="!useCustomProduct" type="advance-search-select" id="product_id"
                            :label="$t('customer_demand')" list-key-field="id" list-value-field="name"
                            :loading-length="10" :fetch-url="SELECTED_PRODUCT" v-model="formData.product_id"
                            :choose-label="$t('choose_a_product')" :errors="$errors(errors, 'product_id')" />
                        <app-input v-else type="text" id="custom_product" :label="$t('customer_demand')"
                            :placeholder="$t('customer_demand')" v-model="formData.custom_product"
                            :errors="$errors(errors, 'custom_product')" />
                    </div>

                    <div class="col-xl-6 col-md-6">
                        <!-- Clears the toggle above the demand field beside it. -->
                        <div class="form-check mb-2 invisible d-none d-xl-block" aria-hidden="true">
                            <input class="form-check-input" type="checkbox" tabindex="-1" disabled />
                            <label class="form-check-label">&nbsp;</label>
                        </div>

                        <app-input type="select" id="media" :label="$t('media')" list-value-field="name"
                            :options="mediaOptions" :selected-disable="false" v-model="formData.media"
                            :choose-label="$t('choose_media')" :errors="$errors(errors, 'media')" />
                    </div>

                    <div class="col-xl-6 col-md-6">
                        <app-input type="advance-search-select" id="telesale_result_id" :label="$t('result')"
                            list-key-field="id" list-value-field="name" :loading-length="100"
                            :fetch-url="SELECTED_TELESALE_RESULTS" v-model="formData.telesale_result_id"
                            :choose-label="$t('choose_a_result')" :errors="$errors(errors, 'telesale_result_id')" />
                    </div>

                    <!-- Row 3 — who handled it, plus the free-text note. -->
                    <div class="col-xl-6 col-md-6">
                        <app-input type="advance-search-select" id="salesman_id" :label="$t('salesman_name')"
                            list-key-field="id" list-value-field="name" :loading-length="10"
                            :fetch-url="`${SELECTED_SELLERS}?active_only=1`" v-model="formData.salesman_id"
                            :choose-label="$t('choose_a_salesman')" :errors="$errors(errors, 'salesman_id')" />
                    </div>

                    <div class="col-xl-6 col-md-6">
                        <app-input type="textarea" id="address" :label="$t('address')" :placeholder="$t('address')"
                            v-model="formData.address" :errors="$errors(errors, 'address')" />
                    </div>

                    <div class="col-xl-6 col-md-6">
                        <app-input type="textarea" id="note" :label="$t('note')" :placeholder="$t('note')"
                            v-model="formData.note" :errors="$errors(errors, 'note')" />
                    </div>
                </div>
            </form>
        </template>
    </app-modal>
</template>

<style scoped lang="scss"></style>
