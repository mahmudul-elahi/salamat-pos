<script setup>
const props = defineProps({
    selected: Number,
})
</script>

<template>
    <ul
        class="dropdown-menu dropdown-menu-end custom-scrollbar"
        aria-labelledby="filterDropdown">
        <li>
            <a href="#" class="dropdown-item d-flex align-items-center"
                :class="{ active: props.selected === 1 }"
                @click.prevent="$emit('applyFilter', 1)">
                {{ $t('last_7_days') }}
            </a>
        </li>
        <li>
            <a href="#" class="dropdown-item d-flex align-items-center"
                :class="{ active: props.selected === 2 }"
                @click.prevent="$emit('applyFilter', 2)">
                {{ $t('this_week') }}
            </a>
        </li>
        <li>
            <a href="#" class="dropdown-item d-flex align-items-center"
                :class="{ active: props.selected === 3 }"
                @click.prevent="$emit('applyFilter', 3)">
                {{ $t('last_week') }}
            </a>
        </li>
        <li>
            <a href="#" class="dropdown-item d-flex align-items-center"
                :class="{ active: props.selected === 4 }"
                @click.prevent="$emit('applyFilter', 4)">
                {{ $t('this_month') }}
            </a>
        </li>
        <li>
            <a href="#" class="dropdown-item d-flex align-items-center"
                :class="{ active: props.selected === 5 }"
                @click.prevent="$emit('applyFilter', 5)">
                {{ $t('last_month') }}
            </a>
        </li>
        <li>
            <a href="#" class="dropdown-item d-flex align-items-center"
                :class="{ active: props.selected === 6 }"
                @click.prevent="$emit('applyFilter', 6)">
                {{ $t('this_year') }}
            </a>
        </li>
        <li>
            <a href="#" class="dropdown-item d-flex align-items-center"
                :class="{ active: props.selected === 7 }"
                @click.prevent="$emit('applyFilter', 7)">
                {{ $t('last_year') }}
            </a>
        </li>

        <li>
            <a href="#" class="dropdown-item d-flex align-items-center"
                :class="{ active: props.selected === 0 }"
                @click.prevent="$emit('applyFilter', 0)">
                {{ $t('total') }}
            </a>
        </li>
    </ul>
</template>

<style scoped lang="scss"></style>
