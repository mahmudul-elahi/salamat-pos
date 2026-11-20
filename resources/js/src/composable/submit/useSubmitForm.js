import { ref, onMounted } from "vue";
import { useToast } from "vue-toastification";
import { Modal } from "bootstrap";
import useEmitter from "@/composable/emitter/useEmitter";
import Axios from "@/services/axios";

export const useSubmitForm = (props, emit, editData = "yes") => {
    const toast = useToast();
    const errors = ref({});
    const formData = ref({});
    const preloader = ref(false);
    const emitter = useEmitter();

    const save = (url, formData, type = "patch", event = null) => {
        errors.value = {};
        preloader.value = true;
        if (props.selectedUrl) {
            if (type === "patch") {
                Axios.patch(url, formData)
                    .then((response) => {
                        afterSuccess(response, event);
                    })
                    .catch(({ response }) => {
                        afterError(response);
                    })
                    .finally(() => afterFinalResponse());
            } else {
                Axios.put(url, formData)
                    .then((response) => {
                        afterSuccess(response, event);
                    })
                    .catch(({ response }) => {
                        afterError(response);
                    })
                    .finally(() => afterFinalResponse());
            }
        } else {
            Axios.post(url, formData)
                .then((response) => {
                    afterSuccess(response, event);
                })
                .catch(({ response }) => {
                    afterError(response);
                })
                .finally(() => afterFinalResponse());
        }
    };
    const afterError = (response) => {
        if (response.status === 422) {
            errors.value = response?.data?.errors;
        } else if (response.status === 413) {
            toast.error('File is too large.');
        } else {
            errors.value = {};
            toast.error(response?.data?.message);
        }
    };
    const afterSuccess = (response, event) => {
        toast.success(response?.data?.message);
        emitter.emit("reload-" + props.tableId);
        if (event) {
            emit("success");
        }
        closeModal();
    };

    const afterFinalResponse = () => {
        preloader.value = false;
    };

    const closeModal = () => {
        const element = document.getElementById(props.modalId);
        const modal = element ? Modal.getInstance(element) : null;

        // Optional chaining matters: callers flip their v-if off the "close"
        // event, so if this throws (no element, or no Bootstrap instance on it)
        // the event never fires and the modal stays on screen with the page
        // locked behind it — which reads as the app hanging.
        modal?.hide();

        emit("close");
    };

    const pageLoader = ref(false);
    const getEditData = () => {
        pageLoader.value = true;
        Axios.get(props.selectedUrl)
            .then(({ data }) => {
                formData.value = data;
            })
            .finally(() => {
                pageLoader.value = false;
            });
    };

    onMounted(() => {
        if (props.selectedUrl && editData === "yes") {
            getEditData();
        }
    });

    return {
        preloader,
        pageLoader,
        formData,
        errors,
        save,
        closeModal,
        getEditData,
        afterSuccess,
        afterError,
        afterFinalResponse,
    };
};
