<?php
/**
 * Pepech Notification System - Activation Script
 * 
 * Bu dosya eklenti aktifleştirildiğinde çalışır
 */

// Güvenlik kontrolü
if (!defined('ABSPATH')) {
    exit;
}

// Eklenti aktifleştirildiğinde çalışacak fonksiyonlar
function pepech_notification_system_activate() {
    // Veritabanı tablosunu oluştur
    pepech_create_notification_table();
    
    // Varsayılan ayarları oluştur
    pepech_create_default_options();
    
    // Bildirimlerim sayfasını oluştur
    pepech_create_notifications_page();
    
    // Rewrite rules'ları yenile
    flush_rewrite_rules();
}

function pepech_create_notification_table() {
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
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY user_id (user_id),
        KEY is_read (is_read),
        KEY created_at (created_at)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

function pepech_create_default_options() {
    add_option('pepech_notification_email_enabled', 1);
    add_option('pepech_notification_max_per_page', 10);
    add_option('pepech_notification_version', '1.0.0');
}

function pepech_create_notifications_page() {
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

// Eklenti deaktive edildiğinde çalışacak fonksiyonlar
function pepech_notification_system_deactivate() {
    // Rewrite rules'ları yenile
    flush_rewrite_rules();
}

// Eklenti silindiğinde çalışacak fonksiyonlar
function pepech_notification_system_uninstall() {
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

// Hook'ları kaydet
register_activation_hook(__FILE__, 'pepech_notification_system_activate');
register_deactivation_hook(__FILE__, 'pepech_notification_system_deactivate');
register_uninstall_hook(__FILE__, 'pepech_notification_system_uninstall');
