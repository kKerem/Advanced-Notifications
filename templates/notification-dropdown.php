<?php
if (!defined('ABSPATH')) {
    exit;
}

$user_id = get_current_user_id();
if (!$user_id) {
    return;
}

// Okunmamış bildirim sayısını getir
$unread_count = pepech_get_unread_count($user_id);

// Son 5 bildirimi getir
$recent_notifications = pepech_get_user_notifications($user_id, 5);
?>

<div class="dropdown">
    <button type="button" id="pepech-notification-toggle" class="pepech-notification-toggle btn btn-icon btn-lg btn-outline-secondary fs-lg border-0 rounded-circle animate-scale" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" aria-label="Bildirimler">
        <span class="notification-icon d-flex animate-target position-relative">
            <i class="ci-bell fs-lg m-0"></i>
            <?php if ($unread_count > 0): ?>
                <span class="pepech-notification-badge position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger fs-xxs">
                    <?php echo $unread_count; ?>
                </span>
            <?php endif; ?>
        </span>
    </button>

    <ul id="pepech-notification-panel" class="dropdown-menu dropdown-menu-end dropdown-menu-md-start pepech-notification-panel" style="--cz-dropdown-min-width: 19.5rem;">
        <li class="dropdown-header d-flex justify-content-between align-items-center pt-0 px-0">
            <span class="fw-semibold">Bildirimler</span>
            <a href="<?php echo home_url('/hesabim/bildirimlerim'); ?>" class="fs-xs text-body">
                Tümünü göster
            </a>
        </li>
        
        <?php if ($recent_notifications): ?>
            <?php foreach ($recent_notifications as $notification): ?>
                <?php if (!empty($notification->link_url)): ?>
                    <!-- Bildirimin tamamı link olarak -->
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
                    <!-- Link yoksa normal bildirim -->
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
                    <p class="mb-0">Henüz bildirim bulunmuyor.</p>
                </div>
            </li>
        <?php endif; ?>
    </ul>
</div>
