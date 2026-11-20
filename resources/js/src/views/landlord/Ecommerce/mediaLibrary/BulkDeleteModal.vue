<script setup>
import { ref, watch, nextTick, onMounted, onBeforeUnmount } from "vue";
import { Modal } from "bootstrap";
import { Trash } from "@iconoir/vue";

const props = defineProps({
  modalId: { type: String, default: "bulk-delete-modal" },
  selectedIds: { type: Array, default: () => [] },
  show: { type: Boolean, default: false },
  icon: { type: [Object, Function], default: () => Trash },
  modalClass: { type: String, default: "text-danger" },
  title: String,
  subTitle: String,
  message: String,
  firstButtonName: String,
  secondButtonName: String,
});

const emit = defineEmits(["cancelled", "confirmed"]);

const preloader = ref(false);
let modalInstance = null;

const closeModal = () => {
  if (modalInstance) modalInstance.hide();
};

const openModal = () => {
  const el = document.getElementById(props.modalId);
  if (el) {
    modalInstance = new Modal(el);
    modalInstance.show();
  }
};

const cancelled = () => {
  closeModal();
  emit("cancelled");
};

const confirmed = () => {
  if (!props.selectedIds.length) return;
  emit("confirmed");
  closeModal();
};

watch(() => props.show, (val) => {
  if (val) nextTick(openModal);
});

onMounted(() => {
  if (props.show) openModal();

  const el = document.getElementById(props.modalId);
  if (!el) return;

  el.addEventListener("hidden.bs.modal", () => {
    emit("cancelled");
  });
});

// cancelled()/confirmed() above emit their close event synchronously right
// alongside calling hide(), instead of waiting for hide()'s async fade
// transition to finish. If a parent ever wraps this component in a v-if
// (unlike the current single caller, which keeps it always mounted and
// toggles it via the `show` prop), tearing it down on that same tick would
// stop Bootstrap's transitionend-based body cleanup from running,
// permanently locking page scroll app-wide. Force it here as a safety net
// whenever this component goes away, however it happened — but only if no
// other modal is genuinely still open.
onBeforeUnmount(() => {
  const el = document.getElementById(props.modalId);
  modalInstance?.dispose();

  const anotherModalOpen = Array.from(document.querySelectorAll(".modal.show")).some((modalEl) => modalEl !== el);
  if (!anotherModalOpen) {
    document.body.classList.remove("modal-open");
    document.body.style.removeProperty("overflow");
    document.body.style.removeProperty("padding-right");
    document.querySelectorAll(".modal-backdrop").forEach((backdrop) => backdrop.remove());
  }
});
</script>

<template>
  <div
    class="modal fade"
    :id="modalId"
    tabindex="-1"
    :aria-labelledby="`${modalId}-ModalLabel`"
    aria-hidden="true"
  >
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
      <div class="modal-content">
        <div class="modal-body">
          <div class="text-left">
            <component
              :is="icon || Trash"
              :class="[modalClass || 'text-danger', 'custom-delete-icon', 'display-3']"
            />
          </div>
          <br />
          <h4 class="text-left font-weight-bold msg-white">
            {{ title || "Are you sure you want to delete?" }}
          </h4>
          <p v-if="subTitle" class="text-center">{{ subTitle }}</p>
          <p class="text-left">
            {{ message || "This content will be deleted permanently." }}
          </p>

          <div class="border-top my-4"></div>

          <div class="d-flex gap-2 justify-content-end">
            <button
              class="btn btn-primary-danger-outline mx-1"
              data-dismiss="modal"
              @click.prevent="cancelled"
            >
              {{ secondButtonName || "Cancel" }}
            </button>
            <button
              class="btn btn-primary-danger btn-danger d-flex align-items-center gap-2"
              data-dismiss="modal"
              @click.prevent="confirmed"
              :disabled="preloader"
            >
              <Trash />
              {{ firstButtonName || "Delete" }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
