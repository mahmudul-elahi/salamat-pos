import coreAppFunction from "@/views/global/helpers/app/coreAppFunction";

// Custom hook that provides utility functions from coreAppFunction
export const useCoreAppFunction = () => {
    // Check if the provided argument is a function
    const isFunction = (func) => coreAppFunction.isFunction(func);

    // Retrieve the application URL based on the provided URL or path
    const getAppUrl = (url) => coreAppFunction.getAppUrl(url);

    // Check if the provided argument is undefined
    const isUndefined = (obj) => coreAppFunction.isUndefined(obj);

    // Retrieve the value of a query string parameter by its key
    const getQueryStringValue = (key) => coreAppFunction.getQueryStringValue(key);

    // Return an object containing the utility functions
    return { isFunction, getAppUrl, isUndefined, getQueryStringValue };
}
