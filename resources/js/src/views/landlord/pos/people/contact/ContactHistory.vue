<script setup>
import { onMounted, ref } from "vue";
import { useI18n } from "vue-i18n";
import { useToast } from "vue-toastification";
import { CONTACT_HISTORY } from "@/services/endpoints/landlord.js";
import Axios from "@/services/axios/index.js";
import router from "@/router/index.js";
import { NavArrowLeft } from "@iconoir/vue";

const props = defineProps({ id: {} });
const { t } = useI18n();
const toast = useToast();

const contact = ref(null);
const entries = ref([]);
const page = ref(1);
const hasMore = ref(true);
const loading = ref(false);
const initialLoading = ref(true);

const TYPE_COLORS = {
    sale: '#2f9e44',
    sale_return: '#e03131',
    purchase: '#1971c2',
    purchase_return: '#f08c00',
    order: '#0c8599',
    telesale: '#868e96',
    order_confirmation: '#9c36b5',
};

const dotColor = (type) => TYPE_COLORS[type] || '#868e96';

// Most users have never uploaded a photo, but `users.profile_photo` isn't
// null for them — it's the literal string "default-profile-image.png" (a
// leftover from legacy data, not a real URL), so a plain truthiness check
// would try to render it as an <img> and fail instead of falling back.
const hasRealAvatar = (avatar) => !!avatar && avatar !== 'default-profile-image.png';

const getInitials = (name) => {
    const parts = (name || '').trim().split(' ').filter(Boolean);
    const first = parts[0] ? parts[0][0] : '';
    const last = parts.length > 1 ? parts[parts.length - 1][0] : '';
    return `${first}${last}`.toUpperCase() || 'NA';
};

const isSameDay = (a, b) => a.getFullYear() === b.getFullYear()
    && a.getMonth() === b.getMonth()
    && a.getDate() === b.getDate();

const dateLabel = (timestamp) => {
    const date = new Date(timestamp);
    const now = new Date();
    const yesterday = new Date(now);
    yesterday.setDate(now.getDate() - 1);

    if (isSameDay(date, now)) return t('today');
    if (isSameDay(date, yesterday)) return t('yesterday');
    return date.toLocaleDateString(undefined, { day: '2-digit', month: 'short', year: 'numeric' });
};

const timeLabel = (timestamp) => new Date(timestamp).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

const loadTimeline = async () => {
    if (loading.value || !hasMore.value) return;
    loading.value = true;
    try {
        const { data } = await Axios.get(`${CONTACT_HISTORY}/${props.id}/timeline`, {
            params: { page: page.value, per_page: 20 },
        });
        contact.value = data.contact;
        entries.value.push(...data.data);
        hasMore.value = page.value < data.last_page;
        page.value += 1;
    } catch (error) {
        toast.error(error?.response?.data?.message || t('something_went_wrong'));
    } finally {
        loading.value = false;
        initialLoading.value = false;
    }
};

onMounted(() => loadTimeline());
</script>

