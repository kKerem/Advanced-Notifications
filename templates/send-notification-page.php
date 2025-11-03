<?php
if (!defined('ABSPATH')) {
    exit;
}

// Bildirim gönderme işlemi
if (isset($_POST['send_notification']) && wp_verify_nonce($_POST['pepech_nonce'], 'send_notification')) {
    error_log('Pepech Notification: Form submitted successfully');
    
    $title = sanitize_text_field($_POST['title']);
    $message = wp_kses_post($_POST['message']);
    $notification_emoji = isset($_POST['notification_emoji']) ? sanitize_text_field($_POST['notification_emoji']) : '📢';
    $send_email = isset($_POST['send_email']) ? 1 : 0;
    $send_to_all = isset($_POST['send_to_all']) ? intval($_POST['send_to_all']) : 0;
    $selected_users = isset($_POST['selected_users']) ? array_map('intval', $_POST['selected_users']) : array();
    $notification_link = isset($_POST['notification_link']) ? sanitize_text_field($_POST['notification_link']) : '';
    $custom_url = isset($_POST['custom_url']) ? esc_url_raw($_POST['custom_url']) : '';
    $link_text = isset($_POST['link_text']) ? sanitize_text_field($_POST['link_text']) : '';
    $thumbnail_url = isset($_POST['thumbnail_url']) ? esc_url_raw($_POST['thumbnail_url']) : '';
    
    // Thumbnail upload işlemi
    if (isset($_FILES['notification_thumbnail']) && $_FILES['notification_thumbnail']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = wp_upload_dir();
        $thumbnail_dir = $upload_dir['basedir'] . '/pepech-notifications/';
        
        // Klasör yoksa oluştur
        if (!file_exists($thumbnail_dir)) {
            wp_mkdir_p($thumbnail_dir);
        }
        
        $file_name = sanitize_file_name($_FILES['notification_thumbnail']['name']);
        $file_extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $allowed_extensions = array('jpg', 'jpeg', 'png', 'gif');
        
        if (in_array($file_extension, $allowed_extensions)) {
            $unique_name = uniqid() . '_' . $file_name;
            $file_path = $thumbnail_dir . $unique_name;
            
            if (move_uploaded_file($_FILES['notification_thumbnail']['tmp_name'], $file_path)) {
                $thumbnail_url = $upload_dir['baseurl'] . '/pepech-notifications/' . $unique_name;
            }
        }
    }
    
    // Link URL'ini belirle
    $link_url = '';
    if ($notification_link === 'custom') {
        $link_url = $custom_url;
    } elseif (!empty($notification_link)) {
        $link_url = esc_url_raw($notification_link);
    }
    
    // Link metnini belirle
    if (empty($link_text) && !empty($link_url)) {
        $link_text = 'Detayları Görüntüle';
    }
    
    if ($title && $message) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'pepech_notifications';
        
        $success_count = 0;
        $error_count = 0;
        
        // Debug için log'lar
        error_log('Pepech Notification Debug:');
        error_log('send_to_all: ' . $send_to_all);
        error_log('selected_users: ' . print_r($selected_users, true));
        error_log('user_selection_type: ' . (isset($_POST['user_selection_type']) ? $_POST['user_selection_type'] : 'not set'));
        error_log('POST data: ' . print_r($_POST, true));
        
        // Hedef kullanıcıları belirle
        $user_selection_type = isset($_POST['user_selection_type']) ? $_POST['user_selection_type'] : 'selected';
        
        if ($user_selection_type === 'all' || $send_to_all) {
            // Tüm kullanıcılara gönder
            $users = get_users(array('fields' => array('ID')));
            $user_ids = array_map(function($user) { return $user->ID; }, $users);
            error_log('Sending to all users: ' . count($user_ids));
        } else {
            // Seçili kullanıcılara gönder
            $user_ids = $selected_users;
            error_log('Sending to selected users: ' . count($user_ids));
        }
        
        if (empty($user_ids)) {
            echo '<div class="notice notice-error"><p>Lütfen en az bir kullanıcı seçin!</p></div>';
        } else {
            foreach ($user_ids as $user_id) {
                // Veritabanı kolonlarını kontrol et
                $columns = $wpdb->get_col("DESCRIBE $table_name");
                $data = array(
                    'user_id' => $user_id,
                    'title' => $title,
                    'message' => $message,
                    'type' => 'info', // Varsayılan tip
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
                                    </h2>
                                    <div style="background: #f8f9fa; padding: 20px; border-radius: 5px; margin: 20px 0;">
                                        ' . wp_kses_post($message) . '
                                    </div>
                                    <p style="margin-top: 30px; font-size: 14px; color: #666;">
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
                }
            }
            
            if ($success_count > 0) {
                echo '<div class="notice notice-success"><p>' . $success_count . ' kullanıcıya bildirim başarıyla gönderildi!</p></div>';
            }
            if ($error_count > 0) {
                echo '<div class="notice notice-warning"><p>' . $error_count . ' kullanıcıya bildirim gönderilemedi!</p></div>';
            }
        }
    } else {
        echo '<div class="notice notice-error"><p>Lütfen başlık ve mesaj alanlarını doldurun!</p></div>';
    }
} else {
    // Form gönderilmedi veya nonce geçersiz
    if (isset($_POST['send_notification'])) {
        error_log('Pepech Notification: Form submitted but nonce verification failed');
        echo '<div class="notice notice-error"><p>Güvenlik kontrolü başarısız! Lütfen tekrar deneyin.</p></div>';
    }
}

