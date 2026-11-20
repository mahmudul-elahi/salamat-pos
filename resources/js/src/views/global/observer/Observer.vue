<script setup>
import {onMounted, onUnmounted, ref} from 'vue';

// Define component props with default options
const props = defineProps({
    options: {
        type: Object,
        default: {}
    }
});

// Create reactive references for DOM element and IntersectionObserver
const root = ref(null);
const observer = ref();

// Define event emitter
const emit = defineEmits(['intersect']);

// Lifecycle hook that sets up the Intersection observer when the component is mounted
onMounted(() => {
    // Create a new IntersectionObserver instance with the specified options
    observer.value = new IntersectionObserver(([entry]) => {
        // Emit 'intersect' event when the observed element is intersecting the viewport
        if (entry && entry.isIntersecting) {
            emit("intersect");
        }
    }, props.options);

    // Start observing the element referenced by 'root'
    observer.value.observe(root.value);
});

// Lifecycle hook that cleans up the Intersection observer when the component is unmounted
onUnmounted(() => {
    // Disconnect the observer to stop observing and clean up resources
    observer.value.disconnect();
});
</script>
<template>
    <!-- Container element where the Intersection observer will be applied -->
    <div ref="root"></div>
</template>

<style scoped lang="scss">

</style>


