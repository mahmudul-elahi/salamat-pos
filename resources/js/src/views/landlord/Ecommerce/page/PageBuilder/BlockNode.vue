<script setup>
import draggable from "vuedraggable";
import { Menu, Trash, Plus } from "@iconoir/vue";
import { BLOCK_TYPES, CHILD_BLOCK_TYPES, createBlockNode } from "./blocks.js";

const props = defineProps({
    node: { type: Object, required: true },
    selectedKey: { type: String, default: null },
})
const emit = defineEmits(['select', 'delete'])

const meta = (type) => BLOCK_TYPES[type]

const addChild = (type) => {
    const child = createBlockNode(type)
    props.node.children.push(child)
    emit('select', child)
}

// Border is universal - every block type can have one, even if its props
// don't include border_* yet (undefined reads as falsy -> 'none' below, so
// older saved blocks without these keys simply render with no border).
const borderStyle = (node) => {
    const width = node.props.border_width || 1
    const color = node.props.border_color || '#e5e7eb'
    const side = (on) => (on ? `${width}px solid ${color}` : 'none')
    return {
        borderTop: side(node.props.border_top),
        borderRight: side(node.props.border_right),
        borderBottom: side(node.props.border_bottom),
        borderLeft: side(node.props.border_left),
    }
}

// Margin (outside space) and padding (inside space) are universal too, same
// undefined-safe pattern as border - blank reads as 0, no layout change for
// blocks that never touched these fields.
const px = (v) => (v ? `${v}px` : '0')
// Leave a side undefined (not '0') when untouched, so the block-node's own
// default margin-bottom spacing (between siblings) survives - forcing '0'
// here would collapse that default the moment ANY margin field is used.
const marginStyle = (node) => ({
    marginTop: node.props.margin_top ? px(node.props.margin_top) : undefined,
    marginRight: node.props.margin_right ? px(node.props.margin_right) : undefined,
    marginBottom: node.props.margin_bottom ? px(node.props.margin_bottom) : undefined,
    marginLeft: node.props.margin_left ? px(node.props.margin_left) : undefined,
})
const paddingStyle = (node) => {
    const anySet = node.props.padding_top || node.props.padding_right || node.props.padding_bottom || node.props.padding_left
    if (!anySet) return {}
    return {
        paddingTop: px(node.props.padding_top),
        paddingRight: px(node.props.padding_right),
        paddingBottom: px(node.props.padding_bottom),
        paddingLeft: px(node.props.padding_left),
    }
}

const rootStyle = (node) => {
    const style = { ...borderStyle(node), ...marginStyle(node) }
    if (node.type === 'column') {
        style.flex = node.props.width ? `0 1 ${node.props.width}%` : '1 1 0'
        style.maxWidth = node.props.width ? `${node.props.width}%` : undefined
        style.minWidth = 0
    }
    if (node.type === 'card' && node.props.background_color) {
        style.backgroundColor = node.props.background_color
    }
    return style
}
</script>

