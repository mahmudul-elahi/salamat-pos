<script setup>
</script>

<template>
    <div class="text-center d-flex w-100vw h-100vh justify-content-center align-items-center flex-column">
        <h1 class="display-1 fw-bold text-danger">404</h1>
        <h6 class="display-6 fw-bold">{{ $t('something_went_wrong') }}</h6>
        <p class="text-secondary">{{ $t('page_not_exist') }}</p>
        <router-link :to="{ name: 'login' }" class="btn btn-primary">
            {{ $t('back') }}
        </router-link>
    </div>
</template>

<style scoped lang="scss"></style>
