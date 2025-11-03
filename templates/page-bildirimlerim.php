<?php
/**
 * Bildirimlerim sayfası için template
 */

if (!defined('ABSPATH')) {
    exit;
}

// Bu dosya WordPress tema template'i olarak kullanılacak
// Tema klasörüne page-bildirimlerim.php olarak kopyalanmalı

get_header(); ?>

<div class="container">
    <div class="row">
        <div class="col-12">
            <?php
            if (is_user_logged_in()) {
                // Bildirimlerim sayfası içeriğini göster
                echo do_shortcode('[pepech_notifications limit="20" show_read="true"]');
            } else {
                echo '<div class="alert alert-warning">Bildirimleri görüntülemek için giriş yapmalısınız.</div>';
            }
            ?>
        </div>
    </div>
</div>

<?php get_footer(); ?>
