<?php
/**
 * Template des réglages admin
 *
 * @package Teknup_AI_Mastering
 */

if (!defined('ABSPATH')) {
    exit;
}

// Sauvegarder les options si le formulaire est soumis
if (isset($_POST['teknup_save_settings']) && check_admin_referer('teknup_settings')) {
    update_option('teknup_dolby_api_key', sanitize_text_field($_POST['teknup_dolby_api_key']));
    update_option('teknup_max_file_size', (int) $_POST['teknup_max_file_size']);
    update_option('teknup_default_intensity', sanitize_text_field($_POST['teknup_default_intensity']));
    update_option('teknup_default_lufs', (float) $_POST['teknup_default_lufs']);
    update_option('teknup_cleanup_days', (int) $_POST['teknup_cleanup_days']);
    update_option('teknup_debug_mode', isset($_POST['teknup_debug_mode']) ? 1 : 0);

    echo '<div class="notice notice-success"><p>' . esc_html__('Réglages sauvegardés.', 'teknup-ai-mastering') . '</p></div>';
}
?>

<div class="wrap teknup-admin-settings">
    <h1><?php echo esc_html__('Teknup AI Mastering - Réglages', 'teknup-ai-mastering'); ?></h1>

    <form method="post" action="">
        <?php wp_nonce_field('teknup_settings'); ?>

        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="teknup_dolby_api_key"><?php echo esc_html__('Clé API Dolby.io', 'teknup-ai-mastering'); ?></label>
                </th>
                <td>
                    <input type="password" id="teknup_dolby_api_key" name="teknup_dolby_api_key" value="<?php echo esc_attr(get_option('teknup_dolby_api_key')); ?>" class="regular-text">
                    <button type="button" id="teknup-test-api" class="button"><?php echo esc_html__('Tester la connexion', 'teknup-ai-mastering'); ?></button>
                    <p class="description"><?php echo esc_html__('Votre clé API Dolby.io Media Enhancement', 'teknup-ai-mastering'); ?></p>
                    <div id="teknup-api-test-result"></div>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="teknup_max_file_size"><?php echo esc_html__('Taille max de fichier (MB)', 'teknup-ai-mastering'); ?></label>
                </th>
                <td>
                    <input type="number" id="teknup_max_file_size" name="teknup_max_file_size" value="<?php echo esc_attr(get_option('teknup_max_file_size', 500)); ?>" min="1" max="1000">
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="teknup_default_intensity"><?php echo esc_html__('Intensité par défaut', 'teknup-ai-mastering'); ?></label>
                </th>
                <td>
                    <select id="teknup_default_intensity" name="teknup_default_intensity">
                        <option value="low" <?php selected(get_option('teknup_default_intensity', 'medium'), 'low'); ?>>Low</option>
                        <option value="medium" <?php selected(get_option('teknup_default_intensity', 'medium'), 'medium'); ?>>Medium</option>
                        <option value="high" <?php selected(get_option('teknup_default_intensity', 'medium'), 'high'); ?>>High</option>
                    </select>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="teknup_default_lufs"><?php echo esc_html__('LUFS cible par défaut', 'teknup-ai-mastering'); ?></label>
                </th>
                <td>
                    <input type="number" id="teknup_default_lufs" name="teknup_default_lufs" value="<?php echo esc_attr(get_option('teknup_default_lufs', -14)); ?>" step="0.1" min="-30" max="0">
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="teknup_cleanup_days"><?php echo esc_html__('Nettoyage après (jours)', 'teknup-ai-mastering'); ?></label>
                </th>
                <td>
                    <input type="number" id="teknup_cleanup_days" name="teknup_cleanup_days" value="<?php echo esc_attr(get_option('teknup_cleanup_days', 30)); ?>" min="1" max="365">
                    <p class="description"><?php echo esc_html__('Les fichiers et jobs seront supprimés après ce délai', 'teknup-ai-mastering'); ?></p>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="teknup_debug_mode"><?php echo esc_html__('Mode Debug', 'teknup-ai-mastering'); ?></label>
                </th>
                <td>
                    <input type="checkbox" id="teknup_debug_mode" name="teknup_debug_mode" value="1" <?php checked(get_option('teknup_debug_mode', false), 1); ?>>
                    <label for="teknup_debug_mode"><?php echo esc_html__('Activer les logs détaillés', 'teknup-ai-mastering'); ?></label>
                </td>
            </tr>
        </table>

        <p class="submit">
            <input type="submit" name="teknup_save_settings" class="button button-primary" value="<?php echo esc_attr__('Enregistrer les modifications', 'teknup-ai-mastering'); ?>">
        </p>
    </form>

    <hr style="margin: 40px 0;">

    <h2><?php echo esc_html__('Produits WooCommerce', 'teknup-ai-mastering'); ?></h2>

    <?php
    $product_ids = get_option('teknup_product_ids', array());
    $products_created = get_option('teknup_products_created', false);
    ?>

    <?php if ($products_created && !empty($product_ids)) : ?>
        <div class="notice notice-success inline">
            <p>
                <strong>✓ Produits créés :</strong>
                <?php echo count($product_ids); ?> produits d'abonnement Teknup sont actifs.
                <a href="<?php echo admin_url('edit.php?post_type=product&product_cat=teknup-subscriptions'); ?>">Voir les produits</a>
            </p>
        </div>
    <?php else : ?>
        <div class="notice notice-warning inline">
            <p>
                <strong>⚠ Aucun produit détecté.</strong>
                Les produits d'abonnement n'ont pas été créés automatiquement lors de l'activation.
            </p>
        </div>
    <?php endif; ?>

    <p>
        <button type="button" id="teknup-create-products" class="button button-secondary">
            <?php echo $products_created ? '🔄 Recréer les produits' : '✨ Créer les 4 produits d\'abonnement'; ?>
        </button>
        <span id="teknup-products-result" style="margin-left: 15px;"></span>
    </p>

    <p class="description">
        <?php echo esc_html__('Crée automatiquement les 4 produits d\'abonnement Teknup : Free Trial (0€), Starter (19€/mois), Pro (39€/mois), et Label (99€/mois).', 'teknup-ai-mastering'); ?>
    </p>

    <?php if (class_exists('WooCommerce')) : ?>
        <p class="description" style="color: green;">
            ✓ WooCommerce est activé
            <?php if (class_exists('WC_Subscriptions')) : ?>
                | ✓ WooCommerce Subscriptions est activé (produits de type subscription)
            <?php else : ?>
                | ⚠ WooCommerce Subscriptions non détecté (produits simples seront créés)
            <?php endif; ?>
        </p>
    <?php else : ?>
        <p class="description" style="color: red;">
            ✗ WooCommerce doit être activé pour créer les produits
        </p>
    <?php endif; ?>

