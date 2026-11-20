<script setup>
</script>

<template>
    <div class="text-center d-flex w-100vw h-100vh justify-content-center align-items-center flex-column">
        <h1 class="display-1 fw-bold text-danger">403</h1>
        <h6 class="display-6 fw-bold">{{ $t('access_denied') }}</h6>
        <p class="text-secondary">{{ $t('dont_have_permission') }}</p>
        <router-link :to="{ name: 'login' }" class="btn btn-primary">{{ $t('back') }}</router-link>

    </div>
</template>

<style scoped lang="scss"></style>
