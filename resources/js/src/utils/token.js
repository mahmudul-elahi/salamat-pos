export const getToken = () => {
    return localStorage.getItem("access_token");
}

export const setToken = (token) => {
    return localStorage.setItem("access_token", token)
}
