<?php
/**
 * Classe de gestion de la base de données
 *
 * @package Teknup_AI_Mastering
 */

if (!defined('ABSPATH')) {
    exit;
}

class Teknup_Database {

    /**
     * Obtenir la table des jobs
     *
     * @return string
     */
    public static function get_jobs_table() {
        global $wpdb;
        return $wpdb->prefix . TEKNUP_TABLE_JOBS;
    }

    /**
     * Créer un nouveau job
     *
     * @param array $data Données du job
     * @return int|false ID du job créé ou false en cas d'erreur
     */
    public static function create_job($data) {
        global $wpdb;

        $defaults = array(
            'user_id' => get_current_user_id(),
            'status' => 'pending',
            'created_at' => current_time('mysql'),
        );

        $data = wp_parse_args($data, $defaults);

        // Sérialiser les settings si c'est un array
        if (isset($data['settings']) && is_array($data['settings'])) {
            $data['settings'] = json_encode($data['settings']);
        }

        $inserted = $wpdb->insert(
            self::get_jobs_table(),
            $data,
            array('%d', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s')
        );

        if ($inserted) {
            return $wpdb->insert_id;
        }

        return false;
    }

    /**
     * Récupérer un job par son ID
     *
     * @param int $job_id ID du job
     * @return object|null
     */
    public static function get_job($job_id) {
        global $wpdb;

        $job = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM " . self::get_jobs_table() . " WHERE id = %d",
                $job_id
            )
        );

        if ($job && $job->settings) {
            $job->settings = json_decode($job->settings, true);
        }

