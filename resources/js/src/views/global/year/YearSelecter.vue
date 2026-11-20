<script setup>
import { ref } from 'vue'
import YearPicker from './YearPicker.vue'
import { NavArrowLeft, NavArrowRight } from '@iconoir/vue'

const showPicker = ref(false)
const selectedYear = ref(new Date().getFullYear())

function prevYear() {
    selectedYear.value--
}

function nextYear() {
    selectedYear.value++
}

function updateYear(val) {
    selectedYear.value = val
    showPicker.value = false
}
</script>

<template>
    <div class="relative d-inline-block">
        <div class="d-flex align-items-center year-selector-wrapper">
            <button class="btn btn-white prev-btn" @click="prevYear">
                <NavArrowLeft />
            </button>
            <button class="btn btn-white selected-year-btn" @click="showPicker = !showPicker">
                {{ selectedYear }}
            </button>
            <button class="btn btn-white next-btn" @click="nextYear">
                <NavArrowRight />
            </button>
        </div>

        <!-- Year Picker Popup -->
        <div v-if="showPicker" class="absolute z-10 mt-2">
            <YearPicker :modelValue="selectedYear" @update:modelValue="updateYear" />
        </div>
    </div>
</template>

<style scoped lang="scss">

</style>
