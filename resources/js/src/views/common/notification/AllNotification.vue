<script setup>
import {ref, onMounted, onBeforeUnmount, computed} from 'vue';
import {NOTIFICATIONS, READ_NOTIFICATIONS} from "@/services/endpoints/app.js";
import {useI18n} from 'vue-i18n';
import {formatDateToLocal} from "@/utils/helper.js";
import Axios from "@/services/axios/index.js";
import useEmitter from "@/composable/emitter/useEmitter.js";
import {Bell, DoubleCheck, Search} from "@iconoir/vue";

const {t} = useI18n();
const emitter = useEmitter()
import {useToast} from "vue-toastification";
import Paginate from "@/views/global/datatable/Paginate.vue";

const toast = useToast()
const notifications = ref([]);
const pagination = ref({
    from: 0,
    to: 0,
    total: 0,
    lastPage: 1,
});
const searchQuery = ref('');
const activePaginationPage = ref(1);
const rowLimit = ref(6);
const showPagination = ref(true);
const selectedNotification = ref(null);

const selectNotification = (notification = null) => {
    selectedNotification.value = notification;
};
const getPaginateData = (page) => {
    activePaginationPage.value = page;
    getNotifications(page);
};
const filteredNotifications = computed(() => {
    if (!searchQuery.value) return notifications.value;
    return notifications.value.filter(n =>
        n.data.title.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
        n.data.message.toLowerCase().includes(searchQuery.value.toLowerCase())
    );
});

const unreadCount = ref(0);

const getNotifications = (page = 1) => {
    Axios.get(`${NOTIFICATIONS}?page=${page}&per_page=${rowLimit.value}`)
        .then(({data}) => {
            notifications.value = data.data;
            unreadCount.value = data.unread_count;
            pagination.value = {
                from: data.from,
                to: data.to,
                total: data.total,
                lastPage: data.last_page,
            };
            activePaginationPage.value = data.current_page;

            if (!selectedNotification.value && notifications.value.length) {
                selectedNotification.value = notifications.value[0];
            }
        }).catch(error => {
        toast.error(error.response?.data?.message || 'Data fetching error');
    });
};
const manageRead = (row) => {
    Axios.patch(`${READ_NOTIFICATIONS}/${row.id}`)
        .then(({data}) => {
            selectNotification(data.result);
            toast.success(data?.message);
            getNotifications();
            emitter.emit("reload-notification");
        }).catch(error => {
        toast.error(error.response?.data?.message || 'Error marking notification as read');
    });
}

const markAsReadLoader = ref(false);

const markAllAsRead = () => {
    markAsReadLoader.value = true;
    Axios.patch(`${NOTIFICATIONS}/read-all`).then(({data}) => {
        getNotifications();
        selectNotification(null);
        toast.success(data?.message);
        emitter.emit("reload-notification");
    }).catch(e => toast.error(e.response?.data?.message))
        .finally(() => markAsReadLoader.value = false);
}
const reloadDataTable = () => {
    emitter.on("reload-nav-notification", (value = true) => {
        if (value) {
            selectedNotification.value = null;
            getNotifications();
        }
    });
}

onMounted(() => {
    getNotifications(activePaginationPage.value);
    reloadDataTable();
});

onBeforeUnmount(() => {
    emitter.off("reload-nav-notification");
});
</script>

<template>
    <div class="content-wrapper">
        <app-breadcrumb :page-title="t('all_notifications')"/>
        <div class="row g-4">
            <div class="col-md-5">
                <div class="card all-notification-card">
                    <div class="d-flex align-items-center justify-content-between notification-header">
                        <h6 class="mb-0 title">{{ $t('notifications') }} ({{ pagination.total }})</h6>
                        <a href="#" class="text-default" v-if="unreadCount > 0" @click.prevent="markAllAsRead">
                            <h6 class="mb-0 key-title">
                                {{ $t('mark_all_as_read') }}
                            </h6>
                        </a>
                    </div>
                    <div class="search-input">
                        <input
                            type="text"
                            v-model="searchQuery"
                            class="form-control"
                            :placeholder="$t('search')"/>
                        <div class="search-icon">
                            <Search/>
                        </div>
                    </div>
                    <div class="notification-wrapper all-notification-body">
                        <div class="notification-list"
                             v-for="(notification, index) in filteredNotifications" :key="index"
                             :class="{ 'active': selectedNotification?.id === notification.id }"
                             @click="selectNotification(notification)">
                            <div class="notification-wrapper-item all-notification-wrapper-item">
                                <div class="icon">
                                    <Bell/>
                                </div>
                                <div class="d-flex flex-column w-100">
                                    <div
                                        class="d-flex flex-column justify-content-between notification-title w-100 flex-wrap gap-2">
                                        <div class="d-flex gap-2 justify-content-between">
                                            <h4 class="name">{{ notification.data.title }}</h4>
                                            <span v-if="notification.read_at" class="text-success" title="Read">
                                                <DoubleCheck/>
                                            </span>
                                        </div>
                                        <span class="time">
                                            {{ formatDateToLocal(notification.created_at) }}
                                        </span>
                                    </div>
                                    <p class="notification-content">
                                        {{ notification.data.message }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Pagination Footer -->
                    <div
                        class="footer-paginate d-flex gap-3 justify-content-center align-items-center"
                        v-if="pagination.total > rowLimit && notifications.length && showPagination">
                        <div class="right-portion text-nowrap gap-3 mt-md-0">
                            <div class="pages-infos d-flex flex-wrap text-nowrap gap-3 justify-content-center">
                                <paginate
                                    :current-page="activePaginationPage"
                                    :total-pages="pagination.lastPage"
                                    @paginate="getPaginateData"/>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-7">
                <div class="card all-notification-card" v-if="selectedNotification">
                    <div class="notification-wrapper all-notification-body">
                        <div class="notification-list">
                            <div class="notification-wrapper-item all-notification-wrapper-item">
                                <div class="icon">
                                    <Bell/>
                                </div>
                                <div class="d-flex flex-column w-100">
                                    <div
                                        class="d-flex flex-column justify-content-between notification-title w-100 flex-wrap gap-2">
                                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                                            <h4 class="name">{{ selectedNotification.data.title }}</h4>
                                            <button
                                                v-if="!selectedNotification.read_at"
                                                class="btn btn-primary"
                                                @click="manageRead(selectedNotification)">
                                                <DoubleCheck/>
                                            </button>
                                        </div>
                                        <span class="time">
                                            {{ formatDateToLocal(selectedNotification.created_at) }}
                                        </span>
                                    </div>
                                    <p class="notification-content">
                                        {{ selectedNotification.data.message }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div v-else>
                    <p>{{ $t('notifications_not_found') }}</p>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped lang="scss"></style>
