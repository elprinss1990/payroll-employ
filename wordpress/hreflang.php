<?php
/**
 * ============================================================
 *  hreflang.php — إضافة hreflang tags للموقع ثنائي اللغة
 *  يُلصق في: functions.php أو داخل WPCode / Code Snippets
 *
 *  يفترض أن النسخة العربية هي الافتراضية (/) والإنجليزية تحت (/en/).
 *  لو تستخدم Polylang / WPML، فعلياً هي تولّد hreflang تلقائياً — لا تستخدم
 *  هذا الملف معها (ستحصل على tags مكررة).
 *
 *  عدّل $pairs أدناه لو هيكل الـ URL مختلف لديك.
 * ============================================================
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

add_action( 'wp_head', 'prinssfut_hreflang_tags', 5 );

function prinssfut_hreflang_tags() {

    $current = home_url( add_query_arg( null, null ) );
    $home    = home_url( '/' );
    $path    = wp_parse_url( $current, PHP_URL_PATH );
    $path    = $path ? ltrim( $path, '/' ) : '';

    // اكتشف اللغة الحالية (ar هي الافتراضية)
    $is_en = ( strpos( $path, 'en/' ) === 0 );

    if ( $is_en ) {
        $ar_url = $home . substr( $path, 3 );
        $en_url = $home . $path;
    } else {
        $ar_url = $home . $path;
        $en_url = $home . 'en/' . $path;
    }

    $pairs = array(
        'ar'        => esc_url( $ar_url ),
        'en'        => esc_url( $en_url ),
        'x-default' => esc_url( $ar_url ),
    );

    foreach ( $pairs as $lang => $url ) {
        printf(
            "\n<link rel=\"alternate\" hreflang=\"%s\" href=\"%s\" />",
            esc_attr( $lang ),
            $url
        );
    }
    echo "\n";
}
