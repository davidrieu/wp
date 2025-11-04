<?php
/**
 * Classe de gestion des shortcodes
 *
 * @package Teknup_AI_Mastering
 */

if (!defined('ABSPATH')) {
    exit;
}

class Teknup_Shortcodes {

    /**
     * Instance unique
     */
    private static $instance = null;

    /**
     * Obtenir l'instance unique
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructeur
     */
    private function __construct() {
        add_shortcode('teknup_app', array($this, 'app_shortcode'));
        add_shortcode('teknup_upload', array($this, 'upload_shortcode'));
        add_shortcode('teknup_dashboard', array($this, 'dashboard_shortcode'));
        add_shortcode('teknup_history', array($this, 'history_shortcode'));
        add_shortcode('teknup_debug', array($this, 'debug_shortcode'));
    }

    /**
     * Shortcode pour l'application complète avec navigation
     * Usage: [teknup_app] ou [teknup_app view="upload"]
     */
    public function app_shortcode($atts) {
        if (!is_user_logged_in()) {
            return $this->render_login_message();
        }

        // Parser les attributs
        $atts = shortcode_atts(array(
            'view' => 'dashboard', // dashboard, upload, ou history
        ), $atts);

        // Enqueue les scripts et styles
        $this->enqueue_app_assets();

        ob_start();
        include TEKNUP_PLUGIN_DIR . 'public/partials/app.php';
        return ob_get_clean();
    }

    /**
     * Enqueue les assets pour l'application
     */
    private function enqueue_app_assets() {
        // CSS
        wp_enqueue_style(
            'teknup-app',
            TEKNUP_PLUGIN_URL . 'assets/css/teknup-styles.css',
            array(),
            TEKNUP_VERSION
        );

        // React App (sera buildé par Webpack)
        wp_enqueue_script(
            'teknup-app',
            TEKNUP_PLUGIN_URL . 'public/js/app.bundle.js',
            array('wp-element'),
            TEKNUP_VERSION,
            true
        );

        // Localiser le script avec les données nécessaires
        wp_localize_script('teknup-app', 'teknupData', array(
            'apiUrl' => rest_url('teknup/v1'),
            'nonce' => wp_create_nonce('wp_rest'),
            'userId' => get_current_user_id(),
            'siteUrl' => home_url(),
            'uploadMaxSize' => get_option('teknup_max_file_size', 500),
            'allowedFormats' => Teknup_File_Manager::ALLOWED_FORMATS,
            'accountUrl' => wc_get_account_endpoint_url('dashboard'),
        ));
    }

