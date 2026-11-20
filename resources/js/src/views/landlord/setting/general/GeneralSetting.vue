<script setup>
import { onMounted, ref } from "vue";
import Attachment from "@/views/global/input/uploader/Attachment.vue";
import Axios from "@/services/axios/index.js";
import { useI18n } from "vue-i18n";
import InputLabel from "@/views/global/input/label/InputLabel.vue";
import { LANDLORD_SETTINGS } from "@/services/endpoints/landlord.js";
import { formDataAssigner } from "@/utils/helper.js";
import { useToast } from "vue-toastification";
import usePermission from "@/composable/permission/usePermission.js";
import { Download } from "@iconoir/vue";

const { canAccess } = usePermission();
const props = defineProps({
    id: {}
});

const { t } = useI18n();
const toast = useToast();

const pageLoader = ref(false)
const formData = ref({});
const errors = ref({});
const preloader = ref(false);

const companyLogo = ref(false);
const companySecondaryLogo = ref(false);
const companyIcon = ref(false);
const companyBanner = ref(false);

const changeCompanyLogo = (file) => {
    companyLogo.value = true;
    formData.value.company_logo = file;
};
const changeCompanyIcon = (file) => {
    companyIcon.value = true;
    formData.value.company_icon = file;
};
const changeCompanyBanner = (file) => {
    companyBanner.value = true;
    formData.value.company_banner = file;
};

const submit = () => {
    preloader.value = true;
    let customData = {
        ...formData.value,
        company_phone: formData.value.company_phone ? formData.value.company_phone : "",
        company_address: formData.value.company_address ? formData.value.company_address : "",
        company_email: formData.value.company_email ? formData.value.company_email : "",
        company_tax_id: formData.value.company_tax_id ? formData.value.company_tax_id : "",
        company_website: formData.value.company_website ? formData.value.company_website : "",
    };
    let data = formDataAssigner(new FormData(), customData);
    Axios.post(LANDLORD_SETTINGS, data, {
        headers: {
            "Content-Type": "multipart/form-data",
        },
    })
        .then((response) => {
            toast.success(response.data.message);
            setTimeout(() => {
                window.location.reload();
            }, 1500);
        })
        .catch(({ response }) => {
            if (response.status === 422) {
                errors.value = response.data.errors;
            } else if (response.status === 413) {
                toast.error('File is too large');
            } else {
                errors.value = {};
                toast.error(response.data.message);
            }
        }).finally(() => (preloader.value = false));
};


const decimalSeparatorList = [
    {
        id: '.',
        name: t('dot')
    },
    {
        id: ',',
        name: t('comma')
    }
];

const changeValue = (type) => {
    if (type === 'thousand_separator') {
        if (formData.value.thousand_separator === ',') {
            formData.value.decimal_separator = '.'
        } else if (formData.value.thousand_separator === '.') {
            formData.value.decimal_separator = ','
        }
    } else {
        formData.value.thousand_separator = formData.value.decimal_separator === ',' ? '.' : ','
    }
}

const dateFormatList = [
    {
        id: 'd-m-Y',
        name: t('dd-mm-yyyy')
    },
    {
        id: 'm-d-Y',
        name: t('mm-dd-yyyy')
    },
    {
        id: 'Y-m-d',
        name: t('yyyy-mm-dd')
    }
];

const thousandSeparatorList = [
    {
        id: '.',
        name: t('dot')
    },
    {
        id: ',',
        name: t('comma')
    },
    {
        id: 'space',
        name: t('space')
    },
]

const currencyPositionList = [
    {
        id: 'prefix_only',
        name: t('prefix_only')
    },
    {
        id: 'prefix_with_space',
        name: t('prefix_with_space')
    },
    {
        id: 'suffix_only',
        name: t('suffix_only')
    },
    {
        id: 'suffix_with_space',
        name: t('suffix_with_space')
    }
];


const getServerGeneralSetting = async () => {
    pageLoader.value = true;
    try {
        const { data } = await Axios.get(LANDLORD_SETTINGS);
        formData.value = data;
    } catch (error) {
    } finally {
        pageLoader.value = false
    }
}
onMounted(() => {
    getServerGeneralSetting();
});
</script>

