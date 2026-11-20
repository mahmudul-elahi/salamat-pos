<script setup>
import { ref, onMounted, inject } from 'vue';
import { POLICY_SETTINGS, POLICY_SETTINGS_UPDATE } from "@/services/endpoints/landlord.js";
import Axios from "@/services/axios/index.js";
import { useToast } from "vue-toastification";
// Loader
const pageLoader = ref(false);
const toast = useToast();
// Form data for the editor
const formData = ref({
    value: "",
    id: null
});

// Errors
const errors = ref({});

// Inject tenantId
const tenantId = inject('tenantId');

const fetchPolicies = () => {
    pageLoader.value = true;

    const url = tenantId
        ? `${POLICY_SETTINGS}?type=exchange-policy&tenant_id=${tenantId}`
        : `${POLICY_SETTINGS}?type=exchange-policy`;

    Axios.get(url)
        .then(({ data }) => {
            if (data.result.length) {
                const policy = data.result[0];
                formData.value.id = policy.id;
                formData.value.value = policy.value;
            } else {
                formData.value.id = null;
                formData.value.value = "";
            }
        })
        .finally(() => (pageLoader.value = false));
};

// Submit update for exchange-policy
const submit = () => {
    pageLoader.value = true;

    const payload = {
        value: formData.value.value,
        type: "exchange-policy",
        tenant_id: tenantId
    };

    let url = formData.value.id 
        ? `${POLICY_SETTINGS_UPDATE}/${formData.value.id}` 
        : POLICY_SETTINGS_UPDATE; 

    Axios.post(url, payload)
        .then(({ data }) => {
            if (!formData.value.id && data.result?.id) {
                 formData.value.id = data.result.id;
            }
            toast.success("Policy updated successfully!");
        })
        .catch(err => {
            errors.value = err.response?.data?.errors || {};
        })
        .finally(() => (pageLoader.value = false));
};

onMounted(() => {
    fetchPolicies();
});
</script>

<template>
    <div class="policy-section">
        <app-loader v-if="pageLoader" />

        <form v-else @submit.prevent="submit">
            <div class="mb-4">
                <app-input
                    type="tiptap"
                    id="value"
                    :label="$t('value')"
                    label-required
                    v-model="formData.value"
                    :errors="$errors(errors, 'value')" />
            </div>

            <button class="btn btn-primary mt-3">
                {{ $t("update") }}
            </button>
        </form>
    </div>
</template>

<style scoped lang="scss">
/* Add styling as needed */
</style>
