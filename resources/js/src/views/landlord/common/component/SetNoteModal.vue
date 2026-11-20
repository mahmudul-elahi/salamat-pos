<script setup>
import { useSubmitForm } from "@/composable/submit/useSubmitForm.js";

// Shared by the offline customer, online contact and supplier lists — they all
// store the note on the same `contacts.note` column, only the PATCH url differs.
const props = defineProps({ modalId: String, tableId: String, selectedUrl: String, contact: Object });
const emit = defineEmits(["close"]);

const { preloader, formData, errors, save, closeModal } = useSubmitForm(props, emit, "no");

formData.value = {
    note: props.contact?.note ?? null,
};

const submit = () => {
    save(props.selectedUrl, formData.value, "patch");
};
</script>

<template>
    <app-modal :modal-id="modalId" :title="$t('note')" save-button-label="Save" :preloader="preloader"
        @submit="submit" @close="closeModal">
        <template #body>
            <form>
                <div class="row g-4">
                    <div class="col-md-12">
                        <app-input type="textarea" id="note" :label="$t('note')" :placeholder="$t('note')"
                            v-model="formData.note" :errors="$errors(errors, 'note')" />
                    </div>
                </div>
            </form>
        </template>
    </app-modal>
</template>

<style scoped lang="scss"></style>
