<template>
    <div>
        <vue-progress-bar />
        <div class="h-100vh login-page-bg">
            <router-view/>
        </div>

    </div>
</template>
<script setup>
import { onMounted, onBeforeUnmount } from "vue";
import { Dropdown } from "bootstrap";
const close = () => {
    const dropdownMenu = document.querySelector(".dropdown-menu.show");
    if (!dropdownMenu) {
        return;
    }
    const dropdownInstance = Dropdown.getInstance(dropdownMenu);
    if (dropdownInstance) {
        dropdownInstance.hide();
    } else {
        new Dropdown(dropdownMenu).hide();
    }
};
function findParentClass(node, clsName) {
    if (node && !node.className) {
        return findParentClass(node.parentNode, clsName);
    } else if (node && node.className) {
        if (!node.classList.contains(clsName)) {
            return findParentClass(node.parentNode, clsName);
        } else {
            return true;
        }
    }
}

const closeDropdown = (e) => {
    if (findParentClass(e.target, "dropdown")) {
        // clicked inside the dropdown
        if (findParentClass(e.target, "dropdown-menu")) {
            // Clicked inside the dropdown menu
            close(); // Close the dropdown
        }
    } else {
        // clicked outside the dropdown
        close(); // Close the dropdown
    }
};
// The "storage" event only fires in tabs OTHER than the one that made the
// change (per spec), so this only ever reloads the tabs that didn't
// initiate the switch — the switching tab already reloads itself in
// ShopSwitcher.vue right after writing the new value.
const onTenantChangedElsewhere = (e) => {
    if (e.key === "tenant" && e.newValue && e.newValue !== e.oldValue) {
        window.location.reload();
    }
};

onMounted(() => {
    document.addEventListener("click", closeDropdown);
    window.addEventListener("storage", onTenantChangedElsewhere);
});

onBeforeUnmount(() => {
    window.removeEventListener("storage", onTenantChangedElsewhere);
});
</script>
<script>
export default {
    created() {
        //  [App.vue specific] When App.vue is first loaded start the progress bar
        this.$Progress.start();
        //  hook the progress bar to start before we move router-view
        this.$router.beforeEach((to, from, next) => {
            //  does the page we want to go to have a meta.progress object
            if (to.meta.progress !== undefined) {
                let meta = to.meta.progress;
                // parse meta tags
                this.$Progress.parseMeta(meta);
            }
            //  start the progress bar
            this.$Progress.start();
            //  continue to next page
            next();
        });
        //  hook the progress bar to finish after we've finished moving router-view
        this.$router.afterEach((to, from) => {
            //  finish the progress bar
            this.$Progress.finish();
        });
    },
};
</script>

<style scoped lang="scss">

</style>
