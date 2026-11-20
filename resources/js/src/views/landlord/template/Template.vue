<script setup>
import { onMounted, ref } from "vue";
import Axios from "@/services/axios/index.js";
import { LANDLORD_EMAIL_TEMPLATE_TYPE } from "@/services/endpoints/landlord.js";
import EmailTemplateModal from "@/views/landlord/template/EmailTemplateModal.vue";
import { useOpenModal } from "@/composable/modal/useOpenModal.js";
import { Edit } from "@iconoir/vue";
import usePermission from "@/composable/permission/usePermission.js";

const { canAccess } = usePermission();


const preloader = ref(false);
const templateTypeList = ref([]);
const getTemplateType = () => {
    preloader.value = true;
    Axios.get(LANDLORD_EMAIL_TEMPLATE_TYPE).then(({ data }) => {
        templateTypeList.value = data;
    }).finally(() => preloader.value = false);
}
const { isModalActive, closeModal } = useOpenModal()
const templateData = ref({});
const openEmailTemplate = (template) => {
    isModalActive.value = true
    templateData.value = template
}
onMounted(() => {
    getTemplateType();
})
</script>

<template>
    <div class="content-wrapper">
        <app-breadcrumb :page-title="$t('email_template')" />
        <div class="center-loader d-flex justify-content-center" v-if="preloader">
            <app-loader />
        </div>
        <div class="row g-4" v-else>
            <div class="col-lg-6" v-for="(templateType, index) in Object.keys(templateTypeList)" :key="index">
                <div class="email-template-card">
                    <div class="card-body">
                        <h4 class="title text-capitalize">{{ templateType.replace(/_/g, ' ') }}</h4>
                        <div class="main-content">
                            <div class="table-wrapper">
                                <div class="table-responsive">
                                    <table class="table template-table">
                                        <thead>
                                            <tr>
                                                <th>{{ $t('name') }}</th>
                                                <th class="text-end">{{ $t('action') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="(template, templateIndex) in templateTypeList[templateType]"
                                                :key="templateIndex">
                                                <td>{{ template.display_name }}</td>
                                                <td class="text-end">
                                                    <div class="d-flex justify-content-end gap-2"
                                                        v-if="canAccess('landlord_update_email_template') && canAccess('landlord_show_email_template_types')">
                                                        <div class="template-action-btn"
                                                            @click="openEmailTemplate(template)">
                                                            <Edit />
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <email-template-modal v-if="isModalActive"
            modal-id="email-template-modal"
            :template-data="templateData"
            @close="closeModal" />
    </div>
</template>
