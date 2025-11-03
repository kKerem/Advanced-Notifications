/* Pepech Notification System - Admin JavaScript */

jQuery(document).ready(function($) {
    
    // Emoji picker için
    initEmojiPicker();
    
    // Bildirim detaylarını göster
    $('.view-notification').on('click', function(e) {
        e.preventDefault();
        var notificationId = $(this).data('id');
        
        $.ajax({
            url: pepech_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'get_notification_details',
                notification_id: notificationId,
                nonce: pepech_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    showNotificationModal(response.data);
                }
            }
        });
    });
    
    // Modal'ı göster
    function showNotificationModal(notification) {
        var html = '<h3>' + escapeHtml(notification.title) + '</h3>';
        html += '<div class="notification-meta">';
        html += '<p><strong>Kullanıcı:</strong> ' + escapeHtml(notification.display_name) + ' (' + escapeHtml(notification.user_email) + ')</p>';
        html += '<p><strong>Tür:</strong> <span class="notification-type notification-type-' + notification.type + '">' + notification.type.charAt(0).toUpperCase() + notification.type.slice(1) + '</span></p>';
        html += '<p><strong>Tarih:</strong> ' + formatDate(notification.created_at) + '</p>';
        html += '<p><strong>E-posta Gönderildi:</strong> ' + (notification.send_email ? 'Evet' : 'Hayır') + '</p>';
        html += '<p><strong>Okundu:</strong> ' + (notification.is_read ? 'Evet' : 'Hayır') + '</p>';
        html += '</div>';
        html += '<div class="notification-content">' + notification.message + '</div>';
        
        $('#notification-details').html(html);
        $('#notification-modal').show();
    }
    
    // Modal'ı kapat
    $('.pepech-modal-close').on('click', function() {
        $('#notification-modal').hide();
    });
    
    // Modal dışına tıklandığında kapat
    $(window).on('click', function(e) {
        if (e.target.id === 'notification-modal') {
            $('#notification-modal').hide();
        }
    });
    
    // Çoklu kullanıcı seçimi işlemleri
    $('input[name="user_selection_type"]').on('change', function() {
        var selectionType = $(this).val();
        var $userSelectionRow = $('#user_selection_row');
        var $sendToAllHidden = $('#send_to_all_hidden');
        
        console.log('User selection type changed to:', selectionType);
        
        if (selectionType === 'all') {
            $userSelectionRow.addClass('hidden');
            $sendToAllHidden.val('1');
            console.log('Set send_to_all to 1');
        } else {
            $userSelectionRow.removeClass('hidden');
            $sendToAllHidden.val('0');
            console.log('Set send_to_all to 0');
        }
    });
    
    // Tümünü seç butonu
    $('#select_all_users').on('click', function() {
        console.log('Tümünü seç butonuna tıklandı');
        
        // Select2 varsa Select2 API'sini kullan
        if (typeof $.fn.select2 !== 'undefined' && $('#selected_users').hasClass('select2-hidden-accessible')) {
            var allValues = [];
            $('#selected_users option').each(function() {
                allValues.push($(this).val());
            });
            $('#selected_users').val(allValues).trigger('change');
        } else {
            // Normal select için
            $('#selected_users option').prop('selected', true);
        }
        
        updateSelectedCount(false);
    });
    
    // Tümünü kaldır butonu
    $('#deselect_all_users').on('click', function() {
        console.log('Tümünü kaldır butonuna tıklandı');
        
        // Select2 varsa Select2 API'sini kullan
        if (typeof $.fn.select2 !== 'undefined' && $('#selected_users').hasClass('select2-hidden-accessible')) {
            $('#selected_users').val(null).trigger('change');
        } else {
            // Normal select için
            $('#selected_users option').prop('selected', false);
        }
        
        updateSelectedCount(false);
    });
    
    // Select2 değişikliklerini dinle
    $(document).on('change', '#selected_users', function() {
        console.log('Select2 değişti:', $(this).val());
        updateSelectedCount(false);
    });
    
    // Seçili kullanıcı sayısını güncelle
    function updateSelectedCount(forceValidation = false) {
        var selectedCount = $('#selected_users').val() ? $('#selected_users').val().length : 0;
        console.log('Seçili kullanıcı sayısı:', selectedCount);
        $('.pepech-selected-count').text(selectedCount + ' kullanıcı seçildi');
        
        // Sadece form validasyonu sırasında veya kullanıcı etkileşimi sonrasında error göster
        if (forceValidation && selectedCount === 0 && $('input[name="user_selection_type"]:checked').val() === 'selected') {
            $('.pepech-user-multiselect').addClass('error');
        } else {
            $('.pepech-user-multiselect').removeClass('error');
        }
    }
    
    // Bildirim linki değişikliklerini dinle
    $(document).on('change', '#notification_link', function() {
        var selectedValue = $(this).val();
        
        // Tüm ek satırları gizle
        $('#custom_url_row, #link_text_row').hide();
        
        // Seçilen değere göre ilgili satırı göster
        if (selectedValue === 'custom') {
            $('#custom_url_row, #link_text_row').show();
        } else if (selectedValue && selectedValue !== '') {
            $('#link_text_row').show();
        }
    });
    
    // Form validasyonu ve AJAX gönderim
    $('form').on('submit', function(e) {
        e.preventDefault();
        var $form = $(this);
        var isValid = true;
        
        // Debug bilgileri
        console.log('Form submit - Debug info:');
        console.log('user_selection_type:', $('input[name="user_selection_type"]:checked').val());
        console.log('send_to_all_hidden:', $('#send_to_all_hidden').val());
        console.log('selected_users:', $('#selected_users').val());
        
        // Zorunlu alanları kontrol et
        $form.find('[required]').each(function() {
            if (!$(this).val().trim()) {
                isValid = false;
                $(this).addClass('error');
                console.log('Required field empty:', $(this).attr('name'));
            } else {
                $(this).removeClass('error');
            }
        });
        
        // Kullanıcı seçimi kontrolü
        var selectionType = $('input[name="user_selection_type"]:checked').val();
        console.log('Selection type:', selectionType);
        
        if (selectionType === 'selected') {
            var selectedUsers = $('#selected_users').val() ? $('#selected_users').val().length : 0;
            console.log('Selected users count:', selectedUsers);
            if (selectedUsers === 0) {
                isValid = false;
                updateSelectedCount(true); // forceValidation = true
                console.log('No users selected - preventing submit');
                alert('Lütfen en az bir kullanıcı seçin.');
            }
        }
        
        // Özel URL kontrolü
        var notificationLink = $('#notification_link').val();
        if (notificationLink === 'custom') {
            var customUrl = $('#custom_url').val();
            if (!customUrl.trim()) {
                isValid = false;
                $('#custom_url').addClass('error');
                console.log('Custom URL empty - preventing submit');
                alert('Özel URL girmelisiniz.');
            }
        }
        
        console.log('Form validation result:', isValid);
        
        if (!isValid) {
            console.log('Form submission prevented due to validation errors');
            if (!$('.pepech-user-multiselect').hasClass('error') && !$('#custom_url').hasClass('error')) {
                alert('Lütfen tüm zorunlu alanları doldurun.');
            }
        } else {
            console.log('Form validation passed - starting AJAX submission...');
            sendNotificationAjax();
        }
    });
    
    // AJAX ile bildirim gönderimi
    function sendNotificationAjax() {
        var formData = {
            action: 'send_bulk_notification',
            nonce: pepech_ajax.nonce,
            title: $('input[name="title"]').val(),
            message: $('#message').val(),
            type: 'info', // Varsayılan tip
            notification_emoji: $('#notification_emoji').val() || '📢',
            send_email: $('#send_email').is(':checked') ? 1 : 0,
            user_ids: $('#selected_users').val() || [],
            link_url: $('#notification_link').val() === 'custom' ? $('#custom_url').val() : $('#notification_link').val(),
            link_text: $('#link_text').val(),
            thumbnail_url: $('#thumbnail_url').val(),
            offset: 0
        };
        
        // Debug log
        console.log('Form data being sent:', formData);
        console.log('notification_emoji value:', $('#notification_emoji').val());
        console.log('selected_users raw value:', $('#selected_users').val());
        console.log('selected_users type:', typeof $('#selected_users').val());
        
        // Progress bar göster
        showProgressBar();
        
        // İlk batch'i gönder
        sendBatch(formData);
    }
    
    function sendBatch(formData) {
        $.ajax({
            url: pepech_ajax.ajax_url,
            type: 'POST',
            data: formData,
                success: function(response) {
                    console.log('AJAX Response:', response);
                    if (response.success) {
                        updateProgressBar(response.data);
                        
                        if (response.data.is_complete) {
                        // Tamamlandı
                        showSuccessMessage(response.data);
                    } else {
                        // Sonraki batch'i gönder
                        formData.offset = response.data.next_offset;
                        setTimeout(function() {
                            sendBatch(formData);
                        }, 500); // 500ms bekle
                    }
                } else {
                    // Hata durumunda progress bar'ı güncelle
                    $('#pepech-progress-bar')
                        .removeClass('progress-bar-animated')
                        .addClass('bg-danger')
                        .css('width', '100%')
                        .text('Hata!');
                    $('#pepech-progress-title').text('Hata Oluştu!');
                    $('#pepech-progress-result').text('❌ ' + response.data).show();
                    $('#pepech-progress-actions').show();
                }
            },
            error: function() {
                // AJAX hatası durumunda progress bar'ı güncelle
                $('#pepech-progress-bar')
                    .removeClass('progress-bar-animated')
                    .addClass('bg-danger')
                    .css('width', '100%')
                    .text('Hata!');
                $('#pepech-progress-title').text('Hata Oluştu!');
                $('#pepech-progress-result').text('❌ Bildirim gönderilirken bir hata oluştu!').show();
                $('#pepech-progress-actions').show();
            }
        });
    }
    
    function showProgressBar() {
        // Seçili kullanıcı sayısını al
        var selectedUsers = $('#selected_users').val() || [];
        var totalUsers = selectedUsers.length;
        
        console.log('showProgressBar called - totalUsers:', totalUsers);
        
        var progressHtml = '<div id="pepech-progress-container" style="position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 20px rgba(0,0,0,0.3); z-index: 9999; min-width: 400px;">' +
            '<h3 id="pepech-progress-title" style="margin: 0 0 20px 0; text-align: center;">Bildirim Gönderiliyor...</h3>' +
            '<div class="progress mb-3" style="height: 25px;">' +
                '<div id="pepech-progress-bar" class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">0%</div>' +
            '</div>' +
            '<div id="pepech-progress-text" style="text-align: center; font-size: 14px; color: #666; margin-bottom: 10px;">0 / ' + totalUsers + ' kullanıcıya gönderildi</div>' +
            '<div id="pepech-progress-details" style="text-align: center; font-size: 12px; color: #999; margin-bottom: 10px;">Başarılı: 0, Hatalı: 0</div>' +
            '<div id="pepech-progress-result" style="text-align: center; font-size: 16px; font-weight: bold; color: #28a745; margin-top: 15px; padding: 10px; background: #f8f9fa; border-radius: 5px; display: none;">Hazırlanıyor...</div>' +
            '<div id="pepech-progress-actions" style="text-align: center; margin-top: 20px; display: none;">' +
                '<button type="button" id="pepech-close-modal" class="btn btn-primary">Kapat</button>' +
            '</div>' +
        '</div>';
        
        console.log('Progress HTML created, appending to body...');
        $('body').append(progressHtml);
        
        // Elementlerin oluşturulup oluşturulmadığını kontrol et
        setTimeout(function() {
            var $container = $('#pepech-progress-container');
            var $result = $('#pepech-progress-result');
            console.log('After append - container found:', $container.length, 'result found:', $result.length);
            
            if ($container.length > 0) {
                console.log('Container HTML:', $container.html());
            }
            
            // Kapat düğmesi olayını ekle
            $('#pepech-close-modal').on('click', function() {
                console.log('Close button clicked');
                hideProgressBar();
                // Ana bildirimler sayfasına yönlendir
                window.location.href = 'admin.php?page=pepech-notifications';
            });
        }, 100);
    }
    
    function updateProgressBar(data) {
        console.log('updateProgressBar called with data:', data);
        console.log('processed:', data.processed, 'total:', data.total);
        
        var processed = parseInt(data.processed) || 0;
        var total = parseInt(data.total) || 0;
        var successCount = parseInt(data.success_count) || 0;
        var errorCount = parseInt(data.error_count) || 0;
        
        var percentage = total > 0 ? Math.round((processed / total) * 100) : 0;
        
        console.log('Calculated - processed:', processed, 'total:', total, 'percentage:', percentage);
        
        // Tüm elementleri kontrol et
        var $progressBar = $('#pepech-progress-bar');
        var $progressText = $('#pepech-progress-text');
        var $progressDetails = $('#pepech-progress-details');
        
        console.log('Elements found - progressBar:', $progressBar.length, 'progressText:', $progressText.length, 'progressDetails:', $progressDetails.length);
        
        if ($progressBar.length > 0) {
            // Bootstrap progress bar güncelleme
            $progressBar
                .css('width', percentage + '%')
                .attr('aria-valuenow', percentage)
                .text(percentage + '%');
            
            console.log('Progress bar updated to:', percentage + '%');
        } else {
            console.log('Progress bar element not found!');
        }
        
        if ($progressText.length > 0) {
            $progressText.text(processed + ' / ' + total + ' kullanıcıya gönderildi');
            console.log('Progress text updated to:', processed + ' / ' + total + ' kullanıcıya gönderildi');
        } else {
            console.log('Progress text element not found!');
        }
        
        if ($progressDetails.length > 0) {
            $progressDetails.text('Başarılı: ' + successCount + ', Hatalı: ' + errorCount);
            console.log('Progress details updated to:', 'Başarılı: ' + successCount + ', Hatalı: ' + errorCount);
        } else {
            console.log('Progress details element not found!');
        }
    }
    
    function hideProgressBar() {
        console.log('hideProgressBar called');
        $('#pepech-progress-container').remove();
    }
    
    function showSuccessMessage(data) {
        console.log('showSuccessMessage called with data:', data);
        
        var processed = parseInt(data.processed) || 0;
        var total = parseInt(data.total) || 0;
        var successCount = parseInt(data.success_count) || 0;
        var errorCount = parseInt(data.error_count) || 0;
        
        console.log('Success message - processed:', processed, 'total:', total, 'success:', successCount, 'error:', errorCount);
        
        // Tüm elementleri kontrol et
        var $progressBar = $('#pepech-progress-bar');
        var $progressText = $('#pepech-progress-text');
        var $progressDetails = $('#pepech-progress-details');
        var $progressTitle = $('#pepech-progress-title');
        var $progressResult = $('#pepech-progress-result');
        var $progressActions = $('#pepech-progress-actions');
        
        console.log('Success elements found - progressBar:', $progressBar.length, 'progressText:', $progressText.length, 'progressDetails:', $progressDetails.length, 'progressTitle:', $progressTitle.length, 'progressResult:', $progressResult.length, 'progressActions:', $progressActions.length);
        
        // Progress bar'ı tamamla
        if ($progressBar.length > 0) {
            $progressBar
                .removeClass('progress-bar-animated')
                .addClass('bg-success')
                .css('width', '100%')
                .attr('aria-valuenow', 100)
                .text('100%');
            console.log('Progress bar completed');
        }
        
        if ($progressText.length > 0) {
            $progressText.text(total + ' / ' + total + ' kullanıcıya gönderildi');
            console.log('Progress text updated');
        }
        
        if ($progressDetails.length > 0) {
            $progressDetails.text('Başarılı: ' + successCount + ', Hatalı: ' + errorCount);
            console.log('Progress details updated');
        }
        
        // Başlığı değiştir
        if ($progressTitle.length > 0) {
            $progressTitle.text('Bildirim Gönderildi!');
            console.log('Progress title updated');
        }
        
        // Sonuç mesajını göster
        var resultMessage = '';
        if (errorCount === 0) {
            resultMessage = '✅ Herkese başarıyla gönderildi!';
        } else if (successCount > 0) {
            resultMessage = '⚠️ ' + successCount + ' kullanıcıya gönderildi, ' + errorCount + ' kullanıcıya gönderilemedi.';
        } else {
            resultMessage = '❌ Hiçbir kullanıcıya gönderilemedi.';
        }
        
        console.log('Result message:', resultMessage);
        
        if ($progressResult.length > 0) {
            $progressResult.text(resultMessage).show();
            console.log('Result message displayed');
        } else {
            console.log('Result element not found!');
        }
        
        // Kapat düğmesini göster
        if ($progressActions.length > 0) {
            $progressActions.show();
            console.log('Close button displayed');
        } else {
            console.log('Actions element not found!');
        }
    }
    
    // Bildirim türüne göre renk değişimi
    $('#type').on('change', function() {
        var type = $(this).val();
        var $preview = $('#type-preview');
        
        if (!$preview.length) {
            $preview = $('<div id="type-preview" style="margin-top: 10px; padding: 10px; border-radius: 4px;"></div>');
            $(this).after($preview);
        }
        
        var colors = {
            'info': { bg: '#e3f2fd', color: '#1976d2', text: 'Bilgi' },
            'success': { bg: '#e8f5e8', color: '#2e7d32', text: 'Başarı' },
            'warning': { bg: '#fff3e0', color: '#f57c00', text: 'Uyarı' },
            'error': { bg: '#ffebee', color: '#d32f2f', text: 'Hata' }
        };
        
        var style = colors[type] || colors['info'];
        $preview.css({
            'background-color': style.bg,
            'color': style.color,
            'border': '1px solid ' + style.color
        }).text(style.text + ' bildirimi önizlemesi');
    });
    
    // Mesaj karakter sayacı
    if ($('#message').length) {
        var $counter = $('<div id="message-counter" style="text-align: right; font-size: 12px; color: #666; margin-top: 5px;"></div>');
        $('#message').after($counter);
        
        function updateCounter() {
            var content = $('#message').val();
            var length = content.length;
            var maxLength = 500;
            
            $counter.text(length + '/' + maxLength + ' karakter');
            
            if (length > maxLength) {
                $counter.css('color', '#d32f2f');
            } else if (length > maxLength * 0.8) {
                $counter.css('color', '#f57c00');
            } else {
                $counter.css('color', '#666');
            }
        }
        
        $('#message').on('input', updateCounter);
        updateCounter();
    }
    
    // Toplu işlemler
    $('.bulk-action').on('click', function(e) {
        e.preventDefault();
        var action = $(this).data('action');
        var selectedIds = [];
        
        $('input[name="notification_ids[]"]:checked').each(function() {
            selectedIds.push($(this).val());
        });
        
        if (selectedIds.length === 0) {
            alert('Lütfen işlem yapmak istediğiniz bildirimleri seçin.');
            return;
        }
        
        if (!confirm('Seçili ' + selectedIds.length + ' bildirim üzerinde ' + action + ' işlemini yapmak istediğinizden emin misiniz?')) {
            return;
        }
        
        $.ajax({
            url: pepech_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'bulk_notification_action',
                bulk_action: action,
                notification_ids: selectedIds,
                nonce: pepech_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert('İşlem sırasında bir hata oluştu: ' + response.data);
                }
            }
        });
    });
    
    // Tümünü seç/seçme
    $('#select-all').on('change', function() {
        $('input[name="notification_ids[]"]').prop('checked', $(this).is(':checked'));
    });
    
    // Yardımcı fonksiyonlar
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
    
    function formatDate(dateString) {
        var date = new Date(dateString);
        return date.toLocaleDateString('tr-TR') + ' ' + date.toLocaleTimeString('tr-TR');
    }
    
    // Otomatik kaydetme (draft)
    var autoSaveTimer;
    $('input[name="title"], #message').on('input', function() {
        clearTimeout(autoSaveTimer);
        autoSaveTimer = setTimeout(function() {
            saveDraft();
        }, 2000);
    });
    
    function saveDraft() {
        var draft = {
            title: $('input[name="title"]').val(),
            message: $('#message').val(),
            type: $('#type').val(),
            send_email: $('#send_email').is(':checked'),
            notification_link: $('#notification_link').val(),
            custom_url: $('#custom_url').val(),
            link_text: $('#link_text').val()
        };
        
        localStorage.setItem('pepech_notification_draft', JSON.stringify(draft));
    }
    
    function loadDraft() {
        var draft = localStorage.getItem('pepech_notification_draft');
        if (draft) {
            try {
                draft = JSON.parse(draft);
                $('input[name="title"]').val(draft.title);
                $('#message').val(draft.message);
                $('#type').val(draft.type);
                $('#send_email').prop('checked', draft.send_email);
                $('#notification_link').val(draft.notification_link);
                $('#custom_url').val(draft.custom_url);
                $('#link_text').val(draft.link_text);
                
                if (confirm('Kaydedilmemiş bir taslak bulundu. Yüklemek istiyor musunuz?')) {
                    // Draft yüklendi
                } else {
                    localStorage.removeItem('pepech_notification_draft');
                }
            } catch (e) {
                localStorage.removeItem('pepech_notification_draft');
            }
        }
    }
    
    // Sayfa yüklendiğinde draft'ı kontrol et
    loadDraft();
    
    // Manuel temizlik butonu
    $('#pepech-manual-cleanup').on('click', function() {
        if (confirm('90 günden eski bildirimleri şimdi temizlemek istediğinizden emin misiniz?')) {
            var $button = $(this);
            $button.prop('disabled', true).text('Temizleniyor...');
            
            $.ajax({
                url: pepech_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'manual_cleanup_notifications',
                    nonce: pepech_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        alert(response.data.message);
                        location.reload(); // Sayfayı yenile
                    } else {
                        alert('Hata: ' + response.data);
                    }
                },
                error: function() {
                    alert('Temizlik sırasında bir hata oluştu!');
                },
                complete: function() {
                    $button.prop('disabled', false).text('Şimdi Temizle');
                }
            });
        }
    });
    setTimeout(function() {
        updateSelectedCount(false); // forceValidation = false
        
        // Sayfa yüklendiğinde hidden field'ı kontrol et
        var currentSelection = $('input[name="user_selection_type"]:checked').val();
        console.log('Page loaded - current selection:', currentSelection);
        if (currentSelection === 'selected') {
            $('#send_to_all_hidden').val('0');
            console.log('Set send_to_all_hidden to 0 on page load');
        }
        
        // Select2'nin yüklenip yüklenmediğini kontrol et
        if (typeof $.fn.select2 !== 'undefined') {
            console.log('Select2 mevcut, kullanıcı seçimi kontrol ediliyor...');
            
            // Eğer Select2 henüz başlatılmamışsa başlat
            if (!$('#selected_users').hasClass('select2-hidden-accessible')) {
                console.log('Select2 başlatılıyor...');
                $('#selected_users').select2({
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
            }
        }
    }, 1000);
    
    // Form gönderildiğinde draft'ı temizle
    $('form').on('submit', function() {
        localStorage.removeItem('pepech_notification_draft');
    });
    
    // Thumbnail upload işlevselliği
    $('#select-thumbnail').on('click', function() {
        $('#notification_thumbnail').click();
    });
    
    $('#notification_thumbnail').on('change', function() {
        var file = this.files[0];
        if (file) {
            // Dosya boyutu kontrolü (2MB)
            if (file.size > 2 * 1024 * 1024) {
                alert('Dosya boyutu 2MB\'dan büyük olamaz!');
                return;
            }
            
            // Dosya türü kontrolü
            var allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
            if (!allowedTypes.includes(file.type)) {
                alert('Sadece JPG, PNG ve GIF dosyaları yüklenebilir!');
                return;
            }
            
            // Preview göster
            var reader = new FileReader();
            reader.onload = function(e) {
                $('#thumbnail-image').attr('src', e.target.result);
                $('#thumbnail-preview').show();
                $('#thumbnail-upload-area').hide();
                $('#thumbnail_url').val(e.target.result);
            };
            reader.readAsDataURL(file);
        }
    });
    
    $('#remove-thumbnail').on('click', function() {
        $('#thumbnail-preview').hide();
        $('#thumbnail-upload-area').show();
        $('#notification_thumbnail').val('');
        $('#thumbnail_url').val('');
    });
});

