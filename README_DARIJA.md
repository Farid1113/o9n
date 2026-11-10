# NOVA MARKET PRO — PHP / MySQL

متجر متعدد الأقسام بالدارجة، الفرنسية والإنجليزية، مع لوحة إدارة مستقلة ومحمية بكلمة سر. المشروع **ماشي مجرد صورة أو mockup**: فيه ملفات PHP، قاعدة MySQL، API لتسجيل الطلبات COD، CSS وJS و12 منتجاً تجريبياً بصور SVG أصلية قابلة للتبديل.

> **قبل استقبال زبناء حقيقيين:** خاص تثبت MySQL، تكمّل إعدادات Telegram/WhatsApp إذا بغيتي، تجرب الطلبات والتوصيل، تدخل رقم الدعم ومعلومات المتجر القانونية، تستعمل HTTPS، وتمسح install.php. الكود راجعناه بـPHP lint وJavaScript syntax check؛ الاختبار الكامل لقاعدة البيانات وخدمات الرسائل خاص يدوز في السيرفر ديالك حيث ما كانتش قاعدة MySQL شغالة فبيئة الإنشاء.

## 1. التثبيت على XAMPP — Windows

1. نزّل ZIP وفكّه؛ نسخ فولدر `NOVA_MARKET_PRO` داخل `C:\xampp\htdocs\NOVA_MARKET_PRO`.
2. من XAMPP شعل **Apache** و **MySQL**. ضروري نسخة PHP 8.1+ مع `pdo_mysql`, `fileinfo`, و `curl` من أجل Telegram/WhatsApp.
3. فتح `config/database.php` وبدّل معلومات MySQL إلا ما كانتش `root` بلا كلمة سر. قاعدة البيانات الافتراضية اسمها `nova_market`، والبرنامج كيحاول ينشئها تلقائياً إذا عندك الصلاحيات.
4. فتح: `http://localhost/NOVA_MARKET_PRO/install.php`.
5. فـ**Admin username** كتب `farid123`، وفـ**Admin password** كتب كلمة السر اللي اخترتي للمشروع (الكود اللي حددتي بنفسك) بنفسك. التطبيق كيخزن غير *hash* ديالها وما كيحطهاش نص واضح في الكود.
6. كليكي **Install store**، وغادي يتحطّو 6 تصنيفات و12 منتجات تجريبية وبعض مقاسات وألوان.
7. شوف الواجهة: `http://localhost/NOVA_MARKET_PRO/`.
8. دخل الإدارة من: `http://localhost/NOVA_MARKET_PRO/control/index.php`، بنفس بيانات الدخول اللي دخلتيها أثناء التثبيت.
9. **من بعد التثبيت مسح `install.php` من السيرفر**؛ وحتى إلا بقى، البرنامج كيسد التثبيت بعدما كتكون إدارة مسجلة.
10. استبدل المنتجات التجريبية بالصور والمنتجات الحقيقية، وبدّل كلمة السر إذا كنت كتستعملها فخدمات أخرى.

### إذا كتب ليك Database unavailable

- شوف واش MySQL خدام فـXAMPP، ومنين phpMyAdmin إلى `http://localhost/phpmyadmin`.
- راجع `host`, `port`, `database`, `username` و`password` فـ`config/database.php`.
- إذا الاستضافة ما كتعطيش صلاحية CREATE DATABASE: أنشئ `nova_market` يدوياً ثم عاود `install.php`.
- إذا ملفاتك فمجلد داخل `htdocs`، الروابط كتكتشف المسار تلقائياً. `base_url` غالباً خليه فارغ.
- عطِ الكتابة لفولدر `uploads/products` من أجل صور المنتجات.

## 2. خدمة الموقع

