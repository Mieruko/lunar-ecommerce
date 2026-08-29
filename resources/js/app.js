/**
 * LUNAR JEWELS storefront entry point.
 * Each concern lives in resources/js/components/ — this file only orchestrates.
 * All modules are progressive enhancements over server-rendered Blade pages;
 * no business data is computed on the client (backend = source of truth).
 */
import initAnimations from './components/animations.js';
import initToasts from './components/toast.js';
import initHeader from './components/header.js';
import initCatalog from './components/catalog.js';
import initProductGallery from './components/product-gallery.js';
import initCart from './components/cart.js';
import initShipping from './components/shipping.js';
import initSupportChat from './components/support-chat.js';

initToasts();
initHeader();
initAnimations();
initCatalog();
initProductGallery();
initCart();
initShipping();
initSupportChat();
