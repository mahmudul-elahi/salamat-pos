<script setup>
import { ref, watch } from 'vue';
import MediaModal from "@/views/landlord/pos/mediaModal/MediaModal.vue";

const props = defineProps({
    modalId: String,
    product: Object,
    conditionList: Array,
    storageList: Array,
    regionList: Array,
    index: Number,
});

const emit = defineEmits(['close', 'save']);

const mediaModalActive = ref(false);

const localData = ref({
    condition: null,
    storage: null,
    region: null,
    photos: [],
});

watch(
    () => props.product,
    (newProduct) => {
        localData.value = {
            condition: newProduct?.condition || null,
            storage: newProduct?.storage || null,
            region: newProduct?.region || null,
            photos: newProduct?.photos
                ? newProduct.photos.map(p =>
                    typeof p === 'string' ? { path: p } : p
                )
                : [],
        };
    },
    { immediate: true }
);

// open media modal
const mediaLibraryModal = () => {
    if (mediaModalActive.value) return;
    mediaModalActive.value = true;
};

// select image
const handleMediaSelected = (url) => {
    localData.value.photos.push({ path: url });
    closeMediaModalActive();
};

// close media modal
const closeMediaModalActive = () => {
    mediaModalActive.value = false;
};

// uploader থেকে update
const updatePhotos = (files) => {
    localData.value.photos = files;
};

// save data
const save = () => {
    emit('save', {
        index: props.index,
        data: { ...localData.value },
    });
    console.log('emitting save with data:');
    emit('close');
};
</script>

<template>
    <app-modal :modal-id="modalId" modal-size="large" title="More Information" @close="$emit('close')" @submit="save">

        <template #body>
            <div class="row g-3">
                <div class="col-4">
                    <app-input type="select" label="Condition" id="condition" v-model="localData.condition"
                        :options="conditionList" />
                </div>

                <div class="col-4">
                    <app-input type="select" label="Storage" id="storage" v-model="localData.storage"
                        :options="storageList" />
                </div>

                <div class="col-4">
                    <app-input type="select" id="region" label="Region" v-model="localData.region"
                        :options="regionList" />
                </div>

                <div class="col-12">
                    <label class="mb-2">Photos</label>

                    <app-input type="multi-image-uploader" id="multi-image-uploader" :initialFiles="localData.photos"
                        @getFiles="updatePhotos" @changeFile="mediaLibraryModal" />
                </div>
            </div>
        </template>
    </app-modal>

    <!-- Media Modal -->
    <app-input type="multi-image-uploader" id="multi-uploader" :initialFiles="localData.photos" @getFiles="updatePhotos"
        @click="mediaLibraryModal" />
</template>