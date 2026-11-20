<script setup>
import { useSubmitForm } from "@/composable/submit/useSubmitForm.js";
import { REVIEWS } from "@/services/endpoints/Ecommerce.js";
import { SELECTED_PRODUCT } from "@/services/endpoints/landlord.js";

const props = defineProps({ modalId: String, tableId: String, selectedUrl: String });
const emit = defineEmits(["close"]);

const { preloader, pageLoader, formData, errors, save, closeModal } = useSubmitForm(props, emit);

formData.value = {
    product_id: null,
    customer_name: null,
    rating: null,
    comment: null,
};

const submit = () => {
    save(REVIEWS, formData.value, "post");
};

const ratingOptions = [
    { id: 1, name: '1' },
    { id: 2, name: '2' },
    { id: 3, name: '3' },
    { id: 4, name: '4' },
    { id: 5, name: '5' },
];
</script>

<template>
    <app-modal :modal-id="modalId" title="Add Review" save-button-label="Add" :preloader="preloader" @submit="submit"
        @close="closeModal">
        <template #body>
            <app-loader v-if="pageLoader" />
            <form v-else>
                <div class="row g-4">
                    <div class="col-md-12">
                        <app-input type="advance-search-select" id="product_id" label="Product" label-required
                            list-value-field="name" v-model="formData.product_id" :loading-length="10"
                            :fetch-url="SELECTED_PRODUCT" choose-label="Choose a product"
                            :errors="$errors(errors, 'product_id')" />
                    </div>

                    <div class="col-md-12">
                        <app-input id="customer_name" type="text" label="Customer Name" label-required
                            placeholder="Enter customer name" v-model="formData.customer_name"
                            :errors="$errors(errors, 'customer_name')" />
                    </div>

                    <div class="col-md-12">
                        <app-input type="select" id="rating" label="Rating" label-required
                            list-value-field="name" :selected-disable="false" :options="ratingOptions"
                            v-model="formData.rating" choose-label="Choose a rating"
                            :errors="$errors(errors, 'rating')" />
                    </div>

                    <div class="col-md-12">
                        <app-input id="comment" type="textarea" label="Comment" placeholder="Write a comment"
                            v-model="formData.comment" :errors="$errors(errors, 'comment')" />
                    </div>
                </div>
            </form>
        </template>
    </app-modal>
</template>

<style scoped lang="scss"></style>
