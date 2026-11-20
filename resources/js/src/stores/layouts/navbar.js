import {defineStore} from 'pinia';

export const useNavbarStore = defineStore('navbar', {
    state: () => ({
        sidebarStatus: 'open',
        darkMode: false
    }),

    actions: {
        setSidebarStatus(payloads) {
            this.sidebarStatus = payloads;
        },
    },
});
