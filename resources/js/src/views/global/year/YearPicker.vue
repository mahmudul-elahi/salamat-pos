<script setup>
import { FastArrowLeft, FastArrowRight } from '@iconoir/vue'
import { ref, computed, watch } from 'vue'

const props = defineProps({
    modelValue: Number,
})

const emit = defineEmits(['update:modelValue'])

const decadeStart = ref(props.modelValue ? props.modelValue - (props.modelValue % 10) : new Date().getFullYear() - (new Date().getFullYear() % 10))

watch(() => props.modelValue, (val) => {
    if (val && (val < decadeStart.value || val > decadeStart.value + 11)) {
        decadeStart.value = val - (val % 10)
    }
})

const years = computed(() => {
    const start = decadeStart.value - 1
    return Array.from({ length: 12 }, (_, i) => start + i)
})

function prevDecade() {
    decadeStart.value -= 10
}

function nextDecade() {
    decadeStart.value += 10
}
</script>
<template>
    <div class="year-picker">
        <div class="year-header">
            <span class="range-btn" @click="prevDecade">
                <FastArrowLeft />
            </span>
            <span class="decade-range">{{ decadeStart - 1 }}–{{ decadeStart + 10 }}</span>
            <span class="range-btn" @click="nextDecade">
                <FastArrowRight />
            </span>
        </div>

        <div class="year-list">
            <div
                v-for="year in years"
                :key="year"
                class="year"
                :class="{ selected: year === modelValue }"
                @click="$emit('update:modelValue', year)">
                {{ year }}
            </div>
        </div>
    </div>
</template>

<style scoped lang="scss">

</style>
