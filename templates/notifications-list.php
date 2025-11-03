<?php
if (!defined('ABSPATH')) {
    exit;
}

$user_id = get_current_user_id();
if (!$user_id) {
    return;
}

// Sayfalama
$per_page = get_option('pepech_notification_max_per_page', 10);
$current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$offset = ($current_page - 1) * $per_page;

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
?>

<div class="pepech-notifications-page">
    <div class="pepech-page-header">
        <h1>Bildirimlerim</h1>
        <div class="pepech-page-actions">
            <button id="pepech-mark-all-read-page" class="button button-primary">
                Tümünü Oku
            </button>
        </div>
    </div>
    
    <!-- Otomatik Silme Uyarısı -->
    <div class="pepech-cleanup-notice">
        <div class="notice notice-info">
            <p>
                <i class="ci-info-circle"></i>
                <strong>Otomatik Temizlik:</strong> 90 günden eski bildirimler otomatik olarak silinir. 
                Bu sayede veritabanı performansı korunur ve gereksiz veri birikimi önlenir.
            </p>
        </div>
    </div>
    
    <div class="pepech-notifications-stats">
        <div class="stat-item">
            <span class="stat-number"><?php echo $total_notifications; ?></span>
            <span class="stat-label">Toplam Bildirim</span>
        </div>
        <div class="stat-item">
            <span class="stat-number"><?php echo $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE user_id = %d AND is_read = 0", $user_id)); ?></span>
            <span class="stat-label">Okunmamış</span>
        </div>
    </div>
    
    <?php if ($notifications): ?>
        <div class="pepech-notifications-list">
            <?php foreach ($notifications as $notification): ?>
                <div class="pepech-notification-item-page <?php echo $notification->is_read ? 'read' : 'unread'; ?>" 
                     data-id="<?php echo $notification->id; ?>">
                    <div class="pepech-notification-icon">
                        <?php
                        $emoji = !empty($notification->notification_emoji) ? $notification->notification_emoji : '📢';
                        ?>
                        <span style="font-size: 24px;"><?php echo $emoji; ?></span>
                    </div>
                    <div class="pepech-notification-content">
                        <div class="pepech-notification-header-item">
                            <h3 class="pepech-notification-title"><?php echo esc_html($notification->title); ?></h3>
                            <span class="pepech-notification-type pepech-notification-type-<?php echo esc_attr($notification->type); ?>">
                                <?php
                                $type_names = array(
                                    'info' => 'Duyuru',
                                    'success' => 'Başarı',
                                    'warning' => 'Uyarı',
                                    'error' => 'Hata'
                                );
                                echo esc_html($type_names[$notification->type] ?? ucfirst($notification->type));
                                ?>
                            </span>
                        </div>
                        <div class="pepech-notification-message">
                            <?php echo wp_kses_post($notification->message); ?>
                            <?php if (!empty($notification->link_url)): ?>
                                <div class="pepech-notification-link-container">
                                    <a href="<?php echo esc_url($notification->link_url); ?>" class="pepech-notification-link-page" target="_blank">
                                        <?php echo esc_html($notification->link_text ?: 'Detayları Görüntüle'); ?>
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="pepech-notification-meta">
                            <span class="pepech-notification-time">
                                <?php echo date('d.m.Y H:i', strtotime($notification->created_at)); ?>
                            </span>
                            <?php if (!$notification->is_read): ?>
                                <button class="pepech-mark-read-page" data-id="<?php echo $notification->id; ?>">
                                    Okundu Olarak İşaretle
                                </button>
                            <?php else: ?>
                                <span class="pepech-read-status">Okundu</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <?php if ($total_pages > 1): ?>
            <div class="pepech-pagination">
                <?php
                $page_links = paginate_links(array(
                    'base' => add_query_arg('paged', '%#%'),
                    'format' => '',
                    'prev_text' => '&laquo; Önceki',
                    'next_text' => 'Sonraki &raquo;',
                    'total' => $total_pages,
                    'current' => $current_page,
                    'type' => 'array'
                ));
                
                if ($page_links) {
                    echo '<ul class="pepech-page-numbers">';
                    foreach ($page_links as $link) {
                        echo '<li>' . $link . '</li>';
                    }
                    echo '</ul>';
                }
                ?>
            </div>
        <?php endif; ?>
        
    <?php else: ?>
        <div class="pepech-no-notifications-page">
            <i class="dashicons dashicons-bell"></i>
            <h3>Henüz bildirim bulunmuyor</h3>
            <p>Size gönderilen bildirimler burada görüntülenecek.</p>
        </div>
    <?php endif; ?>
</div>
