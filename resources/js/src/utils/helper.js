import { format } from "date-fns";
import { fromZonedTime } from "date-fns-tz";
import { useSettingStore } from "@/stores/setting/setting.js";
import { storeToRefs } from "pinia";

const settingStore = useSettingStore();
const { settings } = storeToRefs(settingStore);

export const optional = (obj, ...props) => {
    if (!obj || typeof obj !== "object") return undefined;
    const val = obj[props[0]];
    if (props.length === 1 || !val) return val;
    const rest = props.slice(1);
    return optional.apply(null, [val, ...rest]);
};

export const serverDateFormat = (date) => {
    if (!date) return null; // Handle invalid or null input
    try {
        return format(new Date(date), "yyyy-MM-dd"); // Adjust format as needed
    } catch (error) {
        return null;
    }
};
export const customDateFormat = (date, format) => {
    if (!date) return null; // Handle invalid or null input
    try {
        return format(new Date(date), format); // Adjust format as needed
    } catch (error) {
        return null;
    }
};

export const textTruncate = (str, length = 50, ending = "...") => {
    if (!str) return "";

    // Remove HTML tags
    str = str.replace(/(<([^>]+)>)/gi, "");

    // Replace non-breaking spaces with regular spaces
    str = str.replace(/&nbsp;/g, " ");

    // Truncate the string if it exceeds the desired length
    if (str.length > length) {
        return str.substring(0, length - ending.length) + ending;
    } else {
        return str;
    }
};

export const formDataAssigner = function (
    formData = new FormData(),
    dataObject
) {
    Object.keys(dataObject).map((key) => {
        if (
            dataObject[key] &&
            !dataObject[key].length > 0 &&
            Object.keys(dataObject[key]).length > 0
        ) {
            Object.keys(dataObject[key]).map((childKey) => {
                return formData.append(
                    key + `[${childKey}]`,
                    dataObject[key][childKey]
                );
            });
        } else if (Array.isArray(dataObject[key])) {
            dataObject[key].map((el, index) => {
                Object.keys(el).map((objectKeys) => {
                    formData.append(
                        key + `[${index}][${objectKeys}]`,
                        el[objectKeys]
                    );
                });
            });
        } else {
            return formData.append(key, dataObject[key]);
        }
    });
    return formData;
};

export const date_format = () => {
    return {
        "d-m-Y": "dd-MM-yyyy",
        "m-d-Y": "MM-dd-yyyy",
        "Y-m-d": "yyyy-MM-dd",
        "m/d/Y": "MM/DD/YYYY",
        "d/m/Y": "DD/MM/YYYY",
        "Y/m/d": "YYYY/MM/DD",
        "m.d.Y": "MM.DD.YYYY",
        "d.m.Y": "DD.MM.YYYY",
        "Y.m.d": "YYYY.MM.DD",
    };
};

export const formatted_date = () => {
    return date_format()[optional(settings.value, "date_format")];
};

export const formatted_time = () => {
    return optional(settings.value, "time_format") === "h" ? "12" : "24";
};

export const time_format = () => {
    const settingsData = settings.value;
    const format = optional(settingsData, "time_format");
    return format === "h"
        ? `${settingsData.time_format}:mm A`
        : `${settingsData.time_format}:mm`;
};

export const formatDateToLocal = (date, withTime = false) => {
    if (!date) return "";

    const formatString = withTime
        ? `${formatted_date()} hh:mm a`
        : formatted_date();

    let datetimeStr = date;

   const timeZone = Intl.DateTimeFormat().resolvedOptions().timeZone; // Get browser's time zone
    const zonedDate = fromZonedTime(new Date(datetimeStr), timeZone);

    return format(zonedDate, formatString);
};

export const getThousandSeparator = () => {
    return settings.value.thousand_separator === "space"
        ? " "
        : settings.value.thousand_separator;
};

