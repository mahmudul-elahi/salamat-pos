<script setup>
import { watch, onBeforeUnmount, ref, watchEffect } from "vue";
import { Editor, EditorContent } from "@tiptap/vue-3";
import StarterKit from "@tiptap/starter-kit";
import Heading from "@tiptap/extension-heading";
import Link from "@tiptap/extension-link";
import Highlight from '@tiptap/extension-highlight';
import TextAlign from '@tiptap/extension-text-align';
import { TextStyle } from '@tiptap/extension-text-style'
import { Table } from "@tiptap/extension-table";
import { TableRow } from "@tiptap/extension-table-row";
import { TableCell } from "@tiptap/extension-table-cell";
import { TableHeader } from "@tiptap/extension-table-header";
import CodeBlock from "@tiptap/extension-code-block";

const props = defineProps({
    modelValue: {
        type: String,
        default: "",
    },
    editable: {
        type: Boolean,
        default: true,
    },
});

const emit = defineEmits(["update:modelValue"]);
const currentFontSize = ref("");

const decodeHTML = (html) => {
    if (!html) return "";
    const txt = document.createElement("textarea");
    txt.innerHTML = html;
    return txt.value;
};

const editor = new Editor({
    content: decodeHTML(props.modelValue),
    editable: props.editable,
    extensions: [
        StarterKit,
        TextStyle.extend({
            addAttributes() {
                return {
                    fontSize: {
                        default: null,
                        // This parses the font-size from the HTML/inline style
                        parseHTML: element => element.style.fontSize,
                        // This renders the font-size back into the HTML
                        renderHTML: attributes => {
                            if (!attributes.fontSize) {
                                return {}
                            }
                            return {
                                style: `font-size: ${attributes.fontSize}`,
                            }
                        },
                    },
                    color: {
                        default: null,
                        parseHTML: element => element.style.color,
                        renderHTML: attributes => {
                            if (!attributes.color) return {};
                            return { style: `color: ${attributes.color}` };
                        },
                    },
                    fontFamily: {
                        default: null,
                        parseHTML: element => element.style.fontFamily?.replace(/['"]/g, ''),
                        renderHTML: attributes => {
                            if (!attributes.fontFamily) return {};
                            return { style: `font-family: ${attributes.fontFamily}` };
                        },
                    },
                    lineHeight: {
                        default: null,
                        parseHTML: element => element.style.lineHeight,
                        renderHTML: attributes => {
                            if (!attributes.lineHeight) return {};
                            return { style: `line-height: ${attributes.lineHeight}` };
                        },
                    },
                }
            },
        }),
        Heading.configure({ levels: [1, 2, 3, 4, 5, 6] }),
        Link.configure({ openOnClick: false, autolink: true }),
        Highlight,
        Table.configure({ resizable: true }),
        TableRow,
        TableHeader,
        TableCell,
        CodeBlock,
        TextAlign.configure({ types: ['heading', 'paragraph', 'bulletList', 'orderedList'] }),
    ],
    onUpdate: ({ editor }) => {
        emit("update:modelValue", editor.getHTML());
    },
});

watch(
    () => props.modelValue,
    (value) => {
        if (value !== editor.getHTML()) {
            editor.commands.setContent(decodeHTML(value), false);
        }
    }
);
// const currentFontSize = computed(() => {
//   const attrs = editor.getAttributes('textStyle');
//   return attrs.fontSize || "";
// });

watchEffect(() => {
    const attrs = editor.getAttributes('textStyle');
    currentFontSize.value = attrs.fontSize || "";
});

function onFontSizeChange() {
    const size = currentFontSize.value;
    if (size) {
        editor.chain().focus().setMark('textStyle', { fontSize: size }).run();
    } else {
        editor.chain().focus().updateAttributes('textStyle', { fontSize: null }).run();
    }
}

onBeforeUnmount(() => {
    editor.destroy();
});
</script>

<template>
    <div class="tiptap">
        <div class="control-group" role="toolbar" aria-label="Editor toolbar">
            <div class="button-group">
                <!-- Bold -->
                <button
                    type="button"
                    class="toolbar-btn"
                    :class="{ active: editor.isActive('bold') }"
                    @mousedown.prevent
                    @click="editor.chain().focus().toggleBold().run()"
                    title="Bold (Ctrl+B)"
                    aria-pressed="editor.isActive('bold')">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M6 4h7a4 4 0 0 1 0 8H6z" />
                        <path d="M6 12h8a4 4 0 0 1 0 8H6z" />
                    </svg>
                </button>

                <!-- Italic -->
                <button
                    type="button"
                    class="toolbar-btn"
                    :class="{ active: editor.isActive('italic') }"
                    @mousedown.prevent
                    @click="editor.chain().focus().toggleItalic().run()"
                    title="Italic (Ctrl+I)"
                    aria-pressed="editor.isActive('italic')">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="19" y1="4" x2="10" y2="4" />
                        <line x1="14" y1="20" x2="5" y2="20" />
                        <line x1="15" y1="4" x2="9" y2="20" />
                    </svg>
                </button>

                <!-- Font size -->
                <select v-model="currentFontSize" @change="onFontSizeChange" class="font-size-dropdown">
                    <option value="">Default</option>
                    <option value="12px">12px</option>
                    <option value="14px">14px</option>
                    <option value="16px">16px</option>
                    <option value="18px">18px</option>
                    <option value="24px">24px</option>
                    <option value="28px">28px</option>
                    <option value="32px">32px</option>
                    <option value="36px">36px</option>
                    <option value="40px">40px</option>
                    <option value="44px">44px</option>
                    <option value="48px">48px</option>

                </select>
                <select
                    @change="editor.chain().focus().setMark('textStyle', { fontFamily: $event.target.value }).run()"
                    :value="editor.getAttributes('textStyle').fontFamily || ''"
                    class="font-size-dropdown">
                    <option value="">Default Font</option>
                    <option value="Arial">Arial</option>
                    <option value="Courier New">Courier New</option>
                    <option value="Georgia">Georgia</option>
                    <option value="Times New Roman">Times New Roman</option>
                    <option value="Verdana">Verdana</option>
                </select>

                <select
                    @change="editor.chain().focus().setMark('textStyle', { lineHeight: $event.target.value }).run()"
                    :value="editor.getAttributes('textStyle').lineHeight || ''"
                    class="font-size-dropdown">
                    <option value="">Default Height</option>
                    <option value="1">Single (1.0)</option>
                    <option value="1.15">1.15</option>
                    <option value="1.5">1.5</option>
                    <option value="2">Double (2.0)</option>
                    <option value="2.5">2.5</option>
                </select>
                <div class="color-picker-container">
                    <input
                        type="color"
                        class="color-input toolbar-btn"
                        @input="editor.chain().focus().setMark('textStyle', { color: $event.target.value }).run()"
                        :value="editor.getAttributes('textStyle').color || '#000000'"
                        title="Text Color" />
                </div>

                <!-- Strike -->
                <button
                    type="button"
                    class="toolbar-btn"
                    :class="{ active: editor.isActive('strike') }"
                    @mousedown.prevent
                    @click="editor.chain().focus().toggleStrike().run()"
                    title="Strike (Ctrl+Shift+S)"
                    aria-pressed="editor.isActive('strike')">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="4" y1="12" x2="20" y2="12" />
                        <path d="M16 6a4 4 0 0 0-8 0" />
                        <path d="M16 18a4 4 0 0 1-8 0" />
                    </svg>
                </button>

                <!-- Code -->
                <button
                    type="button"
                    class="toolbar-btn"
                    :class="{ active: editor.isActive('code') }"
                    @mousedown.prevent
                    @click="editor.chain().focus().toggleCode().run()"
                    title="Code (Ctrl+E)"
                    aria-pressed="editor.isActive('code')">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="16 18 22 12 16 6" />
                        <polyline points="8 6 2 12 8 18" />
                    </svg>
                </button>

                <!-- Highlight -->
                <button
                    type="button"
                    class="toolbar-btn"
                    :class="{ active: editor.isActive('highlight') }"
                    @mousedown.prevent
                    @click="editor.chain().focus().toggleHighlight().run()"
                    title="Highlight"
                    aria-pressed="editor.isActive('highlight')">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="2" y="11" width="20" height="6" rx="2" ry="2" />
                        <path d="M6 11V7a4 4 0 0 1 8 0v4" />
                    </svg>
                </button>

                <!-- Text Align Left -->
                <button
                    type="button"
                    class="toolbar-btn"
                    :class="{ active: editor.isActive({ textAlign: 'left' }) }"
                    @mousedown.prevent
                    @click="editor.chain().focus().setTextAlign('left').run()"
                    title="Align Left"
                    aria-pressed="editor.isActive({ textAlign: 'left' })">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="3" y1="6" x2="21" y2="6" />
                        <line x1="3" y1="12" x2="15" y2="12" />
                        <line x1="3" y1="18" x2="21" y2="18" />
                    </svg>
                </button>

                <!-- Text Align Center -->
                <button
                    type="button"
                    class="toolbar-btn"
                    :class="{ active: editor.isActive({ textAlign: 'center' }) }"
                    @mousedown.prevent
                    @click="editor.chain().focus().setTextAlign('center').run()"
                    title="Align Center"
                    aria-pressed="editor.isActive({ textAlign: 'center' })">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="6" y1="6" x2="18" y2="6" />
                        <line x1="3" y1="12" x2="21" y2="12" />
                        <line x1="6" y1="18" x2="18" y2="18" />
                    </svg>
                </button>

                <!-- Text Align Right -->
                <button
                    type="button"
                    class="toolbar-btn"
                    :class="{ active: editor.isActive({ textAlign: 'right' }) }"
                    @mousedown.prevent
                    @click="editor.chain().focus().setTextAlign('right').run()"
                    title="Align Right"
                    aria-pressed="editor.isActive({ textAlign: 'right' })">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="3" y1="6" x2="21" y2="6" />
                        <line x1="9" y1="12" x2="21" y2="12" />
                        <line x1="3" y1="18" x2="21" y2="18" />
                    </svg>
                </button>

                <!-- Text Align Justify -->
                <button
                    type="button"
                    class="toolbar-btn"
                    :class="{ active: editor.isActive({ textAlign: 'justify' }) }"
                    @mousedown.prevent
                    @click="editor.chain().focus().setTextAlign('justify').run()"
                    title="Justify"
                    aria-pressed="editor.isActive({ textAlign: 'justify' })">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="3" y1="6" x2="21" y2="6" />
                        <line x1="3" y1="12" x2="21" y2="12" />
                        <line x1="3" y1="18" x2="21" y2="18" />
                    </svg>
                </button>

                <!-- Clear marks -->
                <button
                    type="button"
                    class="toolbar-btn"
                    @mousedown.prevent
                    @click="editor.chain().focus().unsetAllMarks().run()"
                    title="Clear Marks">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="18" y1="6" x2="6" y2="18" />
                        <line x1="6" y1="6" x2="18" y2="18" />
                    </svg>
                </button>

                <!-- Clear nodes -->
                <button
                    type="button"
                    class="toolbar-btn"
                    @mousedown.prevent
                    @click="editor.chain().focus().clearNodes().run()"
                    title="Clear Nodes">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="4" y="4" width="16" height="16" rx="2" />
                        <line x1="4" y1="4" x2="20" y2="20" />
                    </svg>
                </button>

                <!-- Paragraph -->
                <button
                    type="button"
                    class="toolbar-btn"
                    :class="{ active: editor.isActive('paragraph') }"
                    @mousedown.prevent
                    @click="editor.chain().focus().setParagraph().run()"
                    title="Paragraph"
                    aria-pressed="editor.isActive('paragraph')">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M5 12h14" />
                        <path d="M5 6h14" />
                        <path d="M5 18h14" />
                    </svg>
                </button>

                <!-- Headings H1 to H6 -->
                <template v-for="level in 6" :key="'h' + level">
                    <button
                        type="button"
                        class="toolbar-btn"
                        :class="{ active: editor.isActive('heading', { level }) }"
                        @mousedown.prevent
                        @click="editor.chain().focus().toggleHeading({ level }).run()"
                        :title="'Heading ' + level"
                        :aria-pressed="editor.isActive('heading', { level })">
                        <span style="font-weight: 700; font-size: 0.9rem;">H{{ level }}</span>
                    </button>
                </template>

                <!-- Bullet list -->
                <button type="button" class="toolbar-btn" :class="{ active: editor.isActive('bulletList') }" @mousedown.prevent @click="editor.chain().focus().toggleBulletList().run()" title="Bullet List" aria-pressed="editor.isActive('bulletList')">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="5" cy="12" r="2" />
                        <line x1="10" y1="12" x2="21" y2="12" />
                        <circle cx="5" cy="6" r="2" />
                        <line x1="10" y1="6" x2="21" y2="6" />
                        <circle cx="5" cy="18" r="2" />
                        <line x1="10" y1="18" x2="21" y2="18" />
                    </svg>
                </button>

                <!-- Ordered list -->
                <button type="button" class="toolbar-btn" :class="{ active: editor.isActive('orderedList') }" @mousedown.prevent @click="editor.chain().focus().toggleOrderedList().run()" title="Ordered List" aria-pressed="editor.isActive('orderedList')">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <text x="4" y="7" font-size="7" font-weight="bold">1.</text>
                        <line x1="10" y1="6" x2="21" y2="6" />
                        <text x="4" y="13" font-size="7" font-weight="bold">2.</text>
                        <line x1="10" y1="12" x2="21" y2="12" />
                        <text x="4" y="19" font-size="7" font-weight="bold">3.</text>
                        <line x1="10" y1="18" x2="21" y2="18" />
                    </svg>
                </button>

                <!-- Table insert -->
                <button type="button" class="toolbar-btn" @mousedown.prevent @click="editor.chain().focus().insertTable({ rows: 3, cols: 3, withHeaderRow: true }).run()" title="Insert Table">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round" stroke-linecap="round">
                        <rect x="3" y="3" width="18" height="18" rx="2" ry="2" />
                        <line x1="3" y1="9" x2="21" y2="9" />
                        <line x1="3" y1="15" x2="21" y2="15" />
                        <line x1="9" y1="3" x2="9" y2="21" />
                        <line x1="15" y1="3" x2="15" y2="21" />
                    </svg>
                </button>

                <!-- Delete Table -->
                <button type="button" class="toolbar-btn" :disabled="!editor.isActive('table')" @mousedown.prevent @click="editor.chain().focus().deleteTable().run()" title="Delete Table">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round" stroke-linecap="round">
                        <rect x="3" y="3" width="18" height="18" rx="2" ry="2" />
                        <line x1="3" y1="3" x2="21" y2="21" />
                    </svg>
                </button>
                <!-- Add Row Before -->
                <button type="button" class="toolbar-btn" :disabled="!editor.isActive('table')" @mousedown.prevent @click="editor.chain().focus().addRowBefore().run()" title="Add Row Before" aria-pressed="false">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 3v6" />
                        <path d="M8 9h8" />
                        <rect x="4" y="11" width="16" height="10" rx="2" ry="2" />
                    </svg>
                </button>

                <!-- Add Row After -->
                <button type="button" class="toolbar-btn" :disabled="!editor.isActive('table')" @mousedown.prevent @click="editor.chain().focus().addRowAfter().run()" title="Add Row After" aria-pressed="false">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 21v-6" />
                        <path d="M8 15h8" />
                        <rect x="4" y="3" width="16" height="10" rx="2" ry="2" />
                    </svg>
                </button>

                <!-- Delete Row -->
                <button type="button" class="toolbar-btn" :disabled="!editor.isActive('table')" @mousedown.prevent @click="editor.chain().focus().deleteRow().run()" title="Delete Row" aria-pressed="false">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="7" width="18" height="10" rx="2" ry="2" />
                        <line x1="3" y1="12" x2="21" y2="12" />
                        <path d="M9 7V5a2 2 0 0 1 4 0v2" />
                    </svg>
                </button>
                <!-- Add Column Before -->
                <button type="button" class="toolbar-btn" :disabled="!editor.isActive('table')" @mousedown.prevent @click="editor.chain().focus().addColumnBefore().run()" title="Add Column Before" aria-pressed="false">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3 12h6" />
                        <path d="M9 8v8" />
                        <rect x="11" y="4" width="10" height="16" rx="2" ry="2" />
                    </svg>
                </button>
                <!-- Add Column After -->
                <button type="button" class="toolbar-btn" :disabled="!editor.isActive('table')" @mousedown.prevent @click="editor.chain().focus().addColumnAfter().run()" title="Add Column After" aria-pressed="false">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 12h-6" />
                        <path d="M15 8v8" />
                        <rect x="3" y="4" width="10" height="16" rx="2" ry="2" />
                    </svg>
                </button>

                <!-- Delete Column -->
                <button type="button" class="toolbar-btn" :disabled="!editor.isActive('table')" @mousedown.prevent @click="editor.chain().focus().deleteColumn().run()" title="Delete Column" aria-pressed="false">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="7" y="3" width="10" height="18" rx="2" ry="2" />
                        <line x1="12" y1="3" x2="12" y2="21" />
                        <path d="M7 9H5a2 2 0 0 0 0 4h2" />
                    </svg>
                </button>


                <!-- Undo -->
                <button type="button" class="toolbar-btn" @mousedown.prevent @click="editor.chain().focus().undo().run()" title="Undo (Ctrl+Z)">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round" stroke-linecap="round">
                        <path d="M3 7v6h6" />
                        <path d="M3 13a9 9 0 1 0 9-9" />
                    </svg>
                </button>

                <!-- Redo -->
                <button type="button" class="toolbar-btn" @mousedown.prevent @click="editor.chain().focus().redo().run()" title="Redo (Ctrl+Y)">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round" stroke-linecap="round">
                        <path d="M21 7v6h-6" />
                        <path d="M21 13a9 9 0 1 1-9-9" />
                    </svg>
                </button>
            </div>
        </div>

        <EditorContent :editor="editor" class="tiptap-editor" />
    </div>
</template>

<style lang="scss" scoped>
.tiptap {
    :first-child {
        margin-top: 0;
    }

    .color-input {
        width: 32px;
        height: 32px;
        padding: 0;
        border: none;
        background: none;
        cursor: pointer;
        vertical-align: middle;
    }

    /* Optional: remove the default border/padding in Chrome */
    .color-input::-webkit-color-swatch-wrapper {
        padding: 0;
    }

    .color-input::-webkit-color-swatch {
        border: 1px solid #ccc;
        border-radius: 4px;
    }

    .control-group {
        position: sticky;
        top: 0;
        z-index: 20;
        background-color: #fff;
        border-bottom: 1px solid #ddd;
        padding: 0.5rem 1rem;
        box-shadow: 0 2px 5px rgb(0 0 0 / 0.1);
        display: flex;
        align-items: center;

        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }

    .button-group {
        display: flex;
        gap: 0.4rem;
        flex-wrap: wrap;
    }

    .toolbar-btn {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 36px;
        height: 36px;
        background: #f9f9f9;
        border: 1px solid #ccc;
        border-radius: 6px;
        cursor: pointer;
        transition: background-color 0.15s ease, border-color 0.15s ease;
        color: #444;
        padding: 0;
        user-select: none;

        svg {
            width: 18px;
            height: 18px;
        }

        &:hover:not(.active):not(:disabled) {
            background-color: #e7f0ff;
            border-color: #3a7bfd;
            color: #3a7bfd;
        }

        &.active {
            background-color: #3a7bfd;
            border-color: #3a7bfd;
            color: white;
        }

        &:disabled {
            opacity: 0.4;
            cursor: not-allowed;
        }
    }
}

/* Editor content styles */
.tiptap-editor {
    max-height: 400px;
    overflow-y: auto;
    padding: 1rem;
    border: 1px solid #ddd;
    border-top: none;
    border-radius: 0 0 6px 6px;
    background-color: #fff;
    font-family: system-ui, sans-serif;
    font-size: 1rem;
    line-height: 1.5;


    /* Lists */
    ul {
        list-style-type: disc;
        padding-left: 1.5rem;
        margin: 1.25rem 1rem 1.25rem 0.4rem;
    }

    ol {
        list-style-type: decimal;
        padding-left: 1.5rem;
        margin: 1.25rem 1rem 1.25rem 0.4rem;
    }

    ul li p,
    ol li p {
        margin-top: 0.25em;
        margin-bottom: 0.25em;
    }

    /* Headings */
    h1,
    h2,
    h3,
    h4,
    h5,
    h6 {
        line-height: 1.1;
        margin-top: 2.5rem;
        word-wrap: break-word;
    }

    h1,
    h2 {
        margin-top: 3.5rem;
        margin-bottom: 1.5rem;
    }

    h1 {
        font-size: 1.4rem;
    }

    h2 {
        font-size: 1.2rem;
    }

    h3 {
        font-size: 1.1rem;
    }

    h4,
    h5,
    h6 {
        font-size: 1rem;
    }

    /* Code */
    code {
        background-color: #e0d7ff;
        border-radius: 0.4rem;
        color: #000;
        font-size: 0.85rem;
        padding: 0.25em 0.3em;
    }

    pre {
        background: #222;
        border-radius: 0.5rem;
        color: #eee;
        font-family: "JetBrainsMono", monospace;
        margin: 1.5rem 0;
        padding: 0.75rem 1rem;

        code {
            background: none;
            color: inherit;
            font-size: 0.8rem;
            padding: 0;
        }
    }

    /* Blockquote */
    blockquote {
        border-left: 3px solid #bbb;
        margin: 1.5rem 0;
        padding-left: 1rem;
        color: #555;
        font-style: italic;
    }

    /* Horizontal Rule */
    hr {
        border: none;
        border-top: 1px solid #ccc;
        margin: 2rem 0;
        display: none;
    }
}

.tiptap-editor ul,
.tiptap-editor ol,
.tiptap-editor li {
    text-align: left;
    /* default */
}

.tiptap-editor ul.align-center,
.tiptap-editor ol.align-center,
.tiptap-editor li.align-center {
    text-align: center;
}

.tiptap-editor ul.align-right,
.tiptap-editor ol.align-right,
.tiptap-editor li.align-right {
    text-align: right;
}

.tiptap-editor ul.align-justify,
.tiptap-editor ol.align-justify,
.tiptap-editor li.align-justify {
    text-align: justify;
}

::v-deep(.tiptap-editor table) {
    width: 100% !important;
    border-collapse: collapse !important;
    margin: 1rem 0 !important;
    font-size: 1rem !important;
}

::v-deep(.tiptap-editor th),
::v-deep(.tiptap-editor td) {
    border: 1px solid #ccc !important;
    padding: 0.5rem 0.75rem !important;
    text-align: left !important;
}

::v-deep(.tiptap-editor th) {
    background-color: #f0f0f0 !important;
    font-weight: 600 !important;
}

::v-deep(.tiptap-editor ul) {
    list-style-type: disc;
    padding-left: 1.5rem;
    margin: 1.25rem 1rem 1.25rem 0.4rem;
}

::v-deep(.tiptap-editor ol) {
    list-style-type: decimal;
    padding-left: 1.5rem;
    margin: 1.25rem 1rem 1.25rem 0.4rem;
}

.font-size-dropdown {
    background: none;
    border: 1px solid #ccc;
    border-radius: 4px;
    padding: 4px 8px;
    font-size: 14px;
    color: #333;
    cursor: pointer;
}
</style>
