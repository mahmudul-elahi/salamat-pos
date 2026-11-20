<script setup>
import {ref} from "vue";
import {EmojiSatisfied} from "@iconoir/vue";

const emit = defineEmits(["select"]);

const isOpen = ref(false);

const emojis = [
    "😀", "😁", "😂", "🤣", "😊", "😍", "😘", "😉", "😎", "🤩",
    "🙂", "🙃", "😇", "😅", "😢", "😭", "😡", "😱", "🤔", "🙄",
    "😴", "🤗", "🤝", "👍", "👎", "👏", "🙏", "💪", "👌", "✌️",
    "❤️", "🧡", "💛", "💚", "💙", "💜", "🖤", "💔", "💯", "🔥",
    "🎉", "🎊", "✅", "❌", "⚠️", "❓", "❗", "⭐", "📌", "📎",
    "📷", "🎁", "☕", "🍕", "🚀", "⏰", "📅", "💰", "🛒", "📦",
];

function selectEmoji(emoji) {
    emit("select", emoji);
    isOpen.value = false;
}
</script>

<template>
    <div class="emoji-picker position-relative">
        <button type="button" class="btn btn-sm btn-icon" title="Emoji" @click="isOpen = !isOpen">
            <EmojiSatisfied width="20" height="20" />
        </button>

        <div v-if="isOpen" class="emoji-popover shadow rounded p-2 bg-white">
            <button
                v-for="emoji in emojis"
                :key="emoji"
                type="button"
                class="emoji-btn"
                @click="selectEmoji(emoji)"
            >
                {{ emoji }}
            </button>
        </div>
    </div>
</template>

<style scoped lang="scss">
.emoji-popover {
    position: absolute;
    bottom: 100%;
    left: 0;
    z-index: 50;
    width: 260px;
    display: grid;
    grid-template-columns: repeat(8, 1fr);
    max-height: 220px;
    overflow-y: auto;
}

.emoji-btn {
    background: none;
    border: none;
    font-size: 1.1rem;
    padding: 4px;
    cursor: pointer;
    border-radius: 4px;

    &:hover {
        background: rgba(0, 0, 0, 0.06);
    }
}
</style>
