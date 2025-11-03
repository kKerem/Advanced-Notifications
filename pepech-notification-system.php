<?php
/**
 * Plugin Name: Pepech - Bildirim Sistemi
 * Plugin URI: https://pepech.com
 * Description: Kullanıcılara bildirim gönderme ve yönetme sistemi
 * Version: 1.0.0
 * Author: Kerem ER
 * Author URI: https://kkerem.com
 * License: GPL v2 or later
 * Text Domain: pepech-notification-system
 * Update URI: https://github.com/kKerem/Advanced-Notifications
 */

// Güvenlik kontrolü
if (!defined('ABSPATH')) {
    exit;
}

// Eklenti sabitleri
define('PEPECH_NOTIFICATION_VERSION', '1.0.0');
define('PEPECH_NOTIFICATION_PLUGIN_URL', plugin_dir_url(__FILE__));
define('PEPECH_NOTIFICATION_PLUGIN_PATH', plugin_dir_path(__FILE__));

class PepechNotificationSystem {
    
    public function __construct() {
        add_action('init', array($this, 'init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        register_uninstall_hook(__FILE__, array('PepechNotificationSystem', 'uninstall'));
        
        // Otomatik temizlik cron job'u
        add_action('pepech_cleanup_old_notifications', array($this, 'cleanup_old_notifications'));
        
        // WooCommerce bildirim entegrasyonu
        add_action('woocommerce_new_order', array($this, 'wc_new_order_notification'), 10, 1);
        add_action('woocommerce_order_status_processing', array($this, 'wc_order_processing_notification'), 10, 1);
        add_action('woocommerce_order_status_completed', array($this, 'wc_order_completed_notification'), 10, 1);
        add_action('woocommerce_order_status_cancelled', array($this, 'wc_order_cancelled_notification'), 10, 1);
        add_action('woocommerce_order_status_refunded', array($this, 'wc_order_refunded_notification'), 10, 1);
        add_action('woocommerce_payment_complete', array($this, 'wc_payment_complete_notification'), 10, 1);
        add_action('woocommerce_order_status_kargo-verildi', array($this, 'wc_order_shipped_notification'), 10, 1);

        // GitHub auto-update (Plugin Update Checker) bootstrap
        add_action('plugins_loaded', array($this, 'maybe_init_github_updater'));        
    }
    
    public function init() {
        // Dil dosyalarını yükle
        load_plugin_textdomain('pepech-notification-system', false, dirname(plugin_basename(__FILE__)) . '/languages');
        
        // Admin paneli
        if (is_admin()) {
            add_action('admin_menu', array($this, 'add_admin_menu'));
            add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
        }
        
        // Frontend
        add_action('wp_enqueue_scripts', array($this, 'frontend_enqueue_scripts'));
        // add_action('wp_footer', array($this, 'add_notification_dropdown'));
        add_action('wp_head', array($this, 'add_header_hook'));
        
        // AJAX işlemleri
        add_action('wp_ajax_mark_notification_read', array($this, 'mark_notification_read'));
        add_action('wp_ajax_mark_all_notifications_read', array($this, 'mark_all_notifications_read'));
        add_action('wp_ajax_get_notifications', array($this, 'get_notifications'));
        add_action('wp_ajax_get_notification_details', array($this, 'get_notification_details'));
        add_action('wp_ajax_bulk_notification_action', array($this, 'bulk_notification_action'));
        add_action('wp_ajax_send_bulk_notification', array($this, 'send_bulk_notification_ajax'));
        add_action('wp_ajax_manual_cleanup_notifications', array($this, 'manual_cleanup_notifications'));
        add_action('wp_ajax_delete_all_notifications', array($this, 'delete_all_notifications'));
        
        // Frontend AJAX
        add_action('wp_ajax_nopriv_mark_notification_read', array($this, 'mark_notification_read'));
        add_action('wp_ajax_nopriv_mark_all_notifications_read', array($this, 'mark_all_notifications_read'));
        add_action('wp_ajax_nopriv_get_notifications', array($this, 'get_notifications'));
        
        // Kısa kodlar
        add_shortcode('pepech_notifications', array($this, 'notifications_shortcode'));
        add_shortcode('pepech_notifications_button', array($this, 'notifications_button_shortcode'));
        add_shortcode('pepech_notifications_offcanvas', array($this, 'notifications_offcanvas_shortcode'));
        
        // Diğer eklentiler için hook'lar
        add_action('pepech_send_notification', array($this, 'send_notification'), 10, 3);
        
        // WooCommerce My Account entegrasyonu
        add_filter('woocommerce_account_menu_items', array($this, 'add_notifications_menu_item'));
        add_action('init', array($this, 'add_notifications_endpoint'), 5);
        add_action('woocommerce_account_bildirimlerim_endpoint', array($this, 'notifications_endpoint_content'));
        
        // Query vars'ı ekle
        add_filter('query_vars', array($this, 'add_query_vars'));
        
        // WooCommerce query vars'ı ekle
        add_filter('woocommerce_get_query_vars', array($this, 'add_woocommerce_query_vars'));
    }
    
    /**
     * Initialize GitHub updates via Plugin Update Checker if available
     */
    public function maybe_init_github_updater() {
        // Try common vendor paths without hard-failing
        $puc_paths = array(
            __DIR__ . '/vendor/plugin-update-checker/plugin-update-checker.php',
            __DIR__ . '/vendor/yahnis-elsts/plugin-update-checker/plugin-update-checker.php',
            __DIR__ . '/lib/plugin-update-checker/plugin-update-checker.php'
        );

        foreach ($puc_paths as $puc_file) {
            if (file_exists($puc_file)) {
                require_once $puc_file;
                if (class_exists('Puc_v4_Factory')) {
                    $updater = Puc_v4_Factory::buildUpdateChecker(
                        'https://github.com/kKerem/Advanced-Notifications',
                        __FILE__,
                        'pepech-notification-system'
                    );
                    // Use main branch for updates
                    if (method_exists($updater, 'setBranch')) {
                        $updater->setBranch('main');
                    }
                    // If you publish Releases with ZIP assets, uncomment below
                    // if (method_exists($updater, 'getVcsApi')) {
                    //     $api = $updater->getVcsApi();
                    //     if ($api && method_exists($api, 'enableReleaseAssets')) {
                    //         $api->enableReleaseAssets();
                    //     }
                    // }
                }
                break;
            }
        }
    }

    public function activate() {
        $this->create_tables();
        $this->update_tables();
        $this->create_default_options();
        // $this->create_notifications_page(); // Gereksiz sayfa oluşturmayı kaldırdık
        
        // Otomatik temizlik cron job'unu kur
        if (!wp_next_scheduled('pepech_cleanup_old_notifications')) {
            wp_schedule_event(time(), 'daily', 'pepech_cleanup_old_notifications');
        }
        
        // Rewrite rules'ları yenile (WooCommerce endpoint için)
        flush_rewrite_rules();
        
        // Endpoint'in eklendiğini işaretle
        update_option('pepech_notifications_endpoint_added', true);
        
        // Debug için log ekle
        error_log('Pepech Notification: Plugin activated, rewrite rules flushed');
    }
    
    public function deactivate() {
        // Otomatik temizlik cron job'unu kaldır
        $timestamp = wp_next_scheduled('pepech_cleanup_old_notifications');
        if ($timestamp) {
            wp_unschedule_event($timestamp, 'pepech_cleanup_old_notifications');
        }
        
        // Rewrite rules'ları yenile (WooCommerce endpoint için)
        flush_rewrite_rules();
        
        // Endpoint flag'ini temizle
        delete_option('pepech_notifications_endpoint_added');
    }
    
    public static function uninstall() {
        global $wpdb;
        
        // Veritabanı tablosunu sil
        $table_name = $wpdb->prefix . 'pepech_notifications';
        $wpdb->query("DROP TABLE IF EXISTS $table_name");
        
        // Ayarları sil
        delete_option('pepech_notification_email_enabled');
        delete_option('pepech_notification_max_per_page');
        delete_option('pepech_notification_version');
        
        // Bildirimlerim sayfasını sil
        $page = get_page_by_path('bildirimlerim');
        if ($page) {
            wp_delete_post($page->ID, true);
        }
    }
    
    private function create_tables() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'pepech_notifications';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            title varchar(255) NOT NULL,
            message text NOT NULL,
            type varchar(50) DEFAULT 'info',
            is_read tinyint(1) DEFAULT 0,
            send_email tinyint(1) DEFAULT 1,
            link_url varchar(500) DEFAULT NULL,
            link_text varchar(255) DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY is_read (is_read),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    private function update_tables() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'pepech_notifications';
        
        // link_url kolonu var mı kontrol et
        $column_exists = $wpdb->get_results("SHOW COLUMNS FROM $table_name LIKE 'link_url'");
        if (empty($column_exists)) {
            $wpdb->query("ALTER TABLE $table_name ADD COLUMN link_url varchar(500) DEFAULT NULL AFTER send_email");
        }
        
        // link_text kolonu var mı kontrol et
        $column_exists = $wpdb->get_results("SHOW COLUMNS FROM $table_name LIKE 'link_text'");
        if (empty($column_exists)) {
            $wpdb->query("ALTER TABLE $table_name ADD COLUMN link_text varchar(255) DEFAULT NULL AFTER link_url");
        }
        
        // thumbnail kolonu var mı kontrol et
        $column_exists = $wpdb->get_results("SHOW COLUMNS FROM $table_name LIKE 'thumbnail'");
        if (empty($column_exists)) {
            $wpdb->query("ALTER TABLE $table_name ADD COLUMN thumbnail varchar(500) DEFAULT NULL AFTER link_text");
        }
        
        // notification_emoji kolonu var mı kontrol et
        $column_exists = $wpdb->get_results("SHOW COLUMNS FROM $table_name LIKE 'notification_emoji'");
        if (empty($column_exists)) {
            $wpdb->query("ALTER TABLE $table_name ADD COLUMN notification_emoji varchar(10) DEFAULT '✨' AFTER type");
        }
    }
    
    private function create_default_options() {
        add_option('pepech_notification_email_enabled', 1);
        add_option('pepech_notification_max_per_page', 10);
        add_option('pepech_notification_version', PEPECH_NOTIFICATION_VERSION);
    }
    
    private function create_notifications_page() {
        // Bildirimlerim sayfası zaten var mı kontrol et
        $page_title = 'Bildirimlerim';
        $page_slug = 'bildirimlerim';
        
        $existing_page = get_page_by_path($page_slug);
        
        if (!$existing_page) {
            $page_data = array(
                'post_title' => $page_title,
                'post_content' => '[pepech_notifications limit="20" show_read="true"]',
                'post_status' => 'publish',
                'post_type' => 'page',
                'post_name' => $page_slug,
                'post_author' => 1
            );
            
            $page_id = wp_insert_post($page_data);
            
            if ($page_id) {
                // Sayfa template'ini ayarla
                update_post_meta($page_id, '_wp_page_template', 'page-bildirimlerim.php');
            }
        }
    }
    
    public function add_admin_menu() {
        add_menu_page(
            'Bildirim Sistemi',
            'Bildirimler',
            'manage_options',
            'pepech-notifications',
            array($this, 'admin_page'),
            'dashicons-bell',
            30
        );
        
        add_submenu_page(
            'pepech-notifications',
            'Bildirim Gönder',
            'Bildirim Gönder',
            'manage_options',
            'pepech-send-notification',
            array($this, 'send_notification_page')
        );
        
        add_submenu_page(
            'pepech-notifications',
            'Ayarlar',
            'Ayarlar',
            'manage_options',
            'pepech-notification-settings',
            array($this, 'settings_page')
        );
    }
    
    public function admin_enqueue_scripts($hook) {
        if (strpos($hook, 'pepech') !== false) {
            wp_enqueue_script('jquery');
            
            // Select2 CDN'den yükle
            wp_enqueue_script('select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', array('jquery'), '4.1.0', true);
            wp_enqueue_style('select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css', array(), '4.1.0');
            
            // Sadece bildirim gönderme sayfasında admin.js yükle
            if (strpos($hook, 'pepech-send-notification') !== false) {
                wp_enqueue_script('pepech-admin', PEPECH_NOTIFICATION_PLUGIN_URL . 'assets/admin.js', array('jquery'), '1.0.0', true);
                wp_enqueue_style('pepech-admin', PEPECH_NOTIFICATION_PLUGIN_URL . 'assets/admin.css', array(), '1.0.0');
                
                // AJAX için nonce
                wp_localize_script('pepech-admin', 'pepech_ajax', array(
                    'ajax_url' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('pepech_notification_nonce')
                ));
            }
            
            // Sadece settings sayfasında settings.js yükle
            if (strpos($hook, 'pepech-notification-settings') !== false) {
                wp_enqueue_script('pepech-settings', PEPECH_NOTIFICATION_PLUGIN_URL . 'assets/settings.js', array('jquery'), '1.0.0', true);
                
                // AJAX için nonce
                wp_localize_script('pepech-settings', 'pepech_settings_ajax', array(
                    'ajax_url' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('pepech_notification_nonce')
                ));
            }
            
            // Select2 başlatma scripti - sadece bildirim gönderme sayfasında
            if (strpos($hook, 'pepech-send-notification') !== false) {
                add_action('admin_footer', function() {
                echo '<script>
                jQuery(document).ready(function($) {
                    console.log("Select2 available:", typeof $.fn.select2);
                    
                    // Select2\'yi başlat
                    function initializeAllSelect2() {
                        // Kullanıcı seçimi - Multi-select
                        $("#selected_users").select2({
                            placeholder: "Kullanicilari secin...",
                            allowClear: true,
                            width: "100%",
                            closeOnSelect: false,
                            multiple: true,
                            tags: false,
                            tokenSeparators: [','],
                            language: {
                                noResults: function() {
                                    return "Sonuc bulunamadi";
                                },
                                searching: function() {
                                    return "Araniyor...";
                                },
                                removeAllItems: function() {
                                    return "Tumunu kaldir";
                                },
                                removeItem: function() {
                                    return "Kaldir";
                                }
                            }
                        });
                        
                        // Bildirim linki
                        $("#notification_link").select2({
                            placeholder: "Link secin...",
                            allowClear: true,
                            width: "100%"
                        });
                        
                        console.log("Tum Select2\'ler baslatildi");
                    }
                    
                    // Select2\'nin yüklenmesini bekle
                    if (typeof $.fn.select2 !== "undefined") {
                        console.log("Select2 mevcut, baslatiliyor...");
                        initializeAllSelect2();
                    } else {
                        console.log("Select2 henuz yuklenmedi, bekleniyor...");
                        // Select2\'nin yüklenmesini bekle
                        var checkSelect2 = setInterval(function() {
                            if (typeof $.fn.select2 !== "undefined") {
                                clearInterval(checkSelect2);
                                console.log("Select2 yuklendi, baslatiliyor...");
                                initializeAllSelect2();
                            }
                        }, 100);
                        
                        // 5 saniye sonra timeout
                        setTimeout(function() {
                            clearInterval(checkSelect2);
                            if (typeof $.fn.select2 === "undefined") {
                                console.error("Select2 yuklenemedi!");
                            }
                        }, 5000);
                    }
                });
                </script>';
                });
            }
        }
    }
    
    public function frontend_enqueue_scripts() {
        wp_enqueue_script('jquery');
        wp_enqueue_script('pepech-notification-frontend', PEPECH_NOTIFICATION_PLUGIN_URL . 'assets/frontend.js', array('jquery'), PEPECH_NOTIFICATION_VERSION, true);
        wp_enqueue_style('pepech-notification-frontend', PEPECH_NOTIFICATION_PLUGIN_URL . 'assets/frontend.css', array(), PEPECH_NOTIFICATION_VERSION);
        
        wp_localize_script('pepech-notification-frontend', 'pepech_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('pepech_notification_nonce')
        ));
    }
    
