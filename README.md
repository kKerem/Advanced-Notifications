# Pepech - Bildirim Sistemi

WordPress için gelişmiş bildirim sistemi eklentisi. Kullanıcılara bildirim gönderme, okundu işaretleme ve e-posta entegrasyonu özelliklerini içerir.

## Özellikler

### 🎯 Temel Özellikler
- **Header Dropdown**: Kullanıcıların bildirimlerini header'da bell ikonu ile görüntüleme
- **Okundu İşaretleme**: Tekil ve toplu okundu işaretleme sistemi
- **E-posta Entegrasyonu**: Bildirimlerin e-posta olarak gönderilmesi
- **Yönetici Paneli**: Kolay bildirim gönderme ve yönetim arayüzü
- **Bildirimlerim Sayfası**: Tüm bildirimleri görüntüleme sayfası

### 🔧 Gelişmiş Özellikler
- **API Desteği**: Diğer eklentiler için API fonksiyonları
- **Bildirim Türleri**: Info, Success, Warning, Error türleri
- **AJAX İşlemler**: Dinamik güncelleme ve etkileşim
- **Responsive Tasarım**: Mobil uyumlu arayüz
- **Sayfalama**: Büyük bildirim listeleri için sayfalama

## Kurulum

1. Eklenti dosyalarını `wp-content/plugins/pepech-notification-system/` klasörüne yükleyin
2. WordPress admin panelinden eklentiyi aktifleştirin
3. **Bildirimler** menüsünden ayarları yapılandırın

## Kullanım

### Yönetici Paneli

1. **Bildirim Gönder**: 
   - Kullanıcı seçin
   - Başlık ve mesaj yazın
   - Bildirim türünü belirleyin
   - E-posta gönderimini ayarlayın

2. **Bildirimleri Yönet**:
   - Tüm bildirimleri görüntüleyin
   - Detayları inceleyin
   - Toplu işlemler yapın

3. **Ayarlar**:
   - E-posta bildirimlerini açın/kapatın
   - Sayfa başına bildirim sayısını ayarlayın
   - Eski bildirimleri temizleyin

### Frontend Entegrasyonu

Header'da bildirim dropdown'ını göstermek için tema dosyanıza şu kodu ekleyin:

```php
<?php do_action('pepech_header_notifications'); ?>
```

### API Kullanımı

Diğer eklentilerden bildirim göndermek için:

```php
// Basit bildirim gönderme
pepech_send_notification($user_id, 'Başlık', 'Mesaj içeriği');

// Gelişmiş bildirim gönderme
pepech_send_notification($user_id, 'Başlık', 'Mesaj', 'success', false);

// Hook kullanımı
do_action('pepech_send_notification', $user_id, $title, $message, $type, $send_email);
```

### Kullanıcı Bildirimlerini Getirme

```php
// Son 10 bildirimi getir
$notifications = pepech_get_user_notifications($user_id, 10);

// Sadece okunmamış bildirimleri getir
$unread_notifications = pepech_get_user_notifications($user_id, 10, true);

// Okunmamış bildirim sayısını getir
$unread_count = pepech_get_unread_count($user_id);
```

### Kısa Kod Kullanımı

```php
// Bildirimlerim sayfası için
[pepech_notifications limit="20" show_read="true"]

// Sadece okunmamış bildirimler
[pepech_notifications limit="5" show_read="false"]
```

## Veritabanı Yapısı

Eklenti aşağıdaki tabloyu oluşturur:

```sql
CREATE TABLE wp_pepech_notifications (
    id mediumint(9) NOT NULL AUTO_INCREMENT,
    user_id bigint(20) NOT NULL,
    title varchar(255) NOT NULL,
    message text NOT NULL,
    type varchar(50) DEFAULT 'info',
    is_read tinyint(1) DEFAULT 0,
    send_email tinyint(1) DEFAULT 1,
    created_at datetime DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY user_id (user_id),
    KEY is_read (is_read),
    KEY created_at (created_at)
);
```

## Özelleştirme

### CSS Özelleştirme

Bildirim stillerini özelleştirmek için tema CSS'inize ekleyin:

```css
/* Bildirim badge rengi */
.pepech-notification-badge {
    background: #your-color !important;
}

/* Okunmamış bildirim arka planı */
.pepech-notification-item.unread {
    background-color: #your-color !important;
}
```

### JavaScript Özelleştirme

Bildirim davranışlarını özelleştirmek için:

```javascript
// Bildirim açıldığında özel işlem
jQuery(document).on('pepech_notification_opened', function(event, notification) {
    console.log('Bildirim açıldı:', notification);
});

// Bildirim okundu olarak işaretlendiğinde
jQuery(document).on('pepech_notification_read', function(event, notificationId) {
    console.log('Bildirim okundu:', notificationId);
});
```

## Hook'lar ve Filtreler

### Action Hook'ları

```php
// Bildirim gönderildiğinde
do_action('pepech_notification_sent', $notification_id, $user_id);

// Bildirim okunduğunda
do_action('pepech_notification_read', $notification_id, $user_id);

// Tüm bildirimler okunduğunda
do_action('pepech_all_notifications_read', $user_id);
```

### Filter Hook'ları

```php
// E-posta içeriğini özelleştirme
add_filter('pepech_email_content', function($content, $title, $message) {
    return $custom_content;
}, 10, 3);

// Bildirim sayısını sınırlama
add_filter('pepech_max_notifications', function($limit) {
    return 20; // Maksimum 20 bildirim
});
```

## Sorun Giderme

### Bildirimler Görünmüyor
- Kullanıcının giriş yapmış olduğundan emin olun
- JavaScript hatalarını kontrol edin
- Cache eklentilerini temizleyin

### E-posta Gönderilmiyor
- WordPress mail ayarlarını kontrol edin
- SMTP eklentisi kullanıyorsanız ayarlarını kontrol edin
- E-posta bildirimlerinin açık olduğundan emin olun

### AJAX Hataları
- Nonce değerlerinin doğru olduğundan emin olun
- JavaScript dosyalarının yüklendiğinden emin olun
- Console'da hata mesajlarını kontrol edin

## Gereksinimler

- WordPress 5.0+
- PHP 7.4+
- MySQL 5.6+

## Lisans

GPL v2 veya üzeri

## Destek

Sorularınız için: [pepech.com](https://pepech.com)

## Sürüm Geçmişi

### 1.0.0
- İlk sürüm
- Temel bildirim sistemi
- E-posta entegrasyonu
- Admin paneli
- API fonksiyonları