// Emoji picker fonksiyonu
function initEmojiPicker() {
    jQuery(document).ready(function($) {
        var emojiInput = $('#notification_emoji');
        if (emojiInput.length === 0) return;
        
        // Popüler emoji'ler
        var popularEmojis = ['📢', '✅', '⚠️', '❌', '🎉', '💡', '🔔', '📝', '💰', '🎯', '🚀', '⭐', '❤️', '👍', '👎', '🔥', '💯', '🎊', '🎁', '📊'];
        
        // Emoji picker container oluştur
        var pickerHtml = '<div id="emoji-picker" style="display: none; position: absolute; background: white; border: 1px solid #ddd; border-radius: 8px; padding: 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); z-index: 1000; margin-top: 5px;">';
        pickerHtml += '<div style="display: grid; grid-template-columns: repeat(5, 1fr); gap: 5px; max-width: 200px;">';
        
        popularEmojis.forEach(function(emoji) {
            pickerHtml += '<button type="button" class="emoji-btn" data-emoji="' + emoji + '" style="font-size: 20px; padding: 8px; border: none; background: none; cursor: pointer; border-radius: 4px; transition: background-color 0.2s;">' + emoji + '</button>';
        });
        
        pickerHtml += '</div>';
        pickerHtml += '<div style="margin-top: 10px; text-align: center;">';
        pickerHtml += '<button type="button" id="emoji-clear" style="font-size: 12px; padding: 4px 8px; border: 1px solid #ddd; background: #f9f9f9; cursor: pointer; border-radius: 4px;">Temizle</button>';
        pickerHtml += '</div>';
        pickerHtml += '</div>';
        
        // Picker'ı input'un yanına ekle
        emojiInput.after(pickerHtml);
        
        var picker = $('#emoji-picker');
        
        // Input'a tıklandığında picker'ı göster
        emojiInput.on('focus click', function() {
            picker.show();
        });
        
        // Emoji butonlarına tıklandığında
        $(document).on('click', '.emoji-btn', function() {
            var emoji = $(this).data('emoji');
            emojiInput.val(emoji);
            picker.hide();
        });
        
        // Temizle butonuna tıklandığında
        $(document).on('click', '#emoji-clear', function() {
            emojiInput.val('');
            picker.hide();
        });
        
        // Dışarı tıklandığında picker'ı gizle
        $(document).on('click', function(e) {
            if (!$(e.target).closest('#notification_emoji, #emoji-picker').length) {
                picker.hide();
            }
        });
        
        // Hover efektleri
        $(document).on('mouseenter', '.emoji-btn', function() {
            $(this).css('background-color', '#f0f0f0');
        });
        
        $(document).on('mouseleave', '.emoji-btn', function() {
            $(this).css('background-color', 'transparent');
        });
    });
}