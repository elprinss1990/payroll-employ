/* ============================================================
 *  Meta Pixel — E-commerce Events
 *  داخل GTM: كل حدث = Tag Custom HTML منفصل + Trigger مذكور
 *  متغيرات {{...}} من DataLayer (أنظر wordpress/woocommerce-datalayer.php)
 *  للـ deduplication مع CAPI: eventID يجب يطابق order_id في CAPI
 * ============================================================ */


// ------------------------------------------------------------
// 1) ViewContent — Trigger: Custom Event = view_item
// ------------------------------------------------------------
fbq('track', 'ViewContent', {
  content_ids:  [{{DLV - ecommerce.items.0.item_id}}],
  content_type: 'product',
  content_name: {{DLV - ecommerce.items.0.item_name}},
  content_category: {{DLV - ecommerce.items.0.item_category}},
  value:        {{DLV - ecommerce.value}},
  currency:     'KWD'
});


// ------------------------------------------------------------
// 2) AddToCart — Trigger: Custom Event = add_to_cart
// ------------------------------------------------------------
fbq('track', 'AddToCart', {
  content_ids:  [{{DLV - ecommerce.items.0.item_id}}],
  content_type: 'product',
  content_name: {{DLV - ecommerce.items.0.item_name}},
  value:        {{DLV - ecommerce.value}},
  currency:     'KWD',
  contents: [{
    id:        {{DLV - ecommerce.items.0.item_id}},
    quantity:  {{DLV - ecommerce.items.0.quantity}}
  }]
});


// ------------------------------------------------------------
// 3) InitiateCheckout — Trigger: Custom Event = begin_checkout
// ------------------------------------------------------------
fbq('track', 'InitiateCheckout', {
  content_ids:  {{DLV - ecommerce.items.item_ids_array}},
  content_type: 'product',
  value:        {{DLV - ecommerce.value}},
  currency:     'KWD',
  num_items:    {{DLV - ecommerce.items.count}}
});


// ------------------------------------------------------------
// 4) Purchase — Trigger: Custom Event = purchase  ⭐ الأهم
// ------------------------------------------------------------
fbq('track', 'Purchase', {
  content_ids:  {{DLV - ecommerce.items.item_ids_array}},
  content_type: 'product',
  value:        {{DLV - ecommerce.value}},
  currency:     'KWD',
  num_items:    {{DLV - ecommerce.items.count}},
  order_id:     {{DLV - ecommerce.transaction_id}}
}, {
  eventID:      {{DLV - ecommerce.transaction_id}}  // لـ CAPI deduplication
});


// ------------------------------------------------------------
// 5) Lead — Trigger: Click Element matches CSS = a[href*="wa.me"]
// ------------------------------------------------------------
fbq('track', 'Lead', {
  content_name: 'WhatsApp Contact',
  value:    0.00,
  currency: 'KWD'
});
