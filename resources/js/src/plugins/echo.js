import Echo from "laravel-echo";
import Pusher from "pusher-js";
import instance from "@/services/axios/index.js";

window.Pusher = Pusher;

let echoInstance = null;

export function getEcho() {
    if (echoInstance) {
        return echoInstance;
    }

    echoInstance = new Echo({
        broadcaster: "reverb",
        key: import.meta.env.VITE_REVERB_APP_KEY,
        wsHost: import.meta.env.VITE_REVERB_HOST,
        wsPort: import.meta.env.VITE_REVERB_PORT,
        wssPort: import.meta.env.VITE_REVERB_PORT,
        forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? "http") === "https",
        enabledTransports: ["ws", "wss"],
        authorizer: (channel) => ({
            authorize: (socketId, callback) => {
                instance
                    .post("/admin/broadcasting/auth", {
                        socket_id: socketId,
                        channel_name: channel.name,
                    })
                    .then((response) => callback(false, response.data))
                    .catch((error) => callback(true, error));
            },
        }),
    });

    return echoInstance;
}

export function disconnectEcho() {
    if (echoInstance) {
        echoInstance.disconnect();
        echoInstance = null;
    }
}