    public function admin_page() {
        include PEPECH_NOTIFICATION_PLUGIN_PATH . 'templates/admin-page.php';
    }
    
    public function settings_page() {
        // Ayarları kaydet
        if (isset($_POST['submit'])) {
            check_admin_referer('pepech_settings_nonce');
            
            // Otomatik silme günleri
            $cleanup_days = intval($_POST['cleanup_days']);
            if ($cleanup_days < 1) $cleanup_days = 90; // Minimum 1 gün
            update_option('pepech_notification_cleanup_days', $cleanup_days);
            
            // Sayfa başına bildirim sayısı
            $max_per_page = intval($_POST['max_per_page']);
            if ($max_per_page < 5) $max_per_page = 10; // Minimum 5
            if ($max_per_page > 50) $max_per_page = 50; // Maksimum 50
            update_option('pepech_notification_max_per_page', $max_per_page);
            
            // Varsayılan e-posta gönderimi
            $default_email = isset($_POST['default_email']) ? 1 : 0;
            update_option('pepech_notification_default_email', $default_email);
            
            // Header dropdown aktif/pasif
            $enable_dropdown = isset($_POST['enable_dropdown']) ? 1 : 0;
            update_option('pepech_notification_enable_dropdown', $enable_dropdown);
            
            // Dropdown bildirim sayısı
            $dropdown_limit = intval($_POST['dropdown_limit']);
            if ($dropdown_limit < 3) $dropdown_limit = 5; // Minimum 3
            if ($dropdown_limit > 20) $dropdown_limit = 20; // Maksimum 20
            update_option('pepech_notification_dropdown_limit', $dropdown_limit);
            
            // Debug logları aktif/pasif
            $debug_logs = isset($_POST['debug_logs']) ? 1 : 0;
            update_option('pepech_notification_debug_logs', $debug_logs);
            
            // WooCommerce bildirim ayarları
            $wc_new_order = isset($_POST['wc_new_order']) ? 1 : 0;
            update_option('pepech_wc_new_order', $wc_new_order);
            
            $wc_order_processing = isset($_POST['wc_order_processing']) ? 1 : 0;
            update_option('pepech_wc_order_processing', $wc_order_processing);
            
            $wc_order_completed = isset($_POST['wc_order_completed']) ? 1 : 0;
            update_option('pepech_wc_order_completed', $wc_order_completed);
            
            $wc_order_cancelled = isset($_POST['wc_order_cancelled']) ? 1 : 0;
            update_option('pepech_wc_order_cancelled', $wc_order_cancelled);
            
            $wc_order_refunded = isset($_POST['wc_order_refunded']) ? 1 : 0;
            update_option('pepech_wc_order_refunded', $wc_order_refunded);
            
            $wc_payment_complete = isset($_POST['wc_payment_complete']) ? 1 : 0;
            update_option('pepech_wc_payment_complete', $wc_payment_complete);
            
            $wc_order_shipped = isset($_POST['wc_order_shipped']) ? 1 : 0;
            update_option('pepech_wc_order_shipped', $wc_order_shipped);
            
            echo '<div class="notice notice-success"><p>Ayarlar kaydedildi!</p></div>';
        }
        
        $current_cleanup_days = get_option('pepech_notification_cleanup_days', 90);
        
        include PEPECH_NOTIFICATION_PLUGIN_PATH . 'templates/settings-page.php';
    }
    
