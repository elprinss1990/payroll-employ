<?php
/**
 * ============================================================
 *  woocommerce-datalayer.php — دفع أحداث WooCommerce لـ dataLayer
 *  يُلصق في: functions.php أو Plugin: Code Snippets / WPCode
 *
 *  يعبّئ dataLayer بأحداث جاهزة ليستهلكها GTM:
 *     view_item        → عند مشاهدة منتج
 *     add_to_cart      → عند الضغط على "أضف للسلة"
 *     begin_checkout   → عند الدخول لصفحة الدفع
 *     purchase         → عند صفحة شكراً / order-received
 *
 *  داخل GTM:
 *     1. أنشئ Triggers من نوع "Custom Event" بأسماء الأحداث أعلاه
 *     2. استخدم dataLayer Variables لقراءة قيم ecommerce.items / value
 *     3. اربطها بـ Tags: Meta Pixel / GA4 / TikTok / Snapchat
 *
 *  بديل أسهل: Plugin "Google Tag Manager for WordPress"
 *             (يعمل نفس الوظيفة تلقائياً) — في هذه الحالة
 *             احذف هذا الملف لتجنب التكرار.
 * ============================================================
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

// --------------------------------------------------------------
//  1. تهيئة dataLayer في <head> قبل GTM
// --------------------------------------------------------------
add_action( 'wp_head', 'prinssfut_datalayer_init', 1 );
function prinssfut_datalayer_init() {
    echo "\n<script>window.dataLayer = window.dataLayer || [];</script>\n";
}

// --------------------------------------------------------------
//  2. view_item — عند صفحة المنتج المفرد
// --------------------------------------------------------------
add_action( 'wp_footer', 'prinssfut_dl_view_item' );
function prinssfut_dl_view_item() {
    if ( ! function_exists( 'is_product' ) || ! is_product() ) { return; }

    global $product;
    if ( ! $product instanceof WC_Product ) {
        $product = wc_get_product( get_the_ID() );
    }
    if ( ! $product instanceof WC_Product ) { return; }

    $cat_ids   = wc_get_product_cat_ids( $product->get_id() );
    $cat_name  = '';
    if ( ! empty( $cat_ids ) ) {
        $term = get_term( $cat_ids[0], 'product_cat' );
        if ( $term && ! is_wp_error( $term ) ) { $cat_name = $term->name; }
    }

    $payload = array(
        'event'     => 'view_item',
        'ecommerce' => array(
            'currency' => get_woocommerce_currency(),
            'value'    => (float) $product->get_price(),
            'items'    => array( array(
                'item_id'       => $product->get_sku() ?: (string) $product->get_id(),
                'item_name'     => $product->get_name(),
                'item_category' => $cat_name,
                'price'         => (float) $product->get_price(),
                'quantity'      => 1,
            ) ),
        ),
    );

    echo "\n<script>window.dataLayer=window.dataLayer||[];dataLayer.push({ecommerce:null});dataLayer.push(" . wp_json_encode( $payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . ");</script>\n";
}

// --------------------------------------------------------------
//  3. add_to_cart — عند إضافة منتج للسلة
// --------------------------------------------------------------
add_action( 'woocommerce_add_to_cart', 'prinssfut_dl_add_to_cart', 10, 6 );
function prinssfut_dl_add_to_cart( $cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data ) {
    $product = wc_get_product( $variation_id ?: $product_id );
    if ( ! $product instanceof WC_Product ) { return; }

    $cat_ids   = wc_get_product_cat_ids( $product_id );
    $cat_name  = '';
    if ( ! empty( $cat_ids ) ) {
        $term = get_term( $cat_ids[0], 'product_cat' );
        if ( $term && ! is_wp_error( $term ) ) { $cat_name = $term->name; }
    }

    $payload = array(
        'event'     => 'add_to_cart',
        'ecommerce' => array(
            'currency' => get_woocommerce_currency(),
            'value'    => (float) $product->get_price() * (int) $quantity,
            'items'    => array( array(
                'item_id'       => $product->get_sku() ?: (string) $product->get_id(),
                'item_name'     => $product->get_name(),
                'item_category' => $cat_name,
                'price'         => (float) $product->get_price(),
                'quantity'      => (int) $quantity,
            ) ),
        ),
    );

    WC()->session->set( 'prinssfut_last_atc', $payload );
}

// طباعة الحدث على الصفحة بعد redirect
add_action( 'wp_footer', 'prinssfut_dl_print_atc' );
function prinssfut_dl_print_atc() {
    if ( ! function_exists( 'WC' ) || ! WC()->session ) { return; }
    $payload = WC()->session->get( 'prinssfut_last_atc' );
    if ( ! $payload ) { return; }
    WC()->session->__unset( 'prinssfut_last_atc' );
    echo "\n<script>window.dataLayer=window.dataLayer||[];dataLayer.push({ecommerce:null});dataLayer.push(" . wp_json_encode( $payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . ");</script>\n";
}

// --------------------------------------------------------------
//  4. begin_checkout — عند صفحة Checkout
// --------------------------------------------------------------
add_action( 'wp_footer', 'prinssfut_dl_begin_checkout' );
function prinssfut_dl_begin_checkout() {
    if ( ! function_exists( 'is_checkout' ) || ! is_checkout() || is_wc_endpoint_url( 'order-received' ) ) { return; }
    if ( ! WC()->cart || WC()->cart->is_empty() ) { return; }

    $items = array();
    foreach ( WC()->cart->get_cart() as $cart_item ) {
        $product = $cart_item['data'];
        if ( ! $product instanceof WC_Product ) { continue; }
        $items[] = array(
            'item_id'   => $product->get_sku() ?: (string) $product->get_id(),
            'item_name' => $product->get_name(),
            'price'     => (float) $product->get_price(),
            'quantity'  => (int) $cart_item['quantity'],
        );
    }

    $payload = array(
        'event'     => 'begin_checkout',
        'ecommerce' => array(
            'currency' => get_woocommerce_currency(),
            'value'    => (float) WC()->cart->get_total( 'edit' ),
            'items'    => $items,
        ),
    );

    echo "\n<script>window.dataLayer=window.dataLayer||[];dataLayer.push({ecommerce:null});dataLayer.push(" . wp_json_encode( $payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . ");</script>\n";
}

// --------------------------------------------------------------
//  5. purchase — عند صفحة شكراً (order-received)
// --------------------------------------------------------------
add_action( 'woocommerce_thankyou', 'prinssfut_dl_purchase', 10, 1 );
function prinssfut_dl_purchase( $order_id ) {
    if ( ! $order_id ) { return; }
    if ( get_post_meta( $order_id, '_prinssfut_dl_purchase_sent', true ) === 'yes' ) { return; }

    $order = wc_get_order( $order_id );
    if ( ! $order instanceof WC_Order ) { return; }

    $items = array();
    foreach ( $order->get_items() as $item ) {
        $product = $item->get_product();
        if ( ! $product instanceof WC_Product ) { continue; }
        $items[] = array(
            'item_id'   => $product->get_sku() ?: (string) $product->get_id(),
            'item_name' => $item->get_name(),
            'price'     => (float) ( $item->get_total() / max( 1, $item->get_quantity() ) ),
            'quantity'  => (int) $item->get_quantity(),
        );
    }

    $payload = array(
        'event'     => 'purchase',
        'ecommerce' => array(
            'transaction_id' => (string) $order_id,
            'value'          => (float) $order->get_total(),
            'tax'            => (float) $order->get_total_tax(),
            'shipping'       => (float) $order->get_shipping_total(),
            'currency'       => $order->get_currency(),
            'items'          => $items,
        ),
    );

    echo "\n<script>window.dataLayer=window.dataLayer||[];dataLayer.push({ecommerce:null});dataLayer.push(" . wp_json_encode( $payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . ");</script>\n";

    update_post_meta( $order_id, '_prinssfut_dl_purchase_sent', 'yes' );
}
