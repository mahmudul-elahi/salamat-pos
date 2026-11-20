<script>
import { onMounted } from "vue"
import { Tooltip } from "bootstrap"
import { InfoCircle } from "@iconoir/vue"

export default {
    name: "InputLabel",
    components: {
        InfoCircle,
    },

    props: {
        labelId: {
            type: String,
            require: true
        },
        label: {
            type: String,
            require: true
        },
        labelClass: {
            type: String,
            default: 'mb-2'
        },
        required: {
            type: Boolean,
            default: false
        },
        hintTitle: {
            type: String
        }
    },

    setup() {
        onMounted(() => {
            let tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
            tooltipTriggerList.map((tooltipTriggerEl) => new Tooltip(tooltipTriggerEl))
        })
    }

}
</script>

<template>
    <label :for="labelId" :class="labelClass">
        {{ label }} <span v-if="required" class="text-danger">*</span>
        <span
            v-if="hintTitle"
            class="ms-1 cursor-pointer"
            data-bs-toggle="tooltip"
            data-bs-html="true"
            data-bs-placement="top"
            :title="hintTitle">
            <InfoCircle />
        </span>

    </label>
</template>

<style scoped lang="scss"></style>