// Block type registry for the page section-builder.
// Keep `containerTypes` here in sync with PageBlockController::$containerTypes
// (app/Http/Controllers/Pos/Page/PageBlockController.php) - it decides
// which types are allowed to hold children on the backend too.

// Shared per-side border shape used by Section, Column, and Card. `all`
// pre-checks every side (used by Card, which is a border-y block by nature).
const defaultBorderProps = (all = false) => ({
    border_top: all,
    border_right: all,
    border_bottom: all,
    border_left: all,
    border_color: '#e5e7eb',
    border_width: 1,
})

export const BLOCK_TYPES = {
    section: {
        label: 'Section',
        isContainer: true,
        defaultProps: () => ({ background_color: '#ffffff', padding: 'lg', ...defaultBorderProps() }),
    },
    tabs: {
        label: 'Tabs',
        isContainer: true,
        onlyAllows: ['tab'],
        defaultProps: () => ({}),
    },
    tab: {
        label: 'Tab',
        isContainer: true,
        defaultProps: () => ({ label: 'New Tab' }),
    },
    heading: {
        label: 'Heading',
        isContainer: false,
        // accent_underline: a short decorative bar under the start of the
        // heading (not a full-width text-decoration underline).
        defaultProps: () => ({ text: 'Heading text', level: 'h2', align: 'left', color: '', font_size: '', accent_underline: false, accent_color: '#f97316', accent_width: 60 }),
    },
    paragraph: {
        label: 'Paragraph',
        isContainer: false,
        // tag: 'p' (block) or 'span' (inline) - covers the "span tag" case.
        defaultProps: () => ({ text: 'Write something here...', align: 'left', tag: 'p', color: '', font_size: '' }),
    },
    image: {
        label: 'Image',
        isContainer: false,
        defaultProps: () => ({ url: '', alt: '' }),
    },
    button: {
        label: 'Button',
        isContainer: false,
        defaultProps: () => ({ label: 'Click here', url: '#', style: 'primary' }),
    },
    profile_card: {
        label: 'Profile Card',
        isContainer: false,
        defaultProps: () => ({ image_url: '', name: 'Full Name', title: 'Job Title' }),
    },
    feature_item: {
        label: 'Feature Item',
        isContainer: false,
        defaultProps: () => ({ title: 'Feature title', text: 'Feature description', bullet_color: '#f97316' }),
    },
    divider: {
        label: 'Divider',
        isContainer: false,
        defaultProps: () => ({}),
    },
    row: {
        label: 'Row',
        isContainer: true,
        onlyAllows: ['column'],
        defaultProps: () => ({}),
    },
    column: {
        label: 'Column',
        isContainer: true,
        // Empty width = equal flex share with sibling columns.
        defaultProps: () => ({ width: '', ...defaultBorderProps() }),
    },
    timeline: {
        label: 'Timeline',
        isContainer: true,
        onlyAllows: ['timeline_item'],
        defaultProps: () => ({}),
    },
    timeline_item: {
        label: 'Timeline Item',
        isContainer: true,
        defaultProps: () => ({}),
    },
    cards: {
        label: 'Cards',
        isContainer: true,
        onlyAllows: ['card'],
        // min_width controls how many cards fit per row before wrapping (CSS grid auto-fit).
        defaultProps: () => ({ min_width: 240 }),
    },
    card: {
        label: 'Card',
        isContainer: true,
        // Cards ship with all 4 sides on by default (that's what makes it a "card").
        defaultProps: () => ({ background_color: '#ffffff', ...defaultBorderProps(true) }),
    },
}

// Types an admin can drop inside a Section, Tab, Column, Timeline Item, or Card.
export const CHILD_BLOCK_TYPES = ['section', 'heading', 'paragraph', 'image', 'button', 'profile_card', 'feature_item', 'divider', 'row', 'timeline', 'cards']

let uidCounter = 0
export const nextUid = () => `new-${Date.now()}-${uidCounter++}`

export const createBlockNode = (type) => ({
    _key: nextUid(),
    id: null,
    type,
    props: BLOCK_TYPES[type].defaultProps(),
    is_active: true,
    children: [],
})

// Attach a stable client-side `_key` to every node fetched from the API
// (used for :key / draggable item-key - the numeric `id` alone isn't
// enough since brand-new nodes don't have one yet).
export const hydrateTree = (nodes) => (nodes || []).map((node) => ({
    ...node,
    _key: node.id ? `id-${node.id}` : nextUid(),
    children: hydrateTree(node.children),
}))

// Strip the client-only `_key` before submitting back to the API.
export const dehydrateTree = (nodes) => (nodes || []).map(({ _key, children, ...rest }) => ({
    ...rest,
    children: dehydrateTree(children),
}))