    /**
     * Render message de connexion
     */
    private function render_login_message() {
        ob_start();
        ?>
        <div class="teknup-login-message">
            <div class="teknup-card">
                <h2 class="teknup-heading"><?php echo esc_html__('Connexion requise', 'teknup-ai-mastering'); ?></h2>
                <p><?php echo esc_html__('Vous devez être connecté pour accéder à Teknup AI Mastering.', 'teknup-ai-mastering'); ?></p>
                <a href="<?php echo esc_url(wp_login_url(get_permalink())); ?>" class="teknup-button">
                    <?php echo esc_html__('Se connecter', 'teknup-ai-mastering'); ?>
                </a>
                <?php if (get_option('users_can_register')) : ?>
                    <a href="<?php echo esc_url(wp_registration_url()); ?>" class="teknup-button" style="background: #666; margin-left: 10px;">
                        <?php echo esc_html__('Créer un compte', 'teknup-ai-mastering'); ?>
                    </a>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode pour l'upload
     */
    public function upload_shortcode($atts) {
        if (!is_user_logged_in()) {
            return $this->render_login_message();
        }

        $this->enqueue_app_assets();

        ob_start();
        include TEKNUP_PLUGIN_DIR . 'public/partials/upload.php';
        return ob_get_clean();
    }

    /**
     * Shortcode pour le dashboard
     */
    public function dashboard_shortcode($atts) {
        if (!is_user_logged_in()) {
            return $this->render_login_message();
        }

        $this->enqueue_app_assets();

        ob_start();
        include TEKNUP_PLUGIN_DIR . 'public/partials/dashboard.php';
        return ob_get_clean();
    }

    /**
     * Shortcode pour l'historique
     */
    public function history_shortcode($atts) {
        if (!is_user_logged_in()) {
            return $this->render_login_message();
        }

        $this->enqueue_app_assets();

        ob_start();
        include TEKNUP_PLUGIN_DIR . 'public/partials/history.php';
        return ob_get_clean();
    }

    /**
     * Shortcode de debug pour vérifier la configuration
     * Usage: [teknup_debug]
     */
    public function debug_shortcode($atts) {
        if (!current_user_can('manage_options')) {
            return '<div class="teknup-card"><p>Accès réservé aux administrateurs.</p></div>';
        }

        ob_start();
        ?>
        <div class="teknup-debug-page" style="background: #0a0a0a; color: #fff; padding: 30px; font-family: monospace;">
            <h1 style="color: #DC143C; margin-bottom: 20px;">🔧 Teknup Debug</h1>

            <div style="background: rgba(20,20,20,0.95); border: 1px solid rgba(220,20,60,0.3); border-radius: 8px; padding: 20px; margin-bottom: 20px;">
                <h2 style="color: #DC143C; margin-top: 0;">Produits WooCommerce</h2>
                <?php
                $product_ids = get_option('teknup_product_ids', array());
                if (empty($product_ids)) {
                    echo '<p style="color: #ff6b6b;">❌ Aucun produit trouvé. Allez dans Teknup > Réglages pour les créer.</p>';
                } else {
                    echo '<p style="color: #51cf66;">✅ ' . count($product_ids) . ' produits trouvés</p>';
                    echo '<ul style="list-style: none; padding: 0;">';
                    foreach ($product_ids as $slug => $product_id) {
                        $product = wc_get_product($product_id);
                        if ($product) {
                            echo '<li style="padding: 8px 0; border-bottom: 1px solid rgba(255,255,255,0.1);">';
                            echo '<strong style="color: #DC143C;">' . $slug . '</strong>: ';
                            echo $product->get_name() . ' (' . $product->get_price() . '€) - ';
                            echo '<a href="' . get_permalink($product_id) . '" target="_blank" style="color: #51cf66;">Voir</a>';
                            echo '</li>';
                        } else {
                            echo '<li style="padding: 8px 0; color: #ff6b6b;">' . $slug . ': Produit introuvable (ID: ' . $product_id . ')</li>';
                        }
                    }
                    echo '</ul>';
                }
                ?>
            </div>

            <div style="background: rgba(20,20,20,0.95); border: 1px solid rgba(220,20,60,0.3); border-radius: 8px; padding: 20px; margin-bottom: 20px;">
                <h2 style="color: #DC143C; margin-top: 0;">Test API REST /plans</h2>
                <?php
                if (is_user_logged_in()) {
                    $user_id = get_current_user_id();
                    $request = new WP_REST_Request('GET', '/teknup/v1/plans');
                    $request->set_header('X-WP-Nonce', wp_create_nonce('wp_rest'));

                    $rest_api = Teknup_REST_API::get_instance();
                    $response = $rest_api->get_available_plans($request);

                    if (is_wp_error($response)) {
                        echo '<p style="color: #ff6b6b;">❌ Erreur: ' . $response->get_error_message() . '</p>';
                    } else {
                        $data = $response->get_data();
                        echo '<pre style="background: #000; padding: 15px; border-radius: 4px; overflow-x: auto;">';
                        echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                        echo '</pre>';
                    }
                } else {
                    echo '<p style="color: #ff6b6b;">❌ Vous devez être connecté</p>';
                }
                ?>
            </div>

            <div style="background: rgba(20,20,20,0.95); border: 1px solid rgba(220,20,60,0.3); border-radius: 8px; padding: 20px; margin-bottom: 20px;">
                <h2 style="color: #DC143C; margin-top: 0;">Votre Abonnement</h2>
                <?php
                if (is_user_logged_in()) {
                    $user_id = get_current_user_id();
                    $subscription_manager = Teknup_Subscription_Manager::get_instance();
                    $sub_info = $subscription_manager->get_user_subscription_info($user_id);

                    echo '<pre style="background: #000; padding: 15px; border-radius: 4px; overflow-x: auto;">';
                    echo json_encode($sub_info, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                    echo '</pre>';
                } else {
                    echo '<p style="color: #ff6b6b;">❌ Vous devez être connecté</p>';
                }
                ?>
            </div>

            <div style="background: rgba(20,20,20,0.95); border: 1px solid rgba(220,20,60,0.3); border-radius: 8px; padding: 20px;">
                <h2 style="color: #DC143C; margin-top: 0;">Bundle JavaScript</h2>
                <?php
                $bundle_path = TEKNUP_PLUGIN_DIR . 'public/js/app.bundle.js';
                if (file_exists($bundle_path)) {
                    $size = filesize($bundle_path);
                    $date = date('Y-m-d H:i:s', filemtime($bundle_path));
                    echo '<p style="color: #51cf66;">✅ Bundle trouvé</p>';
                    echo '<ul style="list-style: none; padding: 0;">';
                    echo '<li>Taille: ' . number_format($size / 1024, 2) . ' KB</li>';
                    echo '<li>Modifié: ' . $date . '</li>';
                    echo '<li>URL: <a href="' . TEKNUP_PLUGIN_URL . 'public/js/app.bundle.js" target="_blank" style="color: #51cf66;">Ouvrir</a></li>';
                    echo '</ul>';
                } else {
                    echo '<p style="color: #ff6b6b;">❌ Bundle introuvable. Exécutez "npm run build".</p>';
                }
                ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}
