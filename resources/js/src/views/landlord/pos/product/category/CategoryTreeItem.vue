<script setup>
import draggable from "vuedraggable";
import { EmptyPage, List, Minus, Plus, Trash } from "@iconoir/vue";

defineProps({
    items: Array,
    openMap: Object,
    selectedId: [String, Number]
});

const emit = defineEmits([
    "toggle",
    "edit",
    "delete",
    "updateOrder",
]);

// Smooth expand/collapse for the nested subcategory list — height:auto can't be
// transitioned directly, so we measure scrollHeight and animate to/from that.
const onEnter = (el) => {
    el.style.height = '0';
    el.style.opacity = '0';
    void el.offsetHeight; // force reflow so the transition picks up the starting state
    el.style.height = el.scrollHeight + 'px';
    el.style.opacity = '1';
};
const onAfterEnter = (el) => {
    el.style.height = 'auto';
};
const onBeforeLeave = (el) => {
    el.style.height = el.scrollHeight + 'px';
    void el.offsetHeight;
};
const onLeave = (el) => {
    el.style.height = '0';
    el.style.opacity = '0';
};
</script>

<template>
    <draggable
        :list="items"
        item-key="id"
        class="category-level"
        handle=".drag-indicator"
        ghost-class="drag-ghost"
        chosen-class="drag-chosen"
        drag-class="drag-active"
        :fallback-tolerance="0"
        :force-fallback="true"
        :animation="200"
        easing="cubic-bezier(0.25, 1, 0.5, 1)"
        @end="emit('updateOrder')">
        <template #item="{ element }">
            <li class="list-item-wrapper">
                <div
                    class="category-card"
                    :class="{ 'is-selected': Number(selectedId) === Number(element.id) }">
                    
                    <div class="drag-indicator">
                        <List />
                    </div>

                    <div class="content-body" @click="emit('edit', element)">
                        <div class="category-icon-wrapper">
                            <img
                                v-if="element.logo && !element.imageError"
                                :src="element.logo"
                                @error="element.imageError = true"
                                class="category-img" />
                            <EmptyPage v-else class="icon-placeholder" />
                        </div>

                        <div class="label-section">
                            <span class="category-name">{{ element.name }}</span>

                            <div class="action-area">
                                <Transition name="fade">
                                    <button
                                        v-if="Number(selectedId) === Number(element.id)"
                                        class="btn-icon delete-btn"
                                        @click.stop="emit('delete', element.id)">
                                        <Trash />
                                    </button>
                                </Transition>

                                <div v-if="element.sub_categories?.length" class="toggle-wrapper">
                                    <component
                                        :is="openMap[element.id] ? Minus : Plus"
                                        class="toggle-icon"
                                        @click.stop="emit('toggle', element.id)" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <transition
                    name="slide"
                    @enter="onEnter"
                    @after-enter="onAfterEnter"
                    @before-leave="onBeforeLeave"
                    @leave="onLeave">
                    <ul v-if="openMap[element.id] && element.sub_categories?.length" class="nested-level">
                        <CategoryTreeItem
                            :items="element.sub_categories"
                            :open-map="openMap"
                            :selected-id="selectedId"
                            @toggle="emit('toggle', $event)"
                            @edit="emit('edit', $event)"
                            @delete="emit('delete', $event)"
                            @updateOrder="emit('updateOrder')" />
                    </ul>
                </transition>
            </li>
        </template>
    </draggable>
</template>

<style lang="scss" scoped>
/* PREMIUM VARIABLES */

.category-level {
    list-style: none;
    padding: 0;
    margin: 0;
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.list-item-wrapper {
    list-style: none;
    /* This ensures dragging the li moves everything inside it */
}

.category-card {
    display: flex;
    align-items: center;
    background: var(--white);
    border: 1px solid var(--border-color);
    border-radius: 12px;
    padding: 6px;
    transition: border-color 0.15s ease, background-color 0.15s ease;
    will-change: transform;
    backface-visibility: hidden;

    &:hover {
        border-color: var(--primary);
        background: var(--primary-hover-two);
    }

    &.is-selected {
        border-color: var(--primary);
        background: var(--primary-hover-two);
        box-shadow: 0 0 0 1px var(--primary);
    }
}

.drag-indicator {
    padding: 10px;
    color: #cbd5e1;
    display: flex;
    align-items: center;
    cursor: grab;

    &:active {
        cursor: grabbing;
    }

    svg { width: 18px; height: 18px; }
}

.content-body {
    flex: 1;
    display: flex;
    align-items: center;
    gap: 12px;
    padding-right: 12px;
}

.category-icon-wrapper {
    width: 36px;
    height: 36px;
    background: #f1f5f9;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    padding: 6px;
    
    img { width: 100%; height: 100%; object-fit: cover; }
}

.label-section {
    flex: 1;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.category-name {
    font-weight: 500;
    color: var(--font-color);
}

.action-area {
    display: flex;
    align-items: center;
    gap: 8px;
}

/* BUTTONS & ICONS */
.btn-icon, .toggle-icon {
    cursor: pointer;
    background: transparent;
    border: none;
    display: flex;
    align-items: center;
    padding: 4px;
    border-radius: 6px;
    color: #64748b;
    transition: 0.2s;

    &:hover { color: var(--primary); background: #eee; }
}

.delete-btn:hover {
    color: #ef4444 !important;
    background: #fee2e2 !important;
}

/* NESTING LINE */
.nested-level {
    margin-top: 10px;
    margin-left: 22px;
    padding-left: 18px;
    border-left: 2px solid #f1f5f9;
    list-style: none;
    overflow: hidden;
    transition: height 0.28s cubic-bezier(0.25, 1, 0.5, 1), opacity 0.22s ease;
}

/* DRAG GHOST */
.drag-ghost {
    opacity: 0.3;
    background: var(--primary) !important;
    pointer-events: none;
}
.drag-chosen {
    box-shadow: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1) !important;
    opacity: 1 !important;
    background: #ffffff !important;
    transform: scale(1.02) translateZ(0);
    border: 1px solid var(--primary) !important;
}
.drag-active {
    cursor: grabbing !important;
    transition: none !important;
}

</style>