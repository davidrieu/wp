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
        add_shortcode('teknup_upload', array($this, 'upload_shortcode'));
        add_shortcode('teknup_dashboard', array($this, 'dashboard_shortcode'));
        add_shortcode('teknup_history', array($this, 'history_shortcode'));
    }

    /**
     * Shortcode pour l'upload
     */
    public function upload_shortcode($atts) {
        if (!is_user_logged_in()) {
            return '<p>' . __('Vous devez être connecté pour accéder à cette page.', 'teknup-ai-mastering') . '</p>';
        }

        ob_start();
        include TEKNUP_PLUGIN_DIR . 'public/partials/upload.php';
        return ob_get_clean();
    }

    /**
     * Shortcode pour le dashboard
     */
    public function dashboard_shortcode($atts) {
        if (!is_user_logged_in()) {
            return '<p>' . __('Vous devez être connecté pour accéder à cette page.', 'teknup-ai-mastering') . '</p>';
        }

        ob_start();
        include TEKNUP_PLUGIN_DIR . 'public/partials/dashboard.php';
        return ob_get_clean();
    }

    /**
     * Shortcode pour l'historique
     */
    public function history_shortcode($atts) {
        if (!is_user_logged_in()) {
            return '<p>' . __('Vous devez être connecté pour accéder à cette page.', 'teknup-ai-mastering') . '</p>';
        }

        ob_start();
        include TEKNUP_PLUGIN_DIR . 'public/partials/history.php';
        return ob_get_clean();
    }
}