        return $job;
    }

    /**
     * Mettre à jour un job
     *
     * @param int $job_id ID du job
     * @param array $data Données à mettre à jour
     * @return bool
     */
    public static function update_job($job_id, $data) {
        global $wpdb;

        // Sérialiser les settings si c'est un array
        if (isset($data['settings']) && is_array($data['settings'])) {
            $data['settings'] = json_encode($data['settings']);
        }

        return $wpdb->update(
            self::get_jobs_table(),
            $data,
            array('id' => $job_id),
            null,
            array('%d')
        );
    }

    /**
     * Mettre à jour le statut d'un job
     *
     * @param int $job_id ID du job
     * @param string $status Nouveau statut
     * @param string|null $error_message Message d'erreur optionnel
     * @return bool
     */
    public static function update_job_status($job_id, $status, $error_message = null) {
        $data = array('status' => $status);

        // Mettre à jour les timestamps selon le statut
        switch ($status) {
            case 'uploaded':
                $data['uploaded_at'] = current_time('mysql');
                break;
            case 'processing':
                $data['processing_started_at'] = current_time('mysql');
                break;
            case 'completed':
            case 'failed':
                $data['completed_at'] = current_time('mysql');
                break;
        }

        if ($error_message !== null) {
            $data['error_message'] = $error_message;
        }

        return self::update_job($job_id, $data);
    }

    /**
     * Récupérer les jobs d'un utilisateur
     *
     * @param int $user_id ID de l'utilisateur
     * @param array $args Arguments de requête (limit, offset, status, order_by, order)
     * @return array
     */
    public static function get_user_jobs($user_id, $args = array()) {
        global $wpdb;

        $defaults = array(
            'limit' => 20,
            'offset' => 0,
            'status' => null,
            'order_by' => 'created_at',
            'order' => 'DESC',
        );

        $args = wp_parse_args($args, $defaults);

        $where = $wpdb->prepare("WHERE user_id = %d", $user_id);

        if ($args['status']) {
            $where .= $wpdb->prepare(" AND status = %s", $args['status']);
        }

        $order = sprintf(
            "ORDER BY %s %s",
            esc_sql($args['order_by']),
            $args['order'] === 'ASC' ? 'ASC' : 'DESC'
        );

        $limit = sprintf("LIMIT %d OFFSET %d", (int)$args['limit'], (int)$args['offset']);

        $query = "SELECT * FROM " . self::get_jobs_table() . " {$where} {$order} {$limit}";

        $jobs = $wpdb->get_results($query);

        // Décoder les settings JSON
        foreach ($jobs as $job) {
            if ($job->settings) {
                $job->settings = json_decode($job->settings, true);
            }
        }

        return $jobs;
    }

    /**
     * Compter les jobs d'un utilisateur
     *
     * @param int $user_id ID de l'utilisateur
     * @param string|null $status Statut spécifique ou null pour tous
     * @return int
     */
    public static function count_user_jobs($user_id, $status = null) {
        global $wpdb;

        $where = $wpdb->prepare("WHERE user_id = %d", $user_id);

        if ($status) {
            $where .= $wpdb->prepare(" AND status = %s", $status);
        }

        return (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM " . self::get_jobs_table() . " {$where}"
        );
    }

    /**
     * Compter les jobs complétés ce mois pour un utilisateur
     *
     * @param int $user_id ID de l'utilisateur
     * @return int
     */
    public static function count_user_monthly_jobs($user_id) {
        global $wpdb;

        $start_of_month = date('Y-m-01 00:00:00');

        return (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM " . self::get_jobs_table() . "
                WHERE user_id = %d
                AND status = 'completed'
                AND completed_at >= %s",
                $user_id,
                $start_of_month
            )
        );
    }

    /**
     * Récupérer les jobs en attente depuis trop longtemps
     *
     * @param int $minutes Nombre de minutes
     * @return array
     */
    public static function get_stalled_jobs($minutes = 30) {
        global $wpdb;

        $time_threshold = date('Y-m-d H:i:s', strtotime("-{$minutes} minutes"));

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM " . self::get_jobs_table() . "
                WHERE status IN ('processing', 'uploaded')
                AND created_at < %s
                AND dolby_job_id IS NOT NULL",
                $time_threshold
            )
        );
    }

    /**
     * Supprimer les vieux jobs
     *
     * @param int $days Nombre de jours
     * @return int Nombre de jobs supprimés
     */
    public static function delete_old_jobs($days = 30) {
        global $wpdb;

        $date_threshold = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        return $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM " . self::get_jobs_table() . "
                WHERE created_at < %s
                AND status IN ('completed', 'failed')",
                $date_threshold
            )
        );
    }

    /**
     * Récupérer les statistiques globales
     *
     * @return array
     */
    public static function get_global_stats() {
        global $wpdb;
        $table = self::get_jobs_table();

        $stats = array();

        // Total des jobs
        $stats['total_jobs'] = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table}");

        // Jobs complétés
        $stats['completed_jobs'] = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$table} WHERE status = 'completed'"
        );

        // Jobs échoués
        $stats['failed_jobs'] = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$table} WHERE status = 'failed'"
        );

        // Jobs en cours
        $stats['processing_jobs'] = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$table} WHERE status IN ('pending', 'uploaded', 'processing')"
        );

        // Jobs ce mois
        $start_of_month = date('Y-m-01 00:00:00');
        $stats['monthly_jobs'] = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$table} WHERE created_at >= %s",
                $start_of_month
            )
        );

        // Temps moyen de traitement (en secondes)
        $stats['avg_processing_time'] = (int) $wpdb->get_var(
            "SELECT AVG(TIMESTAMPDIFF(SECOND, processing_started_at, completed_at))
            FROM {$table}
            WHERE status = 'completed'
            AND processing_started_at IS NOT NULL
            AND completed_at IS NOT NULL"
        );

        // Taux de succès (%)
        if ($stats['total_jobs'] > 0) {
            $stats['success_rate'] = round(($stats['completed_jobs'] / $stats['total_jobs']) * 100, 2);
        } else {
            $stats['success_rate'] = 0;
        }

        return $stats;
    }

    /**
     * Récupérer les statistiques quotidiennes pour les 30 derniers jours
     *
     * @return array
     */
    public static function get_daily_stats($days = 30) {
        global $wpdb;
        $table = self::get_jobs_table();

        $date_threshold = date('Y-m-d', strtotime("-{$days} days"));

        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT
                    DATE(created_at) as date,
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                    SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed
                FROM {$table}
                WHERE DATE(created_at) >= %s
                GROUP BY DATE(created_at)
                ORDER BY date ASC",
                $date_threshold
            ),
            ARRAY_A
        );

        return $results;
    }
}
