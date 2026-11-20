<script>
import {useAction} from "@/views/global/datatable/composable/useAction";
import {MoreVert} from "@iconoir/vue";

export default {
    name: "DropdownAction",
    components: {
        MoreVert
    },
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
    <div class="dropdown dropstart action-dropdown" v-if="visibleActions && visibleActions.length > 0">
        <button class="dropdown-toggle"
                :key="'more-vertical-' + visibleActions.length"
                type="button"
                data-bs-toggle="dropdown"
                aria-expanded="false"
                aria-label="action-dropdown">
            <MoreVert/>
        </button>
        <ul class="dropdown-menu dropdown-menu-end scaleIn has-arrow right-top custom-scrollbar"
            :key="'dropdown-' + visibleActions.length">
            <li v-for="(action, index) in visibleActions" :key="index">
                <a :href="action.getHref ? action.getHref(rowData) : '#'"
                   class="dropdown-item d-flex align-items-center"
                   :class="action.className"
                   :aria-label="action.title"
                   @click.prevent="bothAction('', action, index)">
                    <component v-if="action.icon" :is="action.icon"/>
                    {{ action.title }}
                </a>
            </li>
        </ul>
    </div>
</template>

<style scoped lang="scss"></style>
