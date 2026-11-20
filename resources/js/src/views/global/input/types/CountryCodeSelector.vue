<script setup>
import {ref, computed, onMounted, watch} from 'vue';

// Define props for the component
const props = defineProps({
    countries: {
        type: Array,
        required: true // The `countries` prop is required and should be an array
    },
    defaultCountry: {
        type: String,
        default: '' // Default to an empty string if no defaultCountry is provided
    },
    InputClass: {
        type: String,
        default: '' // Default to an empty string if no InputClass is provided
    }
});

// Define emits to declare events that this component can emit
const emit = defineEmits(['update', 'countryCodeUpdate'])
// Create reactive references for the search box and dropdown elements
const SearchBox = ref();
const dropdown = ref();

// Reactive reference for the search query
const searchQuery = ref(null);

// Computed property to filter countries based on the search query
const filterCountries = computed(() => {
    if (searchQuery.value) {
        return props.countries.filter((code) => {
            // Convert search query to lowercase for case-insensitive matching
            const searchLower = searchQuery.value.toLowerCase();
            let foundMatch = false;
            const checkObject = (obj) => {
                Object.keys(obj).forEach((key) => {
                    const value = obj[key];
                    if (typeof value === 'object') {
                        // Recursively check nested objects
                        checkObject(value);
                    } else if (typeof value === 'string' || typeof value === 'number') {
                        if (value.toString().toLowerCase().includes(searchLower)) {
                            foundMatch = true;
                        }
                    }
                });
            };
            checkObject(code);
            return foundMatch;
        });
    }
    return props.countries;
});

// Reactive reference for the selected country
const selectedCountry = ref({});
// Function to handle selecting a country
const selectOption = (country) => {
    selectedCountry.value = country
    emit('update', country)
}
// Watcher to update the selected country based on the `defaultCountry` prop

watch(() => props.defaultCountry, () => {
    let country = props.countries[0]; // Default to the first country if no match is found
    if (props.defaultCountry) {
        country = props.countries.filter(o => o.code.toString().toLowerCase() === props.defaultCountry.toString().toLowerCase())[0]
    }

    selectedCountry.value = country

}, {immediate: true}) // Immediate execution to set the initial value

// Lifecycle hook to set focus on the search box when the dropdown is shown
onMounted(() => {
    dropdown.value.addEventListener('shown.bs.dropdown', () => SearchBox.value.focus())
});

</script>
<template>
    <div class="dropdown" ref="dropdown">
        <div class="dropdown-toggle text-start border default-padding d-flex justify-content-center align-items-center"
             :class="props.InputClass" type="button"
             data-bs-toggle="dropdown">
            {{ `${selectedCountry?.flag} ${selectedCountry?.dial_code}` }}
        </div>
        <div class="dropdown-menu">
            <div class="form-group mb-3 px-2">
                <input type="text" ref="SearchBox" class="form-control" placeholder="Search" v-model="searchQuery"
                       @input="search">
            </div>
            <div class="menu-container overflow-auto custom-scrollbar">
                <div v-for="option in filterCountries" :key="option.id" class="dropdown-item cursor-pointer"
                     @click="selectOption(option)">
                    {{ option.flag + ' ' + option.dial_code + ' (' + option.code + ' - ' + option.name + ')' }}
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped lang="scss">

</style>
