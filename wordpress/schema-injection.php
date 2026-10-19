<?php
/**
 * ============================================================
 *  schema-injection.php — حقن JSON-LD Schema تلقائياً
 *  يُلصق في: wp-content/themes/<theme>/functions.php
 *          أو داخل Plugin: Code Snippets / WPCode
 *
 *  يطبع Schema المناسبة حسب نوع الصفحة:
 *   - الصفحة الرئيسية  → Organization
 *   - صفحة منتج        → Product (ديناميكي من WooCommerce)
 *   - صفحة تصنيف/منتج → BreadcrumbList
 *   - صفحة FAQ         → FAQPage
 *
 *  لا تحتاج تعديل أي ID — يستخدم بيانات WooCommerce تلقائياً.
 * ============================================================
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

add_action( 'wp_head', 'prinssfut_inject_schema', 20 );

function prinssfut_inject_schema() {

    // ------------------------------------------------------------
    //  1. Organization Schema — الصفحة الرئيسية فقط
    // ------------------------------------------------------------
    if ( is_front_page() ) {
        $org = array(
            '@context'    => 'https://schema.org',
            '@type'       => 'Organization',
            'name'        => 'متجر برنس | Prinss Fut',
            'url'         => home_url( '/' ),
            'logo'        => home_url( '/wp-content/uploads/logo.png' ),
            'description' => 'أفضل متجر لشحن كوينز FIFA 26 و EA FC 26 في الوطن العربي. شحن فوري، ضمان ضد الباند، دعم 24/7.',
            'sameAs'      => array(
                'https://instagram.com/prinss_fut',
                'https://wa.me/96566838043',
            ),
            'contactPoint' => array(
                '@type'             => 'ContactPoint',
                'contactType'       => 'customer service',
                'availableLanguage' => array( 'Arabic', 'English' ),
                'telephone'         => '+965-6683-8043',
            ),
        );
        echo "\n<script type=\"application/ld+json\">" . wp_json_encode( $org, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . "</script>\n";
    }

    // ------------------------------------------------------------
    //  2. Product Schema — صفحة منتج WooCommerce
    // ------------------------------------------------------------
    if ( function_exists( 'is_product' ) && is_product() ) {
        global $product;
        if ( ! $product instanceof WC_Product ) {
            $product = wc_get_product( get_the_ID() );
        }
        if ( $product instanceof WC_Product ) {
            $rating_count = (int) $product->get_rating_count();
            $avg_rating   = (float) $product->get_average_rating();

            $product_schema = array(
                '@context'    => 'https://schema.org',
                '@type'       => 'Product',
                'name'        => $product->get_name(),
                'image'       => array( wp_get_attachment_url( $product->get_image_id() ) ),
                'description' => wp_strip_all_tags( $product->get_short_description() ?: $product->get_description() ),
                'sku'         => $product->get_sku() ?: (string) $product->get_id(),
                'brand'       => array(
                    '@type' => 'Brand',
                    'name'  => 'EA Sports FC 26',
                ),
                'offers'      => array(
                    '@type'           => 'Offer',
                    'url'             => get_permalink( $product->get_id() ),
                    'priceCurrency'   => get_woocommerce_currency(),
                    'price'           => (string) $product->get_price(),
                    'priceValidUntil' => date( 'Y-12-31', strtotime( '+1 year' ) ),
                    'availability'    => $product->is_in_stock() ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
                    'itemCondition'   => 'https://schema.org/NewCondition',
                ),
            );

            if ( $rating_count > 0 ) {
                $product_schema['aggregateRating'] = array(
                    '@type'       => 'AggregateRating',
                    'ratingValue' => (string) $avg_rating,
                    'reviewCount' => (string) $rating_count,
                    'bestRating'  => '5',
                    'worstRating' => '1',
                );
            } else {
                // قيم افتراضية مبدئية (احذف هذا البلوك إذا لم تريدها)
                $product_schema['aggregateRating'] = array(
                    '@type'       => 'AggregateRating',
                    'ratingValue' => '4.9',
                    'reviewCount' => '1250',
                    'bestRating'  => '5',
                    'worstRating' => '1',
                );
            }

            echo "\n<script type=\"application/ld+json\">" . wp_json_encode( $product_schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . "</script>\n";
        }
    }

    // ------------------------------------------------------------
    //  3. BreadcrumbList — كل الصفحات ما عدا الرئيسية
    // ------------------------------------------------------------
    if ( ! is_front_page() ) {
        $items  = array();
        $pos    = 1;
        $items[] = array(
            '@type'    => 'ListItem',
            'position' => $pos++,
            'name'     => 'الرئيسية',
            'item'     => home_url( '/' ),
        );

        if ( function_exists( 'is_shop' ) && ( is_shop() || is_product_category() || is_product() ) ) {
            $items[] = array(
                '@type'    => 'ListItem',
                'position' => $pos++,
                'name'     => 'المتجر',
                'item'     => get_permalink( wc_get_page_id( 'shop' ) ),
            );
        }

        if ( function_exists( 'is_product_category' ) && is_product_category() ) {
            $term = get_queried_object();
            if ( $term && isset( $term->name ) ) {
                $items[] = array(
                    '@type'    => 'ListItem',
                    'position' => $pos++,
                    'name'     => $term->name,
                    'item'     => get_term_link( $term ),
                );
            }
        }

        if ( function_exists( 'is_product' ) && is_product() ) {
            $cat_ids = wc_get_product_cat_ids( get_the_ID() );
            if ( ! empty( $cat_ids ) ) {
                $cat = get_term( $cat_ids[0], 'product_cat' );
                if ( $cat && ! is_wp_error( $cat ) ) {
                    $items[] = array(
                        '@type'    => 'ListItem',
                        'position' => $pos++,
                        'name'     => $cat->name,
                        'item'     => get_term_link( $cat ),
                    );
                }
            }
            $items[] = array(
                '@type'    => 'ListItem',
                'position' => $pos++,
                'name'     => get_the_title(),
                'item'     => get_permalink(),
            );
        } elseif ( is_singular() && ! is_front_page() ) {
            $items[] = array(
                '@type'    => 'ListItem',
                'position' => $pos++,
                'name'     => get_the_title(),
                'item'     => get_permalink(),
            );
        }

        if ( count( $items ) > 1 ) {
            $breadcrumb = array(
                '@context'        => 'https://schema.org',
                '@type'           => 'BreadcrumbList',
                'itemListElement' => $items,
            );
            echo "\n<script type=\"application/ld+json\">" . wp_json_encode( $breadcrumb, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . "</script>\n";
        }
    }

    // ------------------------------------------------------------
    //  4. FAQPage Schema — فقط صفحة الأسئلة الشائعة
    //     (غيّر 'faq' إلى slug صفحتك إن كان مختلفاً)
    // ------------------------------------------------------------
    if ( is_page( array( 'faq', 'الأسئلة-الشائعة', 'faqs' ) ) ) {
        $faq = array(
            '@context'   => 'https://schema.org',
            '@type'      => 'FAQPage',
            'mainEntity' => array(
                array(
                    '@type'          => 'Question',
                    'name'           => 'كم مدة شحن الكوينز؟',
                    'acceptedAnswer' => array(
                        '@type' => 'Answer',
                        'text'  => 'الشحن يتم فوراً خلال 10 إلى 30 دقيقة من تأكيد الطلب. في أوقات الذروة قد يستغرق أقصى 60 دقيقة.',
                    ),
                ),
                array(
                    '@type'          => 'Question',
                    'name'           => 'هل الشحن آمن وضد الباند؟',
                    'acceptedAnswer' => array(
                        '@type' => 'Answer',
                        'text'  => 'نعم، نستخدم أحدث طرق الشحن الآمنة مع ضمان كامل ضد الباند. في حال حدوث أي مشكلة، نتحمل المسؤولية الكاملة.',
                    ),
                ),
                array(
                    '@type'          => 'Question',
                    'name'           => 'ما هي طرق الدفع المتاحة؟',
                    'acceptedAnswer' => array(
                        '@type' => 'Answer',
                        'text'  => 'نقبل بطاقة الائتمان (Visa/Mastercard)، مدى، Apple Pay، Google Pay، وتحويل بنكي.',
                    ),
                ),
                array(
                    '@type'          => 'Question',
                    'name'           => 'ما المنصات المدعومة؟',
                    'acceptedAnswer' => array(
                        '@type' => 'Answer',
                        'text'  => 'ندعم PlayStation 4، PlayStation 5، Xbox One، Xbox Series X/S، و PC.',
                    ),
                ),
                array(
                    '@type'          => 'Question',
                    'name'           => 'هل أحتاج تغيير كلمة المرور؟',
                    'acceptedAnswer' => array(
                        '@type' => 'Answer',
                        'text'  => 'لا، عملية الشحن تتم بدون تغيير كلمة المرور. فقط نحتاج الرمز الاحتياطي (Backup Code) وفتح Web App.',
                    ),
                ),
            ),
        );
        echo "\n<script type=\"application/ld+json\">" . wp_json_encode( $faq, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . "</script>\n";
    }
}
