<script setup>
import { onMounted, onBeforeUnmount } from "vue";
import { Modal } from "bootstrap";
import { CheckCircle } from "@iconoir/vue";

const props = defineProps({
    modalId: {
        type: String,
        default: 'app-confirmation-modal'
    },
    title: String,
    icon: {
        type: [Object, Function],
        default: () => CheckCircle,
    },
    subTitle: String,
    message: String,
    modalClass: String,
    firstButtonName: String,
    firstButtonClass: String,
    secondButtonName: String,
    secondButtonClass: String,
    preloader: {
        type: Boolean,
        default: false
    }
});

const emit = defineEmits(['cancel', 'confirm']);
const cancelled = () => {
    closeModal()
    emit('cancel', false);
}

const confirmed = () => {
    emit('confirm');
}

const openModal = () => {
    let modalInstance = new Modal(document.getElementById(props.modalId));
    modalInstance.show();
}
const closeModal = () => {
    let modal = Modal.getInstance(document.getElementById(props.modalId));
    modal.hide();
}

onMounted(() => {
    openModal();

    const modal = document.getElementById(props.modalId);

    modal.addEventListener('hidden.bs.modal', () => {
        emit('cancel');
    });
});

// cancelled() above emits 'cancel' synchronously right alongside calling
// hide(), instead of waiting for hide()'s async fade transition to finish.
// If the parent's v-if tears this component down on that same tick,
// Bootstrap's transitionend-based body cleanup never runs, permanently
// locking page scroll app-wide. Force it here as a safety net whenever this
// modal goes away, however it happened — but only if no other modal is
// genuinely still open.
onBeforeUnmount(() => {
    const el = document.getElementById(props.modalId);
    Modal.getInstance(el)?.dispose();

    const anotherModalOpen = Array.from(document.querySelectorAll('.modal.show')).some((modalEl) => modalEl !== el);
    if (!anotherModalOpen) {
        document.body.classList.remove('modal-open');
        document.body.style.removeProperty('overflow');
        document.body.style.removeProperty('padding-right');
        document.querySelectorAll('.modal-backdrop').forEach((backdrop) => backdrop.remove());
    }
});

</script>
<template>
    <div class="modal fade"
        :id="modalId"
        tabindex="-1"
        :aria-labelledby="`${modalId}-ModalLabel`"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-body">
                    <div class="text-left">
                        <component
                            :is="icon || CheckCircle"
                            :class="[modalClass || 'text-brand', 'custom-delete-icon', 'display-3']" />

                    </div>
                    <br>
                    <h4 class="text-left font-weight-bold msg-white">
                        {{ title ? title : $t('are_you_sure') }}</h4>
                    <p v-if="subTitle" class="text-center">{{ subTitle }}</p>
                    <p class="text-left" v-html="message"></p>
                    <div class="border-top my-4"></div>

                    <div class="text-end">
                        <button type="button" class="d-inline-flex align-items-center mx-1 me-2"
                            :class="[secondButtonClass ? secondButtonClass : 'btn btn-primary-outline']"
                            data-dismiss="modal" @click.prevent="cancelled">
                            {{ secondButtonName ? secondButtonName : $t('no') }}
                        </button>
                        <button type="button"
                            class="d-inline-flex align-items-center"
                            :disabled="preloader"
                            :class="[firstButtonClass ? firstButtonClass : 'btn btn-primary']"
                            data-dismiss="modal"
                            @click.prevent="confirmed">
                            <app-button-loader v-if="preloader" />
                            <CheckCircle class="me-2" />
                            {{ firstButtonName ? firstButtonName : $t('yes') }}
                        </button>

                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped lang="scss"></style>
