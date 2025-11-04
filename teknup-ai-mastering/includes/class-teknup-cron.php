<?php
/**
 * Classe de gestion des tâches cron
 *
 * @package Teknup_AI_Mastering
 */

if (!defined('ABSPATH')) {
    exit;
}

class Teknup_Cron {

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
        // Ajouter un intervalle personnalisé de 5 minutes
        add_filter('cron_schedules', array($this, 'add_custom_cron_intervals'));

        // Enregistrer les actions cron
        add_action('teknup_check_pending_jobs', array($this, 'check_pending_jobs'));
        add_action('teknup_cleanup_old_files', array($this, 'cleanup_old_files'));
        add_action('teknup_cleanup_old_jobs', array($this, 'cleanup_old_jobs'));
    }

    /**
     * Ajouter des intervalles cron personnalisés
     *
     * @param array $schedules Intervalles existants
     * @return array
     */
    public function add_custom_cron_intervals($schedules) {
        $schedules['teknup_five_minutes'] = array(
            'interval' => 300, // 5 minutes en secondes
            'display' => __('Toutes les 5 minutes', 'teknup-ai-mastering'),
        );

        return $schedules;
    }

    /**
     * Vérifier les jobs en attente et les jobs qui traînent
     */
    public function check_pending_jobs() {
        // Récupérer les jobs qui traînent depuis plus de 30 minutes
        $stalled_jobs = Teknup_Database::get_stalled_jobs(30);

        $job_manager = Teknup_Job_Manager::get_instance();

        foreach ($stalled_jobs as $job) {
            // Vérifier le statut auprès de Dolby
            $result = $job_manager->check_job_status($job->id);

            if (is_wp_error($result)) {
                // Si erreur, marquer le job comme échoué après plusieurs tentatives
                $retry_count = get_transient('teknup_job_retry_' . $job->id);

                if ($retry_count === false) {
                    $retry_count = 0;
                }

                $retry_count++;

                if ($retry_count >= 3) {
                    // Échec définitif après 3 tentatives
                    Teknup_Database::update_job_status(
                        $job->id,
                        'failed',
                        __('Job abandonné après plusieurs tentatives infructueuses.', 'teknup-ai-mastering')
                    );

                    // Envoyer notification
                    Teknup_Notifications::get_instance()->send_failure_notification($job->id);

                    // Supprimer le transient
                    delete_transient('teknup_job_retry_' . $job->id);
                } else {
                    // Enregistrer la tentative et réessayer plus tard
                    set_transient('teknup_job_retry_' . $job->id, $retry_count, HOUR_IN_SECONDS);
                }
            }
        }

        // Logger si debug activé
        if (get_option('teknup_debug_mode', false)) {
            error_log(sprintf(
                'Teknup Cron: Checked %d stalled jobs',
                count($stalled_jobs)
            ));
        }
    }

    /**
     * Nettoyer les vieux fichiers
     */
    public function cleanup_old_files() {
        $cleanup_days = get_option('teknup_cleanup_days', 30);

        // Nettoyer les fichiers
        $files_deleted = Teknup_File_Manager::cleanup_old_files($cleanup_days);

        // Logger si debug activé
        if (get_option('teknup_debug_mode', false)) {
            error_log(sprintf(
                'Teknup Cron: Cleaned up %d old files (older than %d days)',
                $files_deleted,
                $cleanup_days
            ));
        }
    }

    /**
     * Nettoyer les vieux jobs de la base de données
     */
    public function cleanup_old_jobs() {
        $cleanup_days = get_option('teknup_cleanup_days', 30);

        // Supprimer les vieux jobs
        $jobs_deleted = Teknup_Database::delete_old_jobs($cleanup_days);

        // Nettoyer les transients expirés
        $this->cleanup_expired_transients();

        // Logger si debug activé
        if (get_option('teknup_debug_mode', false)) {
            error_log(sprintf(
                'Teknup Cron: Cleaned up %d old jobs (older than %d days)',
                $jobs_deleted,
                $cleanup_days
            ));
        }
    }

    /**
     * Nettoyer les transients expirés
     */
    private function cleanup_expired_transients() {
        global $wpdb;

        // Supprimer les transients Teknup expirés
        $wpdb->query(
            "DELETE FROM {$wpdb->options}
            WHERE option_name LIKE '_transient_timeout_teknup_%'
            AND option_value < UNIX_TIMESTAMP()"
        );

        $wpdb->query(
            "DELETE FROM {$wpdb->options}
            WHERE option_name LIKE '_transient_teknup_%'
            AND option_name NOT IN (
                SELECT CONCAT('_transient_', SUBSTRING(option_name, 20))
                FROM {$wpdb->options}
                WHERE option_name LIKE '_transient_timeout_teknup_%'
            )"
        );
    }

    /**
     * Exécuter manuellement une tâche cron (pour les tests ou admin)
     *
     * @param string $task Nom de la tâche
     * @return bool
     */
    public static function run_task_manually($task) {
        $instance = self::get_instance();

        switch ($task) {
            case 'check_pending_jobs':
                $instance->check_pending_jobs();
                return true;

            case 'cleanup_old_files':
                $instance->cleanup_old_files();
                return true;

            case 'cleanup_old_jobs':
                $instance->cleanup_old_jobs();
                return true;

            default:
                return false;
        }
    }
}
