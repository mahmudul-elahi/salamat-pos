<script setup>
import { onMounted, ref, watch } from "vue";
import { useSubmitForm } from "@/composable/submit/useSubmitForm.js";
import { FAQS } from "@/services/endpoints/Ecommerce.js";
import { SELECTED_ECOMMERCES } from "@/services/endpoints/landlord.js";

const props = defineProps({
    modalId: String,
    tableId: String,
    selectedUrl: String,
});

const emit = defineEmits(['close']);
const { preloader, pageLoader, errors, formData, save, closeModal } = useSubmitForm(props, emit);

const activeLang = ref('en');

const langs = [
    { key: 'en', label: 'English' },
    { key: 'bn', label: 'বাংলা' },
];

const statusOptions = [
    { id: 1, name: 'Active' },
    { id: 0, name: 'Inactive' },
];

const defaultFaqs = () => [
    { id: null, lang: 'en', question: '', answer: '' },
    { id: null, lang: 'bn', question: '', answer: '' },
];

const ensureFaqs = () => {
    if (!formData.value.faqs || !formData.value.faqs.length) {
        formData.value.faqs = defaultFaqs();
    }
    // Make sure both EN and BN exist
    langs.forEach(({ key }) => {
        if (!formData.value.faqs.find(f => f.lang === key)) {
            formData.value.faqs.push({ id: null, lang: key, question: '', answer: '' });
        }
    });
};

const faqByLang = (lang) => formData.value.faqs?.find(f => f.lang === lang) ?? { question: '', answer: '' };

watch(pageLoader, (loading) => {
    if (!loading) ensureFaqs();
});

const submit = () => {
    const payload = {
        ...formData.value,
        faqs: (formData.value.faqs ?? []).filter(f => f.question?.trim() && f.answer?.trim()),
    };
    save(props.selectedUrl ? props.selectedUrl : FAQS, payload);
};

onMounted(() => {
    if (!props.selectedUrl) {
        formData.value.status = 1;
        ensureFaqs();
    }
});
</script>

<template>
    <app-modal
        :modal-id="modalId"
        modal-size="large"
        :title="selectedUrl ? $t('update_faq') : $t('add_faq')"
        :preloader="preloader"
        @submit="submit"
        @close="closeModal">
        <template #body>
            <app-loader v-if="pageLoader"/>
            <form v-else @submit.prevent="submit">
                <div class="row g-4">
                    <!-- Shop -->
                    <div class="col-lg-6">
                        <app-input
                            type="advance-search-select"
                            id="tenant_id"
                            label-required
                            :label="$t('shop_name')"
                            list-value-field="name"
                            v-model="formData.tenant_id"
                            :loading-length="10"
                            :fetch-url="SELECTED_ECOMMERCES"
                            :choose-label="$t('choose_a_shop')"
                            :errors="$errors(errors, 'tenant_id')"/>
                    </div>

                                    <!-- Order -->
                    <div class="col-md-6">
                        <app-input
                            type="number"
                            id="order"
                            :label="$t('order')"
                            :placeholder="$t('order')"
                            v-model="formData.order"
                            :errors="$errors(errors, 'order')"/>
                    </div>

                    <!-- Status -->
                    <div class="col-md-6">
                        <app-input
                            type="select"
                            id="status"
                            :label="$t('status')"
                            list-value-field="name"
                            :selected-disable="false"
                            :options="statusOptions"
                            v-model="formData.status"
                            :errors="$errors(errors, 'status')"/>
                    </div>

                    <!-- Language tabs -->
                    <div class="col-12">
                        <ul class="nav nav-tabs mb-3" style="border-bottom: 2px solid #e8eaf0;">
                            <li v-for="lang in langs" :key="lang.key" class="nav-item">
                                <button
                                    type="button"
                                    class="nav-link"
                                    :class="{ active: activeLang === lang.key }"
                                    style="font-weight: 500; font-size: 14px;"
                                    @click="activeLang = lang.key">
                                    {{ lang.label }}
                                </button>
                            </li>
                        </ul>

                        <template v-if="formData.faqs">
                            <div
                                v-for="(faq, index) in formData.faqs"
                                :key="faq.lang"
                                v-show="activeLang === faq.lang"
                                class="row g-3">
                                <div class="col-12">
                                    <app-input
                                        type="textarea"
                                        :id="`question_${faq.lang}`"
                                        :label="$t('question')"
                                        label-required
                                        :placeholder="$t('question')"
                                        v-model="faq.question"
                                        :errors="$errors(errors, `faqs.${index}.question`)"/>
                                </div>
                                <div class="col-12">
                                    <app-input
                                        type="editor"
                                        :id="`answer_${faq.lang}`"
                                        :label="$t('answer')"
                                        label-required
                                        v-model="faq.answer"
                                        :errors="$errors(errors, `faqs.${index}.answer`)"/>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </form>
        </template>
    </app-modal>
</template>

<style scoped lang="scss">
.nav-tabs .nav-link {
    color: #6b7280;
    border: none;
    border-bottom: 2px solid transparent;
    border-radius: 0;
    padding: 8px 20px;
    transition: all 0.2s;

    &:hover {
        color: #5b50f0;
        border-bottom-color: #5b50f0;
        background: transparent;
    }

    &.active {
        color: #5b50f0;
        border-bottom-color: #5b50f0;
        background: transparent;
        font-weight: 600;
    }
}
</style>
