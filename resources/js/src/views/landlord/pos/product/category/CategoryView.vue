<script setup>
import {ref, onMounted} from "vue";
import draggable from "vuedraggable";
import usePermission from "@/composable/permission/usePermission.js";
import {CATEGORIES, UPDATE_CATEGORIES_ORDER} from "@/services/endpoints/landlord.js";
import Axios from "@/services/axios/index.js";
import CreateCategory from "@/views/landlord/pos/product/category/CreateCategory.vue";
import CategoryEdit from "@/views/landlord/pos/product/category/CategoryEdit.vue";
import CategoryTreeItem from "@/views/landlord/pos/product/category/CategoryTreeItem.vue";
import {useToast} from "vue-toastification";
import {useDeleteModal} from "@/composable/modal/useDeleteModal.js";
import {EmptyPage, List, Minus, Plus, Trash} from "@iconoir/vue";
import router from "@/router/index.js";

const categories = ref([]);
const openSubcategories = ref({});
const {canAccess} = usePermission();
const selectedCategoryId = ref(null);
const toast = useToast();

const editCategory = (category) => {

    if (selectedCategoryId.value === category.id) {
        selectedCategoryId.value = null;
    } else {
        selectedCategoryId.value = category.id;
    }
};

const {deleteUrl, isDeleteModal, cancelled} = useDeleteModal()

const pageLoader = ref(false)
const fetchCategories = async (page = 1, allCategories = []) => {
    try {
        pageLoader.value = true;
        const response = await Axios.get(`${CATEGORIES}?page=${page}&per_page=500`);
        const data = response.data;

        allCategories.push(...data.data);

        if (page < data.last_page) {
            return fetchCategories(page + 1, allCategories);
        }
        const categoryMap = new Map();

        allCategories.forEach(cat => {
            cat.sub_categories = [];
            cat.imageError = false;
            categoryMap.set(cat.id, cat);
        });

        allCategories.forEach(cat => {
            if (cat.parent_id && categoryMap.has(cat.parent_id)) {
                categoryMap.get(cat.parent_id).sub_categories.push(cat);
            }
        });

        allCategories.forEach(cat => {
            if (cat.sub_categories && cat.sub_categories.length > 0) {
                cat.sub_categories.sort((a, b) => a.order - b.order);
            }
        });

        categories.value = allCategories
            .filter(cat => !cat.parent_id)
            .sort((a, b) => a.order - b.order);
    } catch (error) {
    } finally {
        pageLoader.value = false;
    }
};

const toggleSubcategories = (categoryId) => {
    openSubcategories.value[categoryId] = !openSubcategories.value[categoryId];
};

// Walks the whole tree so reordering inside any level — children, grandchildren,
// and beyond — gets captured, not just the top two levels.
const buildOrderPayload = (categoryList) => {
    return categoryList.map((category, index) => ({
        id: category.id,
        order: index + 1,
        sub_categories: category.sub_categories?.length
            ? buildOrderPayload(category.sub_categories)
            : [],
    }));
};

const updateOrder = async () => {
    try {
        const updatedCategories = buildOrderPayload(categories.value);

        const response = await Axios.post(UPDATE_CATEGORIES_ORDER, {categories: updatedCategories});

        if (response.data.success) {
            toast.success("Categories order updated successfully!");
        } else {
            toast.error("Failed to update categories order.");
        }
    } catch (error) {
        toast.error("Error updating categories order.");
    }
};

const deleteCategoryId = ref(null);
const deleteCategory = async () => {
    try {
        const response = await Axios.delete(deleteUrl.value);

        if (response.data.success) {
            toast.success("Category deleted successfully!");

            isDeleteModal.value = false;

            if (selectedCategoryId.value === deleteCategoryId.value) {
                selectedCategoryId.value = null;
            }
            deleteCategoryId.value = null;

            // 3. Re-fetch data
            await fetchCategories();
        } else {
            toast.error("Failed to delete category.");
        }
    } catch (error) {
        console.error(error);
        toast.error("Error deleting category.");
    }
};

const openDeleteModal = (id) => {
    deleteCategoryId.value = id;
    deleteUrl.value = `${CATEGORIES}/${id}`;
    isDeleteModal.value = true;
};
const handleCategoryCreated = () => {
    fetchCategories();
    selectedCategoryId.value = null;
};
const handleCategoryUpdated = () => {
    fetchCategories();
    selectedCategoryId.value = null;
};
const showCreateCategory = () => {
    selectedCategoryId.value = null;
};
const goBack = () => {
    router.push('/categories');
};
onMounted(() => {
    fetchCategories()
});
</script>

<template>
    <div class="content-wrapper">
        <app-loader v-if="pageLoader"/>
        <div class="row g-4" v-else>
            <div class="col-lg-6">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <app-breadcrumb :page-title="$t('reorder_category')"></app-breadcrumb>
                    <div class="d-flex gap-2">
                        <button class="btn btn-primary-outline" @click="goBack">
                            {{ $t('back_to_categories') }}
                        </button>
                        <button class="btn btn-primary" @click="showCreateCategory">
                            {{ $t('create_category') }}
                        </button>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body" v-if="canAccess('view_categories')">
                        <ul class="category-lists">
                            <CategoryTreeItem
                                :items="categories"
                                :open-map="openSubcategories"
                                :selected-id="selectedCategoryId"
                                @toggle="toggleSubcategories"
                                @edit="editCategory"
                                @delete="openDeleteModal"
                                @update-order="updateOrder"/>
                        </ul>

                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <create-category v-if="!selectedCategoryId" @category-created="handleCategoryCreated"/>
                <category-edit v-if="selectedCategoryId" :category-id="selectedCategoryId"
                               @category-updated="handleCategoryUpdated"/>
            </div>
        </div>
    </div>

    <app-delete-modal v-if="isDeleteModal" :selected-url="deleteUrl" @cancelled="cancelled"
                      @confirmed="deleteCategory"/>
</template>


<style scoped lang="scss"></style>
