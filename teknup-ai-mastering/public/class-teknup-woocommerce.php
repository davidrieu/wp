<?php
/**
 * Classe d'intégration WooCommerce
 *
 * @package Teknup_AI_Mastering
 */

if (!defined('ABSPATH')) {
    exit;
}

class Teknup_WooCommerce {

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
        // Ajouter des endpoints personnalisés à My Account
        add_action('init', array($this, 'add_endpoints'));
        add_filter('woocommerce_account_menu_items', array($this, 'add_menu_items'));
        add_action('woocommerce_account_teknup-dashboard_endpoint', array($this, 'dashboard_content'));
        add_action('woocommerce_account_teknup-upload_endpoint', array($this, 'upload_content'));
        add_action('woocommerce_account_teknup-history_endpoint', array($this, 'history_content'));

        // Hook sur la création de compte
        add_action('woocommerce_created_customer', array($this, 'on_customer_created'));
    }

    /**
     * Ajouter les endpoints WooCommerce
     */
    public function add_endpoints() {
        add_rewrite_endpoint('teknup-dashboard', EP_ROOT | EP_PAGES);
        add_rewrite_endpoint('teknup-upload', EP_ROOT | EP_PAGES);
        add_rewrite_endpoint('teknup-history', EP_ROOT | EP_PAGES);
    }

    /**
     * Ajouter les items au menu My Account
     */
    public function add_menu_items($items) {
        // Insérer après 'dashboard'
        $new_items = array();
        foreach ($items as $key => $value) {
            $new_items[$key] = $value;
            if ($key === 'dashboard') {
                $new_items['teknup-dashboard'] = __('Teknup Dashboard', 'teknup-ai-mastering');
                $new_items['teknup-upload'] = __('Upload', 'teknup-ai-mastering');
                $new_items['teknup-history'] = __('Historique', 'teknup-ai-mastering');
            }
        }
        return $new_items;
    }

    /**
     * Contenu du dashboard Teknup
     */
    public function dashboard_content() {
        include TEKNUP_PLUGIN_DIR . 'public/partials/dashboard.php';
    }

    /**
     * Contenu de la page upload
     */
    public function upload_content() {
        include TEKNUP_PLUGIN_DIR . 'public/partials/upload.php';
    }

    /**
     * Contenu de la page historique
     */
    public function history_content() {
        include TEKNUP_PLUGIN_DIR . 'public/partials/history.php';
    }

    /**
     * Action lors de la création d'un client
     */
    public function on_customer_created($customer_id) {
        // Envoyer l'email de bienvenue
        Teknup_Notifications::get_instance()->send_welcome_email($customer_id);
    }
}
