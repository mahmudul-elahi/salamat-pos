<script setup>
// Define props using defineProps macro
const props = defineProps({
    data: {},
    value: {},
});
const emit = defineEmits(["radioOption"]);

// Handle radio event to emit the updated value
const radioOption = (value) => {
    emit("radioOption", value);
};
</script>
<template>
    <label
        class="form-check form-check-radio me-2 mb-2"
        :class="{
            'form-check-inline': data.formCheckInline,
            'form-check-boxed': data.formCheckBoxed,
        }"
        v-for="(option, index) in data.options"
        :for="data.id + '-' + index" :key="index"
    >
        <input
            class="form-check-input"
            type="radio"
            :id="data.id + '-' + index"
            :disabled="option.disabled || data.disabled"
            :checked="option[data.listKeyField] == value"
            :value="option[data.listKeyField]"
            v-bind="$attrs"
            @change="radioOption(option[data.listKeyField])"
        />
        <span class="form-check-label">
            {{ option[data.listValueField] }}
        </span>
    </label>
</template>

<style scoped lang="scss">

</style>