/* ============================================================
 *  Snapchat Pixel — E-commerce Events
 *  داخل GTM: كل حدث = Tag Custom HTML منفصل مع Trigger مذكور
 * ============================================================ */


// ------------------------------------------------------------
// 1) VIEW_CONTENT — Trigger: Custom Event = view_item
// ------------------------------------------------------------
snaptr('track', 'VIEW_CONTENT', {
  item_ids: [{{DLV - ecommerce.items.0.item_id}}],
  item_name: {{DLV - ecommerce.items.0.item_name}},
  price:    {{DLV - ecommerce.value}},
  currency: 'KWD'
});


// ------------------------------------------------------------
// 2) ADD_CART — Trigger: Custom Event = add_to_cart
// ------------------------------------------------------------
snaptr('track', 'ADD_CART', {
  item_ids: [{{DLV - ecommerce.items.0.item_id}}],
  price:    {{DLV - ecommerce.value}},
  currency: 'KWD'
});


// ------------------------------------------------------------
// 3) START_CHECKOUT — Trigger: Custom Event = begin_checkout
// ------------------------------------------------------------
snaptr('track', 'START_CHECKOUT', {
  price:    {{DLV - ecommerce.value}},
  currency: 'KWD'
});


// ------------------------------------------------------------
// 4) PURCHASE — Trigger: Custom Event = purchase  ⭐
// ------------------------------------------------------------
snaptr('track', 'PURCHASE', {
  price:          {{DLV - ecommerce.value}},
  currency:       'KWD',
  transaction_id: {{DLV - ecommerce.transaction_id}}
});


// ------------------------------------------------------------
// 5) SIGN_UP — Trigger: Click Element matches a[href*="wa.me"]
// ------------------------------------------------------------
snaptr('track', 'SIGN_UP');
