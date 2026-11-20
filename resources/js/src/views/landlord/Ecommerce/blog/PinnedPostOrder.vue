<script setup>
import { ref, onMounted } from "vue";
import draggable from "vuedraggable";
import { useI18n } from "vue-i18n";
import { useToast } from "vue-toastification";
import { EmptyPage, List } from "@iconoir/vue";
import Axios from "@/services/axios/index.js";
import { BLOG_POSTS_PINNED, BLOG_POSTS_PINNED_ORDER } from "@/services/endpoints/landlord.js";
import router from "@/router/index.js";

// Only the pinned posts are listed here — never the whole archive — so this
// stays usable however many posts exist.
const { t } = useI18n();
const toast = useToast();

const posts = ref([]);
const pageLoader = ref(false);

const loadPosts = async () => {
    try {
        pageLoader.value = true;
        const { data } = await Axios.get(BLOG_POSTS_PINNED);
        posts.value = (data ?? []).map((post) => ({ ...post, imageError: false }));
    } catch (error) {
        toast.error(error?.response?.data?.message || "Failed to load pinned posts");
    } finally {
        pageLoader.value = false;
    }
};

// Saved on drop, matching how the brand reorder page behaves.
const updateOrder = async () => {
    try {
        await Axios.post(BLOG_POSTS_PINNED_ORDER, {
            posts: posts.value.map((post, index) => ({ id: post.id, position: index + 1 })),
        });
        toast.success(t('pinned_order_updated'));
    } catch (error) {
        toast.error(error?.response?.data?.message || "Failed to update pinned order");
        // The saved order is the only truth worth showing.
        await loadPosts();
    }
};

const goBack = () => {
    router.push('/blog-posts');
};

onMounted(loadPosts);
</script>

<template>
    <div class="content-wrapper">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <app-breadcrumb :page-title="t('reorder_pinned_posts')" />
            <button class="btn btn-primary-outline" @click="goBack">
                {{ t('back_to_posts') }}
            </button>
        </div>

        <app-loader v-if="pageLoader" />

        <div class="card" v-else>
            <div class="card-body">
                <p class="text-muted fs-6">{{ t('pinned_posts_hint') }}</p>

                <p v-if="posts.length === 0" class="mb-0">{{ t('no_pinned_posts') }}</p>

                <draggable
                    v-else
                    :list="posts"
                    item-key="id"
                    class="pinned-order-list"
                    handle=".drag-indicator"
                    ghost-class="drag-ghost"
                    chosen-class="drag-chosen"
                    drag-class="drag-active"
                    :fallback-tolerance="0"
                    :force-fallback="true"
                    :animation="200"
                    easing="cubic-bezier(0.25, 1, 0.5, 1)"
                    @end="updateOrder">
                    <template #item="{ element, index }">
                        <li class="list-item-wrapper">
                            <div class="pinned-card">
                                <div class="drag-indicator">
                                    <List />
                                </div>

                                <span class="badge-square-light bg-secondary">{{ index + 1 }}</span>

                                <div class="pinned-thumb">
                                    <img
                                        v-if="element.thumbnail && !element.imageError"
                                        :src="element.thumbnail"
                                        @error="element.imageError = true"
                                        class="pinned-img"
                                        alt="" />
                                    <EmptyPage v-else class="icon-placeholder" />
                                </div>

                                <div class="pinned-meta">
                                    <span class="pinned-title">{{ element.title }}</span>
                                    <span class="text-muted fs-6">{{ element.category?.name || '-' }}</span>
                                </div>

                                <span v-if="!element.is_published" class="badge-square-light bg-warning">
                                    {{ t('unpublished') }}
                                </span>
                            </div>
                        </li>
                    </template>
                </draggable>
            </div>
        </div>
    </div>
</template>

<style scoped lang="scss">
.pinned-order-list {
    list-style: none;
    margin: 0;
    padding: 0;
}

.list-item-wrapper {
    list-style: none;
}

.pinned-card {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 12px;
    margin-bottom: 8px;
    border: 1px solid var(--border-color, #e5e7eb);
    border-radius: 8px;
    background: var(--white, #fff);
}

.drag-indicator {
    cursor: grab;
    display: flex;
    align-items: center;
}

.pinned-thumb {
    width: 44px;
    height: 44px;
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    border-radius: 6px;
}

.pinned-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.pinned-meta {
    display: flex;
    flex-direction: column;
    min-width: 0;
    flex-grow: 1;
}

.pinned-title {
    font-weight: 600;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.drag-ghost {
    opacity: 0.4;
}
</style>