// Kullanıcıları getir
$users = get_users(array('fields' => array('ID', 'display_name', 'user_email')));

// Sayfaları getir
$pages = get_posts(array(
    'post_type' => 'page',
    'post_status' => 'publish',
    'numberposts' => -1,
    'orderby' => 'title',
    'order' => 'ASC'
));

// Ürünleri getir (WooCommerce varsa)
$products = array();
if (class_exists('WooCommerce')) {
    $products = get_posts(array(
        'post_type' => 'product',
        'post_status' => 'publish',
        'numberposts' => -1,
        'orderby' => 'title',
        'order' => 'ASC'
    ));
}
?>

<div class="wrap">
    <h1>Bildirim Gönder</h1>
    
    <form method="post" action="">
        <?php wp_nonce_field('send_notification', 'pepech_nonce'); ?>
        
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="send_to_all">Hedef Kullanıcılar</label>
                </th>
                <td>
                    <div class="pepech-user-selection">
                        <label>
                            <input type="radio" name="user_selection_type" value="all" id="send_to_all_users" />
                            <strong>Tüm Kullanıcılara Gönder</strong>
                            <span class="description">(<?php echo count($users); ?> kullanıcı)</span>
                        </label>
                        <br><br>
                        <label>
                            <input type="radio" name="user_selection_type" value="selected" id="send_to_selected_users" checked />
                            <strong>Seçili Kullanıcılara Gönder</strong>
                        </label>
                    </div>
                </td>
            </tr>
            
            <tr id="user_selection_row">
                <th scope="row">
                    <label for="selected_users">Kullanıcı Seçimi</label>
                </th>
                <td>
                    <div class="pepech-user-multiselect">
                        <div class="pepech-user-controls">
                            <button type="button" id="select_all_users" class="button button-small">Tümünü Seç</button>
                            <button type="button" id="deselect_all_users" class="button button-small">Tümünü Kaldır</button>
                            <span class="pepech-selected-count">0 kullanıcı seçildi</span>
                        </div>
                        <select name="selected_users[]" id="selected_users" multiple="multiple" class="pepech-user-select2" style="width: 100%;">
                            <?php foreach ($users as $user): ?>
                                <option value="<?php echo $user->ID; ?>">
                                    <?php echo esc_html($user->display_name . ' (' . $user->user_email . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="title">Başlık</label>
                </th>
                <td>
                    <input type="text" name="title" id="title" class="regular-text" required />
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="message">Mesaj</label>
                </th>
                <td>
                    <?php
                    wp_editor('', 'message', array(
                        'textarea_name' => 'message',
                        'media_buttons' => false,
                        'textarea_rows' => 10,
                        'teeny' => true
                    ));
                    ?>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="notification_emoji">Bildirim Emoji</label>
                </th>
                <td style="position: relative;">
                    <input type="text" name="notification_emoji" id="notification_emoji" class="regular-text" placeholder="📢" style="font-size: 24px; text-align: center; width: 80px;" />
                    <p class="description">Bildirim başlığının yanında ve e-posta konusunda görünecek emoji. Örnek: 📢 ✅ ⚠️ ❌ 🎉 💡 🔔</p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="notification_thumbnail">Bildirim Thumbnail</label>
                </th>
                <td>
                    <div class="pepech-thumbnail-upload">
                        <div class="pepech-thumbnail-preview" id="thumbnail-preview" style="display: none;">
                            <img id="thumbnail-image" src="" alt="Thumbnail Preview" style="max-width: 200px; max-height: 150px; border-radius: 4px; margin-bottom: 10px;">
                            <button type="button" id="remove-thumbnail" class="button button-small">Thumbnail'ı Kaldır</button>
                        </div>
                        <div class="pepech-thumbnail-upload-area" id="thumbnail-upload-area">
                            <input type="file" name="notification_thumbnail" id="notification_thumbnail" accept="image/*" style="display: none;">
                            <button type="button" id="select-thumbnail" class="button">Thumbnail Seç</button>
                            <p class="description">Bildirim için görsel ekleyin (JPG, PNG, GIF - Max: 2MB)</p>
                        </div>
                        <input type="hidden" name="thumbnail_url" id="thumbnail_url" value="">
                    </div>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="send_email">E-posta Gönderimi</label>
                </th>
                <td>
                    <div class="pepech-email-option">
                        <label class="pepech-checkbox-label">
                            <input type="checkbox" name="send_email" id="send_email" value="1" checked />
                            <span class="pepech-checkbox-text">
                                <strong>Bu bildirimi e-posta olarak da gönder</strong>
                                <small>Seçili kullanıcılara bildirim içeriği e-posta ile de iletilecek</small>
                            </span>
                        </label>
                    </div>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="notification_link">Bildirim Linki</label>
                </th>
                <td>
                    <select name="notification_link" id="notification_link" class="pepech-link-select2" style="width: 100%;">
                        <option value="">Link Yok</option>
                        <optgroup label="Sayfalar">
                            <?php foreach ($pages as $page): ?>
                                <option value="<?php echo get_permalink($page->ID); ?>">
                                    <?php echo esc_html($page->post_title); ?>
                                </option>
                            <?php endforeach; ?>
                        </optgroup>
                        <?php if (!empty($products)): ?>
                        <optgroup label="Ürünler">
                            <?php foreach ($products as $product): ?>
                                <option value="<?php echo get_permalink($product->ID); ?>">
                                    <?php echo esc_html($product->post_title); ?>
                                </option>
                            <?php endforeach; ?>
                        </optgroup>
                        <?php endif; ?>
                        <option value="custom">Özel URL</option>
                    </select>
                </td>
            </tr>
            
            <tr id="custom_url_row" style="display: none;">
                <th scope="row">
                    <label for="custom_url">Özel URL</label>
                </th>
                <td>
                    <input type="url" name="custom_url" id="custom_url" class="regular-text" placeholder="https://example.com" />
                    <p class="description">Tam URL adresini girin (http:// veya https:// ile başlamalı)</p>
                </td>
            </tr>
            
            <tr id="link_text_row" style="display: none;">
                <th scope="row">
                    <label for="link_text">Link Metni</label>
                </th>
                <td>
                    <input type="text" name="link_text" id="link_text" class="regular-text" placeholder="Detayları Görüntüle" />
                    <p class="description">Link butonunda görünecek metin (boş bırakılırsa "Detayları Görüntüle" olur)</p>
                </td>
            </tr>
        </table>
        
        <?php submit_button('Bildirim Gönder', 'primary', 'send_notification'); ?>
        
        <!-- Gizli alanlar -->
        <input type="hidden" name="send_to_all" id="send_to_all_hidden" value="0" />
    </form>
    
    <hr>
    
    <h2>Son Gönderilen Bildirimler</h2>
    
    <?php
    global $wpdb;
    $table_name = $wpdb->prefix . 'pepech_notifications';
    
    $recent_notifications = $wpdb->get_results(
        "SELECT n.*, u.display_name, u.user_email 
         FROM $table_name n 
         LEFT JOIN {$wpdb->users} u ON n.user_id = u.ID 
         ORDER BY n.created_at DESC 
         LIMIT 10"
    );
    ?>
    
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th>Kullanıcı</th>
                <th>Başlık</th>
                <th>Tür</th>
                <th>E-posta</th>
                <th>Okundu</th>
                <th>Tarih</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($recent_notifications): ?>
                <?php foreach ($recent_notifications as $notification): ?>
                    <tr>
                        <td><?php echo esc_html($notification->display_name); ?></td>
                        <td><?php echo esc_html($notification->title); ?></td>
                        <td>
                            <span class="notification-type notification-type-<?php echo esc_attr($notification->type); ?>">
                                <?php echo esc_html(ucfirst($notification->type)); ?>
                            </span>
                        </td>
                        <td><?php echo $notification->send_email ? 'Evet' : 'Hayır'; ?></td>
                        <td><?php echo $notification->is_read ? 'Evet' : 'Hayır'; ?></td>
                        <td><?php echo date('d.m.Y H:i', strtotime($notification->created_at)); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6">Henüz bildirim gönderilmemiş.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
