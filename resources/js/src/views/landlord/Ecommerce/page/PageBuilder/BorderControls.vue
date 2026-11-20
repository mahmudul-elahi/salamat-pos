<script setup>
defineProps({
    node: { type: Object, required: true },
})
</script>

<template>
    <div class="border-controls mb-3">
        <label class="form-label d-block">Border sides</label>
        <div class="d-flex flex-wrap gap-3 mb-2">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" id="border-top" v-model="node.props.border_top" />
                <label class="form-check-label" for="border-top">Top</label>
            </div>
            <div class="form-check">
                <input class="form-check-input" type="checkbox" id="border-right" v-model="node.props.border_right" />
                <label class="form-check-label" for="border-right">Right</label>
            </div>
            <div class="form-check">
                <input class="form-check-input" type="checkbox" id="border-bottom" v-model="node.props.border_bottom" />
                <label class="form-check-label" for="border-bottom">Bottom</label>
            </div>
            <div class="form-check">
                <input class="form-check-input" type="checkbox" id="border-left" v-model="node.props.border_left" />
                <label class="form-check-label" for="border-left">Left</label>
            </div>
        </div>
        <div class="d-flex gap-2">
            <div>
                <label class="form-label small mb-1">Color</label>
                <input type="color" class="form-control form-control-color" v-model="node.props.border_color" />
            </div>
            <div class="flex-grow-1">
                <label class="form-label small mb-1">Width (px)</label>
                <input type="number" min="1" class="form-control" v-model="node.props.border_width" />
            </div>
        </div>
    </div>
</template>
