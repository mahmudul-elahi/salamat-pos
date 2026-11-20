<script setup>
import {ref, onMounted, onBeforeUnmount} from "vue"
import {useToast} from "vue-toastification";
import useEmitter from "@/composable/emitter/useEmitter.js";
import {Bell} from "@iconoir/vue";
import Axios from "@/services/axios/index.js";
import {NOTIFICATIONS, READ_NOTIFICATIONS} from "@/services/endpoints/app.js";
import {formatDateToLocal, textTruncate} from "@/utils/helper.js";

const emitter = useEmitter()
const toast = useToast();

const notifications = ref([])
const unreadCount = ref(0);
const getNotification = () => {
    Axios.get(NOTIFICATIONS).then(({data}) => {
        notifications.value = data.data;
        unreadCount.value = data.unread_count;
    }).catch(e => toast.error(e.response?.data?.message));
}


const markAsReadLoader = ref(false);
const markAllAsRead = () => {
    markAsReadLoader.value = true;
    Axios.patch(`${NOTIFICATIONS}/read-all`).then(({data}) => {
        getNotification();
        toast.success(data?.message);
        emitter.emit("reload-nav-notification");
    }).catch(e => toast.error(e.response?.data?.message))
        .finally(() => markAsReadLoader.value = false);
}

const reloadDataTable = () => {
    emitter.on("reload-notification", (value = true) => {
        if (value) {
            getNotification();
        }
    });
}
const markAsRead = (row) => {
    if (!row.read_at) {
        Axios.patch(`${READ_NOTIFICATIONS}/${row.id}`)
            .then(({data}) => {
                toast.success(data?.message);
                getNotification();
                emitter.emit("reload-notification");
            }).catch(error => {
            toast.error(error.response?.data?.message || 'Error marking notification as read');
        });
    }
}

onMounted(() => {
    getNotification();
    reloadDataTable();
});

onBeforeUnmount(() => {
    emitter.off("reload-notification");
});
</script>

<template>
    <li class="nav-item nav-notification dropdown">
        <a class="nav-link dropdown-toggle border-left" href="#" role="button" id="dropdownNotification"
           data-bs-toggle="dropdown" aria-expanded="false" aria-label="Show notifications">
            <div class="notify-icon position-relative nav-toggle-icon">
                <Bell/>
                <span class="counter counter-box position-absolute" v-if="unreadCount > 0">
                    {{ unreadCount }}
                </span>
            </div>
        </a>
        <div class="dropdown-menu dropdown-menu-end dropdown-notification navbar-dropdown custom-scrollbar"
             aria-labelledby="dropdownNotification">
            <div class="dropdown-header d-flex align-items-center justify-content-between">
                <h6 class="mb-0 title" v-if="notifications.length > 0">{{ $t('notifications') }}</h6>
                <h6 class="mb-0 title" v-else>{{ $t('notifications_not_found') }}</h6>
                <a href="#" class="text-default" v-if="unreadCount > 0" @click.prevent="markAllAsRead">
                    <h6 class="mb-0 key-title">
                        {{ $t('mark_all_as_read') }}
                    </h6>
                </a>
            </div>

            <div class="notification-wrapper notification-body" v-if="notifications.length > 0">
                <div class="notification-scroll">
                    <div class="notification-list"
                         v-for="(notification, index) in notifications"
                         :key="index">
                        <div
                            :class="['notification-wrapper-item cursor-pointer', notification.read_at ? 'read' : 'unread']"
                            @click="markAsRead(notification)">
                            <div class="icon">
                                <Bell/>
                            </div>
                            <div class="d-flex flex-column w-100">
                                <div
                                    class="d-flex flex-column justify-content-between notification-title w-100 flex-wrap">
                                    <h4 class="name">{{ notification.data.title }}</h4>
                                    <span class="time">
                                            {{ formatDateToLocal(notification.created_at) }}
                                        </span>
                                </div>
                                <p class="notification-content">
                                    {{ textTruncate(notification.data.message, 80) }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="notification-footer" v-if="notifications.length > 0">
                <router-link :to="{ name: 'AllNotification' }" class="btn btn-primary">
                    {{ $t('view_all') }}
                </router-link>
            </div>


        </div>
    </li>
</template>

<style scoped lang="scss"></style>
