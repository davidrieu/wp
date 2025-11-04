<?php
/**
 * Classe de gestion des réglages admin
 *
 * @package Teknup_AI_Mastering
 */

if (!defined('ABSPATH')) {
    exit;
}

class Teknup_Admin_Settings {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('admin_init', array($this, 'register_settings'));
        add_action('wp_ajax_teknup_test_api', array($this, 'test_api_connection'));
        add_action('wp_ajax_teknup_create_products', array($this, 'create_products'));
    }

    public function register_settings() {
        register_setting('teknup_settings', 'teknup_dolby_api_key');
        register_setting('teknup_settings', 'teknup_max_file_size');
        register_setting('teknup_settings', 'teknup_default_intensity');
        register_setting('teknup_settings', 'teknup_default_lufs');
        register_setting('teknup_settings', 'teknup_cleanup_days');
        register_setting('teknup_settings', 'teknup_debug_mode');
    }

    public function test_api_connection() {
        check_ajax_referer('teknup_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
        }

        $dolby_api = Teknup_Dolby_API::get_instance();
        $result = $dolby_api->test_connection();

        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }

        wp_send_json_success($result);
    }

    public function create_products() {
        check_ajax_referer('teknup_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
        }

        // Vérifier que WooCommerce est actif
        if (!class_exists('WooCommerce')) {
            wp_send_json_error(array(
                'message' => 'WooCommerce doit être installé et activé pour créer les produits.'
            ));
        }

        // Forcer la recréation en supprimant l'option
        delete_option('teknup_products_created');

        // Charger l'activator
        require_once TEKNUP_PLUGIN_DIR . 'includes/class-teknup-activator.php';

        // Utiliser la réflexion pour appeler la méthode privée
        $reflection = new ReflectionClass('Teknup_Activator');
        $method = $reflection->getMethod('create_woocommerce_products');
        $method->setAccessible(true);
        $method->invoke(null);

        // Récupérer les IDs des produits créés
        $product_ids = get_option('teknup_product_ids', array());

        if (empty($product_ids)) {
            wp_send_json_error(array(
                'message' => 'Erreur lors de la création des produits. Vérifiez que WooCommerce est bien activé.'
            ));
        }

        wp_send_json_success(array(
            'message' => sprintf('✓ %d produits créés avec succès !', count($product_ids)),
            'products' => $product_ids
        ));
    }
}
