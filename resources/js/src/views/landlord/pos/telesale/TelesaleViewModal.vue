<script setup>
import { computed } from "vue";
import { useSubmitForm } from "@/composable/submit/useSubmitForm.js";

const props = defineProps({ modalId: String, tableId: String, selectedUrl: String });
const emit = defineEmits(["close"]);

// Default 'yes' — the composable fetches selectedUrl into formData on mount.
const { pageLoader, formData, closeModal } = useSubmitForm(props, emit);

const telesale = computed(() => formData.value);
</script>

<template>
    <app-modal :modal-id="modalId" modal-size="large" :title="$t('telesale_details')" :show-footer="false" scrollable
        @close="closeModal">
        <template #body>
            <app-loader v-if="pageLoader" />

            <div v-else-if="telesale?.id" class="row g-4">
                <div class="col-md-6">
                    <div class="text-muted fs-6">{{ $t('date') }}</div>
                    <div>{{ telesale.date || '-' }}</div>
                </div>

                <div class="col-md-6">
                    <div class="text-muted fs-6">{{ $t('customer_type') }}</div>
                    <div>
                        <span v-if="telesale.customer_type === 'existing'"
                            class="badge-square-light bg-success">{{ $t('existing') }}</span>
                        <span v-else class="badge-square-light bg-primary">{{ $t('new') }}</span>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="text-muted fs-6">{{ $t('customer_name') }}</div>
                    <div>{{ telesale.customer_name || '-' }}</div>
                </div>

                <div class="col-md-6">
                    <div class="text-muted fs-6">{{ $t('customer_number') }}</div>
                    <div>{{ telesale.customer_number || '-' }}</div>
                </div>

                <div class="col-12">
                    <div class="text-muted fs-6">{{ $t('address') }}</div>
                    <div class="text-break">{{ telesale.address || '-' }}</div>
                </div>

                <div class="col-md-6">
                    <div class="text-muted fs-6">{{ $t('customer_demand') }}</div>
                    <div>{{ telesale.demand_product || '-' }}</div>
                </div>

                <div class="col-md-6">
                    <div class="text-muted fs-6">{{ $t('media') }}</div>
                    <div>
                        <span v-if="telesale.media" class="badge-square-light bg-success">{{ telesale.media }}</span>
                        <span v-else>-</span>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="text-muted fs-6">{{ $t('result') }}</div>
                    <div>
                        <span v-if="telesale.result"
                            :class="`badge-square-light bg-${telesale.result.color || 'secondary'}`">
                            {{ telesale.result.name }}
                        </span>
                        <span v-else>-</span>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="text-muted fs-6">{{ $t('salesman_name') }}</div>
                    <div>{{ telesale.salesman || '-' }}</div>
                </div>

                <div class="col-12">
                    <div class="text-muted fs-6">{{ $t('note') }}</div>
                    <div class="text-break">{{ telesale.note || '-' }}</div>
                </div>
            </div>
        </template>
    </app-modal>
</template>

<style scoped lang="scss"></style>
