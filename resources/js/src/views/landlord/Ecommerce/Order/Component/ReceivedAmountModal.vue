<script setup>
import { ref, onMounted, onBeforeUnmount, nextTick } from "vue";
import { Modal } from "bootstrap";
import Axios from "@/services/axios/index.js";
import { ORDERS } from "@/services/endpoints/Ecommerce.js";
import { useToast } from "vue-toastification";

const props = defineProps({
    modalId: { type: String, default: 'received-amount-modal' },
    order: { type: Object, required: true },
    tableId: { type: String },
});

const emit = defineEmits(['close', 'submitted']);
const toast = useToast();

const receivedAmount = ref('');
const errors = ref({});
const preloader = ref(false);

const payableAmount = props.order.grand_total ?? 0;
const currentDue = props.order.due_amount ?? 0;
const alreadyReceived = payableAmount - currentDue;

const dueAmount = () => {
    const grandTotal = Number(props.order.grand_total ?? 0);
    const receivedAmount = Number(props.order.payable_amount ?? 0);

    return (grandTotal - receivedAmount).toFixed(2);
}


const clampValue = async () => {
    const val = parseFloat(receivedAmount.value);
    if (!isNaN(val) && val > payableAmount) {
        receivedAmount.value = '';
        await nextTick();
        receivedAmount.value = payableAmount;
    }
};

const openModal = () => {
    receivedAmount.value = alreadyReceived > 0 ? alreadyReceived : '';
    new Modal(document.getElementById(props.modalId)).show();
};

const closeModal = () => {
    const modal = Modal.getInstance(document.getElementById(props.modalId));
    if (modal) modal.hide();
    emit('close');
};

const submit = async () => {
    errors.value = {};
    const val = parseFloat(receivedAmount.value);

    if (!receivedAmount.value && receivedAmount.value !== 0) {
        errors.value.received_amount = 'Received amount is required.';
        return;
    }
    if (isNaN(val) || val < 0) {
        errors.value.received_amount = 'Please enter a valid amount.';
        return;
    }
    if (val > payableAmount) {
        errors.value.received_amount = `Amount cannot exceed payable amount (${payableAmount}).`;
        return;
    }

    try {
        preloader.value = true;
        const { data } = await Axios.patch(`${ORDERS}/${props.order.id}/receive-amount`, {
            received_amount: val,
        });
        toast.success(data.message);
        emit('submitted');
        closeModal();
    } catch (error) {
        if (error.response?.status === 422) {
            const err = error.response.data.errors;
            errors.value.received_amount = err?.received_amount?.[0] ?? '';
        } else {
            toast.error(error.response?.data?.message || 'Something went wrong');
        }
    } finally {
        preloader.value = false;
    }
};

onMounted(() => {
    openModal();
    document.getElementById(props.modalId)
        ?.addEventListener('hidden.bs.modal', () => emit('close'));
});

// closeModal() above emits 'close' synchronously right alongside calling
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
    <div class="modal fade" :id="modalId" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Receive Amount</h5>
                    <button type="button" class="btn-close" @click="closeModal" />
                </div>
                <div class="modal-body">
                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <div class="p-3 rounded bg-light text-center">
                                <small class="text-muted d-block">Payable Amount</small>
                                <strong>{{ payableAmount }}</strong>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 rounded bg-light text-center">
                                <small class="text-muted d-block">Due Amount</small>
                                <strong class="text-danger">{{ dueAmount() }}</strong>
                            </div>
                        </div>
                    </div>

                    <div class="mb-1">
                        <label class="form-label fw-semibold">
                            Received Amount <span class="text-danger">*</span>
                        </label>
                        <input type="number" class="form-control" :class="{ 'is-invalid': errors.received_amount }"
                            placeholder="Enter received amount" v-model="receivedAmount" :min="0" :max="payableAmount"
                            @input="clampValue" />
                        <div v-if="errors.received_amount" class="invalid-feedback">
                            {{ errors.received_amount }}
                        </div>
                        <small class="text-muted">
                            Max: {{ payableAmount }} &nbsp;|&nbsp;
                            Due after save: <strong class="text-danger">
                                {{ Math.max(0, payableAmount - (parseFloat(receivedAmount) || 0)).toFixed(2) }}
                            </strong>
                        </small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" @click="closeModal">Cancel</button>
                    <button type="button" class="btn btn-primary" @click="submit" :disabled="preloader">
                        <span v-if="preloader" class="spinner-border spinner-border-sm me-1" />
                        Save
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