    public function send_notification_page() {
        include PEPECH_NOTIFICATION_PLUGIN_PATH . 'templates/send-notification-page.php';
    }
    
    public function add_notification_dropdown() {
        if (is_user_logged_in()) {
            include PEPECH_NOTIFICATION_PLUGIN_PATH . 'templates/notification-dropdown.php';
        }
    }
    
    public function add_header_hook() {
        // Header'a bildirim dropdown'ını eklemek için hook
        add_action('pepech_header_notifications', array($this, 'render_notification_dropdown'));
    }
    
    public function render_notification_dropdown() {
        if (is_user_logged_in()) {
            include PEPECH_NOTIFICATION_PLUGIN_PATH . 'templates/notification-dropdown.php';
        }
    }
    
    public function mark_notification_read() {
        check_ajax_referer('pepech_notification_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_die('Unauthorized');
        }
        
        $notification_id = intval($_POST['notification_id']);
        $user_id = get_current_user_id();
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'pepech_notifications';
        
        $result = $wpdb->update(
            $table_name,
            array('is_read' => 1),
            array('id' => $notification_id, 'user_id' => $user_id),
            array('%d'),
            array('%d', '%d')
        );
        
        if ($result !== false) {
            wp_send_json_success();
        } else {
            wp_send_json_error();
        }
    }
    
    public function mark_all_notifications_read() {
        check_ajax_referer('pepech_notification_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_die('Unauthorized');
        }
        
        $user_id = get_current_user_id();
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'pepech_notifications';
        
        $result = $wpdb->update(
            $table_name,
            array('is_read' => 1),
            array('user_id' => $user_id, 'is_read' => 0),
            array('%d'),
            array('%d', '%d')
        );
        
        if ($result !== false) {
            wp_send_json_success();
        } else {
            wp_send_json_error();
        }
    }
    
