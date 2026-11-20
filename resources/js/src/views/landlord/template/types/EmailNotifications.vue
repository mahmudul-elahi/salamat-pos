<script setup>
import {onMounted, ref, computed} from "vue"
import {useToast} from "vue-toastification";
import usePermission from "@/composable/permission/usePermission.js";
import Axios from "@/services/axios/index.js";
import {TEMPLATE_UPDATE} from "@/services/endpoints/landlord.js";
import { Download } from "@iconoir/vue";

const {canAccess} = usePermission();
const props = defineProps({
    componentData: {}
})

const toast = useToast()

const formData = ref({
    subject: '',
    description: ''
})
const getAllTags = ref({
    '{receiver_name}': 'the_profile_who_will_receive_the_notification',
    '{first_name}': 'the_profile_who_will_receive_the_notification',
    '{last_name}': 'the_profile_who_will_receive_the_notification',
    '{email}': 'the_profile_who_will_receive_the_notification',
    '{password}': 'the_profile_who_will_receive_the_notification',
    '{name}': 'the_profile_who_will_receive_the_notification',
    '{company_name}': 'the_profile_who_will_receive_the_notification',
    '{invoice_number}': 'the_profile_who_will_receive_the_notification',
    '{due_date}': 'the_profile_who_will_receive_the_notification',
    '{app_url}': 'the_profile_who_will_receive_the_notification',
    '{app_logo}': 'the_profile_who_will_receive_the_notification',
    '{app_name}': 'the_profile_who_will_receive_the_notification',
    '{action_by}': 'the_profile_who_will_receive_the_notification',
    '{button_url}': 'the_profile_who_will_receive_the_notification',
    '{otp_number}': 'the_profile_who_will_receive_the_notification',
    '{here_link}': 'the_profile_who_will_receive_the_notification',
    '{support_email}': 'the_profile_who_will_receive_the_notification',
    '{estimate_number}': 'the_profile_who_will_receive_the_notification',
})

const allTags = computed(() => {
    const tags = Object.keys(getAllTags.value).filter(tag => {
        // const templateType = props.componentData?.template_type
        // if (templateType.name === 'subscriber_register') {
        //     return ['{app_name}', '{receiver_name}', '{app_logo}', '{button_url}', '{email}', '{password}'].includes(tag)
        // } else if (templateType.name === 'forget_password') {
        //     return ['{app_name}', '{receiver_name}', '{app_logo}', '{button_url}'].includes(tag)
        // } else if (templateType.name === 'user_invitation') {
        //     return ['{app_logo}', '{button_url}'].includes(tag)
        // } else if (templateType.name === 'user_joined') {
        //     return ['{name}', '{company_name}', '{app_name}', '{receiver_name}', '{app_logo}'].includes(tag)
        // } else if (templateType.name === 'password_change_confirmation') {
        //     return ['{app_name}', '{receiver_name}', '{app_logo}', '{here_link}', '{support_email}'].includes(tag)
        // } else if (templateType.name === 'invoice_sending_attachment') {
        //     return ['{app_name}', '{receiver_name}', '{invoice_number}', '{due_date}', '{app_logo}'].includes(tag)
        // } else if (templateType.name === 'payment_received') {
        //     return ['{app_name}', '{receiver_name}', '{invoice_number}', '{app_logo}'].includes(tag)
        // } else if (templateType.name === 'quotation_sending_attachment') {
        //     return ['{app_name}', '{receiver_name}', '{quotation_number}', '{app_logo}'].includes(tag)
        // } else if (templateType.name === 'mobile_app_forget_password') {
        //     return ['{app_name}', '{receiver_name}', '{otp_number}', '{app_logo}'].includes(tag)
        // } else if (templateType.name === 'confirmation_of_account_deletion_request') {
        //     return ['{app_name}', '{receiver_name}', '{app_logo}'].includes(tag)
        // } else if (templateType.name === 'account_has_been_deleted') {
        //     return ['{app_name}', '{receiver_name}', '{app_logo}'].includes(tag)
        // } else {
        //     return ['{name}', '{action_by}'].includes(tag)
        // }
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
const preloader = ref(false)
const submit = () => {
    formData.value.description = formData.value.description.replace(/<p>(\s|&nbsp;|<br\s*\/?>)*<\/p>/gi, '');
    preloader.value = true
    Axios.patch(`${TEMPLATE_UPDATE}/${props.componentData.id}`, formData.value).then(({data}) => {
        toast.success(data?.message);
    }).finally(() => preloader.value = false)
}
onMounted(() => {
    if (props.componentData) {
        formData.value.subject = props.componentData?.subject
        formData.value.description = props.componentData?.custom_content ?? props.componentData?.default_content
    }
})

</script>

<template>
    <div class="row">
        <div class="col-md-8">
            <div class="form-group mb-4">
                <app-input type="text"
                           id="name"
                           label="Subject"
                           label-required
                           placeholder="Subject"
                           v-model="formData.subject"/>
            </div>

            <div class="form-group mb-4">
                <app-input type="editor"
                           id="description"
                           :label="$t('email_content')"
                           ref="quillEditor"
                           v-model="formData.description"/>

            </div>
            <div class="form-group mt-4">
                <button type="button"
                        class="btn btn-primary"
                        :disabled="preloader"
                        @click.prevent="submit">
                    <app-button-loader v-if="preloader"/>
                    <Download />
                    {{ $t('save_changes') }}
                </button>
            </div>

        </div>
        <div class="col-md-4">
            <div class="template-variables mt-4">
                <p class="title">Template Variables</p>
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



