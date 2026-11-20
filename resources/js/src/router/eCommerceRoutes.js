import {alreadyLogin} from "@/router/gurd.js";

export default [
    {
        path: "/",
        component: () => import("@/views/common/layout/Main.vue"),
        beforeEnter: [alreadyLogin],
        children: [
            {
                path: "/dashboard",
                name: "dashboard",
                component: () => import("@/views/landlord/dashboard/Dashboard.vue"),
            },
            {
                path: "/all-pages",
                name: "allPages",
                component: () => import("@/views/landlord/Ecommerce/page/Page.vue"),
                meta: {
                    permissions: ["view_all_pages"],
                },
            },
            {
                path: "/pages",
                name: "pages",
                component: () => import("@/views/landlord/Ecommerce/SEO/Page.vue"),
                meta: {
                    permissions: ["view_pages"],
                },
            },
            {
                path: "/page-builder/:id",
                name: "page_builder",
                component: () => import("@/views/landlord/Ecommerce/page/PageBuilder/PageBuilder.vue"),
                meta: {
                    permissions: ["view_page_blocks"],
                },
            },
            {
                path: "/tekzo-pages",
                name: "tekzo_pages",
                component: () => import("@/views/landlord/Ecommerce/SEO/TekzoPage.vue"),
                meta: {
                    permissions: ["view_tekzo_pages"],
                },
            },
            {
                path: "/coupon",
                name: "coupon",
                component: () => import("@/views/landlord/Ecommerce/coupon/Coupon.vue"),
                meta: {
                    permissions: ["view_coupons"],
                },
            },
            {
                path: "/gift-products",
                name: "giftProduct",
                component: () => import("@/views/landlord/Ecommerce/gifts/Gift.vue"),
                meta: {
                    permissions: ["view_gift_products"],
                },
            },
            {
                path: "/media-library",
                name: "mediaLibrary",
                component: () => import("@/views/landlord/Ecommerce/mediaLibrary/MediaLibrary.vue"),
                meta: {
                    permissions: ["view_medias"],
                },
            },
            {
                path: "/warranties",
                name: "warranty",
                component: () => import("@/views/landlord/Ecommerce/warranty/Warranty.vue"),
                meta: {
                    permissions: ["view_warranties"],
                },
            },
            {
                path: "/warranty-claims",
                name: "warrantyClaim",
                component: () => import("@/views/landlord/Ecommerce/warranty/WarrantyClaim.vue"),
                meta: {
                    permissions: ["create_warranty_claims"],
                },
            },
            {
                path: "/product-verifies",
                name: "productVerify",
                component: () => import("@/views/landlord/Ecommerce/verify/ProductVerify.vue"),
                meta: {
                    permissions: ["view_product_verifies"],
                }
            },
            {
                path: "/sliders",
                name: "sliders",
                component: () => import("@/views/landlord/Ecommerce/slider/Slider.vue"),
                meta: {
                    permissions: ["view_sliders"],
                },
            },
            {
                path: "/orders",
                name: "orders",
                component: () => import("@/views/landlord/Ecommerce/Order/Order.vue"),
                meta: {
                    permissions: ["view_orders"],
                },
            },
            {
                path: "/pending-orders",
                name: "pendingOrders",
                component: () => import("@/views/landlord/Ecommerce/Order/PendingOrder.vue"),
                meta: {
                    permissions: ["view_orders"],
                },
            },
            {
                path: "/hold-orders",
                name: "holdOrders",
                component: () => import("@/views/landlord/Ecommerce/Order/HoldOrder.vue"),
                meta: {
                    permissions: ["view_orders"],
                },
            },
            {
                path: "/confirm-orders",
                name: "confirmOrders",
                component: () => import("@/views/landlord/Ecommerce/Order/ConfirmOrder.vue"),
                meta: {
                    permissions: ["view_orders"],
                },
            },
            {
                path: "/approve-orders",
                name: "approveOrders",
                component: () => import("@/views/landlord/Ecommerce/Order/ApproveOrder.vue"),
                meta: {
                    permissions: ["view_orders"],
                },
            },
            {
                path: "/processing-orders",
                name: "processingOrders",
                component: () => import("@/views/landlord/Ecommerce/Order/ProcessingOrder.vue"),
                meta: {
                    permissions: ["view_orders"],
                },
            },
            {
                path: "/onway-orders",
                name: "onWayOrders",
                component: () => import("@/views/landlord/Ecommerce/Order/OnWayOrder.vue"),
                meta: {
                    permissions: ["view_orders"],
                },
            },
            {
                path: "/return-orders",
                name: "returnOrders",
                component: () => import("@/views/landlord/Ecommerce/Order/ReturnOrder.vue"),
                meta: {
                    permissions: ["view_orders"],
                },
            },
            {
                path: "/complete-orders",
                name: "completeOrders",
                component: () => import("@/views/landlord/Ecommerce/Order/CompleteOrder.vue"),
                meta: {
                    permissions: ["view_orders"],
                },
            },
            {
                path: "/rejected-orders",
                name: "rejectedOrders",
                component: () => import("@/views/landlord/Ecommerce/Order/RejectedOrder.vue"),
                meta: {
                    permissions: ["view_orders"],
                },
            },
            {
                path: "/order-details/:id",
                props: true,
                name: "orderDetails",
                component: () => import("@/views/landlord/Ecommerce/Order/OrderDetails.vue"),
                meta: {
                    permissions: ["view_order_details"],
                },
            },
            {
                path: "/edit-order/:id",
                props: true,
                name: "editOrder",
                component: () => import("@/views/landlord/Ecommerce/Order/EditOrder.vue"),
                meta: {
                    permissions: ["view_order_details"],
                },
            },
            {
                path: "/reviews",
                name: "reviews",
                component: () => import("@/views/landlord/Ecommerce/Review/Review.vue"),
                meta: {
                    permissions: ["view_reviews"],
                },
            },
            {
                path: "/sell-device-requests",
                name: "sellDeviceRequests",
                component: () => import("@/views/landlord/Ecommerce/SellDeviceRequest/SellDeviceRequest.vue"),
                meta: {
                    permissions: ["view_sell_device_requests"],
                },
            },
            {
                path: "/our-teams",
                name: "ourTeams",
                component: () => import("@/views/landlord/Ecommerce/ourTeam/OurTeam.vue"),
                meta: {
                    permissions: ["view_our_teams"],
                },
            },
            {
                path: "/careers",
                name: "careers",
                component: () =>
                    import("@/views/landlord/Ecommerce/career/CareerList.vue"),
                meta: {
                    permissions: ["view_careers"],
                },
            },
            {
                path: "/emi-banks",
                name: "emiBanks",
                component: () =>
                    import("@/views/landlord/Ecommerce/emi/EmiList.vue"),
                meta: {
                    permissions: ["view_emi_banks"],
                },
            },
            {
                path: "/faq-categories",
                name: "faqCategories",
                component: () =>
                    import("@/views/landlord/Ecommerce/faq/FaqCategory.vue"),
                meta: {
                    permissions: ["view_faq_categories"],
                },
            },
            {
                path: "/faqs",
                name: "faqs",
                component: () =>
                    import("@/views/landlord/Ecommerce/faq/Faq.vue"),
                meta: {
                    permissions: ["view_faqs"],
                },
            },
            {
                path: "/blog-categories",
                name: "blogCategories",
                component: () =>
                    import("@/views/landlord/Ecommerce/blog/BlogCategory.vue"),
                meta: {
                    permissions: ["view_blog_categories"],
                },
            },
            {
                path: "/blog-tags",
                name: "blogTags",
                component: () =>
                    import("@/views/landlord/Ecommerce/blog/BlogTag.vue"),
                meta: {
                    permissions: ["view_blog_tags"],
                },
            },
            {
                path: "/blog-posts",
                name: "blogPosts",
                component: () =>
                    import("@/views/landlord/Ecommerce/blog/BlogPost.vue"),
                meta: {
                    permissions: ["view_blog_posts"],
                },
            },
            {
                // Before the /:id/edit route so "pinned-order" is never taken
                // for a post id.
                path: "/blog-posts/pinned-order",
                name: "pinnedPostOrder",
                component: () =>
                    import("@/views/landlord/Ecommerce/blog/PinnedPostOrder.vue"),
                meta: {
                    permissions: ["update_blog_posts"],
                },
            },
            {
                path: "/blog-posts/create",
                name: "createBlogPost",
                component: () =>
                    import("@/views/landlord/Ecommerce/blog/CreateBlogPost.vue"),
                meta: {
                    permissions: ["create_blog_posts"],
                },
            },
            {
                path: "/blog-posts/:id/edit",
                name: "editBlogPost",
                props: true,
                component: () =>
                    import("@/views/landlord/Ecommerce/blog/EditBlogPost.vue"),
                meta: {
                    permissions: ["update_blog_posts"],
                },
            },
            {
                path: "/outlets",
                name: "outlets",
                component: () =>
                    import("@/views/landlord/Ecommerce/outlet/OutletList.vue"),
                meta: {
                    permissions: ["view_outlets"],
                },
            },
            {
                path: "/create-outlet",
                name: "createOutlet",
                component: () =>
                    import("@/views/landlord/Ecommerce/outlet/CreateOutlet.vue"),
                meta: {
                    permissions: ["create_outlets"],
                },
            },
            {
                path: "/edit-outlet/:id",
                name: "editOutlet",
                props: true,
                component: () =>
                    import("@/views/landlord/Ecommerce/outlet/EditOutlet.vue"),
                meta: {
                    permissions: ["update_outlets"],
                },
            },
            {
                path: "/create-career",
                name: "createCareer",
                component: () =>
                    import("@/views/landlord/Ecommerce/career/CreateCareer.vue"),
                meta: {
                    permissions: ["create_careers"],
                },
            },
            {
                path: "/edit-career/:id",
                name: "editCareer",
                props: true,
                component: () =>
                    import("@/views/landlord/Ecommerce/career/EditCareer.vue"),
                meta: {
                    permissions: ["update_careers"],
                },
            },
               {
                path: "/applicants",
                name: "applicants",
                component: () =>
                    import("@/views/landlord/Ecommerce/career/Applicants.vue"),
                meta: {
                    permissions: ["view_career_applicants"],
                },
            },
            {
                path: "/offers",
                name: "OfferList",
                component: () => import("@/views/landlord/Ecommerce/offer/OfferList.vue"),
                meta: { permissions: ["view_offers"] },
            },
            {
                path: "/offers/create",
                name: "OfferCreate",
                component: () => import("@/views/landlord/Ecommerce/offer/OfferCreate.vue"),
                meta: { permissions: ["create_offers"] },
            },
            {
                path: "/offers/:id/edit",
                name: "OfferEdit",
                component: () => import("@/views/landlord/Ecommerce/offer/OfferEdit.vue"),
                meta: { permissions: ["update_offers"] },
                props: true
            },
             {
                path: "/reels-videos",
                name: "ReelsVideoList",
                component: () => import("@/views/landlord/Ecommerce/reels/ReelsVideo.vue"),
                meta: { permissions: ["view_reels_videos"] },
            },
            {
                path: "/elite",
                name: "TekzoElite",
                component: () => import("@/views/landlord/Ecommerce/tekzoElite/TekzoElite.vue"),
                meta: { permissions: ["view_elites"] },
            },
            {
                path: "/popup-dialogs",
                name: "PopupDialogList",
                component: () => import("@/views/landlord/Ecommerce/popupDialog/PopupDialog.vue"),
                meta: { permissions: ["view_popup_dialogs"] },
            },
              {
                path: "/pre-owned-products",
                name: "PreOwnedProductList",
                component: () => import("@/views/landlord/Ecommerce/preOwned/PreOwnedProduct.vue"),
                meta: { permissions: ["view_pre_owned_products"] },
            },
            {
                path: "/add-pre-owned-products",
                name: "AddPreOwnedProduct",
                component: () => import("@/views/landlord/Ecommerce/preOwned/PreOwnedCreateProduct.vue"),
                meta: { permissions: ["create_pre_owned_products"] },
            },

            {
                path: "/edit-pre-owned-products/:id",
                props: true,
                name: "editPreOwnedProduct",
                component: () => import("@/views/landlord/Ecommerce/preOwned/PreOwnedEditProduct.vue"),
                meta: { permissions: ["create_pre_owned_products"] },
            },
        ]
    }
]
