<script setup>
import { ref } from "vue"
import { formDataAssigner, serverDateFormat } from "@/utils/helper.js";
import {
    INCOME_CATEGORY, SELECTED_DIRECTORS, SELECTED_SHOPS,
} from "@/services/endpoints/landlord.js";
import Axios from "@/services/axios/index.js";
import { useToast } from "vue-toastification";
import { Xmark } from "@iconoir/vue";

const toast = useToast();
const props = defineProps({
    modalId: String,
    tableId: String,
    selectedUrl: String,
    tenant: {
        type: Object,
        default: () => ({})
    }
});
const formData = ref({
    tenant_id: '',
    director_id : ''
});

const errors = ref({});
const preloader = ref(false);
const submit = async () => {
  preloader.value = true;
  errors.value = {};

  try {
    const response = await Axios.post(INCOME_CATEGORY,  {
        tenant_id: formData.value.tenant_id,
        director_id: formData.value.director_id
    });
    toast.success(response.data?.message);

    formData.value.tenant_id = '';
    formData.value.director_id = '';
  } catch (error) {
    if (error.response?.status === 422) {
      errors.value = error.response.data.errors;
    } else {
      toast.error(error.response?.data?.message || 'Submission failed');
    }
  } finally {
    preloader.value = false;
  }
};


</script>
<template>
    <div class="content-wrapper">
        <app-breadcrumb :page-title="$t('create_income_category')" />
        <div class="card">
            <div class="card-body">
                <div class="row g-4">
<!--                    <div class="col-xl-6">-->
<!--                        <app-input type="text"-->
<!--                            id="name"-->
<!--                            :label="$t('name')"-->
<!--                            label-required-->
<!--                            :placeholder="$t('name')"-->
<!--                            v-model="formData.name"-->
<!--                            :errors="$errors(errors, 'name')" />-->
<!--                    </div>-->

                    <div class="col-sm-6">
                        <app-input type="advance-search-select"
                                   id="shop_id"
                                   :label="$t('warehouse_or_store_name')"
                                   label-required
                                   list-value-field="name"
                                   v-model="formData.tenant_id"
                                   :loading-length="10"
                                   :fetch-url="SELECTED_SHOPS"
                                   :choose-label="$t('choose_a_shop')"
                                   :errors="$errors(errors, 'tenant_id')"/>
                    </div>

                    <div class="col-xl-6">
                        <app-input type="advance-search-select"
                                   id="director"
                                   label="Directors"
                                   label-required
                                   listValueField="name"
                                   list-key-field="id"
                                   v-model="formData.director_id"
                                   :loading-length="10"
                                   :fetch-url="`${SELECTED_DIRECTORS}`"
                                   :choose-label="$t('choose_a_director')"
                                   :errors="$errors(errors, 'director_id')"/>
                    </div>
                    <div class="col-xl-12">
                        <div class="d-flex justify-content-end">
                            <button class="btn btn-primary" @click="submit" :disabled="preloader">
                                <span v-if="preloader" class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                                {{ $t('submit') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
