<script setup>
import { ref } from "vue"
import Avatar from "@/core/global/avatar/Avatar.vue";
import { Maximize } from "@iconoir/vue";

const props = defineProps({
    avatars: {
        type: Array,
        required: true
    },
    viewLength: {
        type: Number,
        default: 5
    }
})

const minimalView = ref(true)

</script>

<template>
    <div class="app-avatar-group">

        <div v-if="minimalView" class="minimal-view d-flex align-items-center cursor-pointer"
            @click.prevent="minimalView = false">
            <template v-for="(avatar, index) in avatars" :key="index">
                <avatar v-if="index < viewLength" :image="avatar.image" :name="avatar.name" :active="avatar.active"
                    size="36" />
            </template>

            <span class="mx-3 user-name">
                <!-- + - viewLength-->
                {{ avatars.length }} Users
            </span>
        </div>
        <div v-else>
            <template v-for="(avatar, index) in avatars" :key="index">
                <div class="d-flex align-items-center my-3 gap-2" v-if="index < viewLength" :key="index">
                    <avatar :image="avatar.image" :name="avatar.name" :active="avatar.active" :size="36" />
                    <div class="ms-2 d-flex justify-content-center flex-column">
                        <p class="m-0" v-if="avatar.name">{{ avatar.name }}</p>
                        <small v-if="avatar.title">{{ avatar.title }}</small>
                    </div>
                </div>
            </template>
        </div>
        <div title="Hide Details" v-if="!minimalView" class="avatar-minimize p-2 rounded-3 cursor-pointer"
            @click.prevent="minimalView = true">
            <Maximize />
        </div>
    </div>
</template>

<style scoped lang="scss">
</style>
