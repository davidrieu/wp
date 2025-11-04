<?php
/**
 * Classe principale de la partie publique
 *
 * @package Teknup_AI_Mastering
 */

if (!defined('ABSPATH')) {
    exit;
}

class Teknup_Public {

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
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
    }

    /**
     * Enregistrer les scripts et styles
     */
    public function enqueue_scripts() {
        // Enregistrer uniquement sur les pages WooCommerce My Account
        if (!is_account_page()) {
            return;
        }

        // CSS
        wp_enqueue_style(
            'teknup-public',
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
        ));
    }
}