export const getCurrencySymbol = () => {
    return settings.value.currency_symbol
        ? settings.value.currency_symbol
        : " ";
};
export const getCurrencyPosition = () => {
    return settings.value.currency_position
        ? settings.value.currency_position
        : " ";
};
export const getDecimalSeparator = () => {
    return settings.value.decimal_separator
        ? settings.value.decimal_separator
        : " ";
};
export const getNumberOfDecimal = () => {
    return settings.value.number_of_decimal
        ? settings.value.number_of_decimal
        : " ";
};

// export const numberFormatter = (number) => {
//     if (!isNaN(parseFloat(number))) {
//         number = parseFloat(number).toFixed(getNumberOfDecimal());
//         let parts = number.toString().split(".");
//         parts[0] = parts[0].replace(
//             /\B(?=(\d{3})+(?!\d))/g,
//             getThousandSeparator()
//         );
//         return parts.join(getDecimalSeparator());
//     }
//     return 0;
// };

export const numberFormatter = (number) => {
    if (isNaN(parseFloat(number))) return "0";

    // Save negative sign if present
    const isNegative = parseFloat(number) < 0;

    // Convert to absolute number for formatting
    number = Math.abs(parseFloat(number)).toFixed(getNumberOfDecimal());

    let parts = number.split(".");
    let integerPart = parts[0];
    let lastThree = integerPart.slice(-3);
    let otherNumbers = integerPart.slice(0, -3);

    if (otherNumbers !== "") {
        lastThree = "," + lastThree;
    }

    const formattedInteger =
        otherNumbers.replace(/\B(?=(\d{2})+(?!\d))/g, ",") + lastThree;

    const decimalPart = parts[1] ? getDecimalSeparator() + parts[1] : "";

    // Add negative sign back if necessary
    return (isNegative ? "-" : "") + formattedInteger + decimalPart;
};


export const numberWithCurrencySymbol = (number) => {
    let modifiedValue;
    let formattedNumber = numberFormatter(number).toString();
    let currencySymbol = getCurrencySymbol().toString();

    if (getCurrencyPosition() === "prefix_with_space") {
        modifiedValue = currencySymbol + " " + formattedNumber;
    } else if (getCurrencyPosition() === "prefix_only") {
        modifiedValue = currencySymbol + formattedNumber;
    } else if (getCurrencyPosition() === "suffix_with_space") {
        modifiedValue = formattedNumber + " " + currencySymbol;
    } else {
        modifiedValue = formattedNumber + currencySymbol;
    }
    return modifiedValue;
};
export const formatNumberShortWithCurrency = (value) => {
    if (!value && value !== 0) return "-";

    // Convert string to number safely
    const sanitizedValue = typeof value === "string" ? value.replace(/,/g, "") : value;
    const number = Number(sanitizedValue);

    if (isNaN(number)) return "-";

    const abs = Math.abs(number);
    let formatted = 0;
    let suffix = "";

    const truncate = (num, decimals) => {
        const factor = Math.pow(10, decimals);
        return Math.floor(num * factor) / factor;
    };

    if (abs >= 1_000_000_000) {
        formatted = truncate(number / 1_000_000_000, 2);
        suffix = "B";
    } else if (abs >= 1_000_000) {
        formatted = truncate(number / 1_000_000, 2);
        suffix = "M";
    } else if (abs >= 1_000) {
        formatted = truncate(number / 1_000, 2);
        suffix = "K";
    } else {
        formatted = number;
    }

    const shortNumber = `${formatted}${suffix}`;
    const symbol = getCurrencySymbol();
    const position = getCurrencyPosition();

    switch (position) {
        case "prefix_with_space":
            return `${symbol} ${shortNumber}`;
        case "prefix_only":
            return `${symbol}${shortNumber}`;
        case "suffix_with_space":
            return `${shortNumber} ${symbol}`;
        case "suffix_only":
        default:
            return `${shortNumber}${symbol}`;
    }
};




