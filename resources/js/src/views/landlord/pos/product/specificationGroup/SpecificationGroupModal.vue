<script setup>
import { onMounted, watch } from "vue";
import { useSubmitForm } from "@/composable/submit/useSubmitForm.js";
import { SPECIFICATION_GROUPS, SELECTED_SPECIFICATION_GROUPS } from "@/services/endpoints/landlord.js";
import { Plus, Trash } from "@iconoir/vue";

const props = defineProps({
    modalId: String,
    tableId: String,
    selectedUrl: String,
});

const emit = defineEmits(["close"]);

const { preloader, pageLoader, formData, errors, save, closeModal } = useSubmitForm(props, emit);

/**
 * Ensure values are always an array, convert objects to strings
 */
const transformValues = () => {
    if (!formData.value.values) {
        formData.value.values = [""];
    } else if (Array.isArray(formData.value.values)) {
        if (formData.value.values.length > 0 && typeof formData.value.values[0] === "object") {
            formData.value.values = formData.value.values.map((v) => v?.value ?? v?.name ?? v);
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
const addValue = () => {
    formData.value.values.push("");
};

const removeValue = (index) => {
    formData.value.values.splice(index, 1);
    if (formData.value.values.length === 0) {
        formData.value.values.push("");
    }
};

const submit = () => {
    const submitData = {
        ...formData.value,
        values: formData.value.values.filter((value) => value.trim() !== ""),
    };
    save(props.selectedUrl ? props.selectedUrl : SPECIFICATION_GROUPS, submitData);
};
watch(
    () => formData.value,
    (newValue) => {
        // Convert sub_specification_groups → values
        if (newValue.sub_specification_groups && !newValue.values) {
            formData.value.values = newValue.sub_specification_groups.map((item) => item.name);
        }

        // Ensure at least one empty input
        if (!formData.value.values || formData.value.values.length === 0) {
            formData.value.values = [""];
        }
    },
    { deep: true, immediate: true }
);

</script>
<template>
    <app-modal :modal-id="modalId"
        modal-size="large"
        :title="selectedUrl ? $t('update_specification_groups') : $t('add_specification_groups')"
        :preloader="preloader"
        @submit="submit" @close="closeModal">
        <template v-slot:body>
            <app-loader v-if="pageLoader" />
            <form v-else>
                <div class="mb-4">
                    <app-input type="advance-search-select"
                        id="specifcations"
                        :label="$t('specifcations')"
                        listValueField="name"
                        list-key-field="id"
                        v-model="formData.parent_id"
                        :loading-length="10"
                        :fetch-url="SELECTED_SPECIFICATION_GROUPS"
                        :choose-label="$t('choose_a_specification')" />
                </div>
                <div class="mb-4">
                    <label class="form-label">
                        {{ $t('sub_specification_value') }}
                        <!-- <span class="text-danger">*</span> -->
                    </label>

                    <div v-for="(value, index) in formData.values" :key="index">
                        <div class="d-flex mb-2 align-items-start w-100">
                            <div class="w-100">
                                <app-input
                                    type="text"
                                    :id="`value-${index}`"
                                    :placeholder="$t('enter_sub_specification_value')"
                                    v-model="formData.values[index]"
                                    :errors="$errors(errors, `values.${index}`)"
                                    class="flex-grow-1"
                                />
                            </div>
                            <button
                                type="button"
                                class="btn btn-danger ms-2"
                                @click="removeValue(index)"
                                v-if="formData.values.length > 1"
                            >
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