<script setup>
import { computed } from "vue";
import { useSubmitForm } from "@/composable/submit/useSubmitForm.js";
import { useI18n } from "vue-i18n";
import { formatDateToLocal } from "@/utils/helper.js";
import {
    Mail,
    Phone,
    ProfileCircle,
    Calendar,
    Page,
    Download,
    BrightStar,
    Megaphone,
    QuestionMark,
    Building,
} from "@iconoir/vue";

const props = defineProps({
    modalId: String,
    tableId: String,
    selectedUrl: String,
});

const { t } = useI18n();
const emit = defineEmits(["close"]);

const { pageLoader, closeModal, formData } = useSubmitForm(props, emit);

const applicant = computed(() => formData.value);

const photoUrl = computed(() => {
    if (!applicant.value?.photo) return null;
    const p = applicant.value.photo;
    return p.startsWith("http") ? p : `/storage/${p}`;
});

const cvUrl = computed(() => {
    if (!applicant.value?.cv) return null;
    const c = applicant.value.cv;
    return c.startsWith("http") ? c : `/storage/${c}`;
});

const statusLabel = computed(() => {
    const s = applicant.value?.status;
    if (s == 1 || s === "1") return { label: t("active"), color: "success" };
    if (s == 0 || s === "0") return { label: t("inactive"), color: "danger" };
    return { label: String(s ?? "-"), color: "secondary" };
});
</script>

