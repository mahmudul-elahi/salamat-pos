<script setup>

</script>

<template>
  <div class="card-skeleton shadow-sm">
    <div class="content">
      <div class="heading">
        <p class=""></p>
        <p class=""></p>
        <p class=""></p>
        <p class=""></p>
      </div>
      <div class="data ">
        <p class="shimmer"> </p>
        <p class="shimmer"> </p>
        <p class="shimmer"> </p>
        <p class="shimmer"> </p>
      </div>
      <div class="data" v-for="(n, index) in 5" :key="index">
        <p class="shimmer"> </p>
        <p class="shimmer"> </p>
        <p class="shimmer"> </p>
        <p class="shimmer"> </p>
      </div>

    </div>
  </div>
</template>



<style scoped lang="scss">

</style>