<template>
    <div class="content-wrapper">
        <app-breadcrumb :page-title="$t('contact_history')">
            <template #breadcrumb-actions>
                <button class="btn btn-light btn-sm d-flex align-items-center gap-1" @click="router.push('/people')">
                    <NavArrowLeft style="width:15px;height:15px;" /> Back
                </button>
            </template>
        </app-breadcrumb>

        <div v-if="contact" class="contact-header">
            <span class="contact-avatar">
                <img v-if="hasRealAvatar(contact.avatar)" :src="contact.avatar" alt="">
                <span v-else class="contact-avatar-fallback">{{ getInitials(contact.name) }}</span>
            </span>
            <div>
                <div class="contact-name">{{ contact.name }}</div>
                <div class="contact-business" v-if="contact.business_name">{{ contact.business_name }}</div>
                <div class="contact-contact-info">
                    <span v-if="contact.phone">{{ contact.phone }}</span>
                    <span v-if="contact.phone && contact.email"> · </span>
                    <span v-if="contact.email">{{ contact.email }}</span>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-center py-5" v-if="initialLoading">
            <app-loader></app-loader>
        </div>

        <div v-else-if="!entries.length" class="text-center text-muted py-5">
            {{ t('no_activity_found') }}
        </div>

        <div v-else class="timeline">
            <div v-for="entry in entries" :key="entry.id" class="timeline-entry">
                <div class="timeline-spine">
                    <span class="timeline-dot" :style="{ background: dotColor(entry.type) }"></span>
                    <span class="timeline-line"></span>
                </div>

                <div class="timeline-body">
                    <div class="timeline-date">{{ dateLabel(entry.timestamp) }} {{ timeLabel(entry.timestamp) }}</div>

                    <div class="timeline-card">
                        <div class="timeline-card-header">
                            <template v-if="entry.actor">
                                <span class="timeline-avatar">
                                    <img v-if="hasRealAvatar(entry.actor.avatar)" :src="entry.actor.avatar" alt="">
                                    <span v-else class="timeline-avatar-fallback">{{ getInitials(entry.actor.name) }}</span>
                                </span>
                                <span class="timeline-actor-name">{{ entry.actor.name }}</span>
                            </template>
                            <span class="timeline-title">{{ entry.title }}</span>
                        </div>

                        <div class="timeline-meta" v-if="entry.meta && entry.meta.length">
                            <div class="timeline-meta-row" v-for="m in entry.meta" :key="m.label">
                                <span class="timeline-meta-label">{{ m.label }}</span>
                                <span class="timeline-meta-value">{{ m.value }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="text-center mt-3" v-if="hasMore">
                <button class="btn btn-light btn-sm" :disabled="loading" @click="loadTimeline">
                    {{ loading ? t('loading') : t('load_more') }}
                </button>
            </div>
        </div>
    </div>
</template>

<style scoped lang="scss">
.contact-header {
    max-width: 700px;
    margin: 0 auto 16px;
    display: flex;
    align-items: center;
    gap: 12px;
}

.contact-avatar {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    overflow: hidden;
    flex-shrink: 0;
    display: inline-flex;

    img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
}

.contact-avatar-fallback {
    width: 100%;
    height: 100%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: #e0f2f1;
    color: #00a09d;
    font-size: 14px;
    font-weight: 600;
}

.contact-name {
    font-weight: 700;
    font-size: 16px;
}

.contact-business {
    font-size: 13px;
    color: #868e96;
}

.contact-contact-info {
    font-size: 13px;
    color: #868e96;
}

.timeline {
    max-width: 700px;
    margin: 0 auto;
    padding: 8px 0;
}

.timeline-entry {
    display: flex;
    gap: 16px;
}

.timeline-spine {
    display: flex;
    flex-direction: column;
    align-items: center;
    width: 12px;
    flex-shrink: 0;
}

.timeline-dot {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    flex-shrink: 0;
}

.timeline-line {
    flex: 1;
    width: 2px;
    background: #e5e7eb;
    margin-top: 4px;
}

.timeline-entry:last-child .timeline-line {
    display: none;
}

.timeline-body {
    flex: 1;
    padding-bottom: 24px;
    min-width: 0;
}

.timeline-date {
    font-size: 13px;
    color: #868e96;
    margin-bottom: 8px;
}

.timeline-card {
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    padding: 14px 16px;
}

.timeline-card-header {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}

.timeline-avatar {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    overflow: hidden;
    flex-shrink: 0;
    display: inline-flex;

    img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
}

.timeline-avatar-fallback {
    width: 100%;
    height: 100%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: #e0f2f1;
    color: #00a09d;
    font-size: 10px;
    font-weight: 600;
}

.timeline-actor-name {
    font-weight: 600;
    font-size: 14px;
}

.timeline-title {
    font-size: 14px;
    color: #495057;
}

.timeline-meta {
    margin-top: 10px;
    padding-top: 10px;
    border-top: 1px solid #f1f3f5;
}

.timeline-meta-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 3px 0;
    font-size: 13px;
}

.timeline-meta-label {
    color: #868e96;
}

.timeline-meta-value {
    font-weight: 500;
    text-align: right;
}
</style>
