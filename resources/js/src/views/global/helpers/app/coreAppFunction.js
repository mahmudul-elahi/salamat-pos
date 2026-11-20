export default class coreAppFunction {
    // Get the base URL from localStorage or default to the current origin
    static baseUrl() {
        const app_url = window.localStorage.getItem('base_url');
        return app_url || window.location.origin;
    }

    // Construct a full URL by combining the base URL with a given path
    static getAppUrl(path) {
        return `${this.baseUrl()}/${path}`;
    }

    // Retrieve the value of a query string parameter by key
    static getQueryStringValue(key) {
        const urlParams = new URLSearchParams(window.location.search);
        return urlParams.get(key);
    }

    // Check if a given argument is a function
    static isFunction(func) {
        return typeof func === "function";
    }

    // Check if a given argument is undefined
    static isUndefined(obj) {
        return typeof obj === "undefined";
    }

    // Split a string by slashes and return the last segment
    static splitNameBySlas(item) {
        if (item.includes("/")) {
            let itemArr = item.split("/");
            return itemArr[itemArr.length - 1];
        }
        return item;
    }
}
