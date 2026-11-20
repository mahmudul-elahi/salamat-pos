<script setup>
import {useSubmitForm} from "@/composable/submit/useSubmitForm.js";
import {computed, onMounted, ref} from "vue";
import {LANDLORD_EMAIL_TEMPLATE} from "@/services/endpoints/landlord.js";
import Axios from "@/services/axios/index.js";


const emit = defineEmits(['close'])
const props = defineProps({
    modalId: {},
    tableId: {},
    selectedUrl: {},
    templateData: {}
})

const {preloader, pageLoader, formData, errors, save, closeModal} = useSubmitForm(props, emit, 'no')

const submit = () => {
    const data = {
        ...formData.value,
        description: formData.value.description
    }
    save(`${LANDLORD_EMAIL_TEMPLATE}/${formData.value.id}`, data)
}

const getEditData = () => {
    pageLoader.value = true;
    Axios.get(`${LANDLORD_EMAIL_TEMPLATE}/${props.templateData?.id}`)
        .then(({data}) => {
            formData.value = data;
            formData.value.description = data.custom_content ?? data.default_content
        }).finally(() => (pageLoader.value = false));
};

onMounted(() => {
    getEditData();
})

const getAllTags = ref({
    '{receiver_name}': 'the_profile_who_will_receive_the_notification',
    '{email}': 'the_profile_who_will_receive_the_notification',
    '{password}': 'the_profile_who_will_receive_the_notification',
    '{invoice_number}': 'the_profile_who_will_receive_the_notification',
    '{due_date}': 'the_profile_who_will_receive_the_notification',
    '{app_url}': 'the_profile_who_will_receive_the_notification',
    '{app_logo}': 'the_profile_who_will_receive_the_notification',
    '{app_name}': 'the_profile_who_will_receive_the_notification',
    '{action_by}': 'the_profile_who_will_receive_the_notification',
    '{button_url}': 'the_profile_who_will_receive_the_notification',
    '{support_email}': 'the_profile_who_will_receive_the_notification',
    '{estimate_number}': 'the_profile_who_will_receive_the_notification',
    '{estimate_date}': 'the_profile_who_will_receive_the_notification',
    '{payment_date}': 'the_profile_who_will_receive_the_notification',
    '{subscriber_name}': 'the_profile_who_will_receive_the_notification',
    '{subscription_plan}': 'the_profile_who_will_receive_the_notification',
    '{billing_amount}': 'the_profile_who_will_receive_the_notification',
    '{billing_month}': 'the_profile_who_will_receive_the_notification',
    '{expired_date}': 'the_profile_who_will_receive_the_notification',
})

const allTags = computed(() => {
    const tags = Object.keys(getAllTags.value).filter(tag => {
        const templateType = props.templateData

        if (templateType.name === 'subscriber_register') {
            return ['{app_name}', '{receiver_name}', '{app_logo}', '{button_url}', '{email}', '{password}'].includes(tag)
        } else if (templateType.name === 'forget_password') {
            return ['{app_name}', '{receiver_name}', '{app_logo}', '{button_url}'].includes(tag)
        } else if (templateType.name === 'user_invitation') {
            return ['{app_logo}', '{button_url}'].includes(tag)
        } else if (templateType.name === 'user_joined') {
            return ['{app_name}', '{receiver_name}', '{app_logo}'].includes(tag)
        } else if (templateType.name === 'password_change_confirmation') {
            return ['{app_name}', '{receiver_name}', '{app_logo}', '{button_url}', '{support_email}'].includes(tag)
        } else if (templateType.name === 'invoice_sending_attachment') {
            return ['{app_name}', '{receiver_name}', '{invoice_number}', '{due_date}', '{app_logo}'].includes(tag)
        } else if (templateType.name === 'payment_received') {
            return ['{app_name}', '{receiver_name}', '{invoice_number}', '{payment_date}', '{app_logo}'].includes(tag)
        } else if (templateType.name === 'estimate_sending_attachment') {
            return ['{app_name}', '{receiver_name}', '{estimate_number}', '{estimate_date}', '{app_logo}'].includes(tag)
        } else if (templateType.name === 'confirmation_of_account_deletion_request') {
            return ['{app_name}', '{receiver_name}', '{app_logo}', '{button_url}'].includes(tag)
        } else if (templateType.name === 'account_has_been_deleted') {
            return ['{app_name}', '{receiver_name}', '{app_logo}', '{button_url}'].includes(tag)
        } else if (templateType.name === 'subscriber_expired_reminder') {
            return ['{app_name}', '{subscriber_name}', '{app_logo}', '{subscription_plan}', '{expired_date}'].includes(tag)
        } else if (templateType.name === 'subscriber_billing_information') {
            return ['{app_logo}', '{app_name}', '{subscriber_name}', '{billing_month}', '{subscription_plan}', '{billing_amount}'].includes(tag)
        } else {
            return ['{name}', '{action_by}'].includes(tag)
        }
    })

    return tags.map(tag => {
        return {tag, description: getAllTags.value[tag]}
    })
})
const quillEditor = ref();
const insertVariables = (text) => {
    const quill = quillEditor.value.quill.getQuill();
    
    if (quill.getSelection()) {
        let cursorPosition = quill.getSelection().index;
        quill.insertText(cursorPosition, text);
    }
}

</script>

<template>
    <app-modal :modal-id="modalId"
               modal-size="extra-large"
               :title="$t('update_email_template')"
               :preloader="preloader"
               @submit="submit"
               @close="closeModal">
        <template v-slot:body>
            <app-loader v-if="pageLoader"/>
            <div class="row" v-else>
                <div class="col-lg-8">
                    <div class="form-group mb-4">
                        <app-input type="text"
                                   id="name"
                                   :label="$t('subject')"
                                   label-required
                                   :placeholder="$t('subject')"
                                   v-model="formData.subject"
                                   :errors="$errors(errors, 'subject')"/>
                    </div>

                    <div class="form-group mb-4">
                        <app-input type="editor"
                                   id="description"
                                   :label="$t('email_content')"
                                   ref="quillEditor"
                                   v-model="formData.description"
                                   :errors="$errors(errors, 'description')"/>

                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="template-variables mt-4">
                        <p class="title">{{ $t('template_variables') }}</p>
                        <div class="variables">
                            <button v-for="(tag, index) in allTags" :key="index"
                                    class="btn btn-transparent btn-sm d-block text-muted m-0 text-start"
                                    @click.prevent="insertVariables(tag.tag)">
                                {{ tag.tag }}
                            </button>
                        </div>

                    </div>
                </div>
            </div>
        </template>
    </app-modal>
</template>

<style scoped lang="scss">

</style>
