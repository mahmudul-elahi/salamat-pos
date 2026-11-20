import { computed } from "vue";
import { useProfileStore } from "@/stores/profile/profile.js";
import { storeToRefs } from "pinia";
import { format } from "date-fns";
import { toZonedTime, format as tzFormat } from "date-fns-tz";

const DEFAULT_TZ = "Asia/Dhaka";

export function useTimezone() {
    const store = useProfileStore();
    const { profileData } = storeToRefs(store);

    const userTimezone = computed(() => profileData.value?.timezone ?? DEFAULT_TZ);

    /**
     * Convert a UTC date string/Date to the user's timezone and return a Date object.
     */
    const toUserTz = (date) => {
        if (!date) return null;
        return toZonedTime(new Date(date), userTimezone.value);
    };

    /**
     * Format a date string in the user's timezone.
     * @param {string|Date} date
     * @param {string} fmt  date-fns format string (default: 'dd MMM yyyy')
     */
    const formatDate = (date, fmt = "dd MMM yyyy") => {
        if (!date) return "-";
        try {
            const zoned = toZonedTime(new Date(date), userTimezone.value);
            return tzFormat(zoned, fmt, { timeZone: userTimezone.value });
        } catch {
            return "-";
        }
    };

    /**
     * Format a datetime string in the user's timezone.
     * @param {string|Date} date
     * @param {string} fmt  date-fns format string (default: 'dd MMM yyyy, hh:mm a')
     */
    const formatDateTime = (date, fmt = "dd MMM yyyy, hh:mm a") => {
        return formatDate(date, fmt);
    };

    /**
     * Format only the time portion in the user's timezone.
     * @param {string|Date} date
     */
    const formatTime = (date) => {
        return formatDate(date, "hh:mm a");
    };

    /**
     * Format a pure date field (no timezone conversion).
     * Use this for fields like start_date / end_date that are date-only strings
     * (e.g. "2026-06-21" or "2026-06-21T00:00:00.000000Z") — converting them
     * through a timezone would shift the day for users in negative UTC offsets.
     * @param {string|Date} date
     * @param {string} fmt  date-fns format string (default: 'dd MMM yyyy')
     */
    const formatDateOnly = (date, fmt = "dd MMM yyyy") => {
        if (!date) return "-";
        try {
            const dateStr = typeof date === "string" ? date.substring(0, 10) : null;
            const d = dateStr ? new Date(`${dateStr}T12:00:00`) : new Date(date);
            return format(d, fmt);
        } catch {
            return "-";
        }
    };

    return {
        userTimezone,
        toUserTz,
        formatDate,
        formatDateTime,
        formatTime,
        formatDateOnly,
    };
}