    public function get_notifications() {
        check_ajax_referer('pepech_notification_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_die('Unauthorized');
        }
        
        $user_id = get_current_user_id();
        $limit = intval($_POST['limit']) ?: 5;
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'pepech_notifications';
        
        $notifications = $wpdb->get_results($wpdb->prepare(
            "SELECT *, is_read FROM $table_name WHERE user_id = %d ORDER BY created_at DESC LIMIT %d",
            $user_id,
            $limit
        ));
        
        // Tarih formatını kısalt
        foreach ($notifications as $notification) {
            $notification->time_ago = $this->get_short_time_ago($notification->created_at);
        }
        
        // Toplam okunmamış bildirim sayısını al
        $total_unread = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE user_id = %d AND is_read = 0",
            $user_id
        ));
        
        wp_send_json_success(array(
            'notifications' => $notifications,
            'total_unread' => intval($total_unread)
        ));
    }
    
    /**
     * Kısa tarih formatı
     */
    private function get_short_time_ago($date_string) {
        $now = current_time('timestamp');
        $date = strtotime($date_string);
        $diff_in_seconds = $now - $date;
        
        if ($diff_in_seconds < 60) {
            return 'Az önce';
        } else if ($diff_in_seconds < 3600) {
            return floor($diff_in_seconds / 60) . 'dk önce';
        } else if ($diff_in_seconds < 86400) {
            return floor($diff_in_seconds / 3600) . 's önce';
        } else if ($diff_in_seconds < 2592000) { // 30 gün
            return floor($diff_in_seconds / 86400) . 'g önce';
        } else {
            return floor($diff_in_seconds / 2592000) . 'ay önce';
        }
    }
    
    public function send_notification($user_id, $title, $message, $type = 'info', $send_email = true, $link_url = '', $link_text = '', $thumbnail = '', $notification_emoji = '') {
        global $wpdb;
        $table_name = $wpdb->prefix . 'pepech_notifications';
        
        // Veritabanı sütunlarını kontrol et
        $columns = $wpdb->get_col("DESCRIBE $table_name");
        
        $data = array(
            'user_id' => $user_id,
            'title' => $title,
            'message' => $message,
            'type' => $type,
            'send_email' => $send_email ? 1 : 0,
            'created_at' => current_time('mysql')
        );
        
        $format = array('%d', '%s', '%s', '%s', '%d', '%s');
        
        // Link URL varsa ekle
        if (!empty($link_url) && in_array('link_url', $columns)) {
            $data['link_url'] = $link_url;
            $format[] = '%s';
        }
        
        // Link text varsa ekle
        if (!empty($link_text) && in_array('link_text', $columns)) {
            $data['link_text'] = $link_text;
            $format[] = '%s';
        }
        
        // Thumbnail varsa ekle
        if (!empty($thumbnail) && in_array('thumbnail', $columns)) {
            $data['thumbnail'] = $thumbnail;
            $format[] = '%s';
        }
        
        // Notification emoji varsa ekle
        if (!empty($notification_emoji) && in_array('notification_emoji', $columns)) {
            $data['notification_emoji'] = $notification_emoji;
            $format[] = '%s';
        }
        
        $result = $wpdb->insert($table_name, $data, $format);
        
        if ($result && $send_email) {
            $this->send_notification_email($user_id, $title, $message, $type, $link_url, $link_text, $thumbnail, $notification_emoji);
        }
        
        return $result;
    }
    
    public function send_bulk_notification($user_ids, $title, $message, $type = 'info', $send_email = true) {
        $success_count = 0;
        $error_count = 0;
        
        foreach ($user_ids as $user_id) {
            $result = $this->send_notification($user_id, $title, $message, $type, $send_email);
            
            if ($result) {
                $success_count++;
            } else {
                $error_count++;
            }
        }
        
        return array(
            'success_count' => $success_count,
            'error_count' => $error_count,
            'total_count' => count($user_ids)
        );
    }
    
    private function send_notification_email($user_id, $title, $message, $type = 'info', $link_url = '', $link_text = '', $thumbnail = '', $notification_emoji = '') {
        $user = get_user_by('id', $user_id);
        if (!$user) {
            return false;
        }
        
        // Emoji'yi al (parametreden veya type'dan)
        $emoji = !empty($notification_emoji) ? $notification_emoji : $this->get_notification_emoji($type);
        $subject = $emoji . ' ' . get_bloginfo('name') . ' - ' . $title;
        $headers = array('Content-Type: text/html; charset=UTF-8');
        
        $email_content = $this->get_email_template($title, $message, $type, $link_url, $link_text, $thumbnail, $emoji);
        
        return wp_mail($user->user_email, $subject, $email_content, $headers);
    }
    
    private function get_notification_emoji($type) {
        $emojis = array(
            'info' => '✨',
            'success' => '🎉',
            'warning' => '😶',
            'error' => '😔'
        );
        
        return isset($emojis[$type]) ? $emojis[$type] : '✨';
    }
    
    private function get_email_template($title, $message, $type = 'info', $link_url = '', $link_text = '', $thumbnail = '', $emoji = '') {
        // Emoji'yi al (parametreden veya type'dan)
        if (empty($emoji)) {
            $emoji = $this->get_notification_emoji($type);
        }
        
        $template = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>' . $emoji . ' ' . esc_html($title) . '</title>
        </head>
        <body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
            <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
                <h2 style="color: #2c3e50; display: flex; align-items: center; gap: 10px;">
                    <span style="font-size: 24px;">' . $emoji . '</span>
                    ' . esc_html($title) . '
                </h2>';
        
        // Thumbnail varsa ekle
        if (!empty($thumbnail)) {
            $template .= '
                <div style="text-align: center; margin: 20px 0;">
                    <img src="' . esc_url($thumbnail) . '" alt="' . esc_attr($title) . '" style="max-width: 300px; height: auto; border-radius: 5px;">
                </div>';
        }
        
        $template .= '
                <div style="background: #f8f9fa; padding: 20px; border-radius: 5px; margin: 20px 0;">
                    ' . wp_kses_post($message) . '
                </div>';
        
        // Link varsa ekle
        if (!empty($link_url) && !empty($link_text)) {
            $template .= '
                <div style="text-align: center; margin: 20px 0;">
                    <a href="' . esc_url($link_url) . '" style="background: #007cba; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; display: inline-block;">
                        ' . esc_html($link_text) . '
                    </a>
                </div>';
        }
        
        $template .= '
                <p style="margin-top: 30px; font-size: 14px; color: #666;">
                    Bu bildirimi <a href="' . home_url() . '">' . get_bloginfo('name') . '</a> sitesinden aldınız.
                </p>
            </div>
        </body>
        </html>';
        
        return $template;
    }
    
    public function notifications_shortcode($atts) {
        $atts = shortcode_atts(array(
            'limit' => 10,
            'show_read' => true
        ), $atts);
        
        if (!is_user_logged_in()) {
            return '<p>Bildirimleri görüntülemek için giriş yapmalısınız.</p>';
        }
        
        $user_id = get_current_user_id();
        global $wpdb;
        $table_name = $wpdb->prefix . 'pepech_notifications';
        
        $where_clause = "WHERE user_id = %d";
        $params = array($user_id);
        
        if (!$atts['show_read']) {
            $where_clause .= " AND is_read = 0";
        }
        
        $notifications = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name $where_clause ORDER BY created_at DESC LIMIT %d",
            array_merge($params, array($atts['limit']))
        ));
        
        ob_start();
        include PEPECH_NOTIFICATION_PLUGIN_PATH . 'templates/notifications-list.php';
        return ob_get_clean();
    }
    
    /**
     * Bildirim butonu shortcode'u
     * Kullanım: [pepech_notifications_button type="mobile"|"desktop"]
     */
    public function notifications_button_shortcode($atts) {
        $atts = shortcode_atts(array(
            'type' => 'mobile' // mobile veya desktop
        ), $atts);
        
        if (!is_user_logged_in()) {
            return '';
        }
        
        $user_id = get_current_user_id();
        $unread_count = 0;
        
        if (function_exists('pepech_get_unread_count')) {
            $unread_count = pepech_get_unread_count($user_id);
        }
        
        ob_start();
        
        if ($atts['type'] === 'mobile') {
            // Mobile bottom bar için buton
            ?>
            <button type="button" class="btn d-flex flex-column align-items-center justify-content-center text-decoration-none text-inherit p-0 text-center pt-2 animate-scale position-relative border-0 bg-transparent w-100" data-bs-toggle="offcanvas" data-bs-target="#offcanvas-notifications" aria-controls="offcanvas-notifications" aria-label="<?php esc_attr_e('Bildirimler', 'pepech'); ?>" style="flex: 1 1 0;">
              <span class="notification-icon d-flex position-relative">
                <i class="ci-bell m-0 fs-4 animate-target"></i>
                <?php if ($unread_count > 0): ?>
                  <span class="position-absolute top-0 start-100 translate-middle badge rounded-circle bg-danger p-0" style="width: 14px; height: 14px; line-height: 13px; font-size: 9px; text-align: center;"><?php echo $unread_count; ?></span>
                <?php endif; ?>
              </span>
              <span class="small"><?php echo esc_html__('Bildirimler', 'pepech'); ?></span>
            </button>
            <?php
        } else {
            // Desktop header için buton (dropdown)
            ?>
            <div class="dropdown d-none d-lg-inline-flex">
                <button type="button" id="pepech-notification-toggle" class="pepech-notification-toggle btn btn-icon btn-lg btn-outline-secondary fs-lg border-0 rounded-circle animate-scale" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" aria-label="<?php esc_attr_e('Bildirimler', 'pepech'); ?>">
                    <span class="notification-icon d-flex animate-target position-relative">
                        <i class="ci-bell fs-lg m-0"></i>
                        <?php if ($unread_count > 0): ?>
                            <span class="pepech-notification-badge position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger fs-xxs">
                                <?php echo $unread_count; ?>
                            </span>
                        <?php endif; ?>
                    </span>
                </button>
                <?php
                // Desktop için dropdown menü
                $recent_notifications = array();
                if (function_exists('pepech_get_user_notifications')) {
                    $recent_notifications = pepech_get_user_notifications($user_id, 5);
                }
                ?>
                <ul id="pepech-notification-panel" class="dropdown-menu dropdown-menu-end dropdown-menu-md-start pepech-notification-panel" style="--cz-dropdown-min-width: 19.5rem;">
                    <li class="dropdown-header d-flex justify-content-between align-items-center pt-0 px-0">
                        <span class="fw-semibold"><?php esc_html_e('Bildirimler', 'pepech'); ?></span>
                        <a href="<?php echo home_url('/hesabim/bildirimlerim'); ?>" class="fs-xs text-body">
                            <?php esc_html_e('Tümünü göster', 'pepech'); ?>
                        </a>
                    </li>
                    
                    <?php if ($recent_notifications): ?>
                        <?php foreach ($recent_notifications as $notification): ?>
                            <?php if (!empty($notification->link_url)): ?>
                                <li>
                                    <a href="<?php echo esc_url($notification->link_url); ?>" class="dropdown-item pepech-notification-item-link p-2 <?php echo $notification->is_read ? 'read' : 'unread'; ?>" 
                                       data-id="<?php echo $notification->id; ?>" target="_blank">
                                        <div class="d-flex align-items-start">
                                            <div class="pepech-notification-icon me-3">
                                                <?php
                                                $emoji = !empty($notification->notification_emoji) ? $notification->notification_emoji : '📢';
                                                ?>
                                                <span style="font-size: 24px;"><?php echo $emoji; ?></span>
                                            </div>
                                            <div class="pepech-notification-body flex-grow-1">
                                                <div class="d-flex justify-content-between align-items-start">
                                                    <h6 class="pepech-notification-title mb-0 fs-xs"><?php echo esc_html($notification->title); ?></h6>
                                                </div>
                                                <?php if (!empty($notification->thumbnail)): ?>
                                                    <div class="pepech-notification-thumbnail mb-2">
                                                        <img src="<?php echo esc_url($notification->thumbnail); ?>" alt="<?php echo esc_attr($notification->title); ?>" class="img-thumbnail" style="max-width: 60px; max-height: 45px;">
                                                    </div>
                                                <?php endif; ?>
                                                <small class="pepech-notification-time text-muted fs-xs">
                                                    <?php echo human_time_diff(strtotime($notification->created_at), current_time('timestamp')) . ' önce'; ?>
                                                </small>
                                            </div>
                                        </div>
                                    </a>
                                </li>
                            <?php else: ?>
                                <li>
                                    <div class="dropdown-item pepech-notification-item <?php echo $notification->is_read ? 'read' : 'unread'; ?>" 
                                         data-id="<?php echo $notification->id; ?>">
                                        <div class="d-flex align-items-start">
                                            <div class="pepech-notification-icon me-3">
                                                <?php
                                                $emoji = !empty($notification->notification_emoji) ? $notification->notification_emoji : '📢';
                                                ?>
                                                <span style="font-size: 24px;"><?php echo $emoji; ?></span>
                                            </div>
                                            <div class="pepech-notification-body flex-grow-1">
                                                <div class="d-flex justify-content-between align-items-start">
                                                    <h6 class="pepech-notification-title mb-0 fs-xs"><?php echo esc_html($notification->title); ?></h6>
                                                </div>
                                                <?php if (!empty($notification->thumbnail)): ?>
                                                    <div class="pepech-notification-thumbnail mb-2">
                                                        <img src="<?php echo esc_url($notification->thumbnail); ?>" alt="<?php echo esc_attr($notification->title); ?>" class="img-thumbnail" style="max-width: 60px; max-height: 45px;">
                                                    </div>
                                                <?php endif; ?>
                                                <small class="pepech-notification-time text-muted fs-xs">
                                                    <?php echo human_time_diff(strtotime($notification->created_at), current_time('timestamp')) . ' önce'; ?>
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                </li>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <li>
                            <div class="dropdown-item text-center text-muted py-3">
                                <i class="ci-bell fs-1 d-block mb-2"></i>
                                <p class="mb-0"><?php esc_html_e('Henüz bildirim bulunmuyor.', 'pepech'); ?></p>
                            </div>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
            <?php
        }
        
        return ob_get_clean();
    }
    
    /**
     * Bildirim offcanvas shortcode'u
     * Kullanım: [pepech_notifications_offcanvas]
     */
    public function notifications_offcanvas_shortcode($atts) {
        if (!is_user_logged_in()) {
            return '';
        }
        
        $user_id = get_current_user_id();
        $recent_notifications = array();
        
        if (function_exists('pepech_get_user_notifications')) {
            $recent_notifications = pepech_get_user_notifications($user_id, 5);
        }
        
        ob_start();
        ?>
        <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvas-notifications" aria-labelledby="offcanvas-notifications-label">
            <div class="offcanvas-header pb-2 pb-lg-3">
                <span class="h5 offcanvas-title" id="offcanvas-notifications-label"><?php esc_html_e('Bildirimler', 'pepech'); ?></span>
                <div class="d-flex justify-content-between align-items-center ms-auto">
                    <a href="<?php echo home_url('/hesabim/bildirimlerim'); ?>" class="btn btn-sm btn-outline-secondary">
                        <?php esc_html_e('Tümünü göster', 'pepech'); ?>
                    </a>
                </div>
                <button type="button" class="btn-close text-reset ms-0" data-bs-dismiss="offcanvas" aria-label="<?php esc_attr_e('Kapat', 'pepech'); ?>"></button>
            </div>
            <div class="offcanvas-body position-relative pt-0 px-2">
                
                
                <?php if (!empty($recent_notifications)): ?>
                    <div class="pepech-notification-list">
                        <?php foreach ($recent_notifications as $notification): ?>
                            <?php if (!empty($notification->link_url)): ?>
                                <a href="<?php echo esc_url($notification->link_url); ?>" class="pepech-notification-item-link text-decoration-none d-block my-3 rounded <?php echo $notification->is_read ? 'read bg-light' : 'unread bg-light-subtle'; ?>" 
                                   data-id="<?php echo $notification->id; ?>" target="_blank">
                                    <div class="d-flex align-items-start">
                                        <div class="pepech-notification-icon me-3">
                                            <?php
                                            $emoji = !empty($notification->notification_emoji) ? $notification->notification_emoji : '📢';
                                            ?>
                                            <span style="font-size: 24px;"><?php echo $emoji; ?></span>
                                        </div>
                                        <div class="pepech-notification-body flex-grow-1">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <h6 class="pepech-notification-title mb-1 fs-sm fw-semibold"><?php echo esc_html($notification->title); ?></h6>
                                            </div>
                                            <?php if (!empty($notification->thumbnail)): ?>
                                                <div class="pepech-notification-thumbnail mb-2">
                                                    <img src="<?php echo esc_url($notification->thumbnail); ?>" alt="<?php echo esc_attr($notification->title); ?>" class="img-thumbnail" style="max-width: 60px; max-height: 45px;">
                                                </div>
                                            <?php endif; ?>
                                            <small class="pepech-notification-time text-muted d-block">
                                                <?php echo human_time_diff(strtotime($notification->created_at), current_time('timestamp')) . ' önce'; ?>
                                            </small>
                                        </div>
                                    </div>
                                </a>
                            <?php else: ?>
                                <div class="pepech-notification-item mb-3 p-3 rounded <?php echo $notification->is_read ? 'read bg-light' : 'unread bg-light-subtle'; ?>" 
                                     data-id="<?php echo $notification->id; ?>">
                                    <div class="d-flex align-items-start">
                                        <div class="pepech-notification-icon me-3">
                                            <?php
                                            $emoji = !empty($notification->notification_emoji) ? $notification->notification_emoji : '📢';
                                            ?>
                                            <span style="font-size: 24px;"><?php echo $emoji; ?></span>
                                        </div>
                                        <div class="pepech-notification-body flex-grow-1">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <h6 class="pepech-notification-title mb-1 fs-sm fw-semibold"><?php echo esc_html($notification->title); ?></h6>
                                            </div>
                                            <?php if (!empty($notification->thumbnail)): ?>
                                                <div class="pepech-notification-thumbnail mb-2">
                                                    <img src="<?php echo esc_url($notification->thumbnail); ?>" alt="<?php echo esc_attr($notification->title); ?>" class="img-thumbnail" style="max-width: 60px; max-height: 45px;">
                                                </div>
                                            <?php endif; ?>
                                            <small class="pepech-notification-time text-muted d-block">
                                                <?php echo human_time_diff(strtotime($notification->created_at), current_time('timestamp')) . ' önce'; ?>
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center text-muted py-5">
                        <i class="ci-bell fs-1 d-block mb-3"></i>
                        <p class="mb-0"><?php esc_html_e('Henüz bildirim bulunmuyor.', 'pepech'); ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    public function get_notification_details() {
        check_ajax_referer('pepech_notification_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $notification_id = intval($_POST['notification_id']);
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'pepech_notifications';
        
        $notification = $wpdb->get_row($wpdb->prepare(
            "SELECT n.*, u.display_name, u.user_email 
             FROM $table_name n 
             LEFT JOIN {$wpdb->users} u ON n.user_id = u.ID 
             WHERE n.id = %d",
            $notification_id
        ));
        
        if ($notification) {
            wp_send_json_success($notification);
        } else {
            wp_send_json_error('Bildirim bulunamadı');
        }
    }
    
    public function bulk_notification_action() {
        check_ajax_referer('pepech_notification_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $action = sanitize_text_field($_POST['bulk_action']);
        $notification_ids = array_map('intval', $_POST['notification_ids']);
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'pepech_notifications';
        
        $placeholders = implode(',', array_fill(0, count($notification_ids), '%d'));
        
        switch ($action) {
            case 'mark_read':
                $result = $wpdb->query($wpdb->prepare(
                    "UPDATE $table_name SET is_read = 1 WHERE id IN ($placeholders)",
                    $notification_ids
                ));
                break;
                
            case 'mark_unread':
                $result = $wpdb->query($wpdb->prepare(
                    "UPDATE $table_name SET is_read = 0 WHERE id IN ($placeholders)",
                    $notification_ids
                ));
                break;
                
            case 'delete':
                $result = $wpdb->query($wpdb->prepare(
                    "DELETE FROM $table_name WHERE id IN ($placeholders)",
                    $notification_ids
                ));
                break;
                
            default:
                wp_send_json_error('Geçersiz işlem');
        }
        
        if ($result !== false) {
            wp_send_json_success(array('affected_rows' => $result));
        } else {
            wp_send_json_error('İşlem başarısız');
        }
    }
    
    public function send_bulk_notification_ajax() {
        check_ajax_referer('pepech_notification_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $title = sanitize_text_field($_POST['title']);
        $message = wp_kses_post($_POST['message']);
        $type = sanitize_text_field($_POST['type']);
        $notification_emoji = isset($_POST['notification_emoji']) ? sanitize_text_field($_POST['notification_emoji']) : '✨';
        $send_email = isset($_POST['send_email']) ? 1 : 0;
        $user_ids = array_map('intval', $_POST['user_ids']);
        $link_url = isset($_POST['link_url']) ? esc_url_raw($_POST['link_url']) : '';
        $link_text = isset($_POST['link_text']) ? sanitize_text_field($_POST['link_text']) : '';
        $thumbnail_url = isset($_POST['thumbnail_url']) ? esc_url_raw($_POST['thumbnail_url']) : '';
        
        // Debug log
        error_log('Pepech AJAX Debug - POST data: ' . print_r($_POST, true));
        error_log('Pepech AJAX Debug - notification_emoji: ' . $notification_emoji);
        error_log('Pepech AJAX Debug - user_ids: ' . print_r($user_ids, true));
        
        // Veritabanı kolonlarını kontrol et ve eksikse ekle
        $this->update_tables();
        $batch_size = 10; // Her batch'te 10 kullanıcı
        $offset = intval($_POST['offset']);
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'pepech_notifications';
        
        $success_count = 0;
        $error_count = 0;
        
        // Batch'teki kullanıcıları al
        $batch_users = array_slice($user_ids, $offset, $batch_size);
        
        foreach ($batch_users as $user_id) {
            // Veritabanı kolonlarını kontrol et
            $columns = $wpdb->get_col("DESCRIBE $table_name");
            $data = array(
                'user_id' => $user_id,
                'title' => $title,
                'message' => $message,
                'type' => $type,
                'notification_emoji' => $notification_emoji,
                'send_email' => $send_email,
                'created_at' => current_time('mysql')
            );
            $format = array('%d', '%s', '%s', '%s', '%s', '%d', '%s');
            
            // Link kolonları varsa ekle
            if (in_array('link_url', $columns)) {
                $data['link_url'] = $link_url;
                $format[] = '%s';
            }
            if (in_array('link_text', $columns)) {
                $data['link_text'] = $link_text;
                $format[] = '%s';
            }
            if (in_array('thumbnail', $columns)) {
                $data['thumbnail'] = $thumbnail_url;
                $format[] = '%s';
            }
            
            $result = $wpdb->insert($table_name, $data, $format);
            
            if ($result) {
                $success_count++;
                
                if ($send_email) {
                    $user = get_user_by('id', $user_id);
                    if ($user) {
                        // Emoji'yi kullan
                        $emoji = $notification_emoji;
                        $subject = $emoji . ' ' . get_bloginfo('name') . ' - ' . $title;
                        $headers = array('Content-Type: text/html; charset=UTF-8');
                        
                        $email_content = '
                        <!DOCTYPE html>
                        <html>
                        <head>
                            <meta charset="UTF-8">
                            <title>' . $emoji . ' ' . esc_html($title) . '</title>
                        </head>
                        <body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
                            <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
                                <h2 style="color: #2c3e50; display: flex; align-items: center; gap: 10px;">
                                    <span style="font-size: 24px;">' . $emoji . '</span>
                                    ' . esc_html($title) . '
                                </h2>';
                        
                        // Thumbnail varsa göster
                        error_log('Pepech Email Debug - thumbnail_url: ' . $thumbnail_url);
                        if (!empty($thumbnail_url)) {
                            $email_content .= '<div style="text-align: center; margin: 20px 0;">
                                <img src="' . esc_url($thumbnail_url) . '" alt="' . esc_attr($title) . '" style="max-width: 100%; height: auto; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                            </div>';
                            error_log('Pepech Email Debug - Thumbnail added to email');
                        } else {
                            error_log('Pepech Email Debug - No thumbnail URL provided');
                        }
                        
                        $email_content .= '<div style="background: #f8f9fa; padding: 20px; border-radius: 5px; margin: 20px 0;">
                                    ' . wp_kses_post($message) . '
                                </div>';
                        
                        if (!empty($link_url)) {
                            $email_content .= '<div style="text-align: center; margin: 30px 0;">
                                <a href="' . esc_url($link_url) . '" style="background: #007cba; color: white; padding: 12px 24px; text-decoration: none; border-radius: 4px; display: inline-block;">
                                    ' . esc_html($link_text ?: 'Detayları Görüntüle') . '
                                </a>
                            </div>';
                        }
                        
                        $email_content .= '<p style="margin-top: 30px; font-size: 14px; color: #666;">
                                Bu bildirimi <a href="' . home_url() . '">' . get_bloginfo('name') . '</a> sitesinden aldınız.
                            </p>
                        </div>
                    </body>
                    </html>';
                        
                        wp_mail($user->user_email, $subject, $email_content, $headers);
                    }
                }
            } else {
                $error_count++;
                error_log('Pepech AJAX Error - Database insert failed: ' . $wpdb->last_error);
                error_log('Pepech AJAX Error - Data: ' . print_r($data, true));
                error_log('Pepech AJAX Error - Format: ' . print_r($format, true));
            }
        }
        
        $next_offset = $offset + $batch_size;
        $is_complete = $next_offset >= count($user_ids);
        
        // Toplam işlenen kullanıcı sayısını hesapla
        $total_processed = min($next_offset, count($user_ids));
        
        wp_send_json_success(array(
            'success_count' => $success_count,
            'error_count' => $error_count,
            'processed' => $total_processed, // Toplam işlenen kullanıcı sayısı
            'total' => count($user_ids),
            'next_offset' => $next_offset,
            'is_complete' => $is_complete
        ));
    }
    
    /**
     * 90 günden eski bildirimleri otomatik sil
     */
    public function cleanup_old_notifications() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'pepech_notifications';
        
        // Ayarlardan gün sayısını al, varsayılan 90 gün
        $cleanup_days = get_option('pepech_notification_cleanup_days', 90);
        
        // Belirtilen gün önceki tarihi hesapla
        $cutoff_date = date('Y-m-d H:i:s', strtotime("-{$cleanup_days} days"));
        
        // Eski bildirimleri say
        $old_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE created_at < %s",
            $cutoff_date
        ));
        
        if ($old_count > 0) {
            // Eski bildirimleri sil
            $deleted = $wpdb->query($wpdb->prepare(
                "DELETE FROM $table_name WHERE created_at < %s",
                $cutoff_date
            ));
            
            // Log'a kaydet
            error_log("Pepech Notification Cleanup: $deleted eski bildirim silindi (90+ gün)");
            
            // Son temizlik tarihini kaydet
            update_option('pepech_last_cleanup', current_time('Y-m-d H:i:s'));
            
            // Admin'e bildirim gönder (opsiyonel)
            if ($deleted > 0) {
                $admin_users = get_users(array('role' => 'administrator'));
                foreach ($admin_users as $admin) {
                    $this->send_notification(
                        $admin->ID,
                        'Bildirim Temizliği',
                        "$deleted adet eski bildirim otomatik olarak silindi. (90+ gün)",
                        'info',
                        false
                    );
                }
            }
        }
    }
    
    /**
     * Manuel temizlik AJAX handler
     */
    public function manual_cleanup_notifications() {
        check_ajax_referer('pepech_notification_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        // Temizlik fonksiyonunu çağır
        $this->cleanup_old_notifications();
        
        wp_send_json_success(array(
            'message' => 'Manuel temizlik tamamlandı!',
            'last_cleanup' => get_option('pepech_last_cleanup', 'Henüz yapılmadı')
        ));
    }
    
    /**
     * Tüm bildirimleri sil
     */
    public function delete_all_notifications() {
        error_log('delete_all_notifications called');
        check_ajax_referer('pepech_notification_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'pepech_notifications';
        
        // Tüm bildirimleri sil
        $deleted_count = $wpdb->query("DELETE FROM $table_name");
        
        error_log('delete_all_notifications: deleted_count = ' . $deleted_count);
        
        // Son temizlik zamanını güncelle
        update_option('pepech_last_cleanup', current_time('Y-m-d H:i:s'));
        
        wp_send_json_success(array(
            'message' => 'Tüm bildirimler silindi!',
            'deleted_count' => $deleted_count,
            'last_cleanup' => get_option('pepech_last_cleanup')
        ));
    }
    
    /**
     * WooCommerce My Account menüsüne bildirimler linki ekle
     */
    public function add_notifications_menu_item($items) {
        // 'orders' linkinden sonra ekle
        $new_items = array();
        foreach ($items as $key => $value) {
            $new_items[$key] = $value;
            if ($key === 'orders') {
                $new_items['bildirimlerim'] = 'Bildirimlerim';
            }
        }
        return $new_items;
    }
    
    /**
     * Bildirimler endpoint'ini ekle
     */
    public function add_notifications_endpoint() {
        // WooCommerce My Account endpoint'i ekle
        add_rewrite_endpoint('bildirimlerim', EP_ROOT | EP_PAGES);
        
        // WooCommerce'e endpoint'i kaydet
        if (class_exists('WooCommerce')) {
            WC()->query->query_vars['bildirimlerim'] = 'bildirimlerim';
        }
        
        // Debug log
        error_log('Pepech Notification: bildirimlerim endpoint added');
        
        // Rewrite rules'ları yenile (sadece admin'de)
        if (is_admin()) {
            flush_rewrite_rules();
        }
    }
    
    /**
     * Query vars'a bildirimlerim ekle
     */
    public function add_query_vars($vars) {
        $vars[] = 'bildirimlerim';
        return $vars;
    }
    
    /**
     * WooCommerce query vars'a bildirimlerim ekle
     */
    public function add_woocommerce_query_vars($vars) {
        $vars['bildirimlerim'] = 'bildirimlerim';
        return $vars;
    }
    
    /**
     * WooCommerce rewrite rules flush
     */
    public function flush_rewrite_rules() {
        flush_rewrite_rules();
    }
    
    /**
     * Bildirimler endpoint içeriği
     */
    public function notifications_endpoint_content() {
        // Debug log
        error_log('Pepech Notification: notifications_endpoint_content called');
        
        $user_id = get_current_user_id();
        if (!$user_id) {
            echo '<p>Giriş yapmanız gerekiyor.</p>';
            return;
        }
        
        // Sayfalama
        $per_page = get_option('pepech_notification_max_per_page', 10);
        
        // URL'den sayfa numarasını çıkar (/page/2/ formatı için)
        $current_page = 1;
        $request_uri = $_SERVER['REQUEST_URI'];
        if (preg_match('/\/page\/(\d+)\/?$/', $request_uri, $matches)) {
            $current_page = max(1, intval($matches[1]));
        } elseif (isset($_GET['paged'])) {
            $current_page = max(1, intval($_GET['paged']));
        }
        
        $offset = ($current_page - 1) * $per_page;
        
        // Debug log
        error_log('Pepech Notification: Pagination - per_page: ' . $per_page . ', current_page: ' . $current_page . ', offset: ' . $offset);
        error_log('Pepech Notification: REQUEST_URI: ' . $request_uri);
        error_log('Pepech Notification: GET params: ' . print_r($_GET, true));
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'pepech_notifications';
        
        // Toplam bildirim sayısı
        $total_notifications = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE user_id = %d",
            $user_id
        ));
        
        // Bildirimleri getir
        $notifications = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name WHERE user_id = %d ORDER BY created_at DESC LIMIT %d OFFSET %d",
            $user_id,
            $per_page,
            $offset
        ));
        
        // Sayfalama hesaplama
        $total_pages = ceil($total_notifications / $per_page);
        
        // Template'i include et
        include PEPECH_NOTIFICATION_PLUGIN_PATH . 'templates/myaccount-notifications.php';
    }
    
    /**
     * Yeni sipariş bildirimi
     */
    public function wc_new_order_notification($order_id) {
        if (!get_option('pepech_wc_new_order', 1)) return;
        
        $order = wc_get_order($order_id);
        if (!$order) return;
        
        $user_id = $order->get_user_id();
        if (!$user_id) return;
        
        $title = 'Siparişini aldık!';
        $message = sprintf('En kısa zamanda kargoya vereceğiz. (#%s)', $order->get_order_number());
        $link_url = $order->get_view_order_url();
        
        $this->send_notification($user_id, $title, $message, 'info', false, $link_url, 'Siparişi Görüntüle', '', '🛒');
    }
    
    /**
     * Sipariş işleniyor bildirimi
     */
    public function wc_order_processing_notification($order_id) {
        if (!get_option('pepech_wc_order_processing', 1)) return;
        
        $order = wc_get_order($order_id);
        if (!$order) return;
        
        $user_id = $order->get_user_id();
        if (!$user_id) return;
        
        $title = 'Siparişin işlenmeye alındı!';
        $message = sprintf('En kısa zamanda seni bilgilendireceğiz. (#%s)', $order->get_order_number());
        $link_url = $order->get_view_order_url();
        
        $this->send_notification($user_id, $title, $message, 'info', false, $link_url, 'Siparişi Görüntüle', '', '⚙️');
    }
    
    /**
     * Sipariş tamamlandı bildirimi
     */
    public function wc_order_completed_notification($order_id) {
        if (!get_option('pepech_wc_order_completed', 1)) return;
        
        $order = wc_get_order($order_id);
        if (!$order) return;
        
        $user_id = $order->get_user_id();
        if (!$user_id) return;
        
        $title = 'Siparişin tamamlandı!';
        $message = sprintf('Bizi tercih ettiğin için teşekkür ederiz. (#%s)', $order->get_order_number());
        $link_url = $order->get_view_order_url();
        
        $this->send_notification($user_id, $title, $message, 'success', false, $link_url, 'Siparişi Görüntüle', '', '🎉');
    }
    
    /**
     * Sipariş iptal edildi bildirimi
     */
    public function wc_order_cancelled_notification($order_id) {
        if (!get_option('pepech_wc_order_cancelled', 1)) return;
        
        $order = wc_get_order($order_id);
        if (!$order) return;
        
        $user_id = $order->get_user_id();
        if (!$user_id) return;
        
        $title = 'Siparişin iptal edildi!';
        $message = sprintf('Bir yanlışlık olduğunu düşünüyorsan bize ulaşabilirsin. (#%s)', $order->get_order_number());
        $link_url = $order->get_view_order_url();
        
        $this->send_notification($user_id, $title, $message, 'warning', false, $link_url, 'Siparişi Görüntüle', '', '😔');
    }
    
    /**
     * Sipariş iade edildi bildirimi
     */
    public function wc_order_refunded_notification($order_id) {
        if (!get_option('pepech_wc_order_refunded', 1)) return;
        
        $order = wc_get_order($order_id);
        if (!$order) return;
        
        $user_id = $order->get_user_id();
        if (!$user_id) return;
        
        $title = 'Siparişin iade edildi!';
        $message = sprintf('Siparişiniz için iade işlemi yapıldı. (#%s)', $order->get_order_number());
        $link_url = $order->get_view_order_url();
        
        $this->send_notification($user_id, $title, $message, 'info', false, $link_url, 'Siparişi Görüntüle', '', '💰');
    }
    
    /**
     * Ödeme tamamlandı bildirimi
     */
    public function wc_payment_complete_notification($order_id) {
        if (!get_option('pepech_wc_payment_complete', 1)) return;
        
        $order = wc_get_order($order_id);
        if (!$order) return;
        
        $user_id = $order->get_user_id();
        if (!$user_id) return;
        
        $title = 'Ödeme Tamamlandı!';
        $message = sprintf('Ödemeni başarıyla aldık! (#%s)', $order->get_order_number());
        $link_url = $order->get_view_order_url();
        
        $this->send_notification($user_id, $title, $message, 'success', false, $link_url, 'Siparişi Görüntüle', '', '💳');
    }
    
    /**
     * Sipariş kargoya verildi bildirimi
     */
    public function wc_order_shipped_notification($order_id) {
        if (!get_option('pepech_wc_order_shipped', 1)) return;
        
        $order = wc_get_order($order_id);
        if (!$order) return;
        
        $user_id = $order->get_user_id();
        if (!$user_id) return;
        
        $title = 'Siparişini kargoya verdik!';
        $message = sprintf('Kargo takibi için tıklayın! (#%s)', $order->get_order_number());
        $link_url = $order->get_view_order_url();
        
        $this->send_notification($user_id, $title, $message, 'info', false, $link_url, 'Siparişi Görüntüle', '', '🚚');
    }
}

