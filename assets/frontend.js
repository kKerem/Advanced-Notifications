/* Pepech Notification System - Frontend JavaScript */

jQuery(document).ready(function($) {
    
    // Bootstrap dropdown için özel işlemler
    $('#pepech-notification-toggle').on('click', function(e) {
        // Bootstrap dropdown'ının kendi event'ini engelleme
        e.stopPropagation();
    });
    
    // Dropdown açıldığında bildirimleri yenile
    $('#pepech-notification-panel').on('show.bs.dropdown', function() {
        refreshNotifications();
    });
    
    // Dropdown içindeki bildirimler için event handler'lar
    $(document).on('click', '.pepech-notification-item.unread', function(e) {
        e.preventDefault();
        var notificationId = $(this).data('id');
        var $item = $(this);
        
        markNotificationAsRead(notificationId, $item);
    });
    
    // Mouse hover ile okundu işaretleme
    $(document).on('mouseenter', '.pepech-notification-item.unread', function() {
        var $item = $(this);
        var notificationId = $item.data('id');
        
        // Eğer zaten okundu olarak işaretlenmişse işlem yapma
        if ($item.hasClass('read')) {
            return;
        }
        
        // Hover işlemini geciktir (yanlışlıkla hover olmasını önlemek için)
        var hoverTimer = setTimeout(function() {
            markNotificationAsRead(notificationId, $item);
        }, 1000); // 1 saniye hover sonrası
        
        // Mouse çıkarsa timer'ı iptal et
        $item.on('mouseleave', function() {
            clearTimeout(hoverTimer);
        });
    });
    
    // Okundu işaretleme fonksiyonu
    function markNotificationAsRead(notificationId, $item) {
        // Eğer zaten okundu olarak işaretlenmişse işlem yapma
        if ($item.hasClass('read')) {
            return;
        }
        
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
                    $item.removeClass('unread').addClass('read');
                    $item.find('.badge').remove(); // "Yeni" badge'ini kaldır
                    updateNotificationBadge();
                    
                    // Hover event'ini kaldır
                    $item.off('mouseenter mouseleave');
                }
            },
            error: function() {
                console.log('Bildirim okundu olarak işaretlenirken hata oluştu');
            }
        });
    }
    
    // Tüm bildirimleri okundu olarak işaretle
    $('#pepech-mark-all-read').on('click', function(e) {
        e.preventDefault();
        
        $.ajax({
            url: pepech_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'mark_all_notifications_read',
                nonce: pepech_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('.pepech-notification-item').removeClass('unread').addClass('read');
                    $('.pepech-mark-read').remove();
                    updateNotificationBadge();
                }
            }
        });
    });
    
    // Bildirim badge'ini belirli sayı ile güncelle
    function updateNotificationBadgeWithCount(unreadCount) {
        // Sadece header dropdown'ı olan sayfalarda çalış
        if ($('#pepech-notification-toggle').length === 0) {
            console.log('Header dropdown not found, skipping badge update');
            return;
        }
        
        var $badge = $('.pepech-notification-badge');
        
        console.log('updateNotificationBadgeWithCount called - unreadCount:', unreadCount, 'badge exists:', $badge.length);
        
        // Title'ı güncelle
        updatePageTitle(unreadCount);
        
        if (unreadCount > 0) {
            // Okunmamış bildirim varsa badge'i göster/güncelle
            if ($badge.length) {
                $badge.text(unreadCount);
                console.log('Badge updated to:', unreadCount);
            } else {
                $('#pepech-notification-toggle').append('<span class="pepech-notification-badge position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger fs-xxs">' + unreadCount + '</span>');
                console.log('Badge created with count:', unreadCount);
            }
        } else {
            // Okunmamış bildirim yoksa badge'i kaldır
            if ($badge.length) {
                $badge.remove();
                console.log('Badge removed - no unread notifications');
            }
        }
    }
    
    // Bildirim badge'ini güncelle
    function updateNotificationBadge() {
        // Sadece header dropdown'ı olan sayfalarda çalış
        if ($('#pepech-notification-toggle').length === 0) {
            console.log('Header dropdown not found, skipping badge update');
            return;
        }
        
        // Header dropdown'daki okunmamış bildirimleri say
        var unreadCount = $('.pepech-notification-item.unread, .pepech-notification-item-link.unread').length;
        var $badge = $('.pepech-notification-badge');
        
        console.log('updateNotificationBadge called - unreadCount:', unreadCount, 'badge exists:', $badge.length);
        
        // Title'ı güncelle
        updatePageTitle(unreadCount);
        
        if (unreadCount > 0) {
            // Okunmamış bildirim varsa badge'i göster/güncelle
            if ($badge.length) {
                $badge.text(unreadCount);
                console.log('Badge updated to:', unreadCount);
            } else {
                $('#pepech-notification-toggle').append('<span class="pepech-notification-badge position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger fs-xxs">' + unreadCount + '</span>');
                console.log('Badge created with count:', unreadCount);
            }
        } else {
            // Okunmamış bildirim yoksa badge'i kaldır
            if ($badge.length) {
                $badge.remove();
                console.log('Badge removed - no unread notifications');
            }
        }
    }
    
    // Sayfa başlığını güncelle
    function updatePageTitle(unreadCount) {
        // Sadece header dropdown'ı olan sayfalarda çalış
        if ($('#pepech-notification-toggle').length === 0) {
            return;
        }
        
        var $title = $('title');
        var originalTitle = $title.data('original-title') || $title.text();
        
        // İlk kez çağrıldığında orijinal title'ı kaydet
        if (!$title.data('original-title')) {
            $title.data('original-title', originalTitle);
        }
        
        if (unreadCount > 0) {
            // Bildirim sayısını title'a ekle
            var newTitle = '(' + unreadCount + ') ' + originalTitle;
            $title.text(newTitle);
            console.log('Title updated with notification count:', newTitle);
        } else {
            // Bildirim yoksa orijinal title'ı geri yükle
            $title.text(originalTitle);
            console.log('Title restored to original:', originalTitle);
        }
    }
    
    // Bildirimleri yenile
    function refreshNotifications() {
        $.ajax({
            url: pepech_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'get_notifications',
                limit: 5,
                nonce: pepech_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    updateNotificationList(response.data);
                }
            }
        });
    }
    
    // Bildirim listesini güncelle
    function updateNotificationList(data) {
        var $panel = $('#pepech-notification-panel');
        var notifications = data.notifications || data;
        var totalUnread = data.total_unread || 0;
        
        if (notifications.length === 0) {
            var html = '<li><div class="dropdown-item text-center text-muted py-3"><i class="ci-bell fs-1 d-block mb-2"></i><p class="mb-0">Henüz bildirim bulunmuyor.</p></div></li>';
            $panel.find('li:not(.dropdown-header):not(.dropdown-divider):not(:last-child)').remove();
            $panel.find('li:last-child').before(html);
            // Badge'i kaldır (bildirim yoksa badge'e gerek yok)
            updateNotificationBadgeWithCount(0);
            return;
        }
        
        var html = '';
        notifications.forEach(function(notification) {
            var emoji = notification.notification_emoji || '📢';
            
            var readClass = notification.is_read ? 'read' : 'unread bg-info';
            console.log('Notification:', notification.id, 'is_read:', notification.is_read, 'readClass:', readClass);
            var timeAgo = notification.time_ago || 'Az önce';
            var newBadge = !notification.is_read ? '<span class="badge bg-primary rounded-pill ms-2">Yeni</span>' : '';
            var thumbnail = notification.thumbnail ? '<div class="pepech-notification-thumbnail mb-2"><img src="' + escapeHtml(notification.thumbnail) + '" alt="' + escapeHtml(notification.title) + '" class="img-thumbnail" style="max-width: 60px; max-height: 45px;"></div>' : '';
            
            if (notification.link_url) {
                html += '<li><a href="' + escapeHtml(notification.link_url) + '" class="dropdown-item pepech-notification-item-link p-2 ' + readClass + '" data-id="' + notification.id + '"' + (!notification.is_read ? ' style="--cz-bg-opacity: .05;"' : '') + '>';
            } else {
                html += '<li><div class="dropdown-item pepech-notification-item ' + readClass + '" data-id="' + notification.id + '"' + (!notification.is_read ? ' style="--cz-bg-opacity: .05;"' : '') + '>';
            }
            
            html += '<div class="d-flex align-items-start">';
            html += '<div class="pepech-notification-icon me-3"><span style="font-size: 24px;">' + emoji + '</span></div>';
            html += '<div class="pepech-notification-body flex-grow-1">';
            html += '<div class="d-flex justify-content-between align-items-start">';
            html += '<h6 class="pepech-notification-title mb-0 fs-xs">' + escapeHtml(notification.title) + '</h6>';
            html += newBadge;
            html += '</div>';
            html += thumbnail;
            // html += '<p class="pepech-notification-message text-muted small mb-1">' + escapeHtml(notification.message.substring(0, 100)) + '...</p>';
            html += '<small class="pepech-notification-time text-muted fs-xs">' + timeAgo + '</small>';
            html += '</div></div>';
            
            if (notification.link_url) {
                html += '</a>';
            } else {
                html += '</div>';
            }
            html += '</li>';
        });
        
        // Mevcut bildirimleri kaldır ve yenilerini ekle
        $panel.find('li:not(.dropdown-header):not(.dropdown-divider):not(:last-child)').remove();
        $panel.find('li:last-child').before(html);
        
        // Hover olaylarını bağla (bildirimleri okundu olarak işaretle)
        $('.pepech-notification-item.unread').on('mouseenter', function() {
            var $item = $(this);
            var notificationId = $item.data('id');
            
            // AJAX ile bildirimi okundu olarak işaretle
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
                        $item.removeClass('unread bg-info').addClass('read').removeAttr('style');
                        $item.find('.badge').remove(); // "Yeni" badge'ini kaldır
                        updateNotificationBadge(); // Badge'i güncelle
                    }
                }
            });
        });
        
        // Badge'i güncelle (sunucudan gelen toplam okunmamış sayı ile)
        updateNotificationBadgeWithCount(totalUnread);
    }
    
    
    // HTML escape
    function escapeHtml(text) {
        var map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    }
    
    // Sayfa yüklendiğinde bildirimleri kontrol et
    refreshNotifications();
    
    // Her 30 saniyede bir bildirimleri yenile
    setInterval(refreshNotifications, 30000);
    
    // Bildirimlerim sayfası için özel işlemler
    if ($('.pepech-notifications-page').length) {
        
        // Sayfa seviyesinde tümünü oku
        $('#pepech-mark-all-read-page').on('click', function(e) {
            e.preventDefault();
            
            $.ajax({
                url: pepech_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'mark_all_notifications_read',
                    nonce: pepech_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $('.pepech-notification-item-page').removeClass('unread').addClass('read');
                        $('.pepech-mark-read-page').replaceWith('<span class="pepech-read-status">Okundu</span>');
                        location.reload();
                    }
                }
            });
        });
        
        // Sayfa seviyesinde tekil okundu işaretleme (click)
        $('.pepech-mark-read-page').on('click', function(e) {
            e.preventDefault();
            var notificationId = $(this).data('id');
            var $item = $(this).closest('.pepech-notification-item-page');
            
            markNotificationAsReadPage(notificationId, $item);
        });
        
        // Sayfa seviyesinde mouse hover ile okundu işaretleme
        $(document).on('mouseenter', '.pepech-notification-item-page.unread', function() {
            var $item = $(this);
            var notificationId = $item.data('id');
            
            // Eğer zaten okundu olarak işaretlenmişse işlem yapma
            if ($item.hasClass('read')) {
                return;
            }
            
            // Hover işlemini geciktir
            var hoverTimer = setTimeout(function() {
                markNotificationAsReadPage(notificationId, $item);
            }, 1000); // 1 saniye hover sonrası
            
            // Mouse çıkarsa timer'ı iptal et
            $item.on('mouseleave', function() {
                clearTimeout(hoverTimer);
            });
        });
        
        // Sayfa seviyesinde okundu işaretleme fonksiyonu
        function markNotificationAsReadPage(notificationId, $item) {
            // Eğer zaten okundu olarak işaretlenmişse işlem yapma
            if ($item.hasClass('read')) {
                return;
            }
            
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
                        $item.removeClass('unread bg-info').addClass('read').removeAttr('style');
                        $item.find('.pepech-mark-read-page').replaceWith('<span class="pepech-read-status">Okundu</span>');
                        
                        // Hover event'ini kaldır
                        $item.off('mouseenter mouseleave');
                    }
                },
                error: function() {
                    console.log('Bildirim okundu olarak işaretlenirken hata oluştu');
                }
            });
        }
    }
});
