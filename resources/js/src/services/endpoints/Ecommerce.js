export const ORDERS = 'admin/ecommerce/orders'
export const ORDER_UPDATE_ITEM_VARIANT = (orderId) => `admin/ecommerce/orders/${orderId}/update-item-variant`
export const ORDER_UPDATE_GIFT_ITEM = (orderId) => `admin/ecommerce/orders/${orderId}/update-gift-item`
export const ORDER_ADD_ITEM = (orderId) => `admin/ecommerce/orders/${orderId}/add-item`
export const ORDER_DELETE_ITEM = (orderId, itemId) => `admin/ecommerce/orders/${orderId}/items/${itemId}`
export const ORDER_SSLCOMMERZ_CHECK_STATUS = 'admin/ecommerce/orders/sslcommerz/check-status'
export const COUPON = 'admin/ecommerce/coupons'
export const GIFTS = 'admin/ecommerce/gift-products'
export const GIFT_STATUS = (id) => `admin/ecommerce/gift-products/${id}/status`
export const GIFT_BY_PRODUCT = (productId) => `admin/ecommerce/gift-products-by-product/${productId}`
export const GIFT_BY_PRE_OWNED = (preOwnedId) => `admin/ecommerce/gift-products-by-pre-owned/${preOwnedId}`
export const COUPON_STATUS_CHANGE = 'admin/ecommerce/coupons/status-update'
export const PAGES = 'admin/ecommerce/pages'
export const PAGE_BLOCKS = (pageId) => `admin/ecommerce/pages/${pageId}/blocks`
export const ALL_PAGES = 'admin/ecommerce/all-pages'
export const TEKZO_PAGES = 'admin/ecommerce/tekzo-pages'
export const WARRANTIES = 'admin/ecommerce/warranties'
export const WARRANTY_CLAIMS = 'admin/ecommerce/warranty-claims'
export const PRODUCT_VERIFIES = 'admin/ecommerce/product-verifies'
export const ORDER_DETAILS = 'admin/ecommerce/order-details'
export const ORDER_INVOICE_DATA = 'admin/ecommerce/order-invoice-data'
export const MEDIAS = "/admin/ecommerce/medias";
export const NEWS = "/admin/ecommerce/news";
export const BLOGS = "/admin/ecommerce/blogs";
export const SLIDERS = "/admin/ecommerce/sliders";
export const SLIDER_STATUS_CHANGE = "/admin/ecommerce/sliders/status-update";
export const MEDIAS_BULK_DELETE = "/admin/ecommerce/medias/bulk-delete";
export const MEDIA_FOLDERS = "/admin/ecommerce/folder-create";
export const MEDIA_ALT_TEXT = "/admin/ecommerce/medias/alt-text";
export const REVIEWS = "/admin/ecommerce/reviews";
export const SELL_DEVICE_REQUESTS = "/admin/ecommerce/sell-device-requests";
export const OUR_TEAMS = "/admin/ecommerce/our-teams";
export const CAREERS = "/admin/ecommerce/careers";
export const CAREERS_STATUS = "/admin/ecommerce/careers/status";
export const FAQ_CATEGORIES = "/admin/ecommerce/faq-categories";
export const ACTIVE_FAQ_CATEGORIES = "/admin/ecommerce/faq-categories-active";
export const FAQS = "/admin/ecommerce/faqs";
export const OUTLETS = "/admin/ecommerce/outlets";
export const OFFERS = '/admin/ecommerce/offers';
export const OFFER_PRODUCTS = '/admin/ecommerce/offer-products';
export const REELS_VIDEOS = '/admin/ecommerce/reels-videos';
export const POPUP_DIALOGS = '/admin/ecommerce/popup-dialogs';
export const PRE_OWANED_PRODUCTS = '/admin/ecommerce/pre-owned-products';
export const EMI_BANKS = '/admin/ecommerce/emi-banks';
export const PRE_OWANED_PRODUCT_SEOS = '/admin/ecommerce/pre-owned-product-seos';

export const BLOG_CATEGORIES = '/admin/ecommerce/blog-categories';
export const BLOG_TAGS = '/admin/ecommerce/blog-tags';
export const BLOG_POSTS = '/admin/ecommerce/blog-posts';
export const BLOG_POST_TOGGLE = (id, field) => `/admin/ecommerce/blog-posts/${id}/toggle/${field}`;

export const SELECTED_CARES = '/admin/selected/cares';
