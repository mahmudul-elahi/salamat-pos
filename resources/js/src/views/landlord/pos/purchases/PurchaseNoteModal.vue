<script setup>
import { useSubmitForm } from "@/composable/submit/useSubmitForm.js";
import { ref, watch } from 'vue';

const props = defineProps({
    modalId: String,
    note: String,
})
const emit = defineEmits(['close', 'update:note'])

const localNote = ref(props.note || '');
const { closeModal } = useSubmitForm(props, emit);

watch(() => props.note, (val) => {
    localNote.value = val;
});

const submit = () => {
    emit('update:note', localNote.value)
    closeModal();
}
</script>

<template>
    <app-modal
        :modal-id="modalId"
        modal-size="large"
        :title="$t('add_note')"
        @submit="submit"
        @close="emit('close')">
        <template v-slot:body>
            <div class="mb-4">
                <app-input
                    type="textarea"
                    id="note"
                    :label="$t('note')"
                    label-required
                    v-model="localNote" />
            </div>
        </template>
    </app-modal>
</template>
