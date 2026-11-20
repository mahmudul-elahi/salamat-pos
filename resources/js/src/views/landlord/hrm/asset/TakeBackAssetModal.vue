<script setup>
import { useSubmitForm } from "@/composable/submit/useSubmitForm.js";
import { HRM_ASSETS } from "@/services/endpoints/hrm.js";

const props = defineProps({
    modalId: String,
    tableId: String,
    asset: Object,
});

const emit = defineEmits(["close"]);

// editData "no" - this modal always closes the asset's current assignment,
// it never edits an existing record via GET, so useSubmitForm shouldn't try
// to fetch a selectedUrl (which isn't set here) on mount.
const { preloader, formData, errors, save, closeModal } = useSubmitForm(props, emit, "no");

formData.value = {
    returned_date: new Date(),
    condition: null,
    return_note: null,
};

const conditionOptions = [
    { id: 'good', name: 'Good — back in circulation' },
    { id: 'needs_repair', name: 'Needs Repair' },
    { id: 'damaged', name: 'Damaged' },
    { id: 'lost', name: 'Lost / Not Returned' },
];

const submit = () => {
    save(`${HRM_ASSETS}/${props.asset.id}/return`, formData.value);
};
</script>

<template>
    <app-modal :modal-id="modalId" modal-size="default" :title="$t('take_back')" :preloader="preloader"
        @submit="submit" @close="closeModal">
        <template #body>
            <form>
                <div class="row g-4">

                    <div class="col-md-12">
                        <div class="p-3 rounded-3 bg-light">
                            <div class="fw-bold">{{ asset?.asset_name }}</div>
                            <div class="text-muted">{{ asset?.asset_code }}</div>
                            <div v-if="asset?.current_assignment?.employee" class="mt-1">
                                {{ $t('currently_with') }}: <strong>{{ asset.current_assignment.employee.name }}</strong>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-12">
                        <app-input id="returned_date" type="date" :label="$t('returned_date')" label-required
                            :placeholder="$t('returned_date')"
                            v-model="formData.returned_date"
                            :enable-time-picker="false"
                            auto-apply
                            :errors="$errors(errors, 'returned_date')" />
                    </div>

                    <div class="col-md-12">
                        <app-input id="condition" type="select" :label="$t('condition_on_return')" label-required
                            :options="conditionOptions" list-value-field="name" :selected-disable="false"
                            v-model="formData.condition" :errors="$errors(errors, 'condition')" />
                    </div>

                    <div class="col-md-12">
                        <app-input id="return_note" type="textarea" :label="$t('note')" :placeholder="$t('note')"
                            v-model="formData.return_note" :errors="$errors(errors, 'return_note')" />
                    </div>

                </div>
            </form>
        </template>
    </app-modal>
</template>

<style scoped lang="scss"></style>
