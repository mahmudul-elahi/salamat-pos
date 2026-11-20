<script setup>
import {computed, onMounted, ref, watch} from "vue";
import {useToast} from "vue-toastification";
import {Xmark, EditPencil} from "@iconoir/vue";
import {useChatStore} from "@/stores/chat/chat.js";
import Avatar from "@/views/global/avatar/Avatar.vue";
import CustomerModal from "@/views/landlord/pos/people/customers/CustomerModal.vue";
import {CUSTOMERS} from "@/services/endpoints/landlord.js";

const props = defineProps({
    conversation: {type: Object, default: null},
});

const toast = useToast();
const store = useChatStore();

const teams = ref([]);
const labels = ref([]);
const showLabelPicker = ref(false);
const newLabelName = ref("");
const showConversationInfo = ref(false);
const showEditContactModal = ref(false);
const showAddContactModal = ref(false);

const contact = computed(() => props.conversation?.contact ?? null);

// WhatsApp's external_id is the customer's actual phone number; Messenger/
// Instagram's is an opaque per-app scoped id (PSID/IGSID), not a phone.
const prefillPhone = computed(() => (props.conversation?.channel === 'whatsapp' ? props.conversation.external_id : null));

const location = computed(() => {
    if (!contact.value) return null;
    return [contact.value.district?.name, contact.value.area?.name, contact.value.address].filter(Boolean).join(", ") || null;
});

const attachedLabelIds = computed(() => (props.conversation?.labels ?? []).map((l) => l.id));

const priorityOptions = [
    {value: "", label: "None"},
    {value: "low", label: "Low"},
    {value: "medium", label: "Medium"},
    {value: "high", label: "High"},
    {value: "urgent", label: "Urgent"},
];

function priorityDotClass(priority) {
    if (priority === "urgent") return "bg-danger";
    if (priority === "high") return "bg-warning";
    if (priority === "medium") return "bg-info";
    if (priority === "low") return "bg-secondary";
    return "bg-light border";
}

async function loadPickers() {
    try {
        teams.value = await store.fetchTeams();
    } catch (error) {
        teams.value = [];
    }

    try {
        labels.value = await store.fetchLabels();
    } catch (error) {
        labels.value = [];
    }
}

async function changeTeam(event) {
    const teamId = event.target.value || null;
    if (!props.conversation) return;

    try {
        if (teamId) {
            await store.assignTeam(props.conversation.id, teamId);
            toast.success("Team assigned");
        }
    } catch (error) {
        toast.error(error?.response?.data?.message || "Failed to assign team");
    }
}

async function changePriority(event) {
    if (!props.conversation) return;

    try {
        await store.setPriority(props.conversation.id, event.target.value || null);
        toast.success("Priority updated");
    } catch (error) {
        toast.error(error?.response?.data?.message || "Failed to update priority");
    }
}

async function toggleLabel(labelId) {
    if (!props.conversation) return;

    const current = attachedLabelIds.value;
    const updated = current.includes(labelId) ? current.filter((id) => id !== labelId) : [...current, labelId];

    try {
        await store.syncLabels(props.conversation.id, updated);
    } catch (error) {
        toast.error(error?.response?.data?.message || "Failed to update labels");
    }
}

async function removeLabel(labelId) {
    await toggleLabel(labelId);
}

async function createAndAttachLabel() {
    if (!newLabelName.value.trim()) return;

    try {
        const label = await store.createLabel({name: newLabelName.value.trim()});
        labels.value.push(label);
        newLabelName.value = "";
        await toggleLabel(label.id);
    } catch (error) {
        toast.error(error?.response?.data?.message || "Failed to create label");
    }
}

async function onContactCreated(createdContact) {
    showAddContactModal.value = false;

    try {
        await store.attachContact(props.conversation.id, createdContact.id);
        toast.success("Contact added");
    } catch (error) {
        toast.error(error?.response?.data?.message || "Failed to link the new contact to this conversation");
    }
}

watch(() => props.conversation?.id, loadPickers, {immediate: true});
</script>

