<?php
if (!defined('ABSPATH')) {
    exit;
}

$user_id = get_current_user_id();
if (!$user_id) {
    return;
}
?>

<div class="pepech-myaccount-notifications">
    <!-- Sayfa Başlığı -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="card-title mb-1 d-flex align-items-center h5">Bildirimlerim</h2>
        <button id="pepech-mark-all-read-myaccount" class="btn btn-outline-secondary btn-sm">
            <i class="ci-check me-2"></i>Tümünü Oku
        </button>
    </div>

    <div class="row align-items-center small my-3">
        <div class="col-auto">
            <!-- Otomatik Silme Uyarısı -->
            <div class="alert d-flex alert-warning m-0 fs-xs p-2">
                <i class="ci-alert-triangle fs-md pe-1 mt-1 me-2"></i>
                 <div><?php echo get_option('pepech_notification_cleanup_days', 90); ?> günden eski bildirimler otomatik olarak silinir.</div>
            </div>
        </div>
        <div class="col-auto col-md-auto text-end ms-auto mt-3 mt-md-0">
            <span class="text-muted mb-0">Toplam Bildirim: </span>
            <span class="fw-semibold text-primary"><?php echo $total_notifications; ?></span>
        </div>
        <div class="col-auto col-md-auto text-end">
            <span class="text-muted mb-0">Okunmamış: </span>
            <span class="fw-semibold text-warning"><?php echo $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE user_id = %d AND is_read = 0", $user_id)); ?></span>
        </div>
    </div>
    
    <?php if ($notifications): ?>
        <?php
        // Bildirimleri günlerine göre grupla
        $grouped_notifications = array();
        $today = date('Y-m-d');
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        
        foreach ($notifications as $notification) {
            $notification_date = date('Y-m-d', strtotime($notification->created_at));
            
            if ($notification_date == $today) {
                $grouped_notifications['Bugün'][] = $notification;
            } elseif ($notification_date == $yesterday) {
                $grouped_notifications['Dün'][] = $notification;
            } else {
                $formatted_date = date_i18n('j F Y', strtotime($notification->created_at));
                $grouped_notifications[$formatted_date][] = $notification;
            }
        }
        ?>
        
        <?php foreach ($grouped_notifications as $group_title => $group_notifications): ?>
            <!-- Grup Başlığı -->
            <h5 class="mb-2">
                <?php echo esc_html($group_title); ?>
            </h5>
            
            <!-- Grup Bildirimleri -->
            <div class="row mb-4">
                <?php foreach ($group_notifications as $notification): ?>
                    <div class="col-12 mb-2">
                        <?php if (!empty($notification->link_url)): ?>
                        <a class="animate-slide-end text-decoration-none text-inherit" href="<?php echo esc_url($notification->link_url); ?>">
                        <?php endif; ?>
                            <div class="rounded pepech-notification-item-link<?php echo $notification->is_read ? '' : ' bg-info" style="--cz-bg-opacity: .05;'; ?>" data-id="<?php echo $notification->id; ?>">
                                <div class="card-body p-3">
                                    <div class="row align-items-center">
                                        <div class="col">
                                            <p class="card-text text-muted mb-1 small">
                                                <?php echo date_i18n(get_option('time_format'), strtotime($notification->created_at)); ?><span class="opacity-50 px-1"> • </span><?php echo wp_kses_post(wp_trim_words($notification->message, 10)); ?>
                                            </p>
                                            <?php
                                            $emoji = '<span class="pe-2">' . (!empty($notification->notification_emoji) ? $notification->notification_emoji : '🤖') . '</span>';
                                            ?>
                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                <h6 class="card-title mb-0 fs-6 text-body"><?php echo $emoji; ?><?php echo esc_html($notification->title); ?></h6>
                                            </div>
                                        </div>
                                        <?php if (!empty($notification->link_url)): ?>
                                        <div class="col-auto d-none d-md-block">
                                            <span class="btn btn-icon border-0<?php echo $notification->is_read ? ' btn-secondary' : ' btn-info'; ?>">
                                                <i class="ci-chevron-right fs-lg animate-target"></i>
                                            </span>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php if (!empty($notification->link_url)): ?>
                        </a>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
        
        <!-- Sayfalama -->
        <?php if ($total_pages > 1): ?>
            <nav aria-label="Bildirim sayfalama" class="mt-4">
                <ul class="pagination justify-content-center">
                    <?php
                    $base_url = wc_get_account_endpoint_url('bildirimlerim');
                    
                    // URL'den sayfa numarasını çıkar (/page/2/ formatı için)
                    $current_page = 1;
                    $request_uri = $_SERVER['REQUEST_URI'];
                    if (preg_match('/\/page\/(\d+)\/?$/', $request_uri, $matches)) {
                        $current_page = max(1, intval($matches[1]));
                    } elseif (isset($_GET['paged'])) {
                        $current_page = max(1, intval($_GET['paged']));
                    }
                    
                    // Önceki sayfa
                    if ($current_page > 1): ?>
                        <li class="page-item">
                            <a href="<?php echo esc_url($base_url . ($current_page - 1 > 1 ? '/page/' . ($current_page - 1) . '/' : '/')); ?>" class="page-link">
                                <i class="ci-arrow-left me-1"></i>Önceki
                            </a>
                        </li>
                    <?php endif; ?>
                    
                    <!-- Sayfa numaraları -->
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?php echo $i == $current_page ? 'active' : ''; ?>">
                            <?php if ($i == $current_page): ?>
                                <span class="page-link"><?php echo $i; ?></span>
                            <?php else: ?>
                                <a href="<?php echo esc_url($base_url . ($i > 1 ? '/page/' . $i . '/' : '/')); ?>" class="page-link">
                                    <?php echo $i; ?>
                                </a>
                            <?php endif; ?>
                        </li>
                    <?php endfor; ?>
                    
                    <!-- Sonraki sayfa -->
                    <?php if ($current_page < $total_pages): ?>
                        <li class="page-item">
                            <a href="<?php echo esc_url($base_url . '/page/' . ($current_page + 1) . '/'); ?>" class="page-link">
                                Sonraki<i class="ci-arrow-right ms-1"></i>
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        <?php endif; ?>
        
    <?php else: ?>
        <div class="text-center py-5">
            <div class="card">
                <div class="card-body">
                    <i class="ci-bell fs-1 text-muted d-block mb-3"></i>
                    <h3 class="card-title">Henüz bildirim yok</h3>
                    <p class="card-text text-muted">Yeni bildirimler geldiğinde burada görünecek.</p>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
