<script setup>
import { ref, nextTick, onMounted, onBeforeUnmount } from "vue";
import { Modal } from "bootstrap";
import { useToast } from "vue-toastification";
import useEmitter from "@/composable/emitter/useEmitter.js";
import Axios from "@/services/axios/index.js";
import { Trash } from "@iconoir/vue";
import { raiseStackedModal } from "@/views/global/helpers/stackedModal.js";

// Props
const props = defineProps({
    modalId: { type: String, default: 'app-delete-modal' },
    title: String,
    icon: { type: [Object, Function], default: () => Trash },
    subTitle: String,
    message: String,
    modalClass: String,
    firstButtonName: String,
    secondButtonName: String,
    showFooter: { type: Boolean, default: true },
    selectedUrl: String,
    // Bulk-delete mode: POSTs bulkPayload to bulkUrl once instead of issuing
    // a DELETE to selectedUrl. Takes priority over selectedUrl when set.
    bulkUrl: String,
    bulkPayload: Object,
    tableId: String,
    callBackEmit: { type: Boolean, default: false },
});

const emit = defineEmits(['cancelled', 'confirmed', 'confirmSuccess']);
const preloader = ref(false);
const toast = useToast();
const emitter = useEmitter();

const cancelled = () => {
    closeModal();
    emit('cancelled', false);
};

const confirmed = async () => {
    preloader.value = true;
    try {
        const response = props.bulkUrl
            ? await Axios.post(props.bulkUrl, props.bulkPayload)
            : await Axios.delete(props.selectedUrl);

        closeModal();
        toast.success(response.data.message);

        emitter.emit('reload-' + props.tableId);
        if (props.callBackEmit) {
            emit('confirmSuccess');
        }
    } catch (error) {
        toast.error(error.response?.data?.message || "Delete failed");
    } finally {
        preloader.value = false;
    }
};

let modalInstance = null;

const openModal = () => {
    const el = document.getElementById(props.modalId);
    if (el) {
        modalInstance = new Modal(el);
        modalInstance.show();

        // Opened from inside another modal (e.g. confirming a line removal
        // from Update Order Item) it would otherwise render behind it.
        raiseStackedModal(el);
    }
};

const closeModal = () => {
    if (modalInstance) {
        modalInstance.hide();
    }
};

// Properly wait for DOM before showing modal
onMounted(async () => {
    await nextTick();
    openModal();

    const modalElement = document.getElementById(props.modalId);
    if (!modalElement) return;

    modalElement.addEventListener('hidden.bs.modal', () => {
        // Bootstrap has no concept of stacked modals: hiding this one clears
        // the body scroll lock even when the modal that opened it is still
        // open (e.g. confirming a line removal from inside Update Order Item).
        // Put it back so the one left behind stays usable.
        if (document.querySelector('.modal.show')) {
            document.body.classList.add('modal-open');
        }

        emit('cancelled');
    });
});

// The Cancel/Confirm handlers above emit their close event synchronously
// right alongside calling hide(), instead of waiting for hide()'s async
// fade transition to finish. If the parent's v-if tears this component down
// on that same tick, Bootstrap's transitionend-based body cleanup never
// gets to run, permanently locking page scroll app-wide. Force it here as
// a safety net whenever this modal goes away, however it happened — but
// only if no other modal is genuinely still open.
onBeforeUnmount(() => {
    const el = document.getElementById(props.modalId);
    modalInstance?.dispose();

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
    <!-- Teleported for the same reason app-modal is: rendered in place, a
         fixed-position modal is trapped inside whatever stacking context its
         ancestors create, so it can end up *below* a modal that opened it no
         matter what z-index it carries. -->
    <Teleport to="body">
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
                                :is="icon || Trash"
                                :class="[modalClass || 'text-danger', 'custom-delete-icon', 'display-3']" />
                        </div>
                        <br>
                        <h4 class="text-left font-weight-bold msg-white">
                            {{ title ? title : $t('are_you_sure_you_want_to_delete') }}</h4>
                        <p v-if="subTitle" class="text-center">{{ subTitle }}</p>
                        <p class="text-left">
                            {{ message ? message : $t('this_content_will_be_deleted_permanently') }}
                        </p>

                        <div class="border-top my-4"></div>

                        <div class="d-flex gap-2 justify-content-end" v-if="showFooter">
                            <button class="btn btn-primary-danger-outline mx-1"
                                data-dismiss="modal" @click.prevent="cancelled">
                                {{ secondButtonName ? secondButtonName : $t('cancel') }}
                            </button>
                            <button class="btn btn-primary-danger btn-danger d-flex align-items-center gap-2"
                                data-dismiss="modal" @click.prevent="confirmed">
                                <app-button-loader v-if="preloader" />
                                <Trash />
                                {{ firstButtonName ? firstButtonName : $t('delete') }}
                            </button>
                        </div>

                        <div v-else class="text-left">
                            <slot name="footer"></slot>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<style scoped lang="scss"></style>
