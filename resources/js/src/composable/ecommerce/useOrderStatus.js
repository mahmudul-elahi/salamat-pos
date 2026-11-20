import {ref} from "vue";
import {useI18n} from "vue-i18n";
import {useToast} from "vue-toastification";
import Axios from "@/services/axios/index.js";
import {Check, Circle, DeliveryTruck, DoubleCheck, Pause, RefreshCircle, UndoCircle, XmarkCircle} from "@iconoir/vue";
import useEmitter from "@/composable/emitter/useEmitter.js";
import {ORDERS} from "@/services/endpoints/Ecommerce.js";

export default function useOrderStatus(tableId) {
    const {t} = useI18n();
    const toast = useToast();
    const emitter = useEmitter();

    const showStatusModal = ref(false);
    const modalData = ref({
        url: "",
        type: "",
        title: "",
        message: "",
        icon: null,
    });
    const preloader = ref(false);

    // 🔹 Status types mapping
    const statusTypes = {
        confirm: {
            message: t("are_you_sure_to_confirm_this_order"),
            icon: Check,
            color: "success",
        },
        approved: {
            message: t("are_you_sure_to_approved_this_order"),
            icon: Check,
            color: "success",
        },
        cancel: {
            message: t("are_you_sure_to_cancel_this_order"),
            icon: XmarkCircle,
            color: "danger",
        },
        hold: {
            message: t("are_you_sure_to_hold_this_order"),
            icon: Pause,
            color: "dark",
        },
        resume: {
            message: t("are_you_sure_to_resume_this_order"),
            icon: RefreshCircle,
            color: "success",
        },
        processing: {
            message: t("are_you_sure_to_mark_this_order_processing"),
            icon: Circle,
            color: "warning",
        },
        onway: {
            message: t("are_you_sure_to_mark_this_order_onway"),
            icon: DeliveryTruck,
            color: "info",
        },
        return: {
            message: t("are_you_sure_to_mark_this_order_return"),
            icon: UndoCircle,
            color: "danger",
        },
        complete: {
            message: t("are_you_sure_to_mark_this_order_complete"),
            icon: DoubleCheck,
            color: "primary",
        },
        changeamount: {
            message: t("are_you_sure_to_change_amount"),
            icon: DoubleCheck,
            color: "primary",
        },
    };

    // 🔹 Open modal dynamically
    const openStatusModal = (type, row) => {
        const info = statusTypes[type];
        modalData.value = {
            url: `${ORDERS}/${row.id}/${type}`,
            type,
            icon: info.icon,
            title: t("are_you_sure"),
            message: info.message,
        };
        showStatusModal.value = true;
    };

    // 🔹 Update order status via API
    const updateOrderStatus = async () => {
        preloader.value = true;
        try {
            const {data} = await Axios.patch(modalData.value.url);
            toast.success(data?.message);
            emitter.emit("reload-" + tableId.value);
            closeStatusModal();
        } catch (error) {
            toast.error(error?.response?.data?.message || "Status update failed");
        } finally {
            preloader.value = false;
        }
    };

    // 🔹 Close modal
    const closeStatusModal = () => {
        showStatusModal.value = false;
        modalData.value = {url: "", type: "", title: "", message: "", icon: null};
    };

    return {
        showStatusModal,
        modalData,
        preloader,
        openStatusModal,
        updateOrderStatus,
        closeStatusModal,
    };
}
