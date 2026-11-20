import {ref} from "vue";
import {useI18n} from "vue-i18n";
import {useToast} from "vue-toastification";
import Axios from "@/services/axios/index.js";
import {Check, Circle, DeliveryTruck, DoubleCheck, UndoCircle, XmarkCircle} from "@iconoir/vue";
import useEmitter from "@/composable/emitter/useEmitter.js";
import {WARRANTIES} from "@/services/endpoints/Ecommerce.js";

export default function useWarrantyStatus(tableId) {
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
        approved: {
            message: t("are_you_sure_to_approved_this_warranty"),
            icon: Check,
            color: "success",
        },
        rejected: {
            message: t("are_you_sure_to_rejected_this_warranty"),
            icon: XmarkCircle,
            color: "danger",
        },
    };

    // 🔹 Open modal dynamically
    const openStatusModal = (type, row) => {
        const info = statusTypes[type];
        modalData.value = {
            url: `${WARRANTIES}/${row.id}/${type}`,
            type,
            icon: info.icon,
            title: t("are_you_sure"),
            message: info.message,
        };
        showStatusModal.value = true;
    };

    // 🔹 Update warranty status via API
    const updateWarrantyStatus = async () => {
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
        updateWarrantyStatus,
        closeStatusModal,
    };
}
