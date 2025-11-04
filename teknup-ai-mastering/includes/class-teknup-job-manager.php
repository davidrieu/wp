<?php
/**
 * Classe de gestion des jobs de mastering
 *
 * @package Teknup_AI_Mastering
 */

if (!defined('ABSPATH')) {
    exit;
}

class Teknup_Job_Manager {

    /**
     * Instance unique
     */
    private static $instance = null;

    /**
     * Instance de l'API Dolby
     */
    private $dolby_api;

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
        $this->dolby_api = Teknup_Dolby_API::get_instance();
    }

    /**
     * Créer un nouveau job de mastering
     *
     * @param array $file Fichier depuis $_FILES
     * @param array $settings Paramètres de mastering
     * @param int $user_id ID de l'utilisateur
     * @return int|WP_Error ID du job créé ou erreur
     */
    public function create_job($file, $settings = array(), $user_id = null) {
        if ($user_id === null) {
            $user_id = get_current_user_id();
        }

        // Vérifier les permissions de l'utilisateur
        $subscription_manager = Teknup_Subscription_Manager::get_instance();
        $can_upload = $subscription_manager->can_user_upload($user_id);

        if (is_wp_error($can_upload)) {
            return $can_upload;
        }

        // Sauvegarder le fichier
        $file_info = Teknup_File_Manager::save_upload($file, $user_id);

        if (is_wp_error($file_info)) {
            return $file_info;
        }

        // Créer le job en base de données
        $job_data = array(
            'user_id' => $user_id,
            'original_filename' => $file_info['original_filename'],
            'original_filepath' => $file_info['filepath'],
            'file_size' => $file_info['size'],
            'settings' => $settings,
            'status' => 'uploaded',
        );

        $job_id = Teknup_Database::create_job($job_data);

        if (!$job_id) {
            // Nettoyer le fichier si échec de création du job
            @unlink($file_info['filepath']);
            return new WP_Error(
                'job_creation_failed',
                __('Impossible de créer le job.', 'teknup-ai-mastering')
            );
        }

        // Marquer le timestamp d'upload
        Teknup_Database::update_job_status($job_id, 'uploaded');

        return $job_id;
    }

    /**
     * Démarrer le processing d'un job
     *
     * @param int $job_id ID du job
     * @return bool|WP_Error True si succès, WP_Error sinon
     */
    public function start_processing($job_id) {
        $job = Teknup_Database::get_job($job_id);

        if (!$job) {
            return new WP_Error('job_not_found', __('Job introuvable.', 'teknup-ai-mastering'));
        }

        if ($job->status !== 'uploaded') {
            return new WP_Error(
                'invalid_status',
                __('Le job ne peut pas être traité dans son état actuel.', 'teknup-ai-mastering')
            );
        }

        // Générer une URL temporaire pour Dolby
        $input_url = Teknup_File_Manager::generate_temporary_url($job->original_filepath, 7200); // 2 heures

        // Générer l'URL de callback pour recevoir le fichier masterisé
        $output_url = add_query_arg(
            array(
                'teknup_webhook' => 'dolby_output',
                'job_id' => $job_id,
            ),
            home_url('/')
        );

        // Démarrer le job Dolby
        $settings = $job->settings ?? array();
        $dolby_response = $this->dolby_api->start_mastering_job($input_url, $output_url, $settings);

        if (is_wp_error($dolby_response)) {
            Teknup_Database::update_job_status($job_id, 'failed', $dolby_response->get_error_message());
            return $dolby_response;
        }

        // Mettre à jour le job avec l'ID Dolby
        Teknup_Database::update_job($job_id, array(
            'dolby_job_id' => $dolby_response['job_id'] ?? null,
        ));

        // Mettre à jour le statut
        Teknup_Database::update_job_status($job_id, 'processing');

        return true;
    }

    /**
     * Vérifier le statut d'un job auprès de Dolby
     *
     * @param int $job_id ID du job
     * @return bool|WP_Error True si à jour, WP_Error sinon
     */
    public function check_job_status($job_id) {
        $job = Teknup_Database::get_job($job_id);

        if (!$job || !$job->dolby_job_id) {
            return new WP_Error('job_not_found', __('Job introuvable.', 'teknup-ai-mastering'));
        }

        // Récupérer le statut depuis Dolby
        $dolby_status = $this->dolby_api->get_job_status($job->dolby_job_id);

        if (is_wp_error($dolby_status)) {
            return $dolby_status;
        }

        // Mettre à jour le statut selon la réponse Dolby
        $status = $dolby_status['status'] ?? 'unknown';

        switch ($status) {
            case 'Success':
            case 'completed':
                if (isset($dolby_status['output'])) {
                    $this->handle_job_completion($job_id, $dolby_status['output']);
                }
                break;

            case 'Failed':
            case 'failed':
                $error_message = $dolby_status['error'] ?? __('Erreur inconnue', 'teknup-ai-mastering');
                Teknup_Database::update_job_status($job_id, 'failed', $error_message);
                Teknup_Notifications::get_instance()->send_failure_notification($job_id);
                break;

            case 'Pending':
            case 'Running':
            case 'processing':
                // Job toujours en cours, ne rien faire
                break;

            default:
                // Statut inconnu
                break;
        }

        return true;
    }

    /**
     * Gérer la complétion d'un job
     *
     * @param int $job_id ID du job
     * @param string $output_url URL du fichier masterisé
     * @return bool|WP_Error
     */
    public function handle_job_completion($job_id, $output_url) {
        $job = Teknup_Database::get_job($job_id);

        if (!$job) {
            return new WP_Error('job_not_found', __('Job introuvable.', 'teknup-ai-mastering'));
        }

        // Télécharger et sauvegarder le fichier masterisé
        $mastered_filepath = Teknup_File_Manager::save_mastered_file($output_url, $job_id, $job->user_id);

        if (is_wp_error($mastered_filepath)) {
            Teknup_Database::update_job_status($job_id, 'failed', $mastered_filepath->get_error_message());
            return $mastered_filepath;
        }

        // Mettre à jour le job
        Teknup_Database::update_job($job_id, array(
            'mastered_filepath' => $mastered_filepath,
        ));

        Teknup_Database::update_job_status($job_id, 'completed');

        // Incrémenter le compteur de l'utilisateur
        Teknup_Subscription_Manager::get_instance()->increment_usage($job->user_id);

        // Envoyer la notification
        Teknup_Notifications::get_instance()->send_completion_notification($job_id);

        return true;
    }

    /**
     * Annuler un job
     *
     * @param int $job_id ID du job
     * @return bool|WP_Error
     */
    public function cancel_job($job_id) {
        $job = Teknup_Database::get_job($job_id);

        if (!$job) {
            return new WP_Error('job_not_found', __('Job introuvable.', 'teknup-ai-mastering'));
        }

        // Annuler le job chez Dolby si applicable
        if ($job->dolby_job_id && in_array($job->status, array('processing', 'uploaded'))) {
            $this->dolby_api->cancel_job($job->dolby_job_id);
        }

        // Mettre à jour le statut
        Teknup_Database::update_job_status($job_id, 'cancelled');

        return true;
    }

    /**
     * Supprimer un job et ses fichiers
     *
     * @param int $job_id ID du job
     * @return bool|WP_Error
     */
    public function delete_job($job_id) {
        $job = Teknup_Database::get_job($job_id);

        if (!$job) {
            return new WP_Error('job_not_found', __('Job introuvable.', 'teknup-ai-mastering'));
        }

        // Annuler le job s'il est en cours
        if (in_array($job->status, array('processing', 'uploaded'))) {
            $this->cancel_job($job_id);
        }

        // Supprimer les fichiers
        Teknup_File_Manager::delete_job_files($job);

        // Supprimer le job de la base de données
        global $wpdb;
        $wpdb->delete(
            Teknup_Database::get_jobs_table(),
            array('id' => $job_id),
            array('%d')
        );

        return true;
    }

    /**
     * Relancer un job échoué
     *
     * @param int $job_id ID du job
     * @return bool|WP_Error
     */
    public function retry_job($job_id) {
        $job = Teknup_Database::get_job($job_id);

        if (!$job) {
            return new WP_Error('job_not_found', __('Job introuvable.', 'teknup-ai-mastering'));
        }

        if ($job->status !== 'failed') {
            return new WP_Error(
                'invalid_status',
                __('Seuls les jobs échoués peuvent être relancés.', 'teknup-ai-mastering')
            );
        }

        // Réinitialiser le job
        Teknup_Database::update_job($job_id, array(
            'dolby_job_id' => null,
            'error_message' => null,
            'processing_started_at' => null,
            'completed_at' => null,
        ));

        Teknup_Database::update_job_status($job_id, 'uploaded');

        // Redémarrer le processing
        return $this->start_processing($job_id);
    }

    /**
     * Obtenir les statistiques d'un utilisateur
     *
     * @param int $user_id ID de l'utilisateur
     * @return array
     */
    public function get_user_stats($user_id) {
        return array(
            'total_jobs' => Teknup_Database::count_user_jobs($user_id),
            'completed_jobs' => Teknup_Database::count_user_jobs($user_id, 'completed'),
            'failed_jobs' => Teknup_Database::count_user_jobs($user_id, 'failed'),
            'processing_jobs' => Teknup_Database::count_user_jobs($user_id, 'processing'),
            'monthly_jobs' => Teknup_Database::count_user_monthly_jobs($user_id),
        );
    }
}
