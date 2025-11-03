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
        add_action('wp_ajax_save_pool_lead', array(__CLASS__, 'ajax_save_pool_lead'));
        add_action('wp_ajax_nopriv_save_pool_lead', array(__CLASS__, 'ajax_save_pool_lead'));
        add_action('wp_ajax_submit_pool_configuration', array(__CLASS__, 'ajax_submit_configuration'));
        add_action('wp_ajax_nopriv_submit_pool_configuration', array(__CLASS__, 'ajax_submit_configuration'));
    }

    public static function enqueue_public_scripts() {
        // Vérification plus robuste du shortcode
        global $post;
        $has_shortcode = false;

        if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'pool_configurator')) {
            $has_shortcode = true;
        }

        // Aussi vérifier dans le contenu si on est sur une page/post
        if (!$has_shortcode && is_singular()) {
            $content = get_the_content();
            if ($content && has_shortcode($content, 'pool_configurator')) {
                $has_shortcode = true;
            }
        }

        if ($has_shortcode) {
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
        <div id="pool-configurator" class="pool-configurator pool-fullscreen">
            <!-- Logo Poolkit -->
            <div class="pool-logo">
                <img src="https://www.poolkit.clickdev.website/wp-content/uploads/2025/11/PK_WEB_Sans-Baseline.png" alt="Poolkit" />
            </div>

            <div class="pool-configurator-progress">
                <div class="progress-bar">
                    <div class="progress-fill" style="width: 0%;"></div>
                </div>
                <div class="progress-text">
                    <span class="current-step">1</span> / <span class="total-steps">4</span>
                </div>
            </div>

            <div class="pool-configurator-content">
                <!-- Étape 0: Collecte des coordonnées -->
                <div class="pool-step active" data-step="0">
                    <div class="step-header">
                        <div class="poolkit-avatar">
                            <span class="avatar-emoji">👋</span>
                        </div>
                        <h2 class="step-title typewriter" data-text="Bonjour ! Je suis Poolkit"></h2>
                        <p class="step-subtitle typewriter" data-text="Je vais vous accompagner pour créer la piscine de vos rêves. Avant de commencer, laissez-moi vos coordonnées pour personnaliser votre expérience." data-delay="2000"></p>
                    </div>

                    <div class="pool-lead-form">
                        <div class="form-group">
                            <label for="lead-email">Votre email *</label>
                            <input type="email" id="lead-email" name="lead-email" required placeholder="exemple@email.com">
                        </div>
                        <div class="form-group">
                            <label for="lead-phone">Votre téléphone *</label>
                            <input type="tel" id="lead-phone" name="lead-phone" required placeholder="06 12 34 56 78">
                        </div>
                        <p class="privacy-note">
                            🔒 Vos données sont sécurisées et ne seront jamais partagées.
                        </p>
                    </div>
                </div>

                <!-- Étape 1: Choix de la taille -->
                <div class="pool-step" data-step="1">
                    <div class="step-header">
                        <div class="poolkit-avatar">
                            <span class="avatar-emoji">📏</span>
                        </div>
                        <h2 class="step-title typewriter" data-text="Parfait ! Quelle taille de piscine vous ferait plaisir ?"></h2>
                        <p class="step-subtitle typewriter" data-text="Choisissez les dimensions qui correspondent à votre jardin. Tous nos kits sont 100% personnalisables et livrés en 3-10 jours." data-delay="2000"></p>
                    </div>
                    <div class="pool-sizes-grid" id="pool-sizes-container">
                        <div class="loading-spinner">
                            <div class="spinner"></div>
                            <p>Je prépare vos options...</p>
                        </div>
                    </div>
                </div>

                <!-- Étape 2: Choix des options -->
                <div class="pool-step" data-step="2">
                    <div class="step-header">
                        <div class="poolkit-avatar">
                            <span class="avatar-emoji">✨</span>
                        </div>
                        <h2 class="step-title typewriter" data-text="Super choix ! Envie d'ajouter des options ?"></h2>
                        <p class="step-subtitle typewriter" data-text="Nos options vous permettent de personnaliser votre piscine selon vos envies. C'est facultatif, mais ça peut faire toute la différence !" data-delay="2000"></p>
                    </div>
                    <div class="pool-options-grid" id="pool-options-container">
                        <div class="loading-spinner">
                            <div class="spinner"></div>
                            <p>Je charge les options disponibles...</p>
                        </div>
                    </div>
                </div>

                <!-- Étape 3: Récapitulatif -->
                <div class="pool-step" data-step="3">
                    <div class="step-header">
                        <div class="poolkit-avatar">
                            <span class="avatar-emoji">🎉</span>
                        </div>
                        <h2 class="step-title typewriter" data-text="Génial ! Voici votre piscine personnalisée"></h2>
                        <p class="step-subtitle typewriter" data-text="J'ai préparé un récapitulatif de votre configuration. Vous économisez 60% par rapport aux solutions traditionnelles !" data-delay="2000"></p>
                    </div>
                    <div class="pool-summary" id="pool-summary-container">
                        <!-- Le récapitulatif sera inséré ici -->
                    </div>

                    <div class="pool-contact-form">
                        <h3>Un dernier détail...</h3>
                        <p class="form-intro">Pour finaliser votre commande, j'ai besoin de votre nom et d'un message éventuel :</p>
                        <form id="pool-contact-form">
                            <div class="form-group">
                                <label for="customer-name">Votre nom complet *</label>
                                <input type="text" id="customer-name" name="name" required placeholder="Jean Dupont">
                            </div>
                            <div class="form-group">
                                <label for="customer-message">Une question ou un message ? (optionnel)</label>
                                <textarea id="customer-message" name="message" rows="3" placeholder="Besoin d'un conseil pour l'installation ? Posez votre question ici !"></textarea>
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
                        🛒 Ajouter au panier
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
        try {
            // Vérification du nonce moins stricte pour permettre aux invités d'accéder
            $nonce_check = check_ajax_referer('pool_configurator_nonce', 'nonce', false);

            if (!$nonce_check) {
                // Pour les invités, on accepte quand même la requête mais on log l'événement
                error_log('Pool Configurator: Nonce verification failed for guest user in ajax_get_pool_data');
            }

            // Log pour debugging
            error_log('Pool Configurator: ajax_get_pool_data called');

            // Récupérer les tailles de piscine
        $pool_sizes = get_posts(array(
            'post_type' => 'pool_size',
            'posts_per_page' => -1,
            'orderby' => 'menu_order',
            'order' => 'ASC'
        ));

        $sizes_data = array();
        foreach ($pool_sizes as $size) {
            $wc_product_ids = get_post_meta($size->ID, '_pool_wc_products', true);
            $base_price = floatval(get_post_meta($size->ID, '_pool_base_price', true));
            $total_price = $base_price;
            $products = array();

            // Récupérer les détails des produits WooCommerce
            if (is_array($wc_product_ids) && !empty($wc_product_ids)) {
                foreach ($wc_product_ids as $product_id) {
                    $wc_product = wc_get_product($product_id);
                    if ($wc_product) {
                        $product_price = floatval($wc_product->get_price());
                        $products[] = array(
                            'id' => $product_id,
                            'name' => $wc_product->get_name(),
                            'price' => $product_price,
                            'description' => $wc_product->get_short_description()
                        );
                        $total_price += $product_price;
                    }
                }
            }

            // Récupérer les options compatibles
            $compatible_options = get_post_meta($size->ID, '_pool_compatible_options', true);
            if (!is_array($compatible_options)) {
                $compatible_options = array();
            }

            $sizes_data[] = array(
                'id' => $size->ID,
                'title' => $size->post_title,
                'dimensions' => get_post_meta($size->ID, '_pool_dimensions', true),
                'description' => get_post_meta($size->ID, '_pool_description', true),
                'base_price' => $base_price,
                'total_price' => $total_price,
                'products' => $products,
                'compatible_options' => $compatible_options,
                'thumbnail' => get_the_post_thumbnail_url($size->ID, 'medium')
            );
        }

        // Récupérer toutes les options
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
        } catch (Exception $e) {
            error_log('Pool Configurator Error in ajax_get_pool_data: ' . $e->getMessage());
            wp_send_json_error(array(
                'message' => 'Erreur serveur: ' . $e->getMessage()
            ));
        }
    }

    public static function ajax_save_pool_lead() {
        // Vérification du nonce moins stricte pour permettre aux invités d'accéder
        $nonce_check = check_ajax_referer('pool_configurator_nonce', 'nonce', false);

        if (!$nonce_check) {
            // Pour les invités, on accepte quand même la requête mais on log l'événement
            error_log('Pool Configurator: Nonce verification failed for guest user in ajax_save_pool_lead');
        }

        $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
        $phone = isset($_POST['phone']) ? sanitize_text_field($_POST['phone']) : '';

        // Validation
        if (empty($email) || !is_email($email)) {
            wp_send_json_error(array('message' => 'Veuillez fournir une adresse email valide'));
        }

        if (empty($phone)) {
            wp_send_json_error(array('message' => 'Veuillez fournir un numéro de téléphone'));
        }

        // Permettre temporairement aux utilisateurs non connectés de créer des posts
        $current_user_id = get_current_user_id();
        $is_guest = ($current_user_id === 0);

        if ($is_guest) {
            // Temporairement, donner les permissions nécessaires
            add_filter('user_has_cap', array(__CLASS__, 'grant_guest_post_cap'), 10, 3);
        }

        // Enregistrer le lead dans un Custom Post Type
        $lead_data = array(
            'post_title' => 'Lead - ' . $email,
            'post_type' => 'pool_lead',
            'post_status' => 'private',
            'post_author' => $is_guest ? 1 : $current_user_id, // Utiliser l'admin (ID=1) pour les invités
            'meta_input' => array(
                '_lead_email' => $email,
                '_lead_phone' => $phone,
                '_lead_date' => current_time('mysql'),
                '_lead_converted' => 'no'
            )
        );

        $lead_id = wp_insert_post($lead_data);

        // Retirer le filtre
        if ($is_guest) {
            remove_filter('user_has_cap', array(__CLASS__, 'grant_guest_post_cap'), 10);
        }

        if ($lead_id && !is_wp_error($lead_id)) {
            // S'assurer que WooCommerce est chargé et que la session est initialisée
            if (class_exists('WooCommerce') && WC()->session) {
                // Stocker dans la session WooCommerce pour l'utiliser plus tard
                WC()->session->set('pool_lead_id', $lead_id);
                WC()->session->set('pool_lead_email', $email);
                WC()->session->set('pool_lead_phone', $phone);
            }

            // Stocker aussi dans un cookie comme fallback (expiration: 1 heure)
            setcookie('pool_lead_email', $email, time() + 3600, '/');
            setcookie('pool_lead_phone', $phone, time() + 3600, '/');

            wp_send_json_success(array(
                'message' => 'Coordonnées enregistrées avec succès',
                'lead_id' => $lead_id
            ));
        } else {
            wp_send_json_error(array('message' => 'Erreur lors de l\'enregistrement'));
        }
    }

    /**
     * Accorde temporairement les permissions nécessaires aux invités pour créer des leads et des produits
     */
    public static function grant_guest_post_cap($allcaps, $caps, $args) {
        // Permettre la création de posts, produits et termes de taxonomie
        $allowed_caps = array('edit_posts', 'edit_products', 'publish_posts', 'publish_products', 'manage_product_terms', 'edit_product');

        if (isset($args[0]) && in_array($args[0], $allowed_caps)) {
            $allcaps[$args[0]] = true;
        }

        // Capacités générales pour la création de contenu
        $allcaps['edit_posts'] = true;
        $allcaps['edit_products'] = true;
        $allcaps['publish_posts'] = true;
        $allcaps['publish_products'] = true;

        return $allcaps;
    }

    public static function ajax_submit_configuration() {
        // Vérification du nonce moins stricte pour permettre aux invités d'accéder
        $nonce_check = check_ajax_referer('pool_configurator_nonce', 'nonce', false);

        if (!$nonce_check) {
            // Pour les invités, on accepte quand même la requête mais on log l'événement
            error_log('Pool Configurator: Nonce verification failed for guest user in ajax_submit_configuration');
        }

        $config_data = isset($_POST['configuration']) ? json_decode(stripslashes($_POST['configuration']), true) : array();
        $customer_data = isset($_POST['customer']) ? $_POST['customer'] : array();

        // Validation
        if (empty($config_data['size_id'])) {
            wp_send_json_error(array('message' => 'Veuillez sélectionner une taille de piscine'));
        }

        // Vérifier que WooCommerce est chargé
        if (!class_exists('WooCommerce')) {
            wp_send_json_error(array('message' => 'WooCommerce n\'est pas activé'));
        }

        // S'assurer que WooCommerce est complètement initialisé
        if (is_null(WC()->cart) || is_null(WC()->session)) {
            wp_send_json_error(array('message' => 'La session WooCommerce n\'est pas initialisée. Veuillez recharger la page.'));
        }

        // Permettre temporairement aux utilisateurs non connectés de créer des produits
        $current_user_id = get_current_user_id();
        $is_guest = ($current_user_id === 0);

        if ($is_guest) {
            add_filter('user_has_cap', array(__CLASS__, 'grant_guest_post_cap'), 10, 3);
        }

        // Vider le panier avant d'ajouter la nouvelle configuration
        WC()->cart->empty_cart();

        // Récupérer les informations de la taille sélectionnée
        $size_id = intval($config_data['size_id']);
        $size = get_post($size_id);

        if (!$size) {
            wp_send_json_error(array('message' => 'Taille de piscine introuvable'));
        }

        $base_price = floatval(get_post_meta($size_id, '_pool_base_price', true));
        $wc_product_ids = get_post_meta($size_id, '_pool_wc_products', true);

        // Ajouter le "produit" de base (la piscine) comme produit personnalisé
        // Créer un produit WooCommerce personnalisé pour la configuration
        $pool_product_data = array(
            'post_title' => 'Configuration Piscine - ' . $size->post_title,
            'post_type' => 'product',
            'post_status' => 'publish',
            'post_author' => $is_guest ? 1 : $current_user_id
        );

        $pool_product_id = wp_insert_post($pool_product_data);

        if ($pool_product_id) {
            // Définir le type et le prix du produit
            wp_set_object_terms($pool_product_id, 'simple', 'product_type');
            update_post_meta($pool_product_id, '_price', $base_price);
            update_post_meta($pool_product_id, '_regular_price', $base_price);
            update_post_meta($pool_product_id, '_virtual', 'yes');
            update_post_meta($pool_product_id, '_sold_individually', 'yes');

            // Stocker les informations de configuration
            update_post_meta($pool_product_id, '_pool_configuration_data', $config_data);
            update_post_meta($pool_product_id, '_is_pool_configuration', 'yes');

            // Ajouter le produit de base au panier
            $cart_item_data = array(
                'pool_size_id' => $size_id,
                'pool_size_title' => $size->post_title,
                'pool_dimensions' => get_post_meta($size_id, '_pool_dimensions', true),
                'configuration_id' => $pool_product_id
            );

            WC()->cart->add_to_cart($pool_product_id, 1, 0, array(), $cart_item_data);
        }

        // Ajouter les produits WooCommerce inclus
        if (is_array($wc_product_ids) && !empty($wc_product_ids)) {
            foreach ($wc_product_ids as $product_id) {
                $product = wc_get_product($product_id);
                if ($product) {
                    $cart_item_data = array(
                        'pool_configuration' => true,
                        'pool_size_id' => $size_id,
                        'included_in_pool' => true
                    );
                    WC()->cart->add_to_cart($product_id, 1, 0, array(), $cart_item_data);
                }
            }
        }

        // Ajouter les options sélectionnées
        if (isset($config_data['options']) && is_array($config_data['options'])) {
            foreach ($config_data['options'] as $option_id) {
                $option = get_post($option_id);
                if ($option) {
                    $option_price = floatval(get_post_meta($option_id, '_pool_option_price', true));

                    // Créer un produit pour l'option
                    $option_product_data = array(
                        'post_title' => 'Option - ' . $option->post_title,
                        'post_type' => 'product',
                        'post_status' => 'publish',
                        'post_author' => $is_guest ? 1 : $current_user_id
                    );

                    $option_product_id = wp_insert_post($option_product_data);

                    if ($option_product_id) {
                        wp_set_object_terms($option_product_id, 'simple', 'product_type');
                        update_post_meta($option_product_id, '_price', $option_price);
                        update_post_meta($option_product_id, '_regular_price', $option_price);
                        update_post_meta($option_product_id, '_virtual', 'yes');
                        update_post_meta($option_product_id, '_sold_individually', 'yes');
                        update_post_meta($option_product_id, '_is_pool_option', 'yes');

                        $cart_item_data = array(
                            'pool_configuration' => true,
                            'pool_size_id' => $size_id,
                            'pool_option_id' => $option_id
                        );

                        WC()->cart->add_to_cart($option_product_id, 1, 0, array(), $cart_item_data);
                    }
                }
            }
        }

        // Stocker les informations client dans la session
        if (WC()->session) {
            if (!empty($customer_data['name'])) {
                WC()->session->set('pool_customer_name', sanitize_text_field($customer_data['name']));
            }
            if (!empty($customer_data['email'])) {
                WC()->session->set('pool_customer_email', sanitize_email($customer_data['email']));
            }
            if (!empty($customer_data['phone'])) {
                WC()->session->set('pool_customer_phone', sanitize_text_field($customer_data['phone']));
            }
            if (!empty($customer_data['message'])) {
                WC()->session->set('pool_customer_message', sanitize_textarea_field($customer_data['message']));
            }
        }

        // Enregistrer aussi dans la configuration pour l'historique
        $post_data = array(
            'post_title' => 'Configuration - ' . (!empty($customer_data['name']) ? sanitize_text_field($customer_data['name']) : 'Client'),
            'post_type' => 'pool_configuration',
            'post_status' => 'private',
            'post_author' => $is_guest ? 1 : $current_user_id,
            'meta_input' => array(
                '_config_data' => $config_data,
                '_customer_name' => sanitize_text_field($customer_data['name'] ?? ''),
                '_customer_email' => sanitize_email($customer_data['email'] ?? ''),
                '_customer_phone' => sanitize_text_field($customer_data['phone'] ?? ''),
                '_customer_message' => sanitize_textarea_field($customer_data['message'] ?? ''),
                '_config_date' => current_time('mysql')
            )
        );

        wp_insert_post($post_data);

        // Retirer le filtre
        if ($is_guest) {
            remove_filter('user_has_cap', array(__CLASS__, 'grant_guest_post_cap'), 10);
        }

        wp_send_json_success(array(
            'message' => 'Configuration ajoutée au panier avec succès',
            'cart_url' => wc_get_cart_url()
        ));
    }
}
