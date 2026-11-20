<script setup>
import { computed, onMounted, onBeforeUnmount } from "vue"
import { CheckCircle, XmarkCircle } from '@iconoir/vue';
import { Modal } from "bootstrap";
import { raiseStackedModal } from "@/views/global/helpers/stackedModal.js";
defineOptions({ inheritAttrs: false })
import { useI18n } from "vue-i18n";
const { t } = useI18n();
const props = defineProps({
    modalId: {
        type: String,
        required: true
    },
    title: {
        type: String,
        default: 'Title'
    },
    width: {
        type: String
    },
    showFooter: {
        type: Boolean,
        default: true
    },
    preloader: {
        type: Boolean,
        default: false
    },
    saveButtonLabel: {
        type: String,
    },
    saveButtonIcon: {
        type: String,
        default: function () {
            return 'check-circle';
        }
    },
    cancelButtonLabel: {
        type: String
    },
    scrollable: {
        type: Boolean,
        default: false
    },
    verticallyCentered: {
        type: Boolean,
        default: false
    },
    modalSize: {
        type: String,
        default: 'default'
    },
    permission: {
        type: Boolean,
        default: true
    },
    showHeader: {
        type: Boolean,
        default: true
    },
    showTitle: {
        type: Boolean,
        default: true
    },
    showCloseIcon: {
        type: Boolean,
        default: true
    },
    bodyClasses: String,
    headerClasses: String,
    footerClasses: String,
})

const emit = defineEmits(['submit', 'close'])

const checkModalSize = computed(() => {
    if (props.modalSize === 'default') return '';
    else if (props.modalSize === 'small') return 'modal-sm';
    else if (props.modalSize === 'large') return 'modal-lg';
    else if (props.modalSize === 'extra-large') return 'modal-xl';
    else if (props.modalSize === 'fullscreen') return 'modal-fullscreen';
    else return '';
})
const finalSaveButtonLabel = computed(() => {
    return props.saveButtonLabel || t('save');
});

const finalCancelButtonLabel = computed(() => {
    return props.cancelButtonLabel || t('cancel');
});

const submit = () => {
    emit('submit')
}
let modalInstance = null;

const openModal = () => {
    const element = document.getElementById(props.modalId);

    modalInstance = new Modal(element);
    modalInstance.show();

    // Opened from inside another modal (e.g. "Add Customer" from Add Order)
    // it would otherwise render behind it.
    raiseStackedModal(element);
};

const closeModal = () => {
    emit('close');
}
onMounted(() => {
    openModal();

    const modal = document.getElementById(props.modalId);

    modal.addEventListener('hidden.bs.modal', () => {
        // Bootstrap has no concept of stacked modals: hiding this one clears
        // the body scroll lock and can strip the shared backdrop even when a
        // modal underneath is still open (e.g. "Add Customer" opened from
        // inside "Add Order"). Put the lock back so the one left behind stays
        // usable instead of the page scrolling away under it.
        if (document.querySelector('.modal.show')) {
            document.body.classList.add('modal-open');
        }

        emit('close');
    });
});

// Bootstrap only removes the body-level scroll lock ('modal-open' class +
// inline overflow/padding) *after* its own hide() transition finishes. Many
// callers close this modal by flipping a v-if as soon as their action
// succeeds (e.g. right after a save), instead of calling the modal's
// hide() — which tears this component down immediately and never gives
// Bootstrap's transitionend-based cleanup a chance to run. That leaves the
// whole app (not just this page) permanently unscrollable until a hard
// refresh, since this is an SPA and body state never resets on navigation.
// Force the cleanup here as a safety net whenever this modal goes away,
// however it happened — but only if no other modal is genuinely still open.
onBeforeUnmount(() => {
    const el = document.getElementById(props.modalId);

    // Don't call modalInstance.dispose() here: callers close this modal by
    // flipping a v-if right after calling hide(), which unmounts this
    // component (and reaches this line) while hide()'s fade-out transition
    // is still in flight. dispose() nulls out the instance's element
    // reference, and when the transition's already-queued completion
    // callback (_hideModal) then runs, it dereferences that null and throws.
    // Skipping dispose() leaves the (now-detached) element intact for that
    // callback to finish harmlessly; Vue/the GC reclaim it once nothing
    // references it anymore.

    // Exclude this modal's own element from the check — if it was torn down
    // via the bypass path above (hide() never called), it still carries the
    // 'show' class at this point and would otherwise match itself, wrongly
    // looking like "another modal is still open" and skipping the cleanup.
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
    <Teleport to="body">
        <div class="modal fade"
            :id="modalId"
            tabindex="-1"
            :aria-labelledby="`${modalId}ModalLabel`"
            aria-hidden="true">
            <div class="modal-dialog" :class="[
                { 'modal-dialog-scrollable': scrollable },
                { 'modal-dialog-centered': verticallyCentered },
                checkModalSize
            ]">
                <div class="modal-content border-0 rounded-default">
                    <div v-if="showHeader" class="modal-header rounded-top" :class="headerClasses">
                        <h5 class="modal-title" v-if="showTitle" :id="`${modalId}ModalLabel`">{{ title }}</h5>
                        <span class="modal-close"
                            v-if="showCloseIcon"
                            data-bs-dismiss="modal"
                            @click.prevent="closeModal"
                            aria-label="Close">
                            <XmarkCircle />
                        </span>
                    </div>
                    <div class="modal-body" :class="[bodyClasses, { 'overflow-x-hidden': scrollable }]">
                        <slot name="body"></slot>
                    </div>
                    <div v-if="showFooter" class="modal-footer rounded-bottom" :class="footerClasses">
                        <button type="button"
                            class="btn btn-primary-outline me-2"
                            data-bs-dismiss="modal"
                            @click.prevent="closeModal">
                            {{ finalCancelButtonLabel }}
                        </button>
                        <button v-if="permission" type="submit"
                            class="btn btn-primary d-flex align-items-center gap-2"
                            @click.prevent="submit"
                            :disabled="preloader">
                            <app-button-loader v-if="preloader" />
                            <CheckCircle />
                            {{ finalSaveButtonLabel }}
                        </button>
                    </div>
                    <template v-else>
                        <div class="modal-footer rounded-bottom" :class="footerClasses">
                            <slot name="footer"></slot>
                        </div>
                    </template>

                </div>
            </div>
        </div>
    </Teleport>
</template>