// Eklentiyi başlat
new PepechNotificationSystem();

// Diğer eklentiler için global fonksiyonlar
function pepech_send_notification($user_id, $title, $message, $type = 'info', $send_email = true, $link_url = '', $link_text = '', $thumbnail = '', $notification_emoji = '') {
    $notification_system = new PepechNotificationSystem();
    return $notification_system->send_notification($user_id, $title, $message, $type, $send_email, $link_url, $link_text, $thumbnail, $notification_emoji);
}

function pepech_get_user_notifications($user_id, $limit = 10, $unread_only = false) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'pepech_notifications';
    
    $where_clause = "WHERE user_id = %d";
    $params = array($user_id);
    
    if ($unread_only) {
        $where_clause .= " AND is_read = 0";
    }
    
    return $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $table_name $where_clause ORDER BY created_at DESC LIMIT %d",
        array_merge($params, array($limit))
    ));
}

function pepech_get_unread_count($user_id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'pepech_notifications';
    
    return $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table_name WHERE user_id = %d AND is_read = 0",
        $user_id
    ));
}

function pepech_send_bulk_notification($user_ids, $title, $message, $type = 'info', $send_email = true) {
    $notification_system = new PepechNotificationSystem();
    return $notification_system->send_bulk_notification($user_ids, $title, $message, $type, $send_email);
}

function pepech_send_notification_to_all($title, $message, $type = 'info', $send_email = true) {
    $users = get_users(array('fields' => array('ID')));
    $user_ids = array_map(function($user) { return $user->ID; }, $users);
    
    return pepech_send_bulk_notification($user_ids, $title, $message, $type, $send_email);
}
