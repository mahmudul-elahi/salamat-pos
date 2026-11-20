<script setup>
import { ref, onMounted, watch } from "vue"
import { useSubmitForm } from "@/composable/submit/useSubmitForm.js";
import { COUPON, COUPON_STATUS_CHANGE } from "@/services/endpoints/Ecommerce.js";
import { SELECTED_PRODUCT, SELECTED_CATEGORY, SELECTED_BRAND, SELECTED_CUSTOMER_LEVELS, SELECTED_PRE_OWNED } from "@/services/endpoints/landlord.js";
import { formDataAssigner, serverDateFormat } from "@/utils/helper.js";
import Axios from "@/services/axios/index.js";
const props = defineProps({
    modalId: String,
    tableId: String,
    selectedUrl: String,
})
const emit = defineEmits(['close'])
const { preloader, formData, errors, save, closeModal } = useSubmitForm(props, emit)
if (!formData.value.customer_scopes) {
  formData.value.customer_scopes = [];
}
if (formData.value.is_public === undefined) {
  formData.value.is_public = true;
}
const customerLevels = ref([]);
async function loadCustomerLevels() {
  try {
    const { data } = await Axios.get(SELECTED_CUSTOMER_LEVELS);
    customerLevels.value = data.data;
  } catch (error) {
  }
}
function getFilteredCustomerLevels(index) {
  const selectedIdsExceptCurrent = formData.value.customer_scopes
    .map((scope, i) => (i !== index ? scope.customer_level_id : null))
    .filter(Boolean);

  const allCustomerLevels = customerLevels.value || [];

  return allCustomerLevels.filter(level => !selectedIdsExceptCurrent.includes(level.id));
}

function addCustomerScope() {
  formData.value.customer_scopes.push({
    customer_level_id: null,
    point_amount: null,
    discount_amount: null
  });
}

function removeCustomerScope(index) {
  formData.value.customer_scopes.splice(index, 1);
}
const submit = () => {
    formData.value.start_date = serverDateFormat(formData.value.start_date)
    formData.value.end_date = serverDateFormat(formData.value.end_date)

    formData.value.product_scopes = []

    // If type = product
    if (formData.value.type === 'product' && formData.value.product_id?.length) {
        formData.value.product_id.forEach(id => {
            formData.value.product_scopes.push({
                scope_type: 'product',
                product_id: id
            })
        })
    }

    // If type = category
    if (formData.value.type === 'category' && formData.value.category_id?.length) {
        formData.value.category_id.forEach(id => {
            formData.value.product_scopes.push({
                scope_type: 'category',
                category_id: id
            })
        })
    }

    // If type = brand
    if (formData.value.type === 'brand' && formData.value.brand_id?.length) {
        formData.value.brand_id.forEach(id => {
            formData.value.product_scopes.push({
                scope_type: 'brand',
                brand_id: id
            })
        })
    }

    // If type = pre_owned
    if (formData.value.type === 'pre_owned' && formData.value.pre_owned_id?.length) {
        formData.value.pre_owned_id.forEach(id => {
            formData.value.product_scopes.push({
                scope_type: 'pre_owned',
                pre_owned_id: id
            })
        })
    }
    save(props.selectedUrl ? props.selectedUrl : COUPON, formData.value)
}
watch(
  () => formData.value.type,
  (newType) => {
    if (newType === "customer") {
      if (!formData.value.customer_scopes || formData.value.customer_scopes.length === 0) {
        formData.value.customer_scopes = [
          {
            customer_level_id: null,
            point_amount: null,
            discount_amount: null
          }
        ];
      }
    }
  }
);
onMounted(async () => {
    if (props.selectedUrl) {
        const { data } = await Axios.get(props.selectedUrl);

        const coupon = data.data;

        // assign base fields
        formData.value = {
            ...formData.value,
            id: coupon.id,
            type: coupon.type,
            code: coupon.code,
            discount_value: coupon.discount_value,
            start_date: coupon.start_date,
            end_date: coupon.end_date,
            usage_limit: coupon.usage_limit,
            min_purchase_amount: coupon.min_purchase_amount,
            is_public: coupon.is_public,
        };

        // load selected values for multi-select
        if (coupon.type === "product") {
            formData.value.product_id = coupon.product_scopes.map(s => s.product_id);
        }

        if (coupon.type === "category") {
            formData.value.category_id = coupon.product_scopes.map(s => s.category_id);
        }

        if (coupon.type === "brand") {
            formData.value.brand_id = coupon.product_scopes.map(s => s.brand_id);
        }

        if (coupon.type === "pre_owned") {
            formData.value.pre_owned_id = coupon.product_scopes.map(s => s.pre_owned_id);
        }
        // Customer scopes:
        if (coupon.type === "customer") {
            formData.value.customer_scopes = coupon.customer_scopes.map(s => ({
                customer_level_id: s.customer_level_id,
                point_amount: s.point_amount,
                discount_amount: s.discount_amount
            }));
        }
        await loadCustomerLevels();
    } else {
    await loadCustomerLevels();
  }
});

