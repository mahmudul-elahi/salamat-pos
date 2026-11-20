<script setup>
import { onMounted } from "vue";
import { useSubmitForm } from "@/composable/submit/useSubmitForm.js";
import Axios from "@/services/axios/index.js";
import { ORDER_CONFIRMATION_OPTIONS } from "@/services/endpoints/landlord.js";

const props = defineProps({ modalId: String, tableId: String, selectedUrl: String, defaultType: String });
const emit = defineEmits(["close"]);

const { preloader, pageLoader, formData, errors, save, closeModal } = useSubmitForm(props, emit, "no");

const typeOptions = [
    { id: "advance_media", name: "Advance Media" },
    { id: "delivery_status", name: "Delivery Status" },
    { id: "delivery_by", name: "Delivery By" },
];

// Same palette as the telesale results — every one has a matching
// .badge-square-light.bg-* rule in _badges.scss, light and dark mode both.
const colorOptions = [
    { id: "success", name: "Green" },
    { id: "warning", name: "Yellow" },
    { id: "danger", name: "Red" },
    { id: "primary", name: "Blue" },
    { id: "info", name: "Sky" },
    { id: "secondary", name: "Grey" },
    { id: "purple", name: "Purple" },
    { id: "pink", name: "Pink" },
    { id: "orange", name: "Orange" },
    { id: "teal", name: "Teal" },
    { id: "indigo", name: "Indigo" },
    { id: "brown", name: "Brown" },
];

const submit = () => {
    save(props.selectedUrl ? props.selectedUrl : ORDER_CONFIRMATION_OPTIONS, {
        type: formData.value.type || "",
        name: formData.value.name || "",
        color: formData.value.color || "secondary",
        sort_order: formData.value.sort_order ?? 0,
        status: formData.value.status ?? true,
    });
};

onMounted(async () => {
    if (props.selectedUrl) {
        pageLoader.value = true;
        try {
            const { data } = await Axios.get(props.selectedUrl);
            formData.value = data;
        } finally {
            pageLoader.value = false;
        }
    } else {
        // Opening from a filtered list pre-picks that list's type.
        formData.value = {
            type: props.defaultType || "advance_media",
            color: "secondary",
            sort_order: 0,
            status: true,
        };
    }
});
</script>

<template>
    <app-modal :modal-id="modalId" :title="selectedUrl ? $t('update_option') : $t('add_option')" :preloader="preloader"
        @submit="submit" @close="closeModal">
        <template #body>
            <app-loader v-if="pageLoader" />
            <form v-else @submit.prevent="submit">
                <div class="row g-4">
                    <div class="col-md-12">
                        <app-input type="select" id="type" :label="$t('type')" label-required list-value-field="name"
                            :options="typeOptions" :selected-disable="false" v-model="formData.type"
                            :choose-label="$t('type')" :disabled="!!selectedUrl"
                            :errors="$errors(errors, 'type')" />
                    </div>
                    <div class="col-md-12">
                        <app-input type="text" id="name" :label="$t('name')" label-required :placeholder="$t('name')"
                            v-model="formData.name" :errors="$errors(errors, 'name')" />
                    </div>
                    <div class="col-md-6">
                        <app-input type="select" id="color" :label="$t('color')" list-value-field="name"
                            :options="colorOptions" :selected-disable="false" v-model="formData.color"
                            :choose-label="$t('color')" :errors="$errors(errors, 'color')" />
                    </div>
                    <div class="col-md-6">
                        <app-input type="number" id="sort_order" :label="$t('sort_order')" placeholder="0"
                            v-model="formData.sort_order" :errors="$errors(errors, 'sort_order')" />
                    </div>
                    <div class="col-md-12">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="status" v-model="formData.status" />
                            <label class="form-check-label" for="status">{{ $t('active') }}</label>
                        </div>
                    </div>
                </div>
            </form>
        </template>
    </app-modal>
</template>

<style scoped lang="scss"></style>
