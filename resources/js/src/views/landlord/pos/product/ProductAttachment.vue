<script setup>
import Avatar from "@/views/global/avatar/Avatar.vue";

const props = defineProps({
    tableId: {},
    rowData: {}
})
</script>

<template>
    <div class="d-flex align-items-center">
        <avatar :image="rowData?.product_image" :name="rowData.product_name" active="success" />
        <div class="d-flex flex-column gap-1">
            <div class="mx-2 text-muted">
                {{ rowData.product_name }}
            </div>
            <div v-if="rowData.has_official_warranty || rowData.is_chinese || rowData.is_unofficial || rowData.is_refurbished"
                class="mx-2 d-flex flex-wrap gap-1">
                <span v-if="rowData.has_official_warranty" style="
                    display: inline-flex;
                    align-items: center;
                    gap: 4px;
                    font-size: 0.7rem;
                    font-weight: 600;
                    color: #0f6f3f;
                    background: #d1fae5;
                    border: 1px solid #6ee7b7;
                    border-radius: 20px;
                    padding: 1px 8px;
                    letter-spacing: 0.3px;
                ">
                    ✓ Official Warranty
                </span>
                <span v-if="rowData.is_chinese" style="
                    display: inline-flex;
                    align-items: center;
                    gap: 4px;
                    font-size: 0.7rem;
                    font-weight: 600;
                    color: #b91c1c;
                    background: #fee2e2;
                    border: 1px solid #fca5a5;
                    border-radius: 20px;
                    padding: 1px 8px;
                    letter-spacing: 0.3px;
                ">
                    Chinese
                </span>
                <span v-if="rowData.is_unofficial" style="
                    display: inline-flex;
                    align-items: center;
                    gap: 4px;
                    font-size: 0.7rem;
                    font-weight: 600;
                    color: #92400e;
                    background: #fef3c7;
                    border: 1px solid #fcd34d;
                    border-radius: 20px;
                    padding: 1px 8px;
                    letter-spacing: 0.3px;
                ">
                    Unofficial
                </span>
                <span v-if="rowData.is_refurbished" style="
                    display: inline-flex;
                    align-items: center;
                    gap: 4px;
                    font-size: 0.7rem;
                    font-weight: 600;
                    color: #1d4ed8;
                    background: #dbeafe;
                    border: 1px solid #93c5fd;
                    border-radius: 20px;
                    padding: 1px 8px;
                    letter-spacing: 0.3px;
                ">
                    Refurbished
                </span>
            </div>
        </div>
    </div>
</template>
