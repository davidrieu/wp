<?php
/**
 * Classe principale de la partie admin
 *
 * @package Teknup_AI_Mastering
 */

if (!defined('ABSPATH')) {
    exit;
}

class Teknup_Admin {

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
        add_action('admin_menu', array($this, 'add_menu_pages'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));

        // Initialiser les sous-classes
        Teknup_Admin_Settings::get_instance();
        Teknup_Admin_Dashboard::get_instance();
        Teknup_Admin_Jobs::get_instance();
    }

    /**
     * Ajouter les pages de menu
     */
    public function add_menu_pages() {
        // Page principale
        add_menu_page(
            __('Teknup AI Mastering', 'teknup-ai-mastering'),
            __('Teknup', 'teknup-ai-mastering'),
            'manage_options',
            'teknup-ai-mastering',
            array($this, 'dashboard_page'),
            'dashicons-format-audio',
            30
        );

        // Dashboard
        add_submenu_page(
            'teknup-ai-mastering',
            __('Dashboard', 'teknup-ai-mastering'),
            __('Dashboard', 'teknup-ai-mastering'),
            'manage_options',
            'teknup-ai-mastering',
            array($this, 'dashboard_page')
        );

        // Jobs
        add_submenu_page(
            'teknup-ai-mastering',
            __('Jobs', 'teknup-ai-mastering'),
            __('Jobs', 'teknup-ai-mastering'),
            'manage_options',
            'teknup-jobs',
            array($this, 'jobs_page')
        );

        // Réglages
        add_submenu_page(
            'teknup-ai-mastering',
            __('Réglages', 'teknup-ai-mastering'),
            __('Réglages', 'teknup-ai-mastering'),
            'manage_options',
            'teknup-settings',
            array($this, 'settings_page')
        );
    }

    /**
     * Page dashboard
     */
    public function dashboard_page() {
        include TEKNUP_PLUGIN_DIR . 'admin/partials/dashboard.php';
    }

    /**
     * Page jobs
     */
    public function jobs_page() {
        include TEKNUP_PLUGIN_DIR . 'admin/partials/jobs.php';
    }

    /**
     * Page réglages
     */
    public function settings_page() {
        include TEKNUP_PLUGIN_DIR . 'admin/partials/settings.php';
    }

    /**
     * Enregistrer les scripts et styles admin
     */
    public function enqueue_scripts($hook) {
        // Charger uniquement sur nos pages
        if (strpos($hook, 'teknup') === false) {
            return;
        }

        // CSS
        wp_enqueue_style(
            'teknup-admin',
            TEKNUP_PLUGIN_URL . 'admin/css/admin.css',
            array(),
            TEKNUP_VERSION
        );

        // JS
        wp_enqueue_script(
            'teknup-admin',
            TEKNUP_PLUGIN_URL . 'admin/js/admin.js',
            array('jquery'),
            TEKNUP_VERSION,
            true
        );

        // Chart.js pour les graphiques
        wp_enqueue_script(
            'chart-js',
            'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js',
            array(),
            '4.4.0',
            true
        );

        // Localiser le script
        wp_localize_script('teknup-admin', 'teknupAdmin', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('teknup_admin'),
        ));
    }
}
