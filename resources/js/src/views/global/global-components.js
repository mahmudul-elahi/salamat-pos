import { defineAsyncComponent } from "vue";

export const defineGlobalComponents = (app) => {
    app.component(
        "app-breadcrumb",
        defineAsyncComponent(() =>
            import("@/views/global/breadcrumb/Breadcrumb.vue")
        )
    );
    app.component(
        "app-input",
        defineAsyncComponent(() => import("@/views/global/input/FormInput.vue"))
    );
    app.component(
        "app-tab",
        defineAsyncComponent(() => import("@/views/global/tab/Tab.vue"))
    );
    app.component(
        "app-datatable",
        defineAsyncComponent(() =>
            import("@/views/global/datatable/Datatable.vue")
        )
    );
    app.component(
        "app-filter",
        defineAsyncComponent(() =>
            import("@/views/global/datatable/filters/Filter.vue")
        )
    );
    app.component(
        "app-modal",
        defineAsyncComponent(() => import("@/views/global/modal/Modal.vue"))
    );
    app.component(
        "app-delete-modal",
        defineAsyncComponent(() =>
            import("@/views/global/modal/DeleteModal.vue")
        )
    );
    app.component(
        "app-loader",
        defineAsyncComponent(() =>
            import("@/views/global/loader/LoaderSpin.vue")
        )
    );
    app.component(
        "app-button-loader",
        defineAsyncComponent(() =>
            import("@/views/global/loader/ButtonLoader.vue")
        )
    );
    app.component(
        "app-chart",
        defineAsyncComponent(() => import("@/views/global/charts/Chart.vue"))
    );
    app.component(
        "app-widget",
        defineAsyncComponent(() => import("@/views/global/widget/Widget.vue"))
    );
    app.component(
        "app-note",
        defineAsyncComponent(() => import("@/views/global/note/Note.vue"))
    );
};
