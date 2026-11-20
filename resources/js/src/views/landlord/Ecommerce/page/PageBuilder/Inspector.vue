<script setup>
import { ref } from "vue";
import Attachment from "@/views/global/input/uploader/Attachment.vue";
import MediaModal from "@/views/landlord/pos/mediaModal/MediaModal.vue";
import TiptapEditor from "@/views/global/input/types/TiptapEditor.vue";
import BorderControls from "./BorderControls.vue";
import MarginPaddingControls from "./MarginPaddingControls.vue";
import { BLOCK_TYPES } from "./blocks.js";

const props = defineProps({
    node: { type: Object, required: true },
})
const emit = defineEmits(['delete'])

const mediaModalActive = ref(false);
const activeImageField = ref('url');
const openMediaLibrary = (field = 'url') => {
    activeImageField.value = field;
    mediaModalActive.value = true;
}
const handleMediaSelected = (url) => {
    props.node.props[activeImageField.value] = url
    mediaModalActive.value = false
}
</script>

<template>
    <div class="inspector">
        <div class="inspector__header">
            <h6 class="mb-0">{{ BLOCK_TYPES[node.type].label }} settings</h6>
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" id="block-active" v-model="node.is_active" />
                <label class="form-check-label" for="block-active">Visible</label>
            </div>
        </div>

        <template v-if="node.type === 'tab'">
            <div class="mb-3">
                <label class="form-label">Tab label</label>
                <input type="text" class="form-control" v-model="node.props.label" />
            </div>
        </template>

        <template v-else-if="node.type === 'tabs'">
            <p class="text-muted mb-0">Add tabs below, then select a tab to edit its label and content.</p>
        </template>

        <template v-else-if="node.type === 'section'">
            <div class="mb-3">
                <label class="form-label">Background color</label>
                <input type="color" class="form-control form-control-color" v-model="node.props.background_color" />
            </div>
            <div class="mb-3">
                <label class="form-label">Padding</label>
                <select class="form-select" v-model="node.props.padding">
                    <option value="sm">Small</option>
                    <option value="md">Medium</option>
                    <option value="lg">Large</option>
                </select>
            </div>
        </template>

        <template v-else-if="node.type === 'heading'">
            <div class="mb-3">
                <label class="form-label">Text</label>
                <input type="text" class="form-control" v-model="node.props.text" />
            </div>
            <div class="mb-3">
                <label class="form-label">Level</label>
                <select class="form-select" v-model="node.props.level">
                    <option value="h1">H1</option>
                    <option value="h2">H2</option>
                    <option value="h3">H3</option>
                    <option value="h4">H4</option>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Align</label>
                <select class="form-select" v-model="node.props.align">
                    <option value="left">Left</option>
                    <option value="center">Center</option>
                    <option value="right">Right</option>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Color (blank = default)</label>
                <div class="d-flex gap-2">
                    <input type="color" class="form-control form-control-color"
                           :value="node.props.color || '#000000'" @input="node.props.color = $event.target.value" />
                    <button type="button" class="btn btn-sm btn-outline-secondary" @click="node.props.color = ''">Clear</button>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">Font size (px, blank = default)</label>
                <input type="number" min="8" class="form-control" v-model="node.props.font_size" placeholder="e.g. 32" />
            </div>
            <div class="form-check form-switch mb-2">
                <input class="form-check-input" type="checkbox" id="heading-accent" v-model="node.props.accent_underline" />
                <label class="form-check-label" for="heading-accent">Show accent underline</label>
            </div>
            <div v-if="node.props.accent_underline" class="d-flex gap-2 mb-3">
                <div>
                    <label class="form-label small mb-1">Color</label>
                    <input type="color" class="form-control form-control-color" v-model="node.props.accent_color" />
                </div>
                <div class="flex-grow-1">
                    <label class="form-label small mb-1">Width (px)</label>
                    <input type="number" min="10" class="form-control" v-model="node.props.accent_width" />
                </div>
            </div>
        </template>

        <template v-else-if="node.type === 'paragraph'">
            <div class="mb-3">
                <label class="form-label">Text</label>
                <TiptapEditor v-model="node.props.text" />
                <small class="text-muted">Select text to bold it, color it, turn it into a link, etc.</small>
            </div>
            <div class="mb-3">
                <label class="form-label">Tag</label>
                <select class="form-select" v-model="node.props.tag">
                    <option value="p">Paragraph (&lt;p&gt;, block)</option>
                    <option value="span">Span (&lt;span&gt;, inline)</option>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Color (blank = default)</label>
                <div class="d-flex gap-2">
                    <input type="color" class="form-control form-control-color"
                           :value="node.props.color || '#000000'" @input="node.props.color = $event.target.value" />
                    <button type="button" class="btn btn-sm btn-outline-secondary" @click="node.props.color = ''">Clear</button>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">Font size (px, blank = default)</label>
                <input type="number" min="8" class="form-control" v-model="node.props.font_size" placeholder="e.g. 16" />
            </div>
            <div class="mb-3">
                <label class="form-label">Align</label>
                <select class="form-select" v-model="node.props.align">
                    <option value="left">Left</option>
                    <option value="center">Center</option>
                    <option value="right">Right</option>
                </select>
            </div>
        </template>

        <template v-else-if="node.type === 'image'">
            <div class="mb-3">
                <Attachment label="Image" id="block-image" v-model="node.props.url" :show-input="false"
                            @click="openMediaLibrary" />
            </div>
            <div class="mb-3">
                <label class="form-label">Alt text</label>
                <input type="text" class="form-control" v-model="node.props.alt" />
            </div>
        </template>

        <template v-else-if="node.type === 'button'">
            <div class="mb-3">
                <label class="form-label">Label</label>
                <input type="text" class="form-control" v-model="node.props.label" />
            </div>
            <div class="mb-3">
                <label class="form-label">Link URL</label>
                <input type="text" class="form-control" v-model="node.props.url" />
            </div>
            <div class="mb-3">
                <label class="form-label">Style</label>
                <select class="form-select" v-model="node.props.style">
                    <option value="primary">Primary</option>
                    <option value="outline">Outline</option>
                </select>
            </div>
        </template>

        <template v-else-if="node.type === 'profile_card'">
            <div class="mb-3">
                <Attachment label="Photo" id="block-profile-image" v-model="node.props.image_url" :show-input="false"
                            @click="openMediaLibrary('image_url')" />
            </div>
            <div class="mb-3">
                <label class="form-label">Name</label>
                <input type="text" class="form-control" v-model="node.props.name" />
            </div>
            <div class="mb-3">
                <label class="form-label">Title</label>
                <input type="text" class="form-control" v-model="node.props.title" />
            </div>
        </template>

        <template v-else-if="node.type === 'feature_item'">
            <div class="mb-3">
                <label class="form-label">Title</label>
                <input type="text" class="form-control" v-model="node.props.title" />
            </div>
            <div class="mb-3">
                <label class="form-label">Text</label>
                <textarea class="form-control" rows="3" v-model="node.props.text"></textarea>
            </div>
            <div class="mb-3">
                <label class="form-label">Bullet color</label>
                <input type="color" class="form-control form-control-color" v-model="node.props.bullet_color" />
            </div>
        </template>

        <template v-else-if="node.type === 'divider'">
            <p class="text-muted mb-0">A plain horizontal divider line - no settings.</p>
        </template>

        <template v-else-if="node.type === 'cards'">
            <div class="mb-3">
                <label class="form-label">Minimum card width (px)</label>
                <input type="number" min="120" step="10" class="form-control" v-model="node.props.min_width" />
                <small class="text-muted">Cards wrap to a new row automatically once they can't fit at this width.</small>
            </div>
        </template>

        <template v-else-if="node.type === 'card'">
            <div class="mb-3">
                <label class="form-label">Background color</label>
                <input type="color" class="form-control form-control-color" v-model="node.props.background_color" />
            </div>
        </template>

        <template v-else-if="node.type === 'row'">
            <p class="text-muted mb-0">Add columns below, then select a column to set its width and content.</p>
        </template>

        <template v-else-if="node.type === 'column'">
            <div class="mb-3">
                <label class="form-label">Width % (blank = equal share)</label>
                <input type="number" min="1" max="100" class="form-control" v-model="node.props.width" placeholder="e.g. 50" />
            </div>
        </template>

        <template v-else-if="node.type === 'timeline'">
            <p class="text-muted mb-0">Add timeline items below - each renders as a dot on the timeline with its own content.</p>
        </template>

        <template v-else-if="node.type === 'timeline_item'">
            <p class="text-muted mb-0">Add a Heading and/or Paragraph below for this milestone's content.</p>
        </template>

        <hr class="my-3" />
        <MarginPaddingControls :node="node" />
        <BorderControls :node="node" />

        <button type="button" class="btn btn-sm btn-outline-danger w-100 mt-2" @click="emit('delete', node)">
            Delete block
        </button>

        <media-modal v-if="mediaModalActive" modal-id="block-media-modal" @selected="handleMediaSelected"
                     @close="mediaModalActive = false" />
    </div>
</template>

<style scoped lang="scss">
.inspector {
    &__header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 16px;
    }
}
</style>
