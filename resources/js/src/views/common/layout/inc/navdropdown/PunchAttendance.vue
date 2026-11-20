<script setup>
import { ref, onMounted, computed } from "vue";
import { useI18n } from "vue-i18n";
import { LogIn, LogOut, Pause, Play } from "@iconoir/vue";
import Axios from "@/services/axios";
import { HRM_ATTENDANCES } from "@/services/endpoints/hrm.js";
import PunchModal from "./PunchModal.vue";
import BreakModal from "./BreakModal.vue";

const { t } = useI18n();

const punchedIn   = ref(false);
const todayStatus = ref({});
const isModalActive      = ref(false);
const isBreakModalActive = ref(false);

const onBreak = computed(() => todayStatus.value?.on_break ?? false);

const fetchStatus = () => {
    Axios.get(`${HRM_ATTENDANCES}/today`)
        .then(({ data }) => {
            punchedIn.value   = !!data.punched_in;
            todayStatus.value = data;
        })
        .catch(() => {});
};

const onSuccess = () => fetchStatus();

onMounted(fetchStatus);
</script>

<template>
    <li class="nav-item d-flex align-items-center gap-2 punch-attendance-wrap">

        <!-- Break button -->
        <button v-if="punchedIn" type="button"
            class="punch-btn"
            :class="onBreak ? 'punch-btn--break-active' : 'punch-btn--break'"
            @click="isBreakModalActive = true">
            <span class="punch-btn__icon">
                <component :is="onBreak ? Play : Pause" width="14" height="14" />
            </span>
            <span class="punch-btn__label">{{ onBreak ? t('end_break') : t('take_break') }}</span>
        </button>

        <!-- Punch In / Out button -->
        <button type="button"
            class="punch-btn"
            :class="punchedIn ? 'punch-btn--out' : 'punch-btn--in'"
            @click="isModalActive = true">
            <span class="punch-btn__icon">
                <component :is="punchedIn ? LogOut : LogIn" width="14" height="14" />
            </span>
            <span class="punch-btn__label">{{ punchedIn ? t('punch_out') : t('punch_in') }}</span>
        </button>

    </li>

    <punch-modal v-if="isModalActive" modal-id="punch-attendance-modal"
        :mode="punchedIn ? 'out' : 'in'" :today-status="todayStatus"
        @close="isModalActive = false" @success="onSuccess" />

    <break-modal v-if="isBreakModalActive" modal-id="break-modal"
        :today-status="todayStatus"
        @close="isBreakModalActive = false" @success="onSuccess" />
</template>

<style scoped lang="scss">
.punch-attendance-wrap {
    padding: 0 4px;
}

.punch-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 7px 14px;
    border: none;
    border-radius: 50px;
    font-size: 0.78rem;
    font-weight: 600;
    letter-spacing: 0.2px;
    cursor: pointer;
    transition: all 0.22s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    overflow: hidden;
    white-space: nowrap;

    &::before {
        content: '';
        position: absolute;
        inset: 0;
        background: rgba(255, 255, 255, 0.15);
        opacity: 0;
        transition: opacity 0.2s;
    }

    &:hover::before {
        opacity: 1;
    }

    &:active {
        transform: scale(0.96);
    }

    &__icon {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 22px;
        height: 22px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.25);
        flex-shrink: 0;
    }

    &__label {
        line-height: 1;
    }

    /* Punch In — green gradient */
    &--in {
        background: linear-gradient(135deg, #10b981, #059669);
        color: #fff;
        box-shadow: 0 2px 10px rgba(16, 185, 129, 0.35);

        &:hover {
            box-shadow: 0 4px 16px rgba(16, 185, 129, 0.5);
            transform: translateY(-1px);
        }
    }

    /* Punch Out — red gradient */
    &--out {
        background: linear-gradient(135deg, #ef4444, #dc2626);
        color: #fff;
        box-shadow: 0 2px 10px rgba(239, 68, 68, 0.35);

        &:hover {
            box-shadow: 0 4px 16px rgba(239, 68, 68, 0.5);
            transform: translateY(-1px);
        }
    }

    /* Take Break — amber gradient */
    &--break {
        background: linear-gradient(135deg, #f59e0b, #d97706);
        color: #fff;
        box-shadow: 0 2px 10px rgba(245, 158, 11, 0.3);

        &:hover {
            box-shadow: 0 4px 16px rgba(245, 158, 11, 0.45);
            transform: translateY(-1px);
        }
    }

    /* End Break — indigo gradient */
    &--break-active {
        background: linear-gradient(135deg, #6366f1, #4f46e5);
        color: #fff;
        box-shadow: 0 2px 10px rgba(99, 102, 241, 0.35);

        &:hover {
            box-shadow: 0 4px 16px rgba(99, 102, 241, 0.5);
            transform: translateY(-1px);
        }
    }
}
</style>
