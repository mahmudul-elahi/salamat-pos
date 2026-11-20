<script setup>
import { onMounted, watch } from "vue";
import { useSubmitForm } from "@/composable/submit/useSubmitForm.js";
import { ATTRIBUTES } from "@/services/endpoints/landlord.js";
import { Plus, Trash } from "@iconoir/vue";

const props = defineProps({
    modalId: String,
    tableId: String,
    selectedUrl: String,
})
const emit = defineEmits(['close'])
const { preloader, pageLoader, formData, errors, save, closeModal } = useSubmitForm(props, emit)

const transformValues = () => {
    if (!formData.value.values) {
        formData.value.values = [""];
    } else if (Array.isArray(formData.value.values)) {
        if (formData.value.values.length > 0 && typeof formData.value.values[0] === 'object') {
            formData.value.values = formData.value.values.map(v => v?.value ?? v?.name ?? v);
        }
        if (formData.value.values.length === 0) {
            formData.value.values = [""];
        }
    } else {
        formData.value.values = [""];
    }
};

onMounted(() => {
    transformValues();
});

watch(
    () => formData.value,
    (newFormData) => {
        if (newFormData.values) {
            transformValues();
        }
    },
    { deep: true, immediate: true }
);

const addValue = () => {
    formData.value.values.push("");
};

const removeValue = (index) => {
    formData.value.values.splice(index, 1);
    // Ensure we always have at least one value
    if (formData.value.values.length === 0) {
        formData.value.values.push("");
    }
};

const submit = () => {
    const submitData = {
        ...formData.value,
        values: formData.value.values.filter(value => value.trim() !== "")
    };
    save(props.selectedUrl ? props.selectedUrl : ATTRIBUTES, submitData)
}
</script>

<template>
    <app-modal :modal-id="modalId"
        modal-size="large"
        :title="selectedUrl ? $t('update_attribute') : $t('add_attribute')"
        :preloader="preloader"
        @submit="submit"
        @close="closeModal">
        <template v-slot:body>
            <app-loader v-if="pageLoader" />
            <form v-else @submit.prevent="submit">
                <div class="mb-4">
                    <app-input type="text" id="name"
                        :label="$t('name')"
                        label-required
                        :placeholder="$t('name')"
                        v-model="formData.name"
                        :errors="$errors(errors, 'name')" />
                </div>
                <div class="mb-4">
                    <label class="form-label fw-bold">
                        {{ $t('values') }}
                        <span class="text-danger">*</span>
                    </label>

                    <div v-for="(value, index) in formData.values" :key="index">
                        <div class="d-flex mb-2 align-items-start w-100">
                            <div class="w-100">
                                <app-input
                                    type="text"
                                    :id="`value-${index}`"
                                    :placeholder="$t('enter_value')"
                                    v-model="formData.values[index]"
                                    :errors="$errors(errors, `values.${index}`)"
                                    class="flex-grow-1" />
                            </div>
                            <button
                                type="button"
                                class="btn btn-danger ms-2"
                                @click="removeValue(index)"
                                v-if="formData.values.length > 1">
                                <Trash />
                            </button>
                        </div>
                    </div>

                    <button type="button" class="btn btn-primary btn-sm mt-2" @click="addValue">
                        <Plus /> {{ $t('add_value') }}
                    </button>
                </div>
            </form>
        </template>
    </app-modal>
</template>

<style scoped lang="scss"></style>