<script setup>
import { EyeClosed, Eye } from '@iconoir/vue'
import { ref, computed } from 'vue'

// Define props using defineProps macro
const props = defineProps({
    value: {},
    data: {}
})

// Define reactive references for password visibility and input type
const isPasswordVisible = ref(false)
const inputType = computed(() => isPasswordVisible.value ? 'text' : 'password')

// Method to toggle password visibility
const togglePasswordVisibility = (show) => {
    isPasswordVisible.value = show
}

// Emit the updated value when input changes
const updateValue = (event) => {
    const newValue = event.target.value
    emit('update:value', newValue)
}

// Define emits function
const emit = defineEmits(['update:value'])
</script>

<template>
    <div class="password-input flex-shrink-0">
        <!-- Input field for password, with dynamic type -->
        <input
            :type="inputType"
            :id="data.id"
            class="form-control"
            :value="value"
            v-bind="$attrs"
            @input="updateValue" />


        <!-- Show/Hide password icon toggle -->
        <template v-if="data.showHideEffect">
            <span v-if="isPasswordVisible" @click="togglePasswordVisibility(false)">
                <Eye class="cursor-pointer eye-icon"/>
            </span>
            <span v-else @click="togglePasswordVisibility(true)">
                <EyeClosed class="cursor-pointer eye-icon"/>
            </span>

        </template>
    </div>
</template>

<style scoped lang="scss"></style>