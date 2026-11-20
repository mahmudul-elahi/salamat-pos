<script>
import {computed} from "vue"

export default {
    name: "Avatar",
    props: {
        image: String,
        name: String,
        size: {
            default: 40
        },
        active: String
    },

    setup(props) {
        const titleFilter = computed(() => {
            const name = props.name || 'A V'
            const parts = name.split(' ')
            const first = parts[0] ? parts[0][0] : ''
            const last = parts.length > 1 ? parts[parts.length - 1][0] : ''
            return `${first}${last}`.toUpperCase()
        })

        return {titleFilter}
    }

}
</script>

<template>
    <div class="avatar-wrapper">
        <img v-if="image"
             :src="image"
             class="rounded-circle has-image"  alt="image">
        <div v-else class="no-image fw-semibold">
            {{ titleFilter }}
        </div>
    </div>
</template>

<style scoped lang="scss">

</style>
