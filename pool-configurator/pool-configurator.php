<?php
/**
 * Plugin Name: Configurateur de Piscines
 * Plugin URI: https://example.com/pool-configurator
 * Description: Un configurateur moderne et interactif pour la personnalisation de piscines
 * Version: 1.0.0
 * Author: Votre Nom
 * Author URI: https://example.com
 * License: GPL v2 or later
 * Text Domain: pool-configurator
 */

// Empêcher l'accès direct
if (!defined('ABSPATH')) {
    exit;
}

// Définir les constantes
define('POOL_CONFIGURATOR_VERSION', '1.0.0');
define('POOL_CONFIGURATOR_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('POOL_CONFIGURATOR_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * Classe principale du plugin
 */
class Pool_Configurator {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->load_dependencies();
        $this->init_hooks();
    }

    private function load_dependencies() {
        require_once POOL_CONFIGURATOR_PLUGIN_DIR . 'includes/class-pool-post-types.php';
        require_once POOL_CONFIGURATOR_PLUGIN_DIR . 'admin/class-pool-admin.php';
        require_once POOL_CONFIGURATOR_PLUGIN_DIR . 'public/class-pool-public.php';
    }

    private function init_hooks() {
        add_action('plugins_loaded', array($this, 'load_textdomain'));

        // Initialiser les post types
        Pool_Post_Types::init();

        // Initialiser l'admin
        if (is_admin()) {
            Pool_Admin::init();
        }

        // Initialiser le frontend
        Pool_Public::init();

        // Forcer l'initialisation de la session WooCommerce pour les invités
        add_action('woocommerce_init', array($this, 'ensure_wc_session'));
    }

    /**
     * S'assurer que WooCommerce initialise toujours une session, même pour les invités
     */
    public function ensure_wc_session() {
        if (is_null(WC()->session)) {
            return;
        }

        // Forcer l'initialisation de la session pour tous les utilisateurs
        if (!WC()->session->has_session()) {
            WC()->session->set_customer_session_cookie(true);
        }
    }

    public function load_textdomain() {
        load_plugin_textdomain('pool-configurator', false, dirname(plugin_basename(__FILE__)) . '/languages/');
    }
}

// Initialiser le plugin
function pool_configurator() {
    return Pool_Configurator::get_instance();
}

// Démarrer le plugin
pool_configurator();

// Hook d'activation
register_activation_hook(__FILE__, 'pool_configurator_activate');
function pool_configurator_activate() {
    Pool_Post_Types::register_post_types();
    flush_rewrite_rules();
}

// Hook de désactivation
register_deactivation_hook(__FILE__, 'pool_configurator_deactivate');
function pool_configurator_deactivate() {
    flush_rewrite_rules();
}
