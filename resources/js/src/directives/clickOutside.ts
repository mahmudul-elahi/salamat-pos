import { DirectiveBinding } from 'vue';

interface ClickOutsideElement extends HTMLElement {
    clickOutsideEvent: (event: Event) => void;
}

export default {
    beforeMount(el: ClickOutsideElement, binding: DirectiveBinding) {
        el.clickOutsideEvent = (event: Event) => {
            if (!(el === event.target || el.contains(event.target as Node))) {
                binding.value();
            }
        };
        document.addEventListener('click', el.clickOutsideEvent);
    },
    unmounted(el: ClickOutsideElement) {
        document.removeEventListener('click', el.clickOutsideEvent);
    }
};
