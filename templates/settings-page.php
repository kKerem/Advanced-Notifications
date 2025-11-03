<?php
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1>Bildirim Sistemi Ayarları</h1>
    
    <form method="post" action="">
        <?php wp_nonce_field('pepech_settings_nonce'); ?>
        
        <table class="form-table">
            <tbody>
                <tr>
                    <th scope="row">
                        <label for="max_per_page">Sayfa Başına Bildirim Sayısı</label>
                    </th>
                    <td>
                        <input type="number" 
                               id="max_per_page" 
                               name="max_per_page" 
                               value="<?php echo esc_attr(get_option('pepech_notification_max_per_page', 10)); ?>" 
                               min="5" 
                               max="50" 
                               class="regular-text" />
                        <p class="description">
                            Admin panelinde ve kullanıcı sayfasında sayfa başına kaç bildirim gösterileceğini belirleyin.
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="cleanup_days">Otomatik Silme Süresi</label>
                    </th>
                    <td>
                        <input type="number" 
                               id="cleanup_days" 
                               name="cleanup_days" 
                               value="<?php echo esc_attr($current_cleanup_days); ?>" 
                               min="1" 
                               max="365" 
                               class="regular-text" />
                        <p class="description">
                            Kaç günden eski bildirimlerin otomatik olarak silineceğini belirleyin. 
                            Varsayılan: 90 gün. Minimum: 1 gün, Maksimum: 365 gün.
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="default_email">Varsayılan E-posta Gönderimi</label>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox" 
                                   id="default_email" 
                                   name="default_email" 
                                   value="1" 
                                   <?php checked(get_option('pepech_notification_default_email', 1)); ?> />
                            Bildirim gönderirken e-posta gönderimi varsayılan olarak aktif olsun
                        </label>
                        <p class="description">
                            Bu ayar aktif olduğunda, yeni bildirim gönderirken e-posta checkbox'ı otomatik işaretli gelir.
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="enable_dropdown">Header Dropdown</label>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox" 
                                   id="enable_dropdown" 
                                   name="enable_dropdown" 
                                   value="1" 
                                   <?php checked(get_option('pepech_notification_enable_dropdown', 1)); ?> />
                            Header'da bildirim dropdown'ını göster
                        </label>
                        <p class="description">
                            Bu ayar aktif olduğunda, header'da bildirim ikonu ve dropdown menüsü görünür.
                        </p>
                    </td>
                </tr>
                
            <tr>
                <th scope="row">
                        <label for="dropdown_limit">Dropdown Bildirim Sayısı</label>
                </th>
                <td>
                        <input type="number" 
                               id="dropdown_limit" 
                               name="dropdown_limit" 
                               value="<?php echo esc_attr(get_option('pepech_notification_dropdown_limit', 5)); ?>" 
                               min="3" 
                               max="20" 
                               class="regular-text" />
                        <p class="description">
                            Header dropdown'ında kaç bildirim gösterileceğini belirleyin.
                        </p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                        <label for="debug_logs">Debug Logları</label>
                </th>
                <td>
                        <label>
                            <input type="checkbox" 
                                   id="debug_logs" 
                                   name="debug_logs" 
                                   value="1" 
                                   <?php checked(get_option('pepech_notification_debug_logs', 0)); ?> />
                            Debug loglarını aktif et
                        </label>
                        <p class="description">
                            Bu ayar aktif olduğunda, eklenti işlemleri debug.log dosyasına kaydedilir. 
                            Sadece geliştirme aşamasında aktif edin.
                        </p>
                </td>
            </tr>
            </tbody>
        </table>
        
        <h2>WooCommerce Bildirim Entegrasyonu</h2>
        <table class="form-table">
            <tbody>
                <tr>
                    <th scope="row">WooCommerce Bildirimleri</th>
                    <td>
                        <p class="description">
                            WooCommerce'in gönderdiği e-postaları bildirim sistemi ile entegre edin. 
                            Kullanıcılar hem e-posta hem de bildirim alacak.
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="wc_new_order">Yeni Sipariş</label>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox" 
                                   id="wc_new_order" 
                                   name="wc_new_order" 
                                   value="1" 
                                   <?php checked(get_option('pepech_wc_new_order', 1)); ?> />
                            Yeni sipariş verildiğinde bildirim gönder
                        </label>
                        <p class="description">
                            Kullanıcı sipariş verdiğinde bildirim alır.
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="wc_order_processing">Sipariş İşleniyor</label>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox" 
                                   id="wc_order_processing" 
                                   name="wc_order_processing" 
                                   value="1" 
                                   <?php checked(get_option('pepech_wc_order_processing', 1)); ?> />
                            Sipariş işleme alındığında bildirim gönder
                        </label>
                        <p class="description">
                            Sipariş durumu "İşleniyor" olduğunda bildirim alır.
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="wc_order_completed">Sipariş Tamamlandı</label>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox" 
                                   id="wc_order_completed" 
                                   name="wc_order_completed" 
                                   value="1" 
                                   <?php checked(get_option('pepech_wc_order_completed', 1)); ?> />
                            Sipariş tamamlandığında bildirim gönder
                        </label>
                        <p class="description">
                            Sipariş durumu "Tamamlandı" olduğunda bildirim alır.
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="wc_order_cancelled">Sipariş İptal Edildi</label>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox" 
                                   id="wc_order_cancelled" 
                                   name="wc_order_cancelled" 
                                   value="1" 
                                   <?php checked(get_option('pepech_wc_order_cancelled', 1)); ?> />
                            Sipariş iptal edildiğinde bildirim gönder
                        </label>
                        <p class="description">
                            Sipariş durumu "İptal Edildi" olduğunda bildirim alır.
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="wc_order_refunded">Sipariş İade Edildi</label>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox" 
                                   id="wc_order_refunded" 
                                   name="wc_order_refunded" 
                                   value="1" 
                                   <?php checked(get_option('pepech_wc_order_refunded', 1)); ?> />
                            Sipariş iade edildiğinde bildirim gönder
                        </label>
                        <p class="description">
                            Sipariş iade edildiğinde bildirim alır.
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="wc_payment_complete">Ödeme Tamamlandı</label>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox" 
                                   id="wc_payment_complete" 
                                   name="wc_payment_complete" 
                                   value="1" 
                                   <?php checked(get_option('pepech_wc_payment_complete', 1)); ?> />
                            Ödeme tamamlandığında bildirim gönder
                        </label>
                        <p class="description">
                            Ödeme başarılı olduğunda bildirim alır.
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="wc_order_shipped">Kargoya Verildi</label>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox" 
                                   id="wc_order_shipped" 
                                   name="wc_order_shipped" 
                                   value="1" 
                                   <?php checked(get_option('pepech_wc_order_shipped', 1)); ?> />
                            Sipariş kargoya verildiğinde bildirim gönder
                        </label>
                        <p class="description">
                            Sipariş durumu "Kargoya Verildi" olduğunda bildirim alır.
                        </p>
                    </td>
                </tr>
            </tbody>
        </table>
        
        <?php submit_button('Ayarları Kaydet'); ?>
    </form>
    
    <div class="pepech-settings-info">
        <h3 class="fw-senmb-2">Mevcut Durum</h3>
        <ul class="mt-0">
            <li>
                <strong>Otomatik Temizlik:</strong> 
                <?php echo get_option('pepech_notification_cleanup_days', 90); ?> günden eski bildirimler günlük olarak silinir.
            </li>
            <li>
                <strong>Son Temizlik:</strong> 
                <?php echo get_option('pepech_last_cleanup', 'Henüz yapılmadı'); ?>
            </li>
            <li>
                <strong>Cron Job:</strong> 
                <?php 
                $timestamp = wp_next_scheduled('pepech_cleanup_old_notifications');
                if ($timestamp) {
                    echo 'Aktif - Sonraki çalışma: ' . date('d.m.Y H:i:s', $timestamp);
                } else {
                    echo 'Pasif';
                }
                ?>
            </li>
            <li>
                <strong>Sayfa Başına Bildirim:</strong> 
                <?php echo get_option('pepech_notification_max_per_page', 10); ?> bildirim
            </li>
            <li>
                <strong>Varsayılan E-posta:</strong> 
                <?php echo get_option('pepech_notification_default_email', 1) ? 'Aktif' : 'Pasif'; ?>
            </li>
            <li>
                <strong>Header Dropdown:</strong> 
                <?php echo get_option('pepech_notification_enable_dropdown', 1) ? 'Aktif' : 'Pasif'; ?>
            </li>
            <li>
                <strong>Dropdown Limit:</strong> 
                <?php echo get_option('pepech_notification_dropdown_limit', 5); ?> bildirim
            </li>
            <li>
                <strong>Debug Logları:</strong> 
                <?php echo get_option('pepech_notification_debug_logs', 0) ? 'Aktif' : 'Pasif'; ?>
            </li>
        </ul>
    </div>
    
    <div class="pepech-settings-actions">
        <h3>Diğer İşlemler</h3>
        <p>
            <button type="button" id="pepech-manual-cleanup-settings" class="button me-3">
                Şimdi Temizle
            </button>
            <button type="button" id="pepech-delete-all-notifications" class="button button-secondary me-3">
                Tüm Bildirimleri Sil
            </button>
            <button type="button" id="pepech-reset-settings" class="button button-secondary">
                Varsayılan Ayarlara Dön
            </button>
        </p>
    </div>
    
    <?php
    if ( ! function_exists( 'get_plugin_data' ) ) {
        require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
    }
    $plugin_file = WP_PLUGIN_DIR . '/pepech-notification-system/pepech-notification-system.php';
    $plugin_data = file_exists($plugin_file) ? get_plugin_data( $plugin_file ) : null;

    $plugin_name = isset($plugin_data['Name']) ? $plugin_data['Name'] : 'Pepech - Bildirim Sistemi';
    $author      = isset($plugin_data['Author']) ? $plugin_data['Author'] : '';
    $author_uri  = isset($plugin_data['AuthorURI']) ? $plugin_data['AuthorURI'] : '';
    $version     = isset($plugin_data['Version']) ? $plugin_data['Version'] : '';
    ?>
    <div class="pepech-plugin-info text-center">
        <?php echo esc_html($plugin_name); ?><br><span class="fw-semibold"><?php echo $author; ?></span> - v<?php echo esc_html($version); ?>
    </div>
</div>