import {defineStore} from "pinia";
import {ref} from "vue";

export const useAppStore = defineStore("app", () => {

    const isLoaded = ref(false);

    const setIsLoaded = (value) => {
        isLoaded.value = value;
    };


    return {isLoaded, setIsLoaded};

});