- 6 تصنيفات أولية: إكسسوارات الهاتف، السيارات، رجال، نساء، أطفال، المطبخ والمنزل. تقدر تزيد تصنيفات بلا حد عملي من الإدارة.
- تغيير اللغة من AR/FR/EN، منتوجات بثلاثة أسماء وأوصاف، تصميم متجاوب، البحث/الفلاتر/الترتيب، التخفيضات كيبانو بنسبة مئوية من الثمن القديم.
- المنتج يقدر يكون بسيط أو متنوع: مقاس، لون، SKU، تعديل الثمن وstock لكل variante. سلة مشتريات داخل المتصفح.
- العميل كيطلب *داخل الموقع* وكيعطي الاسم، الهاتف، المدينة وعنوان اختياري، بلا ما يمشي لواتساب. الثمن والمخزون والتخفيض كيتعاود التحقق منهم فالسيرفر، والطلب كيتسجّل في MySQL وكيتم خصم stock في transaction.
- طريقة الدفع الوحيدة: **Cash on Delivery**. المتجر ما كيجمع حتى معلومات بطاقة بنكية.
- تأكيد الطلب كيطلع للزبون برقم طلب ورسالة أن الإدارة غادي تتاصل به. كاين تتبع الطلب باستعمال رقم الطلب + نفس رقم الهاتف.
- كوبونات تخفيض (% أو MAD، الحد الأدنى، تاريخ الانتهاء، عدد الاستعمالات)، ثمن الشحن وحد التوصيل المجاني وقيمة الطلب الدنيا.
- يمكن تثبيت واجهة المتجر كـPWA من الهاتف عبر متصفح يدعم إضافة التطبيقات. إرسال الطلبات كيحتاج الإنترنت.

## 3. لوحة الإدارة — `/control/index.php`

- **Overview:** نظرة عامة على الطلبات، القيمة الإجمالية، الطلبات الجديدة والزوار.
- **Orders:** جميع التفاصيل، تأكيد/تجهيز/شحن/تسليم/إلغاء؛ طباعة الطلب، CSV، فتح محادثة مع الزبون على WhatsApp. الإلغاء كيرجع stock إذا باقِي المنتوج/variante.
- **Products:** إضافة/تعديل/حذف، صور رئيسية ومعرض، أسماء وأوصاف بثلاث لغات، السعر القديم والجديد، SKU، المميزات والظهور، variantes وألوان ومقاسات ومخزون.
- **Categories:** إضافة/تعديل/حذف وترتيب التصنيفات مع 3 لغات.
- **Coupons:** تكوين عروض وتحديد الصلاحية والحد الأقصى.
- **Customers:** لائحة الزبناء من الطلبات، إجمالي المشتريات وتصدير CSV.
- **Analytics:** عدد زيارات تقريبي، مبيعات حسب الأيام، ترتيب المنتجات الأكثر طلباً.
- **Notifications:** Telegram Bot وWhatsApp Business Cloud API مع إعدادات محمية؛ اختبار Telegram.
- **Store settings:** اسم المتجر، شريط الإعلانات، اللون، مصاريف الشحن، التوصيل المجاني، أقل طلب، أرقام التواصل، روابط سوشيال، وضع الصيانة.
- **Security:** تغيير كلمة السر. **Activity:** سجل تغييرات الإدارة.

لوحة الإدارة فمسار مستقل ومحمي بالجلسات، CSRF، معدل محاولات تسجيل الدخول، وhash لكلمة السر. **إخفاء رابط الإدارة وحده ماشي حماية كافية.**

## 4. Telegram: الإشعارات للهاتف

1. فـTelegram تواصل مع `@BotFather`، دير `/newbot` وخذ **bot token**.
2. فتح محادثة مع البوت ودير `Start`؛ باش تجيب Chat ID استعمل رابط `https://api.telegram.org/botYOUR_TOKEN/getUpdates` فمتصفحك بعد ما ترسل رسالة للبوت، وقرا `chat.id` من JSON. ما تشاركش الـtoken.
3. فالإدارة مشي لـ**Notifications**، دخل bot token وchat ID، فعّل `Alert me on every new order` ثم **Save** و **Send Telegram test**.
4. من بعد، كل طلب ناجح كيحاول يرسل البوت: رقم الطلب + الاسم + الهاتف + المدينة + الإجمالي. التنبيه كيجي فـTelegram ديالك حتى إلا المتصفح مسدود، بشرط السيرفر خدام والاتصال بالنت متاح.
5. تقدر تفعّل `Alert on new unique visitors`. التطبيق كيعتمد على معرف تقني مستعار؛ **ماشي تنبيه على كل refresh**، وإنما على زائر جديد معروف لأول مرة، لتجنب spam. زيارة الروبوتات أو عدة أجهزة بنفس المعرف ممكن ما تتفرّقش بشكل كامل.

> التنبيهات الخارجية *best effort*: إلا وقع timeout أو Bot API رفض الطلب، الطلب كيبقى مسجل. خاصك تراجع لوحة Orders كذلك.

## 5. WhatsApp: بدون فتح واتساب عند الزبون

كاين جوج استعمالات مختلفين:

