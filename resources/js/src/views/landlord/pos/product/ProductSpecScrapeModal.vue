<script setup>
import { ref, computed } from 'vue';
import { Trash } from "@iconoir/vue";
import Axios from "@/services/axios/index.js";
import { useToast } from "vue-toastification";
import { PRODUCTS_SCRAPE_SPECS, PRODUCTS_SAVE_SPECS } from "@/services/endpoints/landlord.js";

const props = defineProps({
    modalId: String,
    product: Object,
    tableId: String,
});

const emit = defineEmits(['close']);

const toast = useToast();
const url = ref('');
const scraping = ref(false);
const saving = ref(false);
const specs = ref([]);
const urlError = ref('');

const scrape = async () => {
    urlError.value = '';
    if (!url.value.trim()) {
        urlError.value = 'URL is required';
        return;
    }
    scraping.value = true;
    specs.value = [];
    try {
        const res = await Axios.post(PRODUCTS_SCRAPE_SPECS, { url: url.value });
        specs.value = res.data.specs.map((s, i) => ({ ...s, _key: i }));
        if (specs.value.length === 0) {
            toast.warning('No specifications found on this page.');
        }
    } catch (e) {
        toast.error(e.response?.data?.message ?? 'Failed to scrape the page.');
    } finally {
        scraping.value = false;
    }
};

const removeSpec = (key) => {
    specs.value = specs.value.filter(s => s._key !== key);
};

const save = async () => {
    if (specs.value.length === 0) {
        toast.warning('No specifications to save.');
        return;
    }
    saving.value = true;
    try {
        await Axios.post(`${PRODUCTS_SAVE_SPECS}/${props.product.id}/save-specs`, {
            specs: specs.value.map(({ group, sub, value }) => ({ group, sub, value })),
        });
        toast.success('Specifications saved successfully.');
        emit('close');
    } catch (e) {
        toast.error(e.response?.data?.message ?? 'Failed to save specifications.');
    } finally {
        saving.value = false;
    }
};

const groupedSpecs = computed(() => {
    const groups = {};
    specs.value.forEach(s => {
        const g = s.group || 'General';
        if (!groups[g]) groups[g] = [];
        groups[g].push(s);
    });
    return groups;
});
</script>

<template>
    <app-modal :modal-id="modalId" modal-size="extra-large" :preloader="scraping || saving"
        :title="`Scrape Specifications`" @close="emit('close')" :footer="false">
        <template v-slot:body>

            <!-- Product name banner -->
            <div class="mb-4 px-3 py-2 rounded-2 d-flex align-items-center gap-2"
                style="background:#f8f9fa; border:1px solid #e9ecef;">
                <span style="font-size:0.75rem; color:#888; white-space:nowrap;">Product:</span>
                <span style="font-weight:600; font-size:0.9rem; color:#222;">{{ product?.product_name }}</span>
            </div>

            <!-- URL Input -->
            <div class="mb-4">
                <label class="form-label fw-semibold" style="font-size:0.85rem;">Product Page URL</label>
                <div class="d-flex gap-2">
                    <input v-model="url" type="url" class="form-control"
                        placeholder="https://example.com/product-page"
                        @keydown.enter="scrape" />
                    <button class="btn btn-primary text-nowrap px-4" @click="scrape" :disabled="scraping">
                        <span v-if="scraping" class="spinner-border spinner-border-sm me-1"></span>
                        {{ scraping ? 'Scraping...' : 'Scrape' }}
                    </button>
                </div>
                <div v-if="urlError" class="text-danger mt-1" style="font-size:0.8rem;">{{ urlError }}</div>
            </div>

            <!-- Results -->
            <template v-if="specs.length > 0">

                <!-- Summary bar -->
                <div class="d-flex justify-content-between align-items-center mb-3 px-1">
                    <div>
                        <span class="badge rounded-pill"
                            style="background:#e8f5e9; color:#2e7d32; font-size:0.8rem; font-weight:600; padding:5px 12px;">
                            {{ specs.length }} specifications
                        </span>
                    </div>
                    <button class="btn btn-success btn-sm px-4" @click="save" :disabled="saving">
                        <span v-if="saving" class="spinner-border spinner-border-sm me-1"></span>
                        {{ saving ? 'Saving...' : 'Save to Database' }}
                    </button>
                </div>

                <!-- Grouped spec list -->
                <div v-for="(items, group) in groupedSpecs" :key="group" class="mb-3">

                    <!-- Group header -->
                    <div class="d-flex align-items-center gap-2 mb-1 px-3 py-2 rounded-2"
                        style="background:#eef2ff; border-left:3px solid #4263eb;">
                        <span style="font-weight:700; font-size:0.82rem; color:#3451c7; letter-spacing:0.3px; text-transform:uppercase;">
                            {{ group }}
                        </span>
                        <span style="font-size:0.75rem; color:#7c8db0; margin-left:4px;">
                            ({{ items.length }})
                        </span>
                    </div>

                    <!-- Spec rows -->
                    <div v-for="spec in items" :key="spec._key"
                        class="d-flex align-items-center justify-content-between px-3 py-2"
                        style="border-bottom:1px solid #f2f4f8; transition:background 0.15s;"
                        @mouseenter="$event.currentTarget.style.background='#fafbff'"
                        @mouseleave="$event.currentTarget.style.background='transparent'">

                        <div class="d-flex gap-3 flex-grow-1 align-items-start">
                            <div style="min-width:180px; font-size:0.83rem; font-weight:600; color:#495057; flex-shrink:0;">
                                {{ spec.sub || '—' }}
                            </div>
                            <div style="font-size:0.83rem; color:#333; flex:1; line-height:1.5;">
                                {{ spec.value }}
                            </div>
                        </div>

                        <!-- Premium delete button -->
                        <button
                            @click="removeSpec(spec._key)"
                            title="Remove"
                            class="d-flex align-items-center justify-content-center ms-3 flex-shrink-0"
                            style="
                                width:28px; height:28px; border-radius:6px;
                                background:#fff0f0; border:1px solid #ffd0d0;
                                color:#e03131; cursor:pointer;
                                transition:all 0.15s;
                            "
                            @mouseenter="$event.currentTarget.style.background='#ffe3e3'; $event.currentTarget.style.borderColor='#fa5252';"
                            @mouseleave="$event.currentTarget.style.background='#fff0f0'; $event.currentTarget.style.borderColor='#ffd0d0';">
                            <Trash style="width:13px; height:13px;" />
                        </button>
                    </div>
                </div>

                <!-- Bottom save -->
                <div class="d-flex justify-content-end mt-4 pt-2" style="border-top:1px solid #e9ecef;">
                    <button class="btn btn-success px-5" @click="save" :disabled="saving">
                        <span v-if="saving" class="spinner-border spinner-border-sm me-2"></span>
                        {{ saving ? 'Saving...' : 'Save to Database' }}
                    </button>
                </div>

            </template>

            <!-- Empty state -->
            <div v-else-if="!scraping"
                class="text-center py-5 d-flex flex-column align-items-center gap-2">
                <div style="font-size:2rem;">🔍</div>
                <div style="font-size:0.9rem; color:#868e96;">
                    Enter a product URL above and click <strong>Scrape</strong>
                </div>
                <div style="font-size:0.8rem; color:#adb5bd;">
                    Specifications will be extracted from the page automatically
                </div>
            </div>

        </template>
    </app-modal>
</template>
