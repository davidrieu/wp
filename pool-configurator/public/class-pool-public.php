<?php
/**
 * Gestion de l'interface publique
 */

if (!defined('ABSPATH')) {
    exit;
}

class Pool_Public {

    public static function init() {
        add_shortcode('pool_configurator', array(__CLASS__, 'render_configurator'));
        add_action('wp_enqueue_scripts', array(__CLASS__, 'enqueue_public_scripts'));
        add_action('wp_ajax_get_pool_data', array(__CLASS__, 'ajax_get_pool_data'));
        add_action('wp_ajax_nopriv_get_pool_data', array(__CLASS__, 'ajax_get_pool_data'));
        add_action('wp_ajax_submit_pool_configuration', array(__CLASS__, 'ajax_submit_configuration'));
        add_action('wp_ajax_nopriv_submit_pool_configuration', array(__CLASS__, 'ajax_submit_configuration'));
    }

    public static function enqueue_public_scripts() {
        if (has_shortcode(get_post()->post_content ?? '', 'pool_configurator')) {
            wp_enqueue_style('pool-public-css', POOL_CONFIGURATOR_PLUGIN_URL . 'public/css/public.css', array(), POOL_CONFIGURATOR_VERSION);
            wp_enqueue_script('pool-public-js', POOL_CONFIGURATOR_PLUGIN_URL . 'public/js/public.js', array('jquery'), POOL_CONFIGURATOR_VERSION, true);

            wp_localize_script('pool-public-js', 'poolConfig', array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('pool_configurator_nonce')
            ));
        }
    }

    public static function render_configurator($atts) {
        ob_start();
        ?>
        <div id="pool-configurator" class="pool-configurator">
            <div class="pool-configurator-progress">
                <div class="progress-bar">
                    <div class="progress-fill" style="width: 0%;"></div>
                </div>
                <div class="progress-text">
                    <span class="current-step">1</span> / <span class="total-steps">3</span>
                </div>
            </div>

            <div class="pool-configurator-content">
                <!-- Étape 1: Choix de la taille -->
                <div class="pool-step active" data-step="1">
                    <div class="step-header">
                        <h2 class="step-title">Choisissez la taille de votre piscine</h2>
                        <p class="step-subtitle">Sélectionnez les dimensions idéales pour votre jardin</p>
                    </div>
                    <div class="pool-sizes-grid" id="pool-sizes-container">
                        <div class="loading-spinner">
                            <div class="spinner"></div>
                            <p>Chargement des tailles disponibles...</p>
                        </div>
                    </div>
                </div>

                <!-- Étape 2: Choix des options -->
                <div class="pool-step" data-step="2">
                    <div class="step-header">
                        <h2 class="step-title">Personnalisez votre piscine</h2>
                        <p class="step-subtitle">Ajoutez des options pour rendre votre piscine unique</p>
                    </div>
                    <div class="pool-options-grid" id="pool-options-container">
                        <div class="loading-spinner">
                            <div class="spinner"></div>
                            <p>Chargement des options...</p>
                        </div>
                    </div>
                </div>

                <!-- Étape 3: Récapitulatif -->
                <div class="pool-step" data-step="3">
                    <div class="step-header">
                        <h2 class="step-title">Récapitulatif de votre configuration</h2>
                        <p class="step-subtitle">Vérifiez votre sélection avant de continuer</p>
                    </div>
                    <div class="pool-summary" id="pool-summary-container">
                        <!-- Le récapitulatif sera inséré ici -->
                    </div>

                    <div class="pool-contact-form">
                        <h3>Vos coordonnées</h3>
                        <form id="pool-contact-form">
                            <div class="form-group">
                                <label for="customer-name">Nom complet *</label>
                                <input type="text" id="customer-name" name="name" required>
                            </div>
                            <div class="form-group">
                                <label for="customer-email">Email *</label>
                                <input type="email" id="customer-email" name="email" required>
                            </div>
                            <div class="form-group">
                                <label for="customer-phone">Téléphone *</label>
                                <input type="tel" id="customer-phone" name="phone" required>
                            </div>
                            <div class="form-group">
                                <label for="customer-message">Message (optionnel)</label>
                                <textarea id="customer-message" name="message" rows="4"></textarea>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="pool-configurator-footer">
                <div class="pool-price-display">
                    <div class="price-label">Prix total</div>
                    <div class="price-amount">
                        <span class="currency">€</span>
                        <span class="price-value" id="total-price">0</span>
                    </div>
                </div>

                <div class="pool-navigation">
                    <button type="button" class="pool-btn pool-btn-secondary" id="prev-step" style="display: none;">
                        ← Précédent
                    </button>
                    <button type="button" class="pool-btn pool-btn-primary" id="next-step">
                        Suivant →
                    </button>
                    <button type="button" class="pool-btn pool-btn-primary" id="submit-configuration" style="display: none;">
                        Envoyer ma demande
                    </button>
                </div>
            </div>
        </div>

        <div id="pool-success-modal" class="pool-modal" style="display: none;">
            <div class="pool-modal-content">
                <div class="success-icon">✓</div>
                <h2>Merci !</h2>
                <p>Votre demande a été envoyée avec succès. Nous vous contacterons rapidement.</p>
                <button type="button" class="pool-btn pool-btn-primary" onclick="location.reload()">
                    Nouvelle configuration
                </button>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    public static function ajax_get_pool_data() {
        check_ajax_referer('pool_configurator_nonce', 'nonce');

        // Récupérer les tailles de piscine
        $pool_sizes = get_posts(array(
            'post_type' => 'pool_size',
            'posts_per_page' => -1,
            'orderby' => 'menu_order',
            'order' => 'ASC'
        ));

        $sizes_data = array();
        foreach ($pool_sizes as $size) {
            $products = get_post_meta($size->ID, '_pool_products', true);
            $total_price = floatval(get_post_meta($size->ID, '_pool_base_price', true));

            // Calculer le prix total avec les produits
            if (is_array($products)) {
                foreach ($products as $product) {
                    $total_price += floatval($product['price']);
                }
            }

            $sizes_data[] = array(
                'id' => $size->ID,
                'title' => $size->post_title,
                'dimensions' => get_post_meta($size->ID, '_pool_dimensions', true),
                'description' => get_post_meta($size->ID, '_pool_description', true),
                'base_price' => floatval(get_post_meta($size->ID, '_pool_base_price', true)),
                'total_price' => $total_price,
                'products' => $products,
                'thumbnail' => get_the_post_thumbnail_url($size->ID, 'medium')
            );
        }

        // Récupérer les options
        $pool_options = get_posts(array(
            'post_type' => 'pool_option',
            'posts_per_page' => -1,
            'orderby' => 'menu_order',
            'order' => 'ASC'
        ));

        $options_data = array();
        foreach ($pool_options as $option) {
            $options_data[] = array(
                'id' => $option->ID,
                'title' => $option->post_title,
                'description' => get_post_meta($option->ID, '_pool_option_description', true),
                'price' => floatval(get_post_meta($option->ID, '_pool_option_price', true)),
                'icon' => get_post_meta($option->ID, '_pool_option_icon', true),
                'thumbnail' => get_the_post_thumbnail_url($option->ID, 'medium'),
                'content' => apply_filters('the_content', $option->post_content)
            );
        }

        wp_send_json_success(array(
            'sizes' => $sizes_data,
            'options' => $options_data
        ));
    }

    public static function ajax_submit_configuration() {
        check_ajax_referer('pool_configurator_nonce', 'nonce');

        $config_data = isset($_POST['configuration']) ? json_decode(stripslashes($_POST['configuration']), true) : array();
        $customer_data = isset($_POST['customer']) ? $_POST['customer'] : array();

        // Validation
        if (empty($config_data['size_id']) || empty($customer_data['name']) || empty($customer_data['email'])) {
            wp_send_json_error(array('message' => 'Données incomplètes'));
        }

        // Créer un post pour stocker la configuration
        $post_data = array(
            'post_title' => 'Configuration - ' . sanitize_text_field($customer_data['name']),
            'post_type' => 'pool_configuration',
            'post_status' => 'private',
            'meta_input' => array(
                '_config_data' => $config_data,
                '_customer_name' => sanitize_text_field($customer_data['name']),
                '_customer_email' => sanitize_email($customer_data['email']),
                '_customer_phone' => sanitize_text_field($customer_data['phone']),
                '_customer_message' => sanitize_textarea_field($customer_data['message']),
                '_config_date' => current_time('mysql')
            )
        );

        // Si le CPT pool_configuration n'existe pas encore, on le crée
        if (!post_type_exists('pool_configuration')) {
            register_post_type('pool_configuration', array(
                'labels' => array('name' => 'Configurations'),
                'public' => false,
                'show_ui' => true,
                'show_in_menu' => 'edit.php?post_type=pool_size',
                'capability_type' => 'post'
            ));
        }

        $config_id = wp_insert_post($post_data);

        if ($config_id) {
            // Envoyer un email de notification (optionnel)
            $admin_email = get_option('admin_email');
            $subject = 'Nouvelle configuration de piscine';
            $message = "Nouvelle demande de configuration de piscine.\n\n";
            $message .= "Client: " . $customer_data['name'] . "\n";
            $message .= "Email: " . $customer_data['email'] . "\n";
            $message .= "Téléphone: " . $customer_data['phone'] . "\n";
            $message .= "Prix total: " . $config_data['total_price'] . "€\n";

            wp_mail($admin_email, $subject, $message);

            wp_send_json_success(array('message' => 'Configuration enregistrée avec succès'));
        } else {
            wp_send_json_error(array('message' => 'Erreur lors de l\'enregistrement'));
        }
    }
}
