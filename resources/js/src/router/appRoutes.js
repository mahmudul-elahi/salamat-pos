import {alreadyLogin, notLoginYet} from "@/router/gurd.js";

export default [
    {
        path: "/:catchAll(.*)",
        name: "notFound",
        component: () => import("@/views/common/errors/NotFound.vue"),
    },
    {
        path: "/403",
        name: "accessForbidden",
        component: () => import("@/views/common/errors/403.vue"),
        beforeEnter: [alreadyLogin],
    },
    {
        path: "/login",
        name: "login",
        component: () => import("@/views/common/auth/login/Login.vue"),
        beforeEnter: [notLoginYet],
    },
    {
        path: "/forgot-password",
        name: "forgotPassword",
        component: () => import("@/views/common/auth/password/ForgotPassword.vue"),
        beforeEnter: [notLoginYet],
    },
    {
        path: "/confirm-password",
        name: "confirmPassword",
        component: () => import("@/views/common/auth/password/ConfirmPassword.vue"),
        beforeEnter: [notLoginYet],
    },
    {
        path: "/register",
        name: "register",
        component: () => import("@/views/common/auth/register/Register.vue"),
        beforeEnter: [notLoginYet],
    },
    {
        path: "/user-join",
        name: "userJoin",
        component: () => import("@/views/common/auth/join/UserJoin.vue"),
        beforeEnter: [notLoginYet],
    },
    {
        path: "/",
        component: () => import("@/views/common/layout/Main.vue"),
        beforeEnter: [alreadyLogin],
        children: [
            {
                path: "",
                redirect: "dashboard",
            },
            {
                path: "profiles",
                name: "profiles",
                component: () => import("@/views/common/profile/Index.vue"),
            }
        ]
    }
];
