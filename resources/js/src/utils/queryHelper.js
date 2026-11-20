
// Convert an object to a query string
export const objectToQueryString = (obj) => {
    return Object.keys(obj).reduce((carry, key) => {
        // Check if the value is not null/undefined or is explicitly 0
        if (obj[key] || obj[key] === 0) {
            // Append the key-value pair to the query string
            return carry + `${key}=${(typeof obj[key] == 'object')
                ? Object.keys(obj[key]).length > 0
                    ? JSON.stringify(obj[key]) // Convert object to JSON string
                    : '' : obj[key]}&`
        }
        return carry; // Skip if value is falsy (excluding 0)
    }, '').replace(/&+$/, ''); // Remove trailing '&' if present
}

// Convert a query string to an object
export const queryStringToObject = (queryString = '') => {
    // Use URLSearchParams to parse the query string or use window location search if empty
    const urlParams = new URLSearchParams(queryString === '' ? window.location.search : queryString);
    const params = Object.fromEntries(urlParams.entries()); // Convert URLSearchParams to an object
    let queryObj = {};

    // Iterate over entries to parse nested JSON strings
    for (const [key, value] of Object.entries(params)) {
        // Check if value starts with '{' to detect JSON strings
        if (value[0] === '{') {
            queryObj[key] = JSON.parse(value); // Parse JSON string to object
        } else {
            queryObj[key] = value; // Assign value as-is
        }
    }
    return queryObj; // Return the resulting object
}
