<script setup>
import { ref, onMounted } from 'vue';
import Axios from "@/services/axios/index.js";
import { PRE_OWANED_PRODUCTS } from "@/services/endpoints/Ecommerce.js";
import { SELECTED_CARES } from "@/services/endpoints/landlord.js";
import { useToast } from "vue-toastification";
import { Plus, Trash } from "@iconoir/vue";

const props = defineProps({
    modalId: String,
    preOwnedId: [String, Number],
    tableId: String,
});

const emit = defineEmits(['close']);
const toast = useToast();
const isLoading = ref(false);
const preloader = ref(false);
const errors = ref({});

const careItems = ref([{ care_id: '' }]);
const allCares = ref([]);

const fetchCares = async () => {
    try {
        preloader.value = true;
        const [caresRes, existingRes] = await Promise.all([
            Axios.get(SELECTED_CARES),
            Axios.get(`${PRE_OWANED_PRODUCTS}/${props.preOwnedId}/care`),
        ]);
        allCares.value = caresRes.data;

        if (existingRes.data.length) {
            careItems.value = existingRes.data.map(item => ({ care_id: item.care_id }));
        }
    } catch {
        toast.error('Something went wrong');
    } finally {
        preloader.value = false;
    }
};

const getFilteredCares = (index) => {
    const selectedIds = careItems.value
        .filter((_, i) => i !== index)
        .map(item => Number(item.care_id))
        .filter(id => id);
    return allCares.value.filter(care => !selectedIds.includes(care.id));
};

const addMore = () => careItems.value.push({ care_id: '' });

const removeItem = (index) => {
    if (careItems.value.length > 1) careItems.value.splice(index, 1);
};

const save = async () => {
    errors.value = {};
    if (careItems.value.some(item => !item.care_id)) {
        toast.error('Please select a care for all rows');
        return;
    }
    try {
        isLoading.value = true;
        const { data } = await Axios.post(`${PRE_OWANED_PRODUCTS}/${props.preOwnedId}/care`, {
            care_items: careItems.value,
        });
        toast.success(data.message);
        emit('close');
    } catch (error) {
        if (error.response?.status === 422) {
            errors.value = error.response.data.errors;
        } else {
            toast.error(error.response?.data?.message || 'Something went wrong');
        }
    } finally {
        isLoading.value = false;
    }
};

onMounted(fetchCares);
</script>

<template>
    <app-modal :modal-id="modalId" title="Pre-Owned Care" modal-size="large" :preloader="isLoading"
        @submit="save" @close="emit('close')">
        <template v-slot:body>
            <app-loader v-if="preloader" />
            <div v-else>
                <div v-for="(item, index) in careItems" :key="index" class="row g-2 align-items-end mb-3">
                    <div class="col-11">
                        <app-input type="select" :id="`care_id_${index}`"
                            :label="index === 0 ? 'Select Care' : ''"
                            :options="getFilteredCares(index)"
                            list-value-field="name"
                            choose-label="Choose a care"
                            :showSearch="true"
                            v-model="item.care_id"
                            :errors="$errors(errors, `care_items.${index}.care_id`)" />
                    </div>
                    <div class="col-1">
                        <button type="button" class="btn btn-sm btn-outline-danger mb-1"
                            @click="removeItem(index)" :disabled="careItems.length === 1">
                            <Trash width="16" height="16" />
                        </button>
                    </div>
                </div>

                <button type="button" class="btn btn-outline-success btn-sm mt-1" @click="addMore">
                    <Plus width="16" height="16" class="me-1" />
                    Add More
                </button>
            </div>
        </template>
    </app-modal>
</template>

<style scoped lang="scss"></style>