</script>
<template>
    <app-modal :modal-id="modalId"
        modal-size="large"
        :title="selectedUrl ? $t('update_coupon') : $t('add_coupon')"
        :preloader="preloader"
        @submit="submit" @close="closeModal">
        <template v-slot:body>
            <div class="mb-4">
                <app-input type="select"
                    id="type"
                    :label="$t('coupon_type')"
                    label-required
                    list-value-field="name"
                    list-key-field="id"
                    :choose-label="$t('choose_a_coupon_type')"
                    v-model="formData.type"
                    :disabled="!!selectedUrl"
                    :options="[{ id: 'all', name: 'All' }, { id: 'product', name: 'Product' }, { id: 'category', name: 'Category' }, { id: 'brand', name: 'Brand' }, { id: 'customer', name: 'Customer' }, { id: 'pre_owned', name: 'Pre-Owned' }]"
                    :errors="$errors(errors, 'type')"/>
            </div>
            <template v-if="formData.type === 'product'">
                <div class="mb-4">
                    <app-input
                        type="advance-multi-select"
                        id="product"
                        :label="$t('product')"
                        list-value-field="name"
                        v-model="formData.product_id"
                        :loading-length="10"
                        :fetch-url="SELECTED_PRODUCT"
                        :choose-label="$t('choose_a_product')" :errors="$errors(errors, 'product_id')" />
                </div>
            </template>
            <template v-if="formData.type === 'category'">
                <div class="mb-4">
                    <app-input
                        type="advance-multi-select"
                        id="category"
                        :label="$t('category')"
                        list-value-field="name"
                        v-model="formData.category_id"
                        :loading-length="10"
                        :fetch-url="SELECTED_CATEGORY"
                        :choose-label="$t('choose_a_category')" :errors="$errors(errors, 'category_id')" />
                </div>
            </template>
            <template v-if="formData.type === 'brand'">
                <div class="mb-4">
                    <app-input
                        type="advance-multi-select"
                        id="category"
                        :label="$t('brand')"
                        list-value-field="name"
                        v-model="formData.brand_id"
                        :loading-length="10"
                        :fetch-url="SELECTED_BRAND"
                        :choose-label="$t('choose_a_brand')" :errors="$errors(errors, 'brand_id')" />
                </div>
            </template>
            <template v-if="formData.type === 'pre_owned'">
                <div class="mb-4">
                    <app-input
                        type="advance-multi-select"
                        id="pre_owned"
                        label="Pre-Owned Product"
                        list-value-field="name"
                        v-model="formData.pre_owned_id"
                        :loading-length="10"
                        :fetch-url="SELECTED_PRE_OWNED"
                        choose-label="Choose pre-owned products" :errors="$errors(errors, 'pre_owned_id')" />
                </div>
            </template>
            <template v-if="formData.type === 'all' || formData.type === 'brand' || formData.type === 'category' || formData.type === 'product' || formData.type === 'customer' || formData.type === 'pre_owned'">
                <div class="row g-4">
                    <div class="col-xl-6">
                        <app-input type="text"
                            id="code"
                            :label="$t('code')"
                            label-required
                            :placeholder="$t('code')"
                            v-model="formData.code"
                            :errors="$errors(errors, 'code')" />
                    </div>
                    <div class="col-xl-6">
                        <app-input type="number"
                            id="discount_value"
                            :label="$t('discount_value')"
                            label-required
                            :placeholder="$t('discount_amount')"
                            v-model="formData.discount_value"
                            :errors="$errors(errors, 'discount_value')" />
                    </div>
                    <div class="col-xl-6">
                        <app-input type="date"
                            id="start_date"
                            :label="$t('start_date')"
                            label-required
                            :placeholder="$t('start_date')"
                            v-model="formData.start_date"
                            :enable-time-picker="false"
                            auto-apply
                            :errors="$errors(errors, 'start_date')" />
                    </div>
                    <div class="col-xl-6">
                        <app-input type="date"
                            id="end_date"
                            :label="$t('end_date')"
                            label-required
                            :placeholder="$t('end_date')"
                            v-model="formData.end_date"
                            :enable-time-picker="false"
                            auto-apply
                            :errors="$errors(errors, 'end_date')" />
                    </div>
                    <div class="col-xl-6">
                        <app-input type="number"
                            id="usage_limit"
                            :label="$t('usage_limit')"
                            :placeholder="$t('usage_limit')"
                            v-model="formData.usage_limit"
                            :errors="$errors(errors, 'usage_limit')" />
                    </div>
                    <div class="col-xl-6">
                        <app-input type="number"
                            id="min_purchase_amount"
                            :label="$t('min_purchase_amount')"
                            :placeholder="$t('min_purchase_amount')"
                            v-model="formData.min_purchase_amount"
                            :errors="$errors(errors, 'min_purchase_amount')" />
                    </div>
                    <div class="col-xl-6">
                        <app-input type="select"
                            id="is_public"
                            label="Visibility"
                            label-required
                            list-value-field="name"
                            list-key-field="id"
                            :selected-disable="false"
                            :options="[{ id: true, name: 'Public' }, { id: false, name: 'Private' }]"
                            choose-label="Choose visibility"
                            v-model="formData.is_public"
                            :errors="$errors(errors, 'is_public')" />
                    </div>
                </div>
            </template>
            <template v-if="formData.type === 'customer'">
                <div v-for="(scope, index) in formData.customer_scopes" :key="index" class="row g-4 mt-2">
                      <div class="col-xl-6">
            <app-input
                type="select"
                :id="'customer_level_' + index"
                :label="$t('customer_level')"
                label-required
                list-value-field="name"
                list-key-field="id"
                 :options="getFilteredCustomerLevels(index)"
                :choose-label="$t('choose_customer_level')"
                v-model="scope.customer_level_id"
                :errors="$errors(errors, `customer_scopes.${index}.customer_level_id`)"
            />
        </div>
                    <div class="col-xl-6">
                        <app-input
                            type="number"
                            :id="'point_amount_' + index"
                            label-required
                            :label="$t('point_amount')"
                            :placeholder="$t('point_amount')"
                            v-model="scope.point_amount"
                            :errors="$errors(errors, `customer_scopes.${index}.point_amount`)" />
                    </div>
                    <div class="col-xl-6">
                        <app-input
                            type="number"
                            :id="'discount_amount_' + index"
                            :label="$t('discount_amount')"
                            label-required
                            :placeholder="$t('discount_amount')"
                            v-model="scope.discount_amount"
                            :errors="$errors(errors, `customer_scopes.${index}.discount_amount`)" />
                    </div>
                    <div class="col-12 text-end">
                        <button type="button" class="btn btn-danger btn-sm" @click="removeCustomerScope(index)">
                            {{ $t('remove') }}
                        </button>
                    </div>
                </div>
                <button type="button" class="btn btn-primary btn-sm" @click="addCustomerScope">
                    {{ $t('add_customer_scope') }}
                </button>
            </template>

        </template>
    </app-modal>
</template>

<style scoped lang="scss"></style>