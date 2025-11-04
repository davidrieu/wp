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
}
