<script setup>
import {ref, onMounted} from 'vue';
import {useRoute} from 'vue-router';
import YearPicker from '@/views/global/year/YearPicker.vue';
import {NavArrowLeft, NavArrowRight} from '@iconoir/vue';

const props = defineProps({
    active: {},
    filterKey: {},
});
const emit = defineEmits(['applyFilter']);

const route = useRoute();

const showPicker = ref(false);
const selectedYear = ref(new Date().getFullYear());

function prevYear() {
    selectedYear.value--;
    emit('applyFilter', {[props.filterKey]: selectedYear.value});
}

function nextYear() {
    selectedYear.value++;
    emit('applyFilter', {[props.filterKey]: selectedYear.value});
}

function updateYear(val) {
    selectedYear.value = val;
    showPicker.value = false;
    emit('applyFilter', {[props.filterKey]: val});
}

onMounted(() => {
    if (props.active) {
        selectedYear.value = Number(props.active);
    }
    if (route.query[props.filterKey]) {
        selectedYear.value = Number(route.query[props.filterKey]);
    }
});
</script>

<template>
    <div class="relative">
        <div class="d-flex align-items-center year-selector-wrapper">
            <!-- Previous Year Button -->
            <button class="btn btn-white prev-btn" @click="prevYear"
                    aria-label="previous year"
                    title="previous year">
                <NavArrowLeft/>
            </button>

            <!-- Selected Year Button -->
            <button class="btn btn-white selected-year-btn" @click="showPicker = !showPicker"
                    aria-label="select year"
                    title="select year">
                {{ selectedYear }}
            </button>

            <!-- Next Year Button -->
            <button class="btn btn-white next-btn" @click="nextYear" aria-label="next year"
                    title="next year">
                <NavArrowRight/>
            </button>
        </div>

        <div v-if="showPicker" class="all-years-list-show">
            <YearPicker :modelValue="selectedYear" @update:modelValue="updateYear"/>
        </div>
    </div>
</template>

<style scoped lang="scss">

</style>
