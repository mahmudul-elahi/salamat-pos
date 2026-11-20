<script setup>
import { useSubmitForm } from "@/composable/submit/useSubmitForm.js";

const props = defineProps({ modalId: String, tableId: String, selectedUrl: String, supplier: Object });
const emit = defineEmits(["close"]);

const { preloader, formData, errors, save, closeModal } = useSubmitForm(props, emit, "no");

formData.value = {
    rating: props.supplier?.rating ?? null,
};

const ratingOptions = [
    { id: 1, name: '1' },
    { id: 2, name: '2' },
    { id: 3, name: '3' },
    { id: 4, name: '4' },
    { id: 5, name: '5' },
];

const submit = () => {
    save(props.selectedUrl, formData.value, "patch");
};
</script>

<template>
    <app-modal :modal-id="modalId" title="Rate Supplier" save-button-label="Save" :preloader="preloader"
        @submit="submit" @close="closeModal">
        <template #body>
            <form>
                <div class="row g-4">
                    <div class="col-md-12">
                        <app-input type="select" id="rating" label="Rating" label-required
                            list-value-field="name" :selected-disable="false" :options="ratingOptions"
                            v-model="formData.rating" choose-label="Choose a rating"
                            :errors="$errors(errors, 'rating')" />
                    </div>
                </div>
            </form>
        </template>
    </app-modal>
</template>

<style scoped lang="scss"></style>
