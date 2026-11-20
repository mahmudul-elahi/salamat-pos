<script setup>
import { ref, watch } from "vue";
import { useSubmitForm } from "@/composable/submit/useSubmitForm.js";
import { ELITES } from "@/services/endpoints/landlord.js";
import Attachment from "@/views/global/input/uploader/Attachment.vue";
import MediaModal from "@/views/landlord/pos/mediaModal/MediaModal.vue";
import { Trash } from "@iconoir/vue";

const props = defineProps({
    modalId: String,
    tableId: String,
    selectedUrl: String,
});

const emit = defineEmits(["close"]);

const mediaModalActive = ref(false);
const currentImageType = ref(null);

const { preloader, pageLoader, formData, errors, save, closeModal } = useSubmitForm(props, emit);

formData.value = {
    status_name: '',
    tagline: '',
    logo: null,
    banner: null,
    point: null,
    benefits: [''],
};

watch(() => formData.value, (val) => {
    if (val && !val.benefits?.length) {
        formData.value.benefits = [''];
    }
}, { deep: true });

const addBenefit = () => formData.value.benefits.push('');
const removeBenefit = (i) => formData.value.benefits.splice(i, 1);

const openMediaModal = (type) => {
    currentImageType.value = type;
    mediaModalActive.value = true;
};

const closeMediaModal = () => {
    mediaModalActive.value = false;
    document.body.classList.remove('modal-open');
    document.body.style.overflow = 'visible';
    document.body.style.paddingRight = '0px';
};

const handleMediaSelected = (url) => {
    if (currentImageType.value) formData.value[currentImageType.value] = url;
    closeMediaModal();
};

const submit = () => {
    const payload = {
        ...formData.value,
        benefits: (formData.value.benefits || []).filter(b => b.trim() !== ''),
    };
    save(props.selectedUrl ?? ELITES, payload);
};
</script>

<template>
    <app-modal :modal-id="modalId" modal-size="large" :title="selectedUrl ? $t('update_elite') : $t('add_elite')"
        :preloader="preloader" @submit="submit" @close="closeModal">
        <template #body>
            <app-loader v-if="pageLoader" />
            <form v-else>
                <div class="row g-4">

                    <div class="col-md-6">
                        <app-input id="status_name" type="text" :label="$t('status_name')" label-required
                            v-model="formData.status_name" :placeholder="$t('status_name')"
                            :errors="$errors(errors, 'status_name')" />
                    </div>

                    <div class="col-md-6">
                        <app-input id="tagline" type="text" :label="$t('tagline')" v-model="formData.tagline"
                            :placeholder="$t('tagline')" :errors="$errors(errors, 'tagline')" />
                    </div>

                    <div class="col-md-3">
                        <Attachment :label="$t('logo')" :value="formData.logo" :showInput="false"
                            :error="$errors(errors, 'logo')" @click="openMediaModal('logo')" />
                    </div>

                    <div class="col-md-3">
                        <Attachment :label="$t('banner')" :value="formData.banner" :showInput="false"
                            :error="$errors(errors, 'banner')" @click="openMediaModal('banner')" />
                    </div>

                    <div class="col-md-6">
                        <app-input id="point" type="number" :label="$t('point')" v-model="formData.point"
                            :placeholder="$t('point')" :errors="$errors(errors, 'point')" />
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-bold small text-dark mb-3">{{ $t('add_benefits') }}</label>
                        <div v-for="(_, i) in formData.benefits" :key="i" class="d-flex gap-3 align-items-end mb-3">
                            <div class="flex-grow-1">
                                <app-input :id="`benefit_${i}`" type="text" v-model="formData.benefits[i]"
                                    :placeholder="$t('write_a_benefit')" />
                            </div>
                            <button v-if="formData.benefits.length > 1" type="button"
                                class="btn btn-danger btn-sm px-2 py-1 mb-2 rounded-3" @click="removeBenefit(i)">
                                <Trash width="16" height="16" />
                            </button>
                        </div>
                        <button type="button" class="btn text-white px-4 py-2 mt-1 rounded-4 shadow-none border-0"
                            style="background-color: #707070" @click="addBenefit">
                            <i class="bi bi-plus me-1"></i> {{ $t('add_another_benefit') }}
                        </button>
                    </div>

                </div>
            </form>
        </template>
    </app-modal>

    <media-modal v-if="mediaModalActive" modal-id="elite-media-modal" @selected="handleMediaSelected"
        @close="closeMediaModal" />
</template>

<style scoped lang="scss"></style>
