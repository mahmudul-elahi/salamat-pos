import axios from "axios";
import {urlGenerator} from "@/utils/urlGenerator";
import {LOGIN_API} from "@/services/endpoints/app.js";
import router from "@/router/index.js";
import {useToast} from "vue-toastification";
import {disconnectEcho} from "@/plugins/echo.js";
import {removeTenant} from "@/utils/tenant.js";

const toast = useToast();

export const useAuth = () => {
    const login = (payload) => {
        return axios.post(urlGenerator(LOGIN_API), payload)
    }
    const logout = () => {
        localStorage.removeItem('access_token')
        disconnectEcho()
        removeTenant()
        router.push({name: 'login'}).then(() => {
            toast.success('Logout successfully')
        })
    }

    return {logout, login}

}
