<script setup>
import { computed } from "vue";
import { useSubmitForm } from "@/composable/submit/useSubmitForm.js";

const props = defineProps({ modalId: String, tableId: String, selectedUrl: String });
const emit = defineEmits(["close"]);

// Default 'yes' — the composable fetches selectedUrl into formData on mount.
const { pageLoader, formData, closeModal } = useSubmitForm(props, emit);

const order = computed(() => formData.value);

const money = (value) => Number(value || 0).toLocaleString('en-US', { minimumFractionDigits: 2 });
</script>

<template>
    <app-modal :modal-id="modalId" modal-size="large" :title="$t('order_details')" :show-footer="false" scrollable
        @close="closeModal">
        <template #body>
            <app-loader v-if="pageLoader" />

            <div v-else-if="order?.id" class="row g-4">
                <div class="col-md-6">
                    <div class="text-muted fs-6">{{ $t('date') }}</div>
                    <div>{{ order.date || '-' }}</div>
                </div>

                <div class="col-md-6">
                    <div class="text-muted fs-6">{{ $t('confirmation_date') }}</div>
                    <div>
                        <span v-if="order.confirmation_date">{{ order.confirmation_date }}</span>
                        <span v-else class="badge-square-light bg-warning">{{ $t('not_confirmed') }}</span>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="text-muted fs-6">{{ $t('name') }}</div>
                    <div>{{ order.customer_name || '-' }}</div>
                </div>

                <div class="col-md-6">
                    <div class="text-muted fs-6">{{ $t('mobile') }}</div>
                    <div>{{ order.mobile || '-' }}</div>
                </div>

                <div class="col-12">
                    <div class="text-muted fs-6">{{ $t('address') }}</div>
                    <div class="text-break">{{ order.address || '-' }}</div>
                </div>

                <div class="col-md-6">
                    <div class="text-muted fs-6">{{ $t('source_media') }}</div>
                    <div>
                        <span v-if="order.source_media" class="badge-square-light bg-info">{{ order.source_media }}</span>
                        <span v-else>-</span>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="text-muted fs-6">{{ $t('online_offline') }}</div>
                    <div>
                        <span :class="`badge-square-light bg-${order.order_type === 'offline' ? 'danger' : 'success'}`">
                            {{ order.order_type === 'offline' ? $t('offline') : $t('online') }}
                        </span>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="text-muted fs-6">{{ $t('order_person') }}</div>
                    <div>{{ order.order_person || '-' }}</div>
                </div>

                <div class="col-12">
                    <div class="text-muted fs-6 mb-1">{{ $t('products') }}</div>
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead>
                                <tr>
                                    <th>{{ $t('product') }}</th>
                                    <th class="text-end">{{ $t('quantity') }}</th>
                                    <th class="text-end">{{ $t('unit_price') }}</th>
                                    <th class="text-end">{{ $t('total') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="item in order.items" :key="item.id">
                                    <td>{{ item.product_name || '-' }}</td>
                                    <td class="text-end">{{ item.quantity }}</td>
                                    <td class="text-end">{{ money(item.unit_price) }}</td>
                                    <td class="text-end">{{ money(item.line_total) }}</td>
                                </tr>
                                <tr v-if="!order.items || order.items.length === 0">
                                    <td colspan="4">-</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="text-muted fs-6">{{ $t('advance_media') }}</div>
                    <div>
                        <span v-if="order.advance_media"
                            :class="`badge-square-light bg-${order.advance_media.color || 'secondary'}`">
                            {{ order.advance_media.name }}
                        </span>
                        <span v-else>-</span>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="text-muted fs-6">{{ $t('advance_amount') }}</div>
                    <div>{{ money(order.advance_amount) }}</div>
                </div>

                <div class="col-md-4">
                    <div class="text-muted fs-6">{{ $t('selling_price') }}</div>
                    <div>{{ money(order.selling_price) }}</div>
                </div>

                <div class="col-md-4">
                    <div class="text-muted fs-6">{{ $t('cod') }}</div>
                    <div>{{ money(order.cod) }}</div>
                </div>

                <div class="col-12">
                    <div class="text-muted fs-6">{{ $t('note') }}</div>
                    <div class="text-break">{{ order.note || '-' }}</div>
                </div>

                <div class="col-12">
                    <hr class="my-0" />
                </div>

                <div class="col-md-4">
                    <div class="text-muted fs-6">{{ $t('delivery_status') }}</div>
                    <div>
                        <span v-if="order.delivery_status"
                            :class="`badge-square-light bg-${order.delivery_status.color || 'secondary'}`">
                            {{ order.delivery_status.name }}
                        </span>
                        <span v-else>-</span>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="text-muted fs-6">{{ $t('invoice_no') }}</div>
                    <div>{{ order.invoice_no || '-' }}</div>
                </div>

                <div class="col-md-4">
                    <div class="text-muted fs-6">{{ $t('delivery_by') }}</div>
                    <div>
                        <span v-if="order.delivery_by"
                            :class="`badge-square-light bg-${order.delivery_by.color || 'secondary'}`">
                            {{ order.delivery_by.name }}
                        </span>
                        <span v-else>-</span>
                    </div>
                </div>

                <div class="col-12">
                    <div class="text-muted fs-6">{{ $t('track') }}</div>
                    <div class="text-break">
                        <a v-if="order.track_url" :href="order.track_url" target="_blank" rel="noopener noreferrer">
                            {{ order.track_url }}
                        </a>
                        <span v-else>-</span>
                    </div>
                </div>

                <div class="col-12">
                    <div class="text-muted fs-6">{{ $t('delivery_note') }}</div>
                    <div class="text-break">{{ order.delivery_note || '-' }}</div>
                </div>
            </div>
        </template>
    </app-modal>
</template>

<style scoped lang="scss"></style>
