<?php
/**
 * Classe de gestion des jobs admin
 *
 * @package Teknup_AI_Mastering
 */

if (!defined('ABSPATH')) {
    exit;
}

class Teknup_Admin_Jobs {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('wp_ajax_teknup_admin_get_jobs', array($this, 'get_jobs'));
        add_action('wp_ajax_teknup_admin_delete_job', array($this, 'delete_job'));
        add_action('wp_ajax_teknup_admin_retry_job', array($this, 'retry_job'));
        add_action('wp_ajax_teknup_run_cron', array($this, 'run_cron_task'));
    }

    public function get_jobs() {
        check_ajax_referer('teknup_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
        }

        global $wpdb;
        $table = Teknup_Database::get_jobs_table();

        $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
        $per_page = 20;
        $offset = ($page - 1) * $per_page;

        $status = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
        $where = '';
        if ($status) {
            $where = $wpdb->prepare("WHERE status = %s", $status);
        }

        $jobs = $wpdb->get_results("SELECT * FROM {$table} {$where} ORDER BY created_at DESC LIMIT {$per_page} OFFSET {$offset}");
        $total = $wpdb->get_var("SELECT COUNT(*) FROM {$table} {$where}");

        wp_send_json_success(array(
            'jobs' => $jobs,
            'total' => (int) $total,
            'page' => $page,
            'per_page' => $per_page,
        ));
    }

    public function delete_job() {
        check_ajax_referer('teknup_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
        }

        $job_id = isset($_POST['job_id']) ? (int) $_POST['job_id'] : 0;
        $job_manager = Teknup_Job_Manager::get_instance();
        $result = $job_manager->delete_job($job_id);

        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }

        wp_send_json_success(array('message' => 'Job deleted'));
    }

    public function retry_job() {
        check_ajax_referer('teknup_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
        }

        $job_id = isset($_POST['job_id']) ? (int) $_POST['job_id'] : 0;
        $job_manager = Teknup_Job_Manager::get_instance();
        $result = $job_manager->retry_job($job_id);

        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }

        wp_send_json_success(array('message' => 'Job retried'));
    }

    public function run_cron_task() {
        check_ajax_referer('teknup_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
        }

        $task = isset($_POST['task']) ? sanitize_text_field($_POST['task']) : '';
        $result = Teknup_Cron::run_task_manually($task);

        if ($result) {
            wp_send_json_success(array('message' => 'Task executed'));
        } else {
            wp_send_json_error(array('message' => 'Invalid task'));
        }
    }
}