<template>
    <div class="content-wrapper">
        <app-breadcrumb :page-title="$t('general_setting')" />
    <div class="form-center-loader d-flex justify-content-center" v-if="pageLoader">
        <app-loader />
    </div>
    <form v-else>
        <div class="mb-4">
            <app-input type="text" id="companyName" :label="$t('company_name')" label-required
                :placeholder="$t('company_name')" v-model="formData.company_name"
                :errors="$errors(errors, 'company_name')" />
        </div>

        <div class="mb-4">
            <app-input type="text" id="address" :label="$t('address')" :placeholder="$t('address')"
                v-model="formData.company_address" :errors="$errors(errors, 'company_address')" />
        </div>
        <div class="mb-4">
            <app-input type="tel" id="phone_number" :label="$t('phone_number')" :placeholder="$t('phone_number')"
                v-model="formData.company_phone" :errors="$errors(errors, 'company_phone')" />
        </div>
        <div class="mb-4">
            <app-input type="email" id="email_address" :label="$t('email_address')" :placeholder="$t('email_address')"
                v-model="formData.company_email" :errors="$errors(errors, 'company_email')" />
        </div>
        <div class="mb-4">
            <app-input type="text" id="company_website" :label="$t('website')" :placeholder="$t('website')"
                v-model="formData.company_website" :errors="$errors(errors, 'company_website')" />
        </div>

        <div class="mb-4">
            <app-input type="text" id="currency_symbol" :label="$t('currency_and_symbol')" label-required
                v-model="formData.currency_symbol" :placeholder="$t('currency_and_symbol')"
                :errors="$errors(errors, 'currency_symbol')" />
            <app-note class="mt-2" :message="$t('pdf_will_not_support_all_currency_symbols')" />
        </div>

        <div class="mb-4">
            <app-input type="select" id="date_format" :label="$t('date_format')" label-required list-value-field="name"
                v-model="formData.date_format" :options="dateFormatList" :selected-disable="false"
                :choose-label="$t('choose_a_date_format')" :errors="$errors(errors, 'date_format')" />
        </div>

        <div class="mb-4">
            <input-label label-id="decimal_separator" :label="$t('decimal_separator')" :required="true" />
            <div>
                <app-input type="radio" id="decimal_separator" radio-checkbox-name="decimal_separator"
                    @changeRadio="changeValue('decimal_separator')" list-value-field="name"
                    v-model="formData.decimal_separator" :options="decimalSeparatorList" />
            </div>
        </div>

        <div class="mb-4">
            <input-label label-id="thousand_separator" :label="$t('thousand_separator')" :required="true" />
            <div>
                <app-input type="radio" id="thousand_separator" radio-checkbox-name="thousand_separator"
                    @changeRadio="changeValue('thousand_separator')" list-value-field="name"
                    v-model="formData.thousand_separator" :options="thousandSeparatorList" />
            </div>
        </div>

        <div class="mb-4">
            <input-label label-id="currency_position" :label="$t('currency_position')" :required="true" />
            <div>
                <app-input type="radio" id="currency_position" radio-checkbox-name="currency_position"
                    list-value-field="name" v-model="formData.currency_position" :options="currencyPositionList" />
            </div>
        </div>

        <div class="mb-4">
            <Attachment :label="$t('change_logo')" label-required v-model="formData.company_logo"
                @changeFile="changeCompanyLogo($event)" :error="$errors(errors, 'company_logo')"
                :recommended-size="$t('logo_recommended_size')" :recommended-file-size="$t('setting_file_size')" />
        </div>


        <div class="mb-4">
            <Attachment :label="$t('change_icon')" label-required v-model="formData.company_icon"
                @changeFile="changeCompanyIcon($event)" :error="$errors(errors, 'company_icon')"
                :recommended-size="$t('icon_recommended_size')" :recommended-file-size="$t('setting_file_size')" />
        </div>
        <div class="mb-4">
            <Attachment :label="$t('change_banner')" label-required v-model="formData.company_banner"
                @changeFile="changeCompanyBanner($event)" :error="$errors(errors, 'company_banner')"
                :recommended-size="$t('banner_recommended_size')" :recommended-file-size="$t('setting_file_size')" />
        </div>

        <button type="button" class="btn btn-primary shadow mt-3" :disabled="preloader" @click.prevent="submit">
            <app-button-loader v-if="preloader" />
            <Download />
            {{ $t('update') }}
        </button>
    </form>
    </div>
</template>

<style scoped lang="scss"></style>
