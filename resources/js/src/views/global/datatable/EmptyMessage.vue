<script setup>
import {urlGenerator} from "@/utils/urlGenerator";
</script>

<template>
    <div class="no-data-found text-center">
        <img class="mb-primary" :src="urlGenerator('assets/images/placeholder/nothing.svg')" alt="image">
        <p class="mb-0 text-center mt-3">{{ $t('nothing_to_show_here') }}</p>
    </div>

</template>

<style scoped lang="scss">

</style>

