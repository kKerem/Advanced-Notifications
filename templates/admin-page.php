<?php
if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$table_name = $wpdb->prefix . 'pepech_notifications';

// Sayfalama
$per_page = get_option('pepech_notification_max_per_page', 10);
$current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$offset = ($current_page - 1) * $per_page;

// Toplam bildirim sayısı
$total_notifications = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");

// Bildirimleri getir
$notifications = $wpdb->get_results($wpdb->prepare(
    "SELECT n.*, u.display_name, u.user_email 
     FROM $table_name n 
     LEFT JOIN {$wpdb->users} u ON n.user_id = u.ID 
     ORDER BY n.created_at DESC 
     LIMIT %d OFFSET %d",
    $per_page,
    $offset
));

// Sayfalama hesaplama
$total_pages = ceil($total_notifications / $per_page);
?>
<style>
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin: 20px 0;
}

.stat-card {
    border-radius: 8px;
    padding: 20px;
    display: flex;
    align-items: center;
    transition: transform 0.2s, box-shadow 0.2s;
    text-align: left;
    background: #fff;
    border: 1px solid #c3c4c7;
    border-radius: 8px;
    padding: 20px;
    text-align: center;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.stat-card i {
    font-family: dashicons;
    display: flex;
    font-weight: 400;
    font-style: normal;
    text-rendering: auto;
    -webkit-font-smoothing: antialiased;
    -moz-osx-font-smoothing: grayscale;
    font-size: 36px;
    vertical-align: center;
    transition: color .1s ease-in;
}

.stat-icon{
    margin-right: 1rem;
}

.stat-icon-purple {
    color: #9c27b0;
}

.stat-icon-green {
    color: #4caf50;
}

.stat-icon-red {
    color: #f44336;
}

.stat-icon-blue {
    color: #2196f3;
}

.stat-icon-orange {
    color: #ff9800;
}

.stat-icon-teal {
    color: #009688;
}

.stat-content {
    flex: 1;
    text-align: left;
}

.stat-value {
    font-size: 28px;
    font-weight: bold;
    margin-bottom: 5px;
}

.stat-label {
    font-size: 14px;
    color: #666;
}
</style>

<div class="wrap">
    <h1>Bildirim Sistemi</h1>
    
    <div class="pepech-admin-header">
        <a href="<?php echo admin_url('admin.php?page=pepech-send-notification'); ?>" class="button button-primary">
            Yeni Bildirim Gönder
        </a>
        <a href="<?php echo admin_url('admin.php?page=pepech-notification-settings'); ?>" class="button">
            Ayarlar
        </a>
    </div>
    
    <!-- Otomatik Temizlik Bilgisi -->
    <div class="pepech-cleanup-info">
        <div class="notice notice-info">
            <p>
                <i class="ci-info-circle"></i>
                <strong>Otomatik Temizlik Aktif:</strong> <?php echo get_option('pepech_notification_cleanup_days', 90); ?> günden eski bildirimler günlük olarak otomatik silinir. 
                Son temizlik: <strong><?php echo get_option('pepech_last_cleanup', 'Henüz yapılmadı'); ?></strong>
                <button type="button" id="pepech-manual-cleanup" class="button button-small" style="margin-left: 15px;">
                    Şimdi Temizle
                </button>
            </p>
        </div>
    </div>
    
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon stat-icon-blue">
                <svg xmlns="http://www.w3.org/2000/svg" width="56" height="56" viewBox="0 0 24 24"><g fill="none" fill-rule="evenodd"><path d="m12.594 23.258l-.012.002l-.071.035l-.02.004l-.014-.004l-.071-.036q-.016-.004-.024.006l-.004.01l-.017.428l.005.02l.01.013l.104.074l.015.004l.012-.004l.104-.074l.012-.016l.004-.017l-.017-.427q-.004-.016-.016-.018m.264-.113l-.014.002l-.184.093l-.01.01l-.003.011l.018.43l.005.012l.008.008l.201.092q.019.005.029-.008l.004-.014l-.034-.614q-.005-.019-.02-.022m-.715.002a.02.02 0 0 0-.027.006l-.006.014l-.034.614q.001.018.017.024l.015-.002l.201-.093l.01-.008l.003-.011l.018-.43l-.003-.012l-.01-.01z"/><path fill="currentColor" d="M5 9a7 7 0 0 1 14 0v3.764l1.822 3.644A1.1 1.1 0 0 1 19.838 18h-3.964a4.002 4.002 0 0 1-7.748 0H4.162a1.1 1.1 0 0 1-.984-1.592L5 12.764zm5.268 9a2 2 0 0 0 3.464 0zM12 4a5 5 0 0 0-5 5v3.764a2 2 0 0 1-.211.894L5.619 16h12.763l-1.17-2.342a2 2 0 0 1-.212-.894V9a5 5 0 0 0-5-5"/></g></svg>
            </div>
            <div class="stat-content">
                <div class="stat-value stat-total-affiliates"><?php echo $total_notifications; ?></div>
                <div class="stat-label">Toplam Bildirim</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon stat-icon-orange">
                <svg xmlns="http://www.w3.org/2000/svg" width="56" height="56" viewBox="0 0 24 24"><g fill="none" fill-rule="evenodd"><path d="m12.594 23.258l-.012.002l-.071.035l-.02.004l-.014-.004l-.071-.036q-.016-.004-.024.006l-.004.01l-.017.428l.005.02l.01.013l.104.074l.015.004l.012-.004l.104-.074l.012-.016l.004-.017l-.017-.427q-.004-.016-.016-.018m.264-.113l-.014.002l-.184.093l-.01.01l-.003.011l.018.43l.005.012l.008.008l.201.092q.019.005.029-.008l.004-.014l-.034-.614q-.005-.019-.02-.022m-.715.002a.02.02 0 0 0-.027.006l-.006.014l-.034.614q.001.018.017.024l.015-.002l.201-.093l.01-.008l.003-.011l.018-.43l-.003-.012l-.01-.01z"/><path fill="currentColor" d="M5 9a7 7 0 0 1 7.582-6.976a1 1 0 1 1-.164 1.993A5 5 0 0 0 7 9v3.528a3 3 0 0 1-.317 1.342L5.618 16h12.764l-1.065-2.13A3 3 0 0 1 17 12.528V11a1 1 0 0 1 2 0v1.528a1 1 0 0 0 .105.447l1.717 3.433A1.1 1.1 0 0 1 19.838 18h-3.964a4.002 4.002 0 0 1-7.748 0H4.162a1.1 1.1 0 0 1-.984-1.592l1.716-3.433A1 1 0 0 0 5 12.528zm5.268 9a2 2 0 0 0 3.464 0zM17.5 4a1.5 1.5 0 1 0 0 3a1.5 1.5 0 0 0 0-3M14 5.5a3.5 3.5 0 1 1 7 0a3.5 3.5 0 0 1-7 0"/></g></svg>
            </div>
            <div class="stat-content">
                <div class="stat-value stat-total-affiliates"><?php echo $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE is_read = 0"); ?></div>
                <div class="stat-label">Okunmamış</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon stat-icon-red">
                <svg xmlns="http://www.w3.org/2000/svg" width="56" height="56" viewBox="0 0 24 24"><g fill="none" fill-rule="evenodd"><path d="M0 0h24v24H0z"/><path fill="currentColor" d="m5.025 8.429l1.977 1.977v2.122a3 3 0 0 1-.317 1.342L5.62 16h6.976l2.916 2.916A4.002 4.002 0 0 1 8.126 18H4.164a1.1 1.1 0 0 1-.984-1.592l1.716-3.433a1 1 0 0 0 .106-.447V9q0-.288.023-.571M12.002 2a7 7 0 0 1 7 7v3.528a1 1 0 0 0 .105.447l1.717 3.433A1.1 1.1 0 0 1 19.84 18h-.426l1.071 1.071a1 1 0 0 1-1.414 1.414L3.515 4.93a1 1 0 1 1 1.414-1.414l1.393 1.393A6.99 6.99 0 0 1 12.002 2m1.73 16h-3.464a2 2 0 0 0 3.464 0m-1.73-14a5 5 0 0 0-4.24 2.348L17.414 16h.97l-1.065-2.13a3 3 0 0 1-.317-1.342V9a5 5 0 0 0-5-5"/></g></svg>
            </div>
            <div class="stat-content">
                <div class="stat-value stat-total-affiliates"><?php echo $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE DATE(created_at) = %s", current_time('Y-m-d'))); ?></div>
                <div class="stat-label">Okunmamış</div>
            </div>
        </div>
    </div>
    
    <h2>Tüm Bildirimler</h2>
    
    <?php if ($notifications): ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Kullanıcı</th>
                    <th>Başlık</th>
                    <th>Mesaj</th>
                    <th>Tür</th>
                    <th>E-posta</th>
                    <th>Okundu</th>
                    <th>Tarih</th>
                    <th>İşlemler</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($notifications as $notification): ?>
                    <tr class="<?php echo $notification->is_read ? 'read' : 'unread'; ?>">
                        <td>
                            <strong><?php echo esc_html($notification->display_name); ?></strong><br>
                            <small><?php echo esc_html($notification->user_email); ?></small>
                        </td>
                        <td><?php echo esc_html($notification->title); ?></td>
                        <td>
                            <div class="notification-message">
                                <?php echo wp_kses_post(wp_trim_words($notification->message, 20)); ?>
                            </div>
                        </td>
                        <td>
                            <span class="notification-type notification-type-<?php echo esc_attr($notification->type); ?>">
                                <?php echo esc_html(ucfirst($notification->type)); ?>
                            </span>
                        </td>
                        <td><?php echo $notification->send_email ? 'Evet' : 'Hayır'; ?></td>
                        <td>
                            <span class="read-status read-status-<?php echo $notification->is_read ? 'read' : 'unread'; ?>">
                                <?php echo $notification->is_read ? 'Okundu' : 'Okunmadı'; ?>
                            </span>
                        </td>
                        <td><?php echo date('d.m.Y H:i', strtotime($notification->created_at)); ?></td>
                        <td>
                            <button class="button button-small view-notification" data-id="<?php echo $notification->id; ?>">
                                Görüntüle
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <?php if ($total_pages > 1): ?>
            <div class="tablenav">
                <div class="tablenav-pages">
                    <?php
                    $page_links = paginate_links(array(
                        'base' => add_query_arg('paged', '%#%'),
                        'format' => '',
                        'prev_text' => '&laquo;',
                        'next_text' => '&raquo;',
                        'total' => $total_pages,
                        'current' => $current_page
                    ));
                    echo $page_links;
                    ?>
                </div>
            </div>
        <?php endif; ?>
        
    <?php else: ?>
        <p>Henüz bildirim gönderilmemiş.</p>
    <?php endif; ?>
</div>

<!-- Bildirim Detay Modal -->
<div id="notification-modal" class="pepech-modal" style="display: none;">
    <div class="pepech-modal-content">
        <span class="pepech-modal-close">&times;</span>
        <div id="notification-details"></div>
    </div>
</div>
