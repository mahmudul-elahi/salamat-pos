<script setup>
import {ref} from 'vue'
import {QuillEditor} from '@vueup/vue-quill'
import '@vueup/vue-quill/dist/vue-quill.snow.css';

// Define props using defineProps macro
const props = defineProps({
    value: {}, // The value prop, which will be used for v-model
    data: {} // Additional data prop
})
const content = ref('')
</script>

<template>
    <!-- Quill Editor with v-model binding to content -->
    <quill-editor v-model="content"/>
</template>

<style scoped lang="scss">

</style>
