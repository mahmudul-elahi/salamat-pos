<script setup>
import { computed } from 'vue'
import { Link } from "@iconoir/vue";

const props = defineProps({
    rowData: {
        type: Object,
        required: true,
    }
})
const images = computed(() => {
    if (!props.rowData) return []

    // if backend sends array
    if (Array.isArray(props.rowData.images)) {
        return props.rowData.images
    }

    // if backend sends single image string
    if (props.rowData.image) {
        return [props.rowData.image]
    }

    return []
})

</script>

<template>
    <div class="d-inline-flex gap-2" v-if="images.length">
        <div v-for="(image, index) in images" :key="index">
            <a
                :href="image"
                :title="$t('view_attachment')"
                target="_blank"
                rel="noopener noreferrer">
                <div class="view-icon">
                    <Link class="brand-color" />
                </div>
            </a>
        </div>
    </div>
    <span v-else >-</span>
</template>

<style scoped lang="scss">
</style>