<template>
    <div class="block-node"
         :class="{ 'is-selected': selectedKey === node._key, 'is-inactive': !node.is_active, 'block-node--timeline-item': node.type === 'timeline_item', 'block-node--card': node.type === 'card' }"
         :style="rootStyle(node)"
         @click.stop="emit('select', node)">
        <div class="block-node__header">
            <span class="drag-handle block-node__handle"><Menu width="14" height="14" /></span>
            <span class="block-node__label">{{ meta(node.type).label }}</span>
            <span v-if="node.type === 'tab'" class="block-node__tab-name">{{ node.props.label || 'Untitled tab' }}</span>
            <span v-if="!node.is_active" class="badge-square-light bg-secondary ms-2">Hidden</span>
            <button type="button" class="btn btn-sm btn-icon block-node__delete" @click.stop="emit('delete', node)">
                <Trash width="14" height="14" />
            </button>
        </div>

        <div class="block-node__body" :style="paddingStyle(node)">
            <template v-if="node.type === 'section' || node.type === 'tab' || node.type === 'column' || node.type === 'timeline_item' || node.type === 'card'">
                <draggable v-model="node.children" group="page-blocks" item-key="_key" handle=".drag-handle"
                           class="block-node__children" :class="{ 'is-empty': !node.children.length }">
                    <template #item="{ element }">
                        <BlockNode :node="element" :selected-key="selectedKey" @select="emit('select', $event)"
                                   @delete="(n) => node.children.splice(node.children.indexOf(n), 1)" />
                    </template>
                </draggable>
                <p v-if="!node.children.length" class="block-node__empty-hint">Empty - add a block below</p>

                <div class="block-node__add-row">
                    <button v-for="type in CHILD_BLOCK_TYPES" :key="type" type="button"
                            class="btn btn-sm btn-outline-secondary" @click.stop="addChild(type)">
                        <Plus width="12" height="12" /> {{ meta(type).label }}
                    </button>
                </div>
            </template>

            <template v-else-if="node.type === 'row'">
                <div class="block-node__row-wrap" :class="{ 'is-empty': !node.children.length }">
                    <draggable v-model="node.children" group="page-columns" item-key="_key" handle=".drag-handle"
                               tag="div" class="block-node__children block-node__children--row">
                        <template #item="{ element }">
                            <BlockNode :node="element" :selected-key="selectedKey" @select="emit('select', $event)"
                                       @delete="(n) => node.children.splice(node.children.indexOf(n), 1)" />
                        </template>
                    </draggable>
                </div>
                <p v-if="!node.children.length" class="block-node__empty-hint">No columns yet - add one below</p>

                <div class="block-node__add-row">
                    <button type="button" class="btn btn-sm btn-outline-secondary" @click.stop="addChild('column')">
                        <Plus width="12" height="12" /> Column
                    </button>
                </div>
            </template>

            <template v-else-if="node.type === 'cards'">
                <div class="block-node__cards-wrap" :class="{ 'is-empty': !node.children.length }"
                     :style="{ gridTemplateColumns: `repeat(auto-fit, minmax(${node.props.min_width || 240}px, 1fr))` }">
                    <draggable v-model="node.children" group="page-cards" item-key="_key" handle=".drag-handle"
                               tag="div" class="block-node__children block-node__children--cards">
                        <template #item="{ element }">
                            <BlockNode :node="element" :selected-key="selectedKey" @select="emit('select', $event)"
                                       @delete="(n) => node.children.splice(node.children.indexOf(n), 1)" />
                        </template>
                    </draggable>
                </div>
                <p v-if="!node.children.length" class="block-node__empty-hint">No cards yet - add one below</p>

                <div class="block-node__add-row">
                    <button type="button" class="btn btn-sm btn-outline-secondary" @click.stop="addChild('card')">
                        <Plus width="12" height="12" /> Card
                    </button>
                </div>
            </template>

            <template v-else-if="node.type === 'profile_card'">
                <div class="block-node__profile-card"
                     :style="node.props.image_url ? { backgroundImage: `url(${node.props.image_url})` } : {}">
                    <div v-if="!node.props.image_url" class="block-node__image-placeholder">No image selected</div>
                    <div class="block-node__profile-card-caption">
                        <div class="block-node__profile-card-name">{{ node.props.name || 'Full Name' }}</div>
                        <div class="block-node__profile-card-title">{{ node.props.title || 'Job Title' }}</div>
                    </div>
                </div>
            </template>

            <template v-else-if="node.type === 'tabs'">
                <draggable v-model="node.children" group="page-tabs" item-key="_key" handle=".drag-handle"
                           class="block-node__children block-node__children--tabs"
                           :class="{ 'is-empty': !node.children.length }">
                    <template #item="{ element }">
                        <BlockNode :node="element" :selected-key="selectedKey" @select="emit('select', $event)"
                                   @delete="(n) => node.children.splice(node.children.indexOf(n), 1)" />
                    </template>
                </draggable>
                <p v-if="!node.children.length" class="block-node__empty-hint">No tabs yet - add one below</p>

                <div class="block-node__add-row">
                    <button type="button" class="btn btn-sm btn-outline-secondary" @click.stop="addChild('tab')">
                        <Plus width="12" height="12" /> Tab
                    </button>
                </div>
            </template>

            <template v-else-if="node.type === 'timeline'">
                <draggable v-model="node.children" group="page-timeline" item-key="_key" handle=".drag-handle"
                           class="block-node__children block-node__children--timeline"
                           :class="{ 'is-empty': !node.children.length }">
                    <template #item="{ element }">
                        <BlockNode :node="element" :selected-key="selectedKey" @select="emit('select', $event)"
                                   @delete="(n) => node.children.splice(node.children.indexOf(n), 1)" />
                    </template>
                </draggable>
                <p v-if="!node.children.length" class="block-node__empty-hint">No timeline items yet - add one below</p>

                <div class="block-node__add-row">
                    <button type="button" class="btn btn-sm btn-outline-secondary" @click.stop="addChild('timeline_item')">
                        <Plus width="12" height="12" /> Timeline Item
                    </button>
                </div>
            </template>

            <template v-else-if="node.type === 'heading'">
                <div :style="{ textAlign: node.props.align || 'left' }">
                    <component :is="node.props.level || 'h2'"
                               :style="{ color: node.props.color || undefined, fontSize: node.props.font_size ? node.props.font_size + 'px' : undefined }"
                               class="mb-0">
                        {{ node.props.text || 'Heading text' }}
                    </component>
                    <span v-if="node.props.accent_underline" class="block-node__heading-accent"
                          :style="{ background: node.props.accent_color || '#f97316', width: (node.props.accent_width || 60) + 'px' }"></span>
                </div>
            </template>

            <template v-else-if="node.type === 'feature_item'">
                <div class="block-node__feature">
                    <span class="block-node__feature-bullet" :style="{ background: node.props.bullet_color || '#f97316' }"></span>
                    <div>
                        <div class="block-node__feature-title">{{ node.props.title || 'Feature title' }}</div>
                        <p class="block-node__feature-text mb-0">{{ node.props.text || 'Feature description' }}</p>
                    </div>
                </div>
            </template>

            <template v-else-if="node.type === 'divider'">
                <hr class="block-node__divider" />
            </template>

            <template v-else-if="node.type === 'paragraph'">
                <!-- Always a div: Tiptap's own HTML output already wraps
                     content in <p> tags, so a <p>/<span> wrapper here would
                     nest a block element inside itself (invalid HTML). -->
                <div :style="{ textAlign: node.props.align || 'left', color: node.props.color || undefined, fontSize: node.props.font_size ? node.props.font_size + 'px' : undefined, display: node.props.tag === 'span' ? 'inline-block' : undefined }"
                     class="block-node__rich-text"
                     v-html="node.props.text || 'Write something here...'">
                </div>
            </template>

            <template v-else-if="node.type === 'image'">
                <img v-if="node.props.url" :src="node.props.url" :alt="node.props.alt || ''" class="block-node__image" />
                <div v-else class="block-node__image-placeholder">No image selected</div>
            </template>

            <template v-else-if="node.type === 'button'">
                <span class="btn btn-sm" :class="node.props.style === 'outline' ? 'btn-outline-primary' : 'btn-primary'">
                    {{ node.props.label || 'Click here' }}
                </span>
            </template>
        </div>
    </div>