<template>
    <div class="contact-info-panel h-100 overflow-auto" v-if="conversation">
        <template v-if="contact">
            <div class="text-center panel-section contact-card">
                <div class="d-flex justify-content-center mb-2">
                    <Avatar :image="contact.profile_picture" :name="contact.name" />
                </div>
                <div class="d-flex align-items-center justify-content-center gap-2">
                    <div class="fw-semibold">{{ contact.name }}</div>
                    <button type="button" class="btn btn-sm btn-icon-round" title="Edit contact" @click="showEditContactModal = true">
                        <EditPencil width="15" height="15" />
                    </button>
                </div>
            </div>

            <div class="small panel-section">
                <div v-if="contact.business_name" class="info-row"><span class="text-muted">Company</span><span>{{ contact.business_name }}</span></div>
                <div v-if="contact.phone" class="info-row"><span class="text-muted">Phone</span><span>{{ contact.phone }}</span></div>
                <div v-if="contact.email" class="info-row"><span class="text-muted">Email</span><span>{{ contact.email }}</span></div>
                <div v-if="location" class="info-row"><span class="text-muted">Location</span><span>{{ location }}</span></div>
            </div>
        </template>

        <div v-else class="text-center panel-section contact-card">
            <div class="d-flex justify-content-center mb-2">
                <Avatar :image="null" :name="conversation.profile_name || conversation.external_id" />
            </div>
            <div class="fw-semibold mb-2">{{ conversation.profile_name || conversation.external_id }}</div>
            <p class="small text-muted mb-2">Not saved to Contacts yet.</p>
            <button type="button" class="btn btn-sm btn-primary rounded-pill px-3" @click="showAddContactModal = true">
                + Add to Contacts
            </button>
        </div>

        <div class="panel-section">
            <div class="section-title">Conversation Actions</div>

            <div class="mb-3">
                <label class="field-label">Assigned Agent</label>
                <div class="small">{{ conversation.assigned_agent?.name || "Unassigned" }}</div>
            </div>

            <div class="mb-3">
                <label class="field-label">Assigned Team</label>
                <select class="form-select form-select-sm rounded-pill-select" :value="conversation.assigned_team_id ?? ''" @change="changeTeam">
                    <option value="">Unassigned</option>
                    <option v-for="team in teams" :key="team.id" :value="team.id">
                        {{ team.icon }} {{ team.name }}
                    </option>
                </select>
            </div>

            <div class="mb-3">
                <label class="field-label d-flex align-items-center gap-1">
                    <span class="priority-dot" :class="priorityDotClass(conversation.priority)"></span>
                    Priority
                </label>
                <select class="form-select form-select-sm rounded-pill-select" :value="conversation.priority ?? ''" @change="changePriority">
                    <option v-for="option in priorityOptions" :key="option.value" :value="option.value">
                        {{ option.label }}
                    </option>
                </select>
            </div>

            <div>
                <label class="field-label d-block">Conversation Labels</label>
                <div class="d-flex flex-wrap gap-1 mb-2">
                    <span
                        v-for="label in conversation.labels ?? []"
                        :key="label.id"
                        class="label-chip"
                        :style="{ backgroundColor: label.color || '#6c757d' }"
                    >
                        {{ label.name }}
                        <Xmark width="12" height="12" class="label-remove" @click="removeLabel(label.id)" />
                    </span>
                    <button type="button" class="btn-add-label" @click="showLabelPicker = !showLabelPicker">
                        + Add Labels
                    </button>
                </div>

                <div v-if="showLabelPicker" class="label-picker">
                    <div v-for="label in labels" :key="label.id" class="form-check">
                        <input
                            type="checkbox"
                            class="form-check-input"
                            :id="`label-${label.id}`"
                            :checked="attachedLabelIds.includes(label.id)"
                            @change="toggleLabel(label.id)"
                        />
                        <label class="form-check-label small" :for="`label-${label.id}`">{{ label.name }}</label>
                    </div>

                    <div class="d-flex gap-1 mt-2">
                        <input
                            type="text"
                            class="form-control form-control-sm"
                            placeholder="New label name"
                            v-model="newLabelName"
                            @keydown.enter.prevent="createAndAttachLabel"
                        />
                        <button type="button" class="btn btn-sm btn-primary rounded-pill px-3" @click="createAndAttachLabel">Add</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="panel-section">
            <button type="button" class="section-title toggle-btn" @click="showConversationInfo = !showConversationInfo">
                Conversation Information <span class="toggle-icon">{{ showConversationInfo ? '−' : '+' }}</span>
            </button>

            <div v-if="showConversationInfo" class="small mt-2">
                <div class="info-row"><span class="text-muted">Conversation ID</span><span>{{ conversation.id }}</span></div>
                <div class="info-row"><span class="text-muted">Channel</span><span class="text-capitalize">{{ conversation.channel }}</span></div>
                <div class="info-row"><span class="text-muted">External ID</span><span>{{ conversation.external_id }}</span></div>
                <div class="info-row"><span class="text-muted">Created At</span><span>{{ conversation.created_at }}</span></div>
            </div>
        </div>

        <CustomerModal
            v-if="showEditContactModal && contact"
            modal-id="chat-contact-edit-modal"
            table-id="chat-contact-edit"
            :selected-url="`${CUSTOMERS}/${contact.id}`"
            @close="showEditContactModal = false"
        />

        <CustomerModal
            v-if="showAddContactModal"
            modal-id="chat-contact-add-modal"
            table-id="chat-contact-add"
            :name="conversation.profile_name"
            :phone-number="prefillPhone"
            @close="showAddContactModal = false"
            @created="onContactCreated"
        />
    </div>
