<script setup>
import { CheckCircle, Search, Xmark } from "@iconoir/vue"
import { ref, computed, watch } from "vue"

const props = defineProps({
    value: {},
    data: {}
})
const emit = defineEmits(['addedId', 'deletedId', 'multiSelect'])

const searchValue = ref()
const multiSelectValue = ref([])
const activeIndex = ref(0)

watch(props.value, (newValue) => {
    multiSelectValue.value = Array.isArray(newValue) ? newValue : [];
}, { immediate: true })

const dropDownId = computed(() => `${props.data.id}-multi-select`)

const options = computed(() => {
    activeIndex.value = -1;
    if (searchValue.value) {
        return props.data.options.filter(option => {
            return option[props.data.listValueField].toLowerCase().includes(searchValue.value.toLowerCase());
        });
    } else return props.data.options;
})

const selectedOptions = computed(() => {
    let chipsList = [];
    multiSelectValue.value.forEach(item => {
        let tempChip = props.data.options.find(chip => chip[props.data.listKeyField] === item);
        if (tempChip) chipsList.push(tempChip)
    })
    return chipsList;
})

const hintText = computed(() => {
    return !props.data.options.length ? 'no_options_found' : (!options.length ? 'did_not_match_anything' : '');

})

const isChipSelected = (value) => {
    return multiSelectValue.value.includes(value);
}
const addChips = (chip) => {
    multiSelectValue.value.push(chip[props.data.listKeyField]);
    emit('addedId', chip[props.data.listKeyField]);
    changed();
    searchValue.value = '';
}
const deleteChips = (event, chip) => {
    event.stopPropagation();
    let index = multiSelectValue.value.indexOf(chip[props.data.listKeyField])
    multiSelectValue.value.splice(index, 1);
    emit('deletedId', chip[props.data.listKeyField]);
    changed();
}
const changed = () => {
    emit('multiSelect', multiSelectValue.value);
}

</script>
<template>
    <div class="dropdown keep-dropdown-open">
        <div :id="`multiSelect-${dropDownId}`"
            data-bs-toggle="dropdown"
            aria-expanded="false"
            class="multi-select-control dropdown-toggle">
            <div class="d-flex gap-2 flex-wrap align-items-center cursor-pointer">
                <template v-if="selectedOptions.length">
                    <span @click.prevent="$event.stopPropagation()"
                        v-for="(item, index) in selectedOptions" :key="index"
                        class="badge-square-light bg-primary badge-responsive">
                        {{ item[data.listValueField] }}
                        <a href="#" class="multi-select-btn" @click.prevent="deleteChips($event, item)">
                            <Xmark />
                        </a>
                    </span>
                </template>
                <span v-else>{{ data.chooseLabel }}</span>
            </div>
        </div>

        <ul class="dropdown-menu custom-scrollbar overflow-auto" :id="dropDownId" :aria-labelledby="`multiSelect-${dropDownId}`">
            <li class="mx-2">
                <div class="search-input" @click.stop="() => 0">
                    <span class="search-icon">
                        <Search />                    
                    </span>
                    <input v-model="searchValue" placeholder="Search" class="form-control">
                </div>
            </li>
            <li v-for="(option, index) in options" :key="index">
                <a href="#" @click.prevent="addChips(option)" :class="{ 'active disabled': isChipSelected(option[data.listKeyField]) }" class="dropdown-item d-inline-flex justify-content-between">
                    {{ option[data.listValueField] }}
                    <span v-if="isChipSelected(option[data.listKeyField])" class="cursor-pointer">
                        <CheckCircle />
                    </span>

                </a>
            </li>
            <li v-if="!options.length">
                <div class="text-center text-muted py-2">
                    {{ hintText }}
                </div>
            </li>
        </ul>
    </div>
</template>

<style scoped lang="scss"></style>