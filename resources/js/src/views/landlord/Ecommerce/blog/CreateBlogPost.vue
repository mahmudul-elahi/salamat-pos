<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { useToast } from 'vue-toastification';
import { BLOG_POSTS, BLOG_CATEGORIES, BLOG_TAGS } from '@/services/endpoints/Ecommerce.js';
import MediaModal from '@/views/landlord/pos/mediaModal/MediaModal.vue';
import Attachment from '@/views/global/input/uploader/Attachment.vue';
import Axios from '@/services/axios/index.js';

const toast = useToast();
const router = useRouter();

const errors = ref({});
const preloader = ref(false);
const currentImageType = ref(null);
const mediaModalActive = ref(false);

const formData = ref({
    category_id: null,
    title: '',
    excerpt: '',
    content: '',
    thumbnail: null,
    banner: null,
    reading_time: 5,
    is_featured: false,
    is_trending: false,
    is_popular: false,
    is_published: false,
    published_at: null,
    meta_title: '',
    meta_description: '',
    meta_keywords: '',
});

const tags = ref([]);

const mediaLibraryModal = (type) => {
    currentImageType.value = type;
    mediaModalActive.value = true;
};

const closeMediaModalActive = () => {
    mediaModalActive.value = false;
    currentImageType.value = null;
    document.body.classList.remove('modal-open');
    document.body.style.overflow = 'visible';
    document.body.style.paddingRight = '0px';
};

const handleMediaSelected = (url) => {
    if (currentImageType.value) {
        formData.value[currentImageType.value] = url;
    }
    mediaModalActive.value = false;
    currentImageType.value = null;
};

const editorOptions = {
    theme: 'snow',
    modules: {
        toolbar: [
            [{ header: [1, 2, 3, 4, 5, 6, false] }],
            ['bold', 'italic', 'underline', 'strike'],
            [{ list: 'ordered' }, { list: 'bullet' }],
            [{ align: [] }],
            ['link', 'image'],
            ['clean'],
        ],
    },
};

const submit = () => {
    errors.value = {};
    preloader.value = true;
    Axios.post(BLOG_POSTS, {
        ...formData.value,
        tags: tags.value,
    }).then(() => {
        toast.success('Blog post created successfully');
        router.push({ name: 'blogPosts' });
    }).catch((err) => {
        if (err.response?.status === 422) {
            errors.value = err.response?.data?.errors || {};
        } else {
            toast.error(err.response?.data?.message || 'An error occurred while creating the blog post');
        }
    }).finally(() => {
        preloader.value = false;
    });
};
</script>

<template>
    <div class="content-wrapper">
        <app-breadcrumb :page-title="$t('add_blog_post')" />

        <div class="card">
            <div class="card-body">
                <div class="row g-4">
                    <div class="col-md-6">
                        <app-input type="advance-search-select" id="category_id" :label="$t('category')"
                            label-required list-value-field="name" v-model="formData.category_id"
                            :loading-length="10" :fetch-url="BLOG_CATEGORIES" :choose-label="$t('choose_a_category')"
                            :errors="$errors(errors, 'category_id')" />
                    </div>

                    <div class="col-md-6">
                        <app-input type="advance-multi-select" id="tags" :label="$t('tags')" list-value-field="name"
                            v-model="tags" :loading-length="10" :fetch-url="BLOG_TAGS"
                            :choose-label="$t('choose_tags')" :errors="$errors(errors, 'tags')" />
                    </div>

                    <div class="col-md-12">
                        <app-input type="text" id="title" :label="$t('title')" label-required
                            :placeholder="$t('title')" v-model="formData.title" :errors="$errors(errors, 'title')" />
                    </div>

                    <div class="col-md-12">
                        <app-input type="textarea" id="excerpt" :label="$t('excerpt')"
                            :placeholder="$t('excerpt')" v-model="formData.excerpt"
                            :errors="$errors(errors, 'excerpt')" />
                    </div>

                    <div class="col-md-12">
                        <app-input type="editor" id="content" :label="$t('content')" label-required
                            :editorOptions="editorOptions" :errors="$errors(errors, 'content')"
                            v-model="formData.content" />
                    </div>

                    <div class="col-md-6">
                        <div @click="mediaLibraryModal('thumbnail')">
                            <Attachment id="thumbnail" v-model="formData.thumbnail" :label="$t('thumbnail')"
                                label-required :error="$errors(errors, 'thumbnail')" :showInput="false"
                                :recommended-size="$t('image_recommended_size')"
                                :recommended-file-size="$t('setting_file_size')" />
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div @click="mediaLibraryModal('banner')">
                            <Attachment id="banner" v-model="formData.banner" :label="$t('banner')"
                                :error="$errors(errors, 'banner')" :showInput="false"
                                :recommended-size="$t('image_recommended_size')"
                                :recommended-file-size="$t('setting_file_size')" />
                        </div>
                    </div>

                    <div class="col-md-3">
                        <app-input type="number" id="reading_time" :label="$t('reading_time_minutes')"
                            v-model="formData.reading_time" :errors="$errors(errors, 'reading_time')" />
                    </div>

                    <div class="col-md-9">
                        <app-input type="date" id="published_at" :label="$t('published_at')"
                            v-model="formData.published_at" :errors="$errors(errors, 'published_at')" />
                    </div>

                    <div class="col-12">
                        <div class="d-flex flex-wrap gap-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="is_published"
                                    v-model="formData.is_published">
                                <label class="form-check-label" for="is_published">{{ $t('published') }}</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="is_featured"
                                    v-model="formData.is_featured">
                                <label class="form-check-label" for="is_featured">{{ $t('featured') }}</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="is_trending"
                                    v-model="formData.is_trending">
                                <label class="form-check-label" for="is_trending">{{ $t('trending') }}</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="is_popular"
                                    v-model="formData.is_popular">
                                <label class="form-check-label" for="is_popular">{{ $t('popular') }}</label>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-12">
                        <hr class="my-2">
                        <h6 class="mb-0">{{ $t('seo') }}</h6>
                    </div>

                    <div class="col-md-12">
                        <app-input type="text" id="meta_title" :label="$t('meta_title')"
                            :placeholder="$t('meta_title')" v-model="formData.meta_title"
                            :errors="$errors(errors, 'meta_title')" />
                    </div>

                    <div class="col-md-12">
                        <app-input type="textarea" id="meta_description" :label="$t('meta_description')"
                            v-model="formData.meta_description" :errors="$errors(errors, 'meta_description')" />
                    </div>

                    <div class="col-md-12">
                        <app-input type="text" id="meta_keywords" :label="$t('meta_tag_separate_with_coma')"
                            v-model="formData.meta_keywords" :errors="$errors(errors, 'meta_keywords')" />
                    </div>

                    <div class="col-12">
                        <button class="btn btn-primary" :disabled="preloader" @click="submit">
                            {{ $t('submit') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <media-modal v-if="mediaModalActive" modal-id="media-modal" selection-mode="single"
            @selected="handleMediaSelected" @close="closeMediaModalActive" />
    </div>
</template>

<style scoped lang="scss"></style>