</template>

<style scoped lang="scss">
.contact-info-panel {
    width: 300px;
    flex-shrink: 0;
    background: var(--white, #fff);
    border-left: 1px solid var(--border-color, #eceff1);
}

.panel-section {
    padding: 16px 18px;
    border-bottom: 1px solid var(--border-color, #f1f1f1);
}

.section-title {
    font-size: 0.8125rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: var(--muted-color, #8a8a8a);
    margin-bottom: 12px;
}

.toggle-btn {
    border: none;
    background: none;
    padding: 0;
    width: 100%;
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0;
}

.toggle-icon {
    font-size: 1rem;
}

.field-label {
    display: block;
    font-size: 0.75rem;
    color: var(--muted-color, #8a8a8a);
    margin-bottom: 4px;
}

.info-row {
    display: flex;
    justify-content: space-between;
    gap: 8px;
    padding: 5px 0;

    span:last-child {
        text-align: right;
        font-weight: 500;
    }
}

.rounded-pill-select {
    border-radius: 999px;
    border: 1px solid var(--border-color, #dfe3e8);
}

.priority-dot {
    display: inline-block;
    width: 8px;
    height: 8px;
    border-radius: 50%;
}

.label-chip {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    color: #fff;
    font-size: 0.75rem;
    font-weight: 600;
    padding: 3px 10px;
    border-radius: 999px;
}

.label-remove {
    cursor: pointer;
    opacity: 0.85;

    &:hover {
        opacity: 1;
    }
}

.btn-add-label {
    border: 1px dashed var(--border-color, #dfe3e8);
    background: transparent;
    color: var(--primary);
    font-size: 0.75rem;
    font-weight: 600;
    padding: 3px 10px;
    border-radius: 999px;

    &:hover {
        background: rgba(var(--primary-rgb, 43, 135, 104), 0.06);
    }
}

.label-picker {
    border: 1px solid var(--border-color, #eceff1);
    border-radius: 10px;
    padding: 10px;
    background: rgba(0, 0, 0, 0.015);
}

.btn-icon-round {
    width: 28px;
    height: 28px;
    padding: 0;
    border: none;
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: transparent;
    color: var(--muted-color, #8a8a8a);

    &:hover {
        background: rgba(0, 0, 0, 0.05);
        color: var(--font-color, #333);
    }
}
</style>
