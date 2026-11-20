<script setup>
defineProps({
    message: String,
});
</script>

<template>
    <div class="note p-3">
        {{ message }}
    </div>
</template>

<style scoped lang="scss">

</style>
