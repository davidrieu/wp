<?php
/**
 * Plugin Name: Teknup AI Mastering
 * Plugin URI: https://teknup.com
 * Description: Service SaaS de mastering audio professionnel propulsé par l'IA Dolby.io pour producteurs de musique électronique
 * Version: 1.0.0
 * Author: Teknup
 * Author URI: https://teknup.com
 * License: GPL v2 or later
 * Text Domain: teknup-ai-mastering
 * Requires at least: 6.0
 * Requires PHP: 8.0
 * WC requires at least: 7.0
 * WC tested up to: 8.5
 */

// Empêcher l'accès direct
if (!defined('ABSPATH')) {
    exit;
}

// Définir les constantes du plugin
define('TEKNUP_VERSION', '1.0.0');
define('TEKNUP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('TEKNUP_PLUGIN_URL', plugin_dir_url(__FILE__));
define('TEKNUP_PLUGIN_BASENAME', plugin_basename(__FILE__));
define('TEKNUP_STORAGE_DIR', WP_CONTENT_DIR . '/teknup-storage/');
define('TEKNUP_TABLE_JOBS', 'teknup_mastering_jobs');

/**
 * Classe principale du plugin Teknup AI Mastering
 */
class Teknup_AI_Mastering {

    /**
     * Instance unique du plugin (Singleton)
     */
    private static $instance = null;

    /**
     * Obtenir l'instance unique du plugin
     *
     * @return Teknup_AI_Mastering
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructeur privé (Singleton pattern)
     */
    private function __construct() {
        $this->check_dependencies();
        $this->load_dependencies();
        $this->init_hooks();
    }

    /**
     * Vérifier les dépendances requises
     */
    private function check_dependencies() {
        // Vérifier si WooCommerce est actif
        if (!class_exists('WooCommerce')) {
            add_action('admin_notices', array($this, 'woocommerce_missing_notice'));
            return;
        }

        // Vérifier si WooCommerce Subscriptions est actif
        if (!class_exists('WC_Subscriptions')) {
            add_action('admin_notices', array($this, 'subscriptions_missing_notice'));
        }
    }

    /**
     * Charger les dépendances du plugin
     */
    private function load_dependencies() {
        // Classes core
        require_once TEKNUP_PLUGIN_DIR . 'includes/class-teknup-database.php';
        require_once TEKNUP_PLUGIN_DIR . 'includes/class-teknup-file-manager.php';
        require_once TEKNUP_PLUGIN_DIR . 'includes/class-teknup-job-manager.php';
        require_once TEKNUP_PLUGIN_DIR . 'includes/class-teknup-subscription-manager.php';
        require_once TEKNUP_PLUGIN_DIR . 'includes/class-teknup-dolby-api.php';
        require_once TEKNUP_PLUGIN_DIR . 'includes/class-teknup-notifications.php';
        require_once TEKNUP_PLUGIN_DIR . 'includes/class-teknup-cron.php';

        // Classes admin
        if (is_admin()) {
            require_once TEKNUP_PLUGIN_DIR . 'admin/class-teknup-admin.php';
            require_once TEKNUP_PLUGIN_DIR . 'admin/class-teknup-admin-settings.php';
            require_once TEKNUP_PLUGIN_DIR . 'admin/class-teknup-admin-dashboard.php';
            require_once TEKNUP_PLUGIN_DIR . 'admin/class-teknup-admin-jobs.php';
        }

        // Classes public
        require_once TEKNUP_PLUGIN_DIR . 'public/class-teknup-public.php';
        require_once TEKNUP_PLUGIN_DIR . 'public/class-teknup-rest-api.php';
        require_once TEKNUP_PLUGIN_DIR . 'public/class-teknup-shortcodes.php';
        require_once TEKNUP_PLUGIN_DIR . 'public/class-teknup-woocommerce.php';
    }

    /**
     * Initialiser les hooks WordPress
     */
    private function init_hooks() {
        add_action('plugins_loaded', array($this, 'load_textdomain'));
        add_action('init', array($this, 'init'));

        // Initialiser l'admin
        if (is_admin()) {
            Teknup_Admin::get_instance();
        }

        // Initialiser le frontend
        Teknup_Public::get_instance();

        // Initialiser l'API REST
        Teknup_REST_API::get_instance();

        // Initialiser les shortcodes
        Teknup_Shortcodes::get_instance();

        // Initialiser l'intégration WooCommerce
        Teknup_WooCommerce::get_instance();

        // Initialiser les tâches cron
        Teknup_Cron::get_instance();
    }

    /**
     * Initialisation du plugin
     */
    public function init() {
        // Code d'initialisation supplémentaire si nécessaire
    }

    /**
     * Charger le domaine de traduction
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'teknup-ai-mastering',
            false,
            dirname(TEKNUP_PLUGIN_BASENAME) . '/languages/'
        );
    }

    /**
     * Notice si WooCommerce est manquant
     */
    public function woocommerce_missing_notice() {
        ?>
        <div class="notice notice-error">
            <p>
                <?php
                echo wp_kses_post(
                    sprintf(
                        __('<strong>Teknup AI Mastering</strong> nécessite WooCommerce pour fonctionner. Veuillez <a href="%s">installer et activer WooCommerce</a>.', 'teknup-ai-mastering'),
                        admin_url('plugin-install.php?s=woocommerce&tab=search&type=term')
                    )
                );
                ?>
            </p>
        </div>
        <?php
    }

    /**
     * Notice si WooCommerce Subscriptions est manquant
     */
    public function subscriptions_missing_notice() {
        ?>
        <div class="notice notice-warning">
            <p>
                <?php
                echo esc_html__(
                    'Teknup AI Mastering recommande WooCommerce Subscriptions pour une gestion complète des abonnements.',
                    'teknup-ai-mastering'
                );
                ?>
            </p>
        </div>
        <?php
    }
}

/**
 * Fonction helper pour obtenir l'instance du plugin
 *
 * @return Teknup_AI_Mastering
 */
function teknup_ai_mastering() {
    return Teknup_AI_Mastering::get_instance();
}

// Démarrer le plugin
add_action('plugins_loaded', 'teknup_ai_mastering');

/**
 * Hook d'activation du plugin
 */
register_activation_hook(__FILE__, 'teknup_activate_plugin');
function teknup_activate_plugin() {
    require_once TEKNUP_PLUGIN_DIR . 'includes/class-teknup-activator.php';
    Teknup_Activator::activate();
}

/**
 * Hook de désactivation du plugin
 */
register_deactivation_hook(__FILE__, 'teknup_deactivate_plugin');
function teknup_deactivate_plugin() {
    require_once TEKNUP_PLUGIN_DIR . 'includes/class-teknup-deactivator.php';
    Teknup_Deactivator::deactivate();
}