- العميل **ما كيتحوّلاش لواتساب**: كيطلب مباشرة داخل الموقع.
- من لوحة Orders تقدر تفتح `WhatsApp` باش تتواصل أنت مع الزبون يدوياً.
- باش توصلك **رسالة أوتوماتيكية لواتساب ديالك** بالاسم والرقم ورقم الطلب والمدينة: خاصّك **Meta WhatsApp Business Cloud API**، Access Token صحيح، Phone Number ID، رقم الوجهة، وقالب template مصادق عليه من Meta. فيه 4 body parameters بالترتيب: name, phone, order number, city. أدخلهم في Notifications وفعّل `whatsapp_orders`. ما تفعّلهاش بلا الخدمة الحقيقية، حيث WhatsApp العادي بوحده ما يقدرش يتوصل برسائل API مُرسلة أوتوماتيكياً بلا هاد الإعدادات.

## 6. Cloudflare: الفرق المهم جداً

**Cloudflare Workers / Pages ما كيشغلوش PHP + MySQL مباشرة.** رفع هاد ZIP كملفات static لWorkers ما غاديش يشغّل `index.php` ولا لوحة التحكم ولا تخزين الطلبات.

إذا بغيتي Cloudflare والنفس المشروع:

**الاختيار A: XAMPP + Cloudflare Tunnel**

- خلي XAMPP وMySQL خدامين على جهازك 24/7 (وخلي الجهاز ما ينعسش). استعمل Cloudflare Tunnel لتمرير الدومين لـApache.
- الأسهل تخلي `VirtualHost` لـApache كيشير مباشرة لـ`C:/xampp/htdocs/NOVA_MARKET_PRO` على البورت `8081`. فـ`httpd.conf` ضيف `Listen 8081` مرة وحدة فقط، وحط فـ`httpd-vhosts.conf`:

```apache
<VirtualHost *:8081>
  ServerName shop.example.com
  DocumentRoot "C:/xampp/htdocs/NOVA_MARKET_PRO"
  <Directory "C:/xampp/htdocs/NOVA_MARKET_PRO">
    AllowOverride All
    Require all granted
    Options -Indexes
  </Directory>
</VirtualHost>
```

- دير Public Hostname/Route فـCloudflare Zero Trust Tunnel: `shop.example.com` → `http://localhost:8081` (بدّل الدومين لدومينك الحقيقي).
- دير فـDNS سجل CNAME ديال tunnel عبر Cloudflare dashboard / `cloudflared tunnel route dns`، حسب طريقة التثبيت.
- افتح `https://shop.example.com` وجرب الإدارة والطلبات والإشعارات. HTTPS ضروري قبل النشر.

**الاختيار B:** استضافة PHP/MySQL خارجية مربوطة بـCloudflare DNS/CDN، غالباً أكثر استقراراً من تشغيل متجر مدفوع على PC شخصي.

**متنساش:** حماية قاعدة البيانات، نسخ احتياطية منتظمة، تجنب فتح MySQL (3306) للإنترنت، راجع سياسات البيع والتوصيل والإرجاع ومعلومات الشركة القانونية قبل النشر.

## 7. بنية الملفات

```text
NOVA_MARKET_PRO/
 ├── index.php               # Storefront
 ├── api.php                 # COD checkout + tracking API
 ├── install.php             # One-time installer (delete after setup)
 ├── schema.sql              # MySQL tables
 ├── pages.php               # Info/policy pages; customize before launch
 ├── manifest.webmanifest    # PWA metadata
 ├── service-worker.js       # Offline static cache only
 ├── config/database.php     # MySQL config / base URL
 ├── includes/               # Core helpers & notifications
 ├── control/index.php       # Admin login + dashboard
 ├── control/actions.php     # CRUD and protected admin actions
 ├── control/poll.php        # Live admin order poll
 ├── control/export.php      # CSV export
 ├── control/print.php       # Printable order
 ├── assets/css/             # Store and admin styling
 ├── assets/js/              # Store and admin logic
 ├── assets/img/             # 12 illustrative SVG demo assets + PWA icons
 └── uploads/products/       # Uploaded product photos
```

**ملاحظات:** المنتجات التجريبية والصور SVG مجرد أمثلة، بدّلهم بالمنتجات وصور البيع ديالك. “8K” فالوصف ديال التصميم ماشي ضمان أن كل صورة منتج 8K. إعدادات الحماية جيدة كبداية، ولكن الإنتاج الحقيقي كيحتاج مراجعة أمنية واختبارات وظيفية ونسخ احتياطية ومراقبة مستمرة، خصوصاً إذا كاين بزاف ديال الطلبات.
