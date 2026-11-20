<script setup>
import {ref, watch} from "vue"
// Define props using defineProps macro
const props = defineProps({
    data: {},
    value: {
        type: Array,
        default: []
    }
})

// Define emits to declare events that this component can emit
const emit = defineEmits(['checkboxOption'])

// Create a reactive reference for the input values, initialized with the provided prop value
const inputValue = ref([...props.value])

// Watch for changes to the inputValue array and emit the 'checkboxOption' event with the new value
watch(inputValue, (newValue) => {
    emit('checkboxOption', newValue)
})

</script>

<template>
    <div class="form-check d-flex gap-3 align-items-center" v-for="(option, index) in data.options" :key="index">
        <input class="form-check-input"
               type="checkbox"
               :value="option[data.listKeyField]"
               :id="`${data.id}-${option[data.listKeyField]}`"
               v-model="inputValue"
               v-bind="$attrs">
        <label class="form-check-label fw-semibold fs-5" :for="`${data.id}-${option[data.listKeyField]}`">
            {{ option[data.listValueField] }}
        </label>
    </div>
</template>

<style scoped lang="scss">

</style>