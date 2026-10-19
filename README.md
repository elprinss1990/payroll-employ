# 🚀 حزمة أكواد SEO + Tracking لمتجر برنس (Prinssfut.com)

مقتطفات كود جاهزة للنسخ واللصق — SEO تقني + بيكسلات تتبع لـ WooCommerce.
كل ملف يبدأ بتعليق عربي يشرح مكان لصقه والمتغيرات التي تحتاج تعديلها.

---

## 📁 خريطة الملفات

| الملف | أين يُلصق | ماذا يفعل |
|------|-----------|-----------|
| `robots.txt` | جذر الموقع (public_html/) | يسمح لـ Googlebot ويحظر صفحات الفلترة |
| `schema/organization.jsonld` | مرجع — ملف PHP يحقنه | Organization Schema للصفحة الرئيسية |
| `schema/product.jsonld` | قالب — ملف PHP يملؤه | Product Schema (ديناميكي من WooCommerce) |
| `schema/breadcrumb.jsonld` | قالب — ملف PHP يبنيه | BreadcrumbList لكل صفحة داخلية |
| `schema/faq.jsonld` | مرجع — ملف PHP يحقنه | FAQPage Schema |
| `wordpress/schema-injection.php` | `functions.php` أو WPCode | يحقن JSON-LD تلقائياً حسب نوع الصفحة |
| `wordpress/hreflang.php` | `functions.php` أو WPCode | hreflang AR / EN / x-default |
| `wordpress/capi-purchase.php` | `functions.php` أو WPCode | Meta Conversions API — Purchase server-side |
| `wordpress/woocommerce-datalayer.php` | `functions.php` أو WPCode | يدفع أحداث WC لـ dataLayer ليستهلكها GTM |
| `tracking/gtm/gtm-head.html` | قبل `</head>` | GTM container head snippet |
| `tracking/gtm/gtm-body.html` | بعد `<body>` | GTM noscript fallback |
| `tracking/meta/pixel-base.html` | GTM Custom HTML — All Pages | Meta Pixel base + PageView |
| `tracking/meta/events.js` | GTM Custom HTML — كل event ترigger | ViewContent / AddToCart / Checkout / Purchase / Lead |
| `tracking/tiktok/pixel-base.html` | GTM Custom HTML — All Pages | TikTok Pixel base + page |
| `tracking/tiktok/events.js` | GTM Custom HTML — events | ViewContent / AddToCart / CompletePayment / Contact |
| `tracking/snapchat/pixel-base.html` | GTM Custom HTML — All Pages | Snapchat Pixel base + PAGE_VIEW |
| `tracking/snapchat/events.js` | GTM Custom HTML — events | VIEW_CONTENT / ADD_CART / PURCHASE / SIGN_UP |
| `tracking/ga4/config.html` | GTM tag "GA4 Configuration" | GA4 gtag config (أو عبر GTM مباشرة) |
| `tracking/ga4/ecommerce-events.js` | مرجع — GTM يقرأ من dataLayer | view_item / add_to_cart / begin_checkout / purchase |
| `tracking/google-ads/conversion.html` | GTM tag "Google Ads Conversion" | Conversion event + value + transaction_id |

---

## 🔑 المتغيرات التي يجب استبدالها

| القيمة الافتراضية | استبدلها بـ | من أين |
|-------------------|-------------|--------|
| `GTM-XXXXXX` | Container ID الحقيقي | tagmanager.google.com |
| `YOUR_PIXEL_ID_HERE` | Meta Pixel ID | business.facebook.com/events_manager |
| `YOUR_META_ACCESS_TOKEN_HERE` | CAPI Access Token | Events Manager → Settings → Conversions API |
| `G-XXXXXXXXXX` | GA4 Measurement ID | analytics.google.com → Admin → Data Streams |
| `AW-XXXXXXXXXX/YYYYYYYYYYYY` | Conversion ID/Label | ads.google.com → Tools → Conversions |
| `YOUR_TIKTOK_PIXEL_ID` | TikTok Pixel ID | ads.tiktok.com → Events |
| `YOUR_SNAPCHAT_PIXEL_ID` | Snapchat Pixel ID | ads.snapchat.com → Events |

---

## ✅ أدوات التحقق بعد التركيب

| الأداة | للتحقق من |
|-------|----------|
| Meta Pixel Helper (Chrome) | Meta Pixel + events |
| TikTok Pixel Helper (Chrome) | TikTok Pixel + events |
| Snap Pixel Helper (Chrome) | Snapchat Pixel + events |
| Google Tag Assistant (Chrome) | GTM / GA4 / Google Ads |
| GA4 → Realtime Report | تدفق البيانات من الموقع |
| Rich Results Test (search.google.com/test/rich-results) | JSON-LD / Schema |
| robots.txt Tester (داخل GSC) | قواعد robots |
| Events Manager → Test Events | CAPI deduplication مع البيكسل |

---

## 📝 ملاحظات

- **CAPI deduplication:** قيمة `eventID` في `tracking/meta/events.js` تطابق `order_id` في `capi-purchase.php` — Meta يدمجهما تلقائياً كحدث واحد.
- **GTM أم مباشر؟** الموصى به GTM — كل الملفات متوافقة مع GTM Custom HTML + dataLayer. لو تلصق مباشرة في الـ Theme، استبدل متغيرات `{{DLV - ...}}` بقيم حقيقية.
- **البديل Plugin:** يمكن استبدال `woocommerce-datalayer.php` بـ Plugin "Google Tag Manager for WordPress" (Thomas Geiger) — في هذه الحالة احذف الملف لتجنب التكرار.
