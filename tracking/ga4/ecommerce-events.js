/* ============================================================
 *  GA4 — Enhanced E-commerce Events
 *
 *  طريقتان لاستخدام هذا الملف:
 *
 *  (أ) عبر GTM (الموصى به):
 *      ملف woocommerce-datalayer.php يدفع الأحداث للـ dataLayer
 *      → في GTM أنشئ GA4 Event tag مع:
 *         - Event Name:  view_item / add_to_cart / begin_checkout / purchase
 *         - Trigger:     Custom Event matching نفس الاسم
 *         - Parameters:  More Settings → Ecommerce → Send Ecommerce data = DataLayer
 *      ما تحتاج أي كود — GTM يقرأ ecommerce تلقائياً.
 *
 *  (ب) مباشرة في Theme (gtag):
 *      انسخ المقتطفات أدناه داخل Hooks مناسبة في functions.php
 *      (أو استخدم ملف woocommerce-datalayer.php الذي يفعل ذلك).
 * ============================================================ */


// ------------------------------------------------------------
// 1) view_item
// ------------------------------------------------------------
gtag('event', 'view_item', {
  currency: 'KWD',
  value:    {{product_price}},
  items: [{
    item_id:       '{{product_sku}}',
    item_name:     '{{product_name}}',
    item_category: '{{product_category}}',
    price:         {{product_price}},
    quantity:      1
  }]
});


// ------------------------------------------------------------
// 2) add_to_cart
// ------------------------------------------------------------
gtag('event', 'add_to_cart', {
  currency: 'KWD',
  value:    {{product_price}} * {{quantity}},
  items: [{
    item_id:       '{{product_sku}}',
    item_name:     '{{product_name}}',
    item_category: '{{product_category}}',
    price:         {{product_price}},
    quantity:      {{quantity}}
  }]
});


// ------------------------------------------------------------
// 3) begin_checkout
// ------------------------------------------------------------
gtag('event', 'begin_checkout', {
  currency: 'KWD',
  value:    {{cart_total}},
  items:    {{cart_items_array}}
});


// ------------------------------------------------------------
// 4) purchase  ⭐
// ------------------------------------------------------------
gtag('event', 'purchase', {
  transaction_id: '{{order_id}}',
  value:          {{order_total}},
  tax:            {{order_tax}},
  shipping:       {{order_shipping}},
  currency:       'KWD',
  items: [{
    item_id:       '{{product_sku}}',
    item_name:     '{{product_name}}',
    item_category: '{{product_category}}',
    price:         {{item_price}},
    quantity:      {{item_quantity}}
  }]
});


// ------------------------------------------------------------
// 5) generate_lead  (WhatsApp click)
// ------------------------------------------------------------
gtag('event', 'generate_lead', {
  currency: 'KWD',
  value:    0,
  method:   'WhatsApp'
});
