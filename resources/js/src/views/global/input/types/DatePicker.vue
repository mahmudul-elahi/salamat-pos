<script setup>
import {ref, computed} from 'vue';
import {useCoreAppFunction} from '@/views/global/helpers/CoreLibrary'; // Importing a helper function

// Define props for the component
const props = defineProps({
    value: {}, // Prop for the current value
    data: {}   // Prop for additional data related to the date picker
});

// Define emits for the component, declaring events it can emit
const emit = defineEmits(['datePickerChange']);

// Create a reactive reference for managing border style (not used in this snippet)
const borderGroup = ref(false);

// Destructure the `isUndefined` function from the helper
const {isUndefined} = useCoreAppFunction();

// Computed property to determine if the date picker mode is 'range'
const isRange = computed(() => {
    // Check if dateMode is explicitly 'range'
    if (props.data.dateMode === 'range') {
        return true;
    }
    // Return `data.isRange` if it's defined; otherwise, return false
    return isUndefined(props.data.isRange) ? false : props.data.isRange;
});

// Computed property to determine the date picker mode
const dateMode = computed(() => {
    // Return 'date' if dateMode is 'range' or undefined; otherwise, return the defined dateMode
    if (props.data.dateMode === 'range' || isUndefined(props.data.dateMode)) {
        return 'date';
    } else return props.data.dateMode;
});

// Method to handle date change and emit the event
const handleDate = (context) => {
    emit('datePickerChange', context.date);
};
const theme = localStorage.getItem('theme')
</script>

<template>
    <v-date-picker :value="value"
                   :min-date="data.minDate"
                   :max-date="data.maxDate"
                   :color="data.dateColor"
                   :mode="dateMode"
                   :isDark="theme"
                   :isRange="isRange"
                   :is24hr="24"
                   :masks="{
                       input:['YYYY-MM-DD'],

                       inputDateTime:['h:mm A'],
                       inputDateTime24hr:['HH:mm'],
                       inputTime:['h:mm A'],
                       inputTime24hr:['HH:mm']
                   }"
                   :popover="{visibility: 'click', placement: data.popoverPosition}"
                   @dayclick="handleDate($event)">


        <template v-slot="{ inputValue, inputEvents }">
            <div class="date-picker-input" :class="{'date-picker-input-group':borderGroup}">
                <div class="input-group"
                     :class="{'disabled':data.disabled}"
                     @focusin="borderGroup=true"
                     @focusout="borderGroup=false">
                    <template v-if="isRange">
                        <input :value="inputValue.start || inputValue.end ? inputValue.start+' - '+inputValue.end: ''"
                               :placeholder="data.placeholder"
                               readonly
                               v-on="inputEvents.start || inputEvents.end"
                               class="form-control"
                               v-bind="$attrs"/>
                    </template>
                    <template v-else>
                        <input :value="inputValue ? inputValue : ''"
                               v-bind="$attrs"
                               v-on="inputEvents"
                               class="form-control"
                        />
                    </template>
                </div>
            </div>
        </template>
    </v-date-picker>
</template>

<style scoped lang="scss">

</style>
