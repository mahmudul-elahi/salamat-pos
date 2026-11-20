<script setup>
defineProps({
    node: { type: Object, required: true },
})
</script>

<template>
    <div class="spacing-controls mb-3">
        <label class="form-label d-block">Margin (px, outside space)</label>
        <div class="d-flex gap-2 mb-2">
            <input type="number" min="0" class="form-control" v-model="node.props.margin_top" placeholder="Top" />
            <input type="number" min="0" class="form-control" v-model="node.props.margin_right" placeholder="Right" />
            <input type="number" min="0" class="form-control" v-model="node.props.margin_bottom" placeholder="Bottom" />
            <input type="number" min="0" class="form-control" v-model="node.props.margin_left" placeholder="Left" />
        </div>

        <label class="form-label d-block">Padding (px, inside space)</label>
        <div class="d-flex gap-2">
            <input type="number" min="0" class="form-control" v-model="node.props.padding_top" placeholder="Top" />
            <input type="number" min="0" class="form-control" v-model="node.props.padding_right" placeholder="Right" />
            <input type="number" min="0" class="form-control" v-model="node.props.padding_bottom" placeholder="Bottom" />
            <input type="number" min="0" class="form-control" v-model="node.props.padding_left" placeholder="Left" />
        </div>
    </div>
</template>