jQuery(document).ready(function($) {
    // My Account sayfasında bildirim okundu işaretleme
    $('.pepech-notification-item, .pepech-notification-item-link').on('click', function() {
        var notificationId = $(this).data('id');
        var $item = $(this);
        
        if (!$item.hasClass('read')) {
            $.ajax({
                url: pepech_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'mark_notification_read',
                    notification_id: notificationId,
                    nonce: pepech_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $item.removeClass('border-primary').addClass('read');
                        $item.find('.badge').remove();
                        // Okunmamış sayısını güncelle
                        updateUnreadCount();
                    }
                }
            });
        }
    });
    
    // Tümünü oku butonu
    $('#pepech-mark-all-read-myaccount').on('click', function() {
        if (confirm('Tüm bildirimleri okundu olarak işaretlemek istediğinizden emin misiniz?')) {
            $.ajax({
                url: pepech_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'mark_all_notifications_read',
                    nonce: pepech_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $('.pepech-notification-item, .pepech-notification-item-link').removeClass('border-primary').addClass('read');
                        $('.badge').remove();
                        updateUnreadCount();
                        alert('Tüm bildirimler okundu olarak işaretlendi!');
                    }
                }
            });
        }
    });
    
    function updateUnreadCount() {
        var unreadCount = $('.pepech-notification-item.border-warning, .pepech-notification-item-link.border-primary').length;
        $('.stat-item:last-child .stat-number').text(unreadCount);
    }
});
</script>
