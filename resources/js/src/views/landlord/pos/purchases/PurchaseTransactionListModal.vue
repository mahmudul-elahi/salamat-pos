<template>
    <app-modal :modal-id="modalId" modal-size="extra-large" title="Transaction History" :preloader="preloader"
        @submit="submit" @close="closeModal" :show-footer="false">

        <template v-slot:body>
            <app-loader v-if="pageLoader" />
            <div class="table-responsive" v-else>
                <table class="table purchase-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Invoice Number</th>
                            <th>Date</th>
                            <th>Amount</th>
                            <th>Method</th>
                            <th>Note</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>

                        <tr v-for="(txn, idx) in transactions" :key="txn.id">
                            <td>{{ idx + 1 }}</td>
                            <td>{{ txn.full_invoice_number }}</td>
                            <td>{{ txn.date }}</td>
                            <td>{{ txn.amount }}</td>
                            <td>{{ txn.payment_method ? txn.payment_method.name : '' }}</td>
                            <td>{{ txn.note }}</td>
                            <td>
                                <button class="btn btn-sm btn-primary"
                                    @click="() => window.open(txn.invoice_url, '_blank')">
                                    Delete
                                </button>
                            </td>
                        </tr>
                        <tr v-if="!transactions.length">
                            <td colspan="7" class="text-center py-2">No transactions found.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </template>
    </app-modal>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import Axios from "@/services/axios/index.js";
import { useSubmitForm } from "@/composable/submit/useSubmitForm.js";


const props = defineProps({

    modalId: {
        type: String,
        default: 'purchase-transaction-list-modal'
    },
    purchaseId: {
        type: [String, Number],
        required: true
    },
})
const emit = defineEmits(['close']);

const {
    pageLoader,
    closeModal,
} = useSubmitForm(props, emit, 'no');

const transactions = ref([])

const fetchTransactions = async () => {
    pageLoader.value = true
    try {
        // Replace with your actual API endpoint
        const res = await Axios.get(`/admin/pos/purchases/${props.purchaseId}/transactions`)
        transactions.value = res?.data || []
    } catch (e) {
        transactions.value = []
    } finally {
        pageLoader.value = false
    }
}

onMounted(() => {
    fetchTransactions()
})
</script>