</template>

<style scoped lang="scss">
.block-node {
    border: 1px dashed #d7dbe0;
    border-radius: 8px;
    margin-bottom: 10px;
    background: #fff;
    cursor: pointer;

    &.is-selected {
        border-color: #5b6cff;
        box-shadow: 0 0 0 2px rgba(91, 108, 255, 0.15);
    }

    &.is-inactive {
        opacity: 0.55;
    }

    &--card {
        // Actual border sides/color/width come from inline styles (rootStyle)
        // so the admin's per-side config always wins - this just adds the
        // drop shadow that makes a Card read as a card.
        box-shadow: 0 1px 3px rgba(16, 24, 40, 0.06);
    }

    &--timeline-item {
        position: relative;

        &::before {
            content: '';
            position: absolute;
            left: -21px;
            top: 12px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #6b7280;
            border: 2px solid #fff;
            box-shadow: 0 0 0 1px #d7dbe0;
        }
    }

    &__header {
        display: flex;
        align-items: center;
        gap: 6px;
        padding: 6px 10px;
        border-bottom: 1px solid #eef0f2;
        font-size: 12px;
        color: #6b7280;
        background: #fafbfc;
        border-radius: 8px 8px 0 0;
    }

    &__handle {
        cursor: grab;
        display: inline-flex;
    }

    &__label {
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.03em;
    }

    &__tab-name {
        font-weight: 600;
        color: #374151;
        text-transform: none;
    }

    &__children--tabs {
        display: flex;
        flex-direction: column;
        gap: 4px;
    }

    &__row-wrap {
        display: flex;
        // nowrap - percentage flex-basis columns already sum to 100%, so
        // wrapping would just push the last column onto its own line the
        // moment `gap` pushes total width past the container (flex-shrink
        // handles narrow viewports instead).
        flex-wrap: nowrap;
        align-items: stretch;
        gap: 12px;

        &.is-empty {
            display: block;
        }
    }

    &__children--row {
        display: contents;
    }

    &__cards-wrap {
        display: grid;
        gap: 12px;
        align-items: stretch;

        &.is-empty {
            display: block;
        }
    }

    &__children--cards {
        display: contents;
    }

    &__children--timeline {
        position: relative;
        padding-left: 22px;

        &:not(.is-empty)::before {
            content: '';
            position: absolute;
            left: 5px;
            top: 14px;
            bottom: 14px;
            width: 2px;
            background: #d7dbe0;
        }
    }

    &__delete {
        margin-left: auto;
        color: #dc3545;
    }

    &__body {
        padding: 14px;
    }

    &__children {
        min-height: 12px;

        &.is-empty {
            min-height: 4px;
        }
    }

    &__empty-hint {
        color: #9ca3af;
        font-size: 13px;
        margin: 6px 0 10px;
    }

    &__add-row {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        margin-top: 8px;
    }

    &__image {
        max-width: 100%;
        max-height: 220px;
        border-radius: 6px;
    }

    &__image-placeholder {
        border: 1px dashed #d7dbe0;
        border-radius: 6px;
        padding: 30px;
        text-align: center;
        color: #9ca3af;
        font-size: 13px;
    }

    &__profile-card {
        position: relative;
        min-height: 240px;
        border-radius: 12px;
        background-size: cover;
        background-position: center;
        background-color: #e5e7eb;
        overflow: hidden;
        display: flex;
        align-items: flex-end;
    }

    &__profile-card-caption {
        width: 100%;
        padding: 14px 16px;
        background: linear-gradient(to top, rgba(0, 0, 0, 0.75), rgba(0, 0, 0, 0));
        color: #fff;
    }

    &__profile-card-name {
        font-weight: 700;
        font-size: 16px;
    }

    &__profile-card-title {
        font-size: 11px;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        opacity: 0.85;
    }

    &__feature {
        display: flex;
        gap: 10px;
    }

    &__feature-bullet {
        flex-shrink: 0;
        width: 8px;
        height: 8px;
        border-radius: 50%;
        margin-top: 8px;
    }

    &__feature-title {
        font-weight: 700;
    }

    &__feature-text {
        color: #6b7280;
        font-size: 14px;
    }

    &__divider {
        margin: 4px 0;
        border: none;
        border-top: 1px solid #e5e7eb;
    }

    // Content injected via v-html (Tiptap output) isn't reachable by scoped
    // CSS without :deep() - reset its default <p> spacing and give links a
    // visible style.
    &__rich-text {
        :deep(p) {
            margin: 0 0 0.5em;

            &:last-child {
                margin-bottom: 0;
            }
        }

        :deep(a) {
            color: #2563eb;
            text-decoration: underline;
        }
    }

    &__heading-accent {
        display: inline-block;
        height: 4px;
        border-radius: 2px;
        margin-top: 10px;
    }
}
</style>