<template>
    <app-modal :modal-id="modalId" modal-size="large" title="Applicant Detail" :show-footer="false" scrollable
        @close="closeModal">
        <template v-slot:body>
            <app-loader v-if="pageLoader" />

            <template v-else-if="applicant?.id">
                <!-- Hero / Profile Header -->
                <div class="applicant-hero">
                    <div class="applicant-hero__avatar-wrap">
                        <img v-if="photoUrl" :src="photoUrl" :alt="applicant.name" class="applicant-hero__avatar"
                            @error="$event.target.style.display = 'none'" />
                        <div v-else class="applicant-hero__avatar applicant-hero__avatar--placeholder">
                            <ProfileCircle :width="48" :height="48" />
                        </div>
                    </div>

                    <div class="applicant-hero__info">
                        <h4 class="applicant-hero__name">{{ applicant.name }}</h4>
                        <span class="applicant-hero__position">
                            <Building :width="14" :height="14" />
                            {{ applicant.career?.heading ?? '-' }}
                        </span>
                    </div>

                    <div class="applicant-hero__badges">
                        <span :class="`badge-square-light bg-${statusLabel.color}-light`">
                            {{ statusLabel.label }}
                        </span>

                        <span :class="`badge-square-light bg-${applicant.gender === 'Male' ? 'success' : 'danger'}`">
                            {{ applicant.gender }}
                        </span>
                    </div>
                </div>

                <!-- Contact & Basic Info -->
                <div class="info-grid mt-4">
                    <div class="info-card">
                        <span class="info-card__icon info-card__icon--blue">
                            <Mail :width="16" :height="16" />
                        </span>
                        <div>
                            <p class="info-card__label">{{ $t('email') }}</p>
                            <a :href="`mailto:${applicant.email}`" class="info-card__value info-card__value--link">
                                {{ applicant.email }}
                            </a>
                        </div>
                    </div>

                    <div class="info-card">
                        <span class="info-card__icon info-card__icon--green">
                            <Phone :width="16" :height="16" />
                        </span>
                        <div>
                            <p class="info-card__label">{{ $t('phone') }}</p>
                            <a :href="`tel:${applicant.phone}`" class="info-card__value info-card__value--link">
                                {{ applicant.phone }}
                            </a>
                        </div>
                    </div>

                    <div class="info-card">
                        <span class="info-card__icon info-card__icon--purple">
                            <ProfileCircle :width="16" :height="16" />
                        </span>
                        <div>
                            <p class="info-card__label">{{ $t('age') }}</p>
                            <span class="info-card__value">{{ applicant.age ?? '-' }} {{ $t('years') }}</span>
                        </div>
                    </div>

                    <div class="info-card">
                        <span class="info-card__icon info-card__icon--orange">
                            <Calendar :width="16" :height="16" />
                        </span>
                        <div>
                            <p class="info-card__label">{{ $t('applied_on') }}</p>
                            <span class="info-card__value">{{ formatDateToLocal(applicant.created_at) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Divider -->
                <div class="section-divider"></div>

                <!-- Skills -->
                <div class="detail-section">
                    <div class="detail-section__header">
                        <span class="detail-section__icon detail-section__icon--star">
                            <BrightStar :width="16" :height="16" />
                        </span>
                        <h6 class="detail-section__title">{{ $t('skills') }}</h6>
                    </div>
                    <p class="detail-section__body">{{ applicant.skills ?? '-' }}</p>
                </div>

                <!-- Reason to Join -->
                <div class="detail-section">
                    <div class="detail-section__header">
                        <span class="detail-section__icon detail-section__icon--blue">
                            <Megaphone :width="16" :height="16" />
                        </span>
                        <h6 class="detail-section__title">Reason of join</h6>
                    </div>
                    <p class="detail-section__body">{{ applicant.reason_of_join ?? '-' }}</p>
                </div>

                <!-- Why Choose -->
                <div class="detail-section">
                    <div class="detail-section__header">
                        <span class="detail-section__icon detail-section__icon--green">
                            <QuestionMark :width="16" :height="16" />
                        </span>
                        <h6 class="detail-section__title">Why choose</h6>
                    </div>
                    <p class="detail-section__body">{{ applicant.choos_reason ?? '-' }}</p>
                </div>

                <!-- CV Download -->
                <div v-if="cvUrl" class="cv-download-wrap">
                    <div class="cv-download">
                        <div class="cv-download__info">
                            <Page :width="28" :height="28" class="cv-download__icon" />
                            <div>
                                <p class="cv-download__label">{{ $t('curriculum_vitae') }}</p>
                                <p class="cv-download__filename">{{ applicant.cv }}</p>
                            </div>
                        </div>
                        <a :href="cvUrl" target="_blank" rel="noopener noreferrer" download
                            class="btn btn-primary btn-sm cv-download__btn">
                            <Download :width="14" :height="14" />
                            {{ $t('download') }}
                        </a>
                    </div>
                </div>
            </template>
        </template>

        <template v-slot:footer>
            <button type="button" class="btn btn-primary-outline" data-bs-dismiss="modal" @click.prevent="closeModal">
                {{ $t('close') }}
            </button>
        </template>
    </app-modal>
</template>

<style scoped lang="scss">
/* ── Hero Section ─────────────────────────────────── */
.applicant-hero {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 20px 20px 16px;
    background: linear-gradient(135deg, #f0f4ff 0%, #fafbff 100%);
    border-radius: 12px;
    border: 1px solid #e4eaff;

    &__avatar-wrap {
        flex-shrink: 0;
    }

    &__avatar {
        width: 72px;
        height: 72px;
        border-radius: 50%;
        object-fit: cover;
        border: 3px solid #fff;
        box-shadow: 0 4px 14px rgba(0, 0, 0, 0.12);

        &--placeholder {
            display: flex;
            align-items: center;
            justify-content: center;
            background: #e8edf8;
            color: #7b90c4;
        }
    }

    &__info {
        flex: 1;
        min-width: 0;
    }

    &__name {
        font-size: 18px;
        font-weight: 700;
        color: #1a2340;
        margin: 0 0 4px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    &__position {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        font-size: 13px;
        color: #637093;
    }

    &__badges {
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        gap: 6px;
    }
}

/* ── Info Grid ────────────────────────────────────── */
.info-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
}

.info-card {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 14px 16px;
    background: #fff;
    border: 1px solid #edf0f7;
    border-radius: 10px;
    transition: box-shadow 0.2s;

    &:hover {
        box-shadow: 0 2px 12px rgba(0, 0, 0, 0.07);
    }

    &__icon {
        width: 36px;
        height: 36px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;

        &--blue {
            background: #eff4ff;
            color: #4a6cf7;
        }

        &--green {
            background: #edfff4;
            color: #22c55e;
        }

        &--purple {
            background: #f5f0ff;
            color: #9b5ef7;
        }

        &--orange {
            background: #fff7ed;
            color: #f97316;
        }
    }

    &__label {
        font-size: 11px;
        font-weight: 600;
        color: #9ba4be;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin: 0 0 3px;
    }

    &__value {
        font-size: 13px;
        font-weight: 500;
        color: #2d3d5b;
        margin: 0;
        word-break: break-word;

        &--link {
            color: #4a6cf7;
            text-decoration: none;

            &:hover {
                text-decoration: underline;
            }
        }
    }
}

/* ── Section Divider ──────────────────────────────── */
.section-divider {
    border: none;
    border-top: 1px dashed #e2e8f0;
    margin: 20px 0;
}

/* ── Detail Sections ──────────────────────────────── */
.detail-section {
    margin-bottom: 18px;

    &__header {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 8px;
    }

    &__icon {
        width: 28px;
        height: 28px;
        border-radius: 6px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;

        &--star {
            background: #fef9ec;
            color: #f59e0b;
        }

        &--blue {
            background: #eff4ff;
            color: #4a6cf7;
        }

        &--green {
            background: #edfff4;
            color: #22c55e;
        }
    }

    &__title {
        font-size: 13px;
        font-weight: 700;
        color: #2d3d5b;
        margin: 0;
        text-transform: uppercase;
        letter-spacing: 0.4px;
    }

    &__body {
        font-size: 13.5px;
        color: #4b5675;
        line-height: 1.7;
        margin: 0;
        padding: 12px 14px;
        background: #f8fafc;
        border-left: 3px solid #e2e8f0;
        border-radius: 0 8px 8px 0;
    }
}

/* ── CV Download ──────────────────────────────────── */
.cv-download-wrap {
    margin-top: 4px;
}

.cv-download {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 14px 16px;
    background: #fff;
    border: 1px solid #e4eaff;
    border-radius: 10px;

    &__info {
        display: flex;
        align-items: center;
        gap: 12px;
        min-width: 0;
    }

    &__icon {
        color: #e53e3e;
        flex-shrink: 0;
    }

    &__label {
        font-size: 11px;
        font-weight: 600;
        color: #9ba4be;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin: 0 0 2px;
    }

    &__filename {
        font-size: 12.5px;
        color: #2d3d5b;
        font-weight: 500;
        margin: 0;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 300px;
    }

    &__btn {
        display: flex;
        align-items: center;
        gap: 6px;
        flex-shrink: 0;
    }
}

/* ── Responsive ───────────────────────────────────── */
@media (max-width: 576px) {
    .info-grid {
        grid-template-columns: 1fr;
    }

    .applicant-hero {
        flex-wrap: wrap;

        &__badges {
            flex-direction: row;
            width: 100%;
        }
    }
}
</style>
