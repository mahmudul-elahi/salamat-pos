<script setup>
// Define props using defineProps
const props = defineProps({
    value: {},
    data: {},
});

// Define the emit function to emit events
const emit = defineEmits(['update:value']);

// Handle input event to emit the updated value
const handleInput = (event) => {
    emit('update:value', event.target.value);
};
</script>
<template>
    <input
        type="text"
        :id="data.id"
        class="form-control"
        :value="value"
        @input="handleInput"
        v-bind="$attrs"
    />
</template>

<style scoped lang="scss">

</style>