<?php
/**
 * Classe du dashboard admin
 *
 * @package Teknup_AI_Mastering
 */

if (!defined('ABSPATH')) {
    exit;
}

class Teknup_Admin_Dashboard {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('wp_ajax_teknup_get_stats', array($this, 'get_stats'));
        add_action('wp_ajax_teknup_get_daily_stats', array($this, 'get_daily_stats'));
    }

    public function get_stats() {
        check_ajax_referer('teknup_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
        }

        $stats = Teknup_Database::get_global_stats();
        $stats['storage_size'] = Teknup_File_Manager::get_storage_size();
        $stats['storage_size_formatted'] = Teknup_File_Manager::format_file_size($stats['storage_size']);

        wp_send_json_success($stats);
    }

    public function get_daily_stats() {
        check_ajax_referer('teknup_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
        }

        $days = isset($_GET['days']) ? (int) $_GET['days'] : 30;
        $stats = Teknup_Database::get_daily_stats($days);

        wp_send_json_success($stats);
    }
}
