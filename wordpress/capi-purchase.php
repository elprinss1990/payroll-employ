<?php
/**
 * ============================================================
 *  capi-purchase.php — Meta Conversions API (Server-Side)
 *  يُلصق في: functions.php أو Plugin: Code Snippets / WPCode
 *
 *  ما يفعل:
 *   - عند اكتمال الدفع في WooCommerce، يرسل حدث Purchase مباشرة
 *     لـ Meta Graph API من السيرفر (CAPI).
 *   - يتجاوز Ad Blockers و iOS 14.5+ ويحسّن دقة التتبع.
 *
 *  متغيرات لازم تعدلها:
 *   PRINSSFUT_META_PIXEL_ID       → ID البيكسل من Events Manager
 *   PRINSSFUT_META_ACCESS_TOKEN   → Access Token من Events Manager → Settings
 *   PRINSSFUT_META_TEST_EVENT_CODE→ (اختياري) أثناء الاختبار فقط
 *
 *  نسخة بديلة: لو تستخدم GTM Server Container (Stape.io مثلاً)
 *  فعّل القسم الثاني في أسفل الملف واطفئ الأول.
 * ============================================================
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

// ====== CONFIG — عدّل هذه القيم ==============================
define( 'PRINSSFUT_META_PIXEL_ID',     'YOUR_PIXEL_ID_HERE' );
define( 'PRINSSFUT_META_ACCESS_TOKEN', 'YOUR_META_ACCESS_TOKEN_HERE' );
define( 'PRINSSFUT_META_TEST_EVENT_CODE', '' ); // اتركه فارغاً في الإنتاج
define( 'PRINSSFUT_META_GRAPH_VERSION', 'v19.0' );
// =============================================================

add_action( 'woocommerce_payment_complete', 'prinssfut_capi_send_purchase', 10, 1 );
add_action( 'woocommerce_order_status_completed', 'prinssfut_capi_send_purchase', 10, 1 );

function prinssfut_capi_send_purchase( $order_id ) {

    if ( ! $order_id ) { return; }

    // تجنّب إرسال الحدث مرتين لنفس الطلب
    if ( get_post_meta( $order_id, '_prinssfut_capi_sent', true ) === 'yes' ) {
        return;
    }

    $order = wc_get_order( $order_id );
    if ( ! $order instanceof WC_Order ) { return; }

    // بيانات المستخدم (مُجزّأة SHA-256 كما يتطلب Meta)
    $email = strtolower( trim( $order->get_billing_email() ) );
    $phone = preg_replace( '/[^0-9]/', '', $order->get_billing_phone() );
    $fname = strtolower( trim( $order->get_billing_first_name() ) );
    $lname = strtolower( trim( $order->get_billing_last_name() ) );
    $city  = strtolower( trim( $order->get_billing_city() ) );
    $zip   = strtolower( trim( $order->get_billing_postcode() ) );
    $country = strtolower( trim( $order->get_billing_country() ) );

    $user_data = array(
        'em' => $email  ? array( hash( 'sha256', $email ) )  : null,
        'ph' => $phone  ? array( hash( 'sha256', $phone ) )  : null,
        'fn' => $fname  ? array( hash( 'sha256', $fname ) )  : null,
        'ln' => $lname  ? array( hash( 'sha256', $lname ) )  : null,
        'ct' => $city   ? array( hash( 'sha256', $city ) )   : null,
        'zp' => $zip    ? array( hash( 'sha256', $zip ) )    : null,
        'country' => $country ? array( hash( 'sha256', $country ) ) : null,
        'client_ip_address' => $order->get_customer_ip_address(),
        'client_user_agent' => $order->get_customer_user_agent(),
    );

    // مرر _fbp و _fbc من الكوكيز إن توفرت (مهم للـ matching)
    if ( isset( $_COOKIE['_fbp'] ) ) {
        $user_data['fbp'] = sanitize_text_field( wp_unslash( $_COOKIE['_fbp'] ) );
    }
    if ( isset( $_COOKIE['_fbc'] ) ) {
        $user_data['fbc'] = sanitize_text_field( wp_unslash( $_COOKIE['_fbc'] ) );
    }

    $user_data = array_filter( $user_data );

    // بيانات المحتوى (المنتجات)
    $contents    = array();
    $content_ids = array();
    $num_items   = 0;
    foreach ( $order->get_items() as $item ) {
        $product_id    = $item->get_product_id();
        $qty           = $item->get_quantity();
        $content_ids[] = (string) $product_id;
        $contents[]    = array(
            'id'         => (string) $product_id,
            'quantity'   => (int) $qty,
            'item_price' => (float) ( $item->get_total() / max( 1, $qty ) ),
        );
        $num_items += (int) $qty;
    }

    $payload = array(
        'data' => array(
            array(
                'event_name'      => 'Purchase',
                'event_time'      => time(),
                'event_id'        => (string) $order_id, // لـ deduplication مع البيكسل client-side
                'event_source_url'=> $order->get_checkout_order_received_url(),
                'action_source'   => 'website',
                'user_data'       => $user_data,
                'custom_data'     => array(
                    'currency'     => $order->get_currency(),
                    'value'        => (float) $order->get_total(),
                    'content_type' => 'product',
                    'content_ids'  => $content_ids,
                    'contents'     => $contents,
                    'num_items'    => $num_items,
                    'order_id'     => (string) $order_id,
                ),
            ),
        ),
    );

    if ( PRINSSFUT_META_TEST_EVENT_CODE !== '' ) {
        $payload['test_event_code'] = PRINSSFUT_META_TEST_EVENT_CODE;
    }

    // ===== (أ) إرسال مباشر لـ Meta Graph API =====================
    $endpoint = sprintf(
        'https://graph.facebook.com/%s/%s/events?access_token=%s',
        PRINSSFUT_META_GRAPH_VERSION,
        PRINSSFUT_META_PIXEL_ID,
        rawurlencode( PRINSSFUT_META_ACCESS_TOKEN )
    );

    $response = wp_remote_post( $endpoint, array(
        'timeout' => 10,
        'headers' => array( 'Content-Type' => 'application/json' ),
        'body'    => wp_json_encode( $payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ),
    ) );

    // ===== (ب) بديل: إرسال لـ GTM Server Container ==============
    // علّق قسم (أ) أعلاه وفعّل هذا لو تستخدم Stape.io / GCP
    /*
    $response = wp_remote_post( 'https://gtm.prinssfut.com/data', array(
        'timeout' => 10,
        'headers' => array( 'Content-Type' => 'application/json' ),
        'body'    => wp_json_encode( $payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ),
    ) );
    */

    // تسجيل النتيجة
    if ( is_wp_error( $response ) ) {
        error_log( '[Prinssfut CAPI] Error: ' . $response->get_error_message() );
        return;
    }

    $code = wp_remote_retrieve_response_code( $response );
    $body = wp_remote_retrieve_body( $response );

    if ( $code >= 200 && $code < 300 ) {
        update_post_meta( $order_id, '_prinssfut_capi_sent', 'yes' );
    } else {
        error_log( '[Prinssfut CAPI] HTTP ' . $code . ' — ' . $body );
    }
}
