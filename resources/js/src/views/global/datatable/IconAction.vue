<script>
import {useAction} from "@/views/global/datatable/composable/useAction";

export default {
    name: "IconAction",
    props: {
        actions: {
            required: true,
            type: Array
        },
        rowData: {
            type: Object
        }
    },

    setup(props, {emit}) {
        const {action, visibleActions, bothAction} = useAction(props, emit)

        return {action, visibleActions, bothAction}
    }
}
</script>

<template>
    <div v-if="actions.length > 0"
         class="btn-group btn-group-action"
         role="group"
         aria-label="Default action">
        <button v-for="(action, index) in actions"
                :key="index"
                type="button"
                class="btn"
                :class="action.className"
                :aria-label="action.title"
                data-toggle="tooltip"
                data-placement="top"
                :title="action.title"
                @click.prevent="bothAction('',action, index)">
            <span>{{ action.title }}</span>
        </button>
    </div>
</template>

<style scoped lang="scss">

</style>