</div>

<script>
jQuery(document).ready(function($) {
    $('#teknup-test-api').on('click', function() {
        const button = $(this);
        const result = $('#teknup-api-test-result');

        button.prop('disabled', true).text('Test en cours...');
        result.html('');

        $.ajax({
            url: teknupAdmin.ajaxUrl,
            type: 'POST',
            data: {
                action: 'teknup_test_api',
                nonce: teknupAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    result.html('<p style="color: green;">✓ ' + response.data.message + '</p>');
                } else {
                    result.html('<p style="color: red;">✗ ' + response.data.message + '</p>');
                }
            },
            error: function() {
                result.html('<p style="color: red;">✗ Erreur de connexion</p>');
            },
            complete: function() {
                button.prop('disabled', false).text('Tester la connexion');
            }
        });
    });

    $('#teknup-create-products').on('click', function() {
        const button = $(this);
        const result = $('#teknup-products-result');

        if (!confirm('Voulez-vous créer les 4 produits d\'abonnement Teknup ?\n\n- Teknup Free Trial (0€)\n- Teknup Starter (19€/mois)\n- Teknup Pro (39€/mois)\n- Teknup Label (99€/mois)')) {
            return;
        }

        button.prop('disabled', true).text('Création en cours...');
        result.html('<span style="color: #666;">Veuillez patienter...</span>');

        $.ajax({
            url: teknupAdmin.ajaxUrl,
            type: 'POST',
            data: {
                action: 'teknup_create_products',
                nonce: teknupAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    result.html('<span style="color: green; font-weight: bold;">' + response.data.message + '</span>');
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                } else {
                    result.html('<span style="color: red;">✗ ' + response.data.message + '</span>');
                }
            },
            error: function() {
                result.html('<span style="color: red;">✗ Erreur de connexion</span>');
            },
            complete: function() {
                button.prop('disabled', false).text('✨ Créer les 4 produits d\'abonnement');
            }
        });
    });
});
</script>
