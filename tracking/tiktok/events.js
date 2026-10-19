/* ============================================================
 *  TikTok Pixel — E-commerce Events
 *  داخل GTM: كل حدث = Tag Custom HTML منفصل مع Trigger مذكور
 *  يعتمد على dataLayer من wordpress/woocommerce-datalayer.php
 * ============================================================ */


// ------------------------------------------------------------
// 1) ViewContent — Trigger: Custom Event = view_item
// ------------------------------------------------------------
ttq.track('ViewContent', {
  content_id:   {{DLV - ecommerce.items.0.item_id}},
  content_type: 'product',
  content_name: {{DLV - ecommerce.items.0.item_name}},
  value:        {{DLV - ecommerce.value}},
  currency:     'KWD',
  quantity:     1
});


// ------------------------------------------------------------
// 2) AddToCart — Trigger: Custom Event = add_to_cart
// ------------------------------------------------------------
ttq.track('AddToCart', {
  content_id:   {{DLV - ecommerce.items.0.item_id}},
  content_type: 'product',
  content_name: {{DLV - ecommerce.items.0.item_name}},
  value:        {{DLV - ecommerce.value}},
  currency:     'KWD',
  quantity:     {{DLV - ecommerce.items.0.quantity}}
});


// ------------------------------------------------------------
// 3) InitiateCheckout — Trigger: Custom Event = begin_checkout
// ------------------------------------------------------------
ttq.track('InitiateCheckout', {
  content_type: 'product',
  value:        {{DLV - ecommerce.value}},
  currency:     'KWD'
});


// ------------------------------------------------------------
// 4) CompletePayment — Trigger: Custom Event = purchase  ⭐
// ------------------------------------------------------------
ttq.track('CompletePayment', {
  content_type: 'product',
  value:        {{DLV - ecommerce.value}},
  currency:     'KWD',
  content_id:   {{DLV - ecommerce.transaction_id}}
});


// ------------------------------------------------------------
// 5) Contact — Trigger: Click Element matches a[href*="wa.me"]
// ------------------------------------------------------------
ttq.track('Contact', {
  content_name: 'WhatsApp'
});
