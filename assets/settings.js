jQuery(document).ready(function($) {
    // Manuel temizlik
    $('#pepech-manual-cleanup-settings').on('click', function() {
        if (confirm('Eski bildirimleri şimdi temizlemek istediğinizden emin misiniz?')) {
            $.ajax({
                url: pepech_settings_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'manual_cleanup_notifications',
                    nonce: pepech_settings_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        alert('Temizlik tamamlandı! ' + response.data.deleted_count + ' bildirim silindi.');
                        location.reload();
                    } else {
                        alert('Hata: ' + response.data);
                    }
                }
            });
        }
    });
    
    // Varsayılan ayarlara dön
    $('#pepech-reset-settings').on('click', function() {
        if (confirm('Ayarları varsayılan değerlere döndürmek istediğinizden emin misiniz?')) {
            $('#cleanup_days').val(90);
            $('#max_per_page').val(10);
            $('#default_email').prop('checked', true);
            $('#enable_dropdown').prop('checked', true);
            $('#dropdown_limit').val(5);
            $('#debug_logs').prop('checked', false);
            
            // WooCommerce ayarları
            $('#wc_new_order').prop('checked', true);
            $('#wc_order_processing').prop('checked', true);
            $('#wc_order_completed').prop('checked', true);
            $('#wc_order_cancelled').prop('checked', true);
            $('#wc_order_refunded').prop('checked', true);
            $('#wc_payment_complete').prop('checked', true);
            $('#wc_order_shipped').prop('checked', true);
        }
    });
    
    // Tüm bildirimleri sil
    $('#pepech-delete-all-notifications').on('click', function() {
        console.log('Delete all notifications button clicked');
        if (confirm('TÜM bildirimleri silmek istediğinizden emin misiniz? Bu işlem geri alınamaz!')) {
            if (confirm('Bu işlem TÜM bildirimleri kalıcı olarak silecek. Devam etmek istediğinizden emin misiniz?')) {
                console.log('Sending AJAX request to delete all notifications');
                $.ajax({
                    url: pepech_settings_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'delete_all_notifications',
                        nonce: pepech_settings_ajax.nonce
                    },
                    success: function(response) {
                        console.log('AJAX response:', response);
                        if (response.success) {
                            alert('Tüm bildirimler silindi! ' + response.data.deleted_count + ' bildirim silindi.');
                            location.reload();
                        } else {
                            alert('Hata: ' + response.data);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.log('AJAX error:', xhr, status, error);
                        alert('AJAX hatası: ' + error);
                    }
                });
            }
        }
    });
});
