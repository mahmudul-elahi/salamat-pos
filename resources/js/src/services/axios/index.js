import axios from "axios";
import {getToken} from "@/utils/token";
import {useAuth} from "@/services/auth";
import {useToast} from "vue-toastification";
import {urlGenerator} from "@/utils/urlGenerator.js";
import {getTenant} from "@/utils/tenant.js";

const toast = useToast();
const {logout} = useAuth();


// Create Axios instance
const instance = axios.create({
    baseURL: `${urlGenerator('api')}`,
    timeout: 10000, // Set timeout to 10 seconds
    headers: {
        Accept: "application/json",
        "Content-Type": "application/json",
    },
});

// Request interceptor to add authorization token
instance.interceptors.request.use(
    async (config) => {
        const token = getToken();
        const tenant = getTenant();
        if (token) {
            // Add Bearer token to the Authorization header
            config.headers.Authorization = `Bearer ${token}`;
            if (tenant) {
                config.headers['X-domain'] = tenant;
            }
        }
        return config;
    },
    (error) => {
        // Handle request error
        return Promise.reject(error);
    }
);

// Response interceptor to handle global errors
instance.interceptors.response.use(
    (response) => {
        // Handle successful response
        return response;
    },
    (error) => {
        if (error?.code === 'ERR_CANCELED' || error?.name === 'CanceledError') {
            return Promise.reject(error);
        }

        const {response} = error;

        // Check for specific error status codes and handle accordingly
        if (response) {
            switch (response.status) {
                case 401: // Unauthorized
                    logout();
                    break;

                case 424: // Failed dependency
                    toast.error(
                        "Failed to create the resource due to a dependency failure. Please make sure all required dependencies are fulfilled."
                    );
                    break;

                case 419: // Session expired
                    toast.error("Session expired. Please try again.");
                    break;

                // case 404: // Not found
                //     window.location.replace(urlGenerator('/404'));
                //     break;

                case 403: // Forbidden
                   // window.location.replace(urlGenerator('/403'));
                    break;

                default:
                    break;
            }
        } else {
            // Handle cases where response is not available
            toast.error("Failed to fetch data. Please check your internet connection and try again.");
        }

        return Promise.reject(error);
    }
);

export default instance;
