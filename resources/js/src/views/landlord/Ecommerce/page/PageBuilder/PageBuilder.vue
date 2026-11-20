<script setup>
import { ref, onMounted, computed } from "vue";
import draggable from "vuedraggable";
import { useRoute, useRouter } from "vue-router";
import { useToast } from "vue-toastification";
import Axios from "@/services/axios/index.js";
import { PAGE_BLOCKS } from "@/services/endpoints/Ecommerce.js";
import usePermission from "@/composable/permission/usePermission.js";
import { Plus, ArrowLeft } from "@iconoir/vue";
import BlockNode from "./BlockNode.vue";
import Inspector from "./Inspector.vue";
import { createBlockNode, hydrateTree, dehydrateTree } from "./blocks.js";

const route = useRoute();
const router = useRouter();
const toast = useToast();
const { canAccess } = usePermission();

const pageId = route.params.id;
const page = ref(null);
const blocks = ref([]);
const loading = ref(true);
const saving = ref(false);
const selected = ref(null);

const fetchTree = () => {
    loading.value = true;
    Axios.get(PAGE_BLOCKS(pageId))
        .then(({ data }) => {
            page.value = data.page;
            blocks.value = hydrateTree(data.blocks);
        })
        .catch(() => toast.error('Failed to load page sections'))
        .finally(() => loading.value = false);
}

onMounted(fetchTree);

const addBlock = (type) => {
    const node = createBlockNode(type);
    blocks.value.push(node);
    selected.value = node;
}

const selectBlock = (node) => selected.value = node;

const removeBlockFromTree = (nodes, target) => {
    const index = nodes.indexOf(target);
    if (index !== -1) {
        nodes.splice(index, 1);
        return true;
    }
    return nodes.some((n) => removeBlockFromTree(n.children, target));
}

const deleteBlock = (node) => {
    removeBlockFromTree(blocks.value, node);
    if (selected.value === node) selected.value = null;
}

const save = () => {
    saving.value = true;
    Axios.post(PAGE_BLOCKS(pageId), { blocks: dehydrateTree(blocks.value) })
        .then(() => {
            toast.success('Page sections saved successfully');
            fetchTree();
            selected.value = null;
        })
        .catch(({ response }) => toast.error(response?.data?.message || 'Failed to save page sections'))
        .finally(() => saving.value = false);
}
</script>

<template>
    <div class="content-wrapper page-builder">
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div class="d-flex align-items-center gap-2">
                <button type="button" class="btn btn-sm btn-icon" @click="router.back()">
                    <ArrowLeft width="18" height="18" />
                </button>
                <div>
                    <h5 class="mb-0">Section Builder</h5>
                    <small class="text-muted" v-if="page">{{ page.title }} &middot; /{{ page.slug }}</small>
                </div>
            </div>
            <button type="button" class="btn btn-primary" :disabled="saving || !canAccess('update_page_blocks')"
                    @click="save">
                {{ saving ? 'Saving...' : 'Save' }}
            </button>
        </div>

        <app-loader v-if="loading" />

        <div v-else class="row g-4">
            <div class="col-lg-8">
                <draggable v-model="blocks" group="page-blocks-root" item-key="_key" handle=".drag-handle">
                    <template #item="{ element }">
                        <BlockNode :node="element" :selected-key="selected?._key" @select="selectBlock"
                                   @delete="deleteBlock" />
                    </template>
                </draggable>

                <div class="d-flex gap-2 mt-2">
                    <button type="button" class="btn btn-outline-primary" @click="addBlock('section')">
                        <Plus width="14" height="14" /> Add Section
                    </button>
                    <button type="button" class="btn btn-outline-primary" @click="addBlock('tabs')">
                        <Plus width="14" height="14" /> Add Tabs
                    </button>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="page-builder__panel">
                    <Inspector v-if="selected" :node="selected" @delete="deleteBlock" />
                    <p v-else class="text-muted mb-0">Select a block to edit its content.</p>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped lang="scss">
.page-builder__panel {
    position: sticky;
    top: 16px;
    background: #fff;
    border: 1px solid #eef0f2;
    border-radius: 8px;
    padding: 16px;
}
</style>
