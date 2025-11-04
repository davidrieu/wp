<?php
/**
 * Classe d'activation du plugin
 *
 * @package Teknup_AI_Mastering
 */

if (!defined('ABSPATH')) {
    exit;
}

class Teknup_Activator {

    /**
     * Actions à effectuer lors de l'activation du plugin
     */
    public static function activate() {
        // Créer la table de base de données
        self::create_database_table();

        // Créer la structure de dossiers
        self::create_storage_directories();

        // Créer les fichiers de protection
        self::create_protection_files();

        // Configurer les options par défaut
        self::set_default_options();

        // Planifier les tâches cron
        self::schedule_cron_jobs();

        // Flush des rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Créer la table de base de données pour les jobs
     */
    private static function create_database_table() {
        global $wpdb;

        $table_name = $wpdb->prefix . TEKNUP_TABLE_JOBS;
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            original_filename varchar(255) NOT NULL,
            original_filepath varchar(500) NOT NULL,
            mastered_filepath varchar(500) DEFAULT NULL,
            status varchar(50) NOT NULL DEFAULT 'pending',
            dolby_job_id varchar(255) DEFAULT NULL,
            file_size bigint(20) DEFAULT NULL,
            settings longtext DEFAULT NULL,
            error_message text DEFAULT NULL,
            created_at datetime NOT NULL,
            uploaded_at datetime DEFAULT NULL,
            processing_started_at datetime DEFAULT NULL,
            completed_at datetime DEFAULT NULL,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY status (status),
            KEY created_at (created_at),
            KEY dolby_job_id (dolby_job_id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Créer la structure de dossiers de stockage
     */
    private static function create_storage_directories() {
        $directories = array(
            TEKNUP_STORAGE_DIR,
            TEKNUP_STORAGE_DIR . 'original/',
            TEKNUP_STORAGE_DIR . 'mastered/',
        );

        foreach ($directories as $dir) {
            if (!file_exists($dir)) {
                wp_mkdir_p($dir);
                chmod($dir, 0755);
            }
        }
    }

    /**
     * Créer les fichiers de protection (.htaccess et index.php)
     */
    private static function create_protection_files() {
        // Protection .htaccess pour empêcher l'accès direct
        $htaccess_content = "# Teknup AI Mastering - Protection des fichiers\n";
        $htaccess_content .= "Order Deny,Allow\n";
        $htaccess_content .= "Deny from all\n";
        $htaccess_content .= "<FilesMatch '\.(php)$'>\n";
        $htaccess_content .= "    Allow from all\n";
        $htaccess_content .= "</FilesMatch>\n";

        $htaccess_file = TEKNUP_STORAGE_DIR . '.htaccess';
        if (!file_exists($htaccess_file)) {
            file_put_contents($htaccess_file, $htaccess_content);
        }

        // Fichiers index.php pour éviter le listing de répertoires
        $index_content = "<?php\n// Silence is golden.\n";
        $directories = array(
            TEKNUP_STORAGE_DIR,
            TEKNUP_STORAGE_DIR . 'original/',
            TEKNUP_STORAGE_DIR . 'mastered/',
        );

        foreach ($directories as $dir) {
            $index_file = $dir . 'index.php';
            if (!file_exists($index_file)) {
                file_put_contents($index_file, $index_content);
            }
        }
    }

    /**
     * Définir les options par défaut
     */
    private static function set_default_options() {
        $default_options = array(
            'teknup_dolby_api_key' => '',
            'teknup_max_file_size' => 500, // MB
            'teknup_default_intensity' => 'medium',
            'teknup_default_lufs' => -14,
            'teknup_cleanup_days' => 30,
            'teknup_debug_mode' => false,
            'teknup_version' => TEKNUP_VERSION,
        );

        foreach ($default_options as $key => $value) {
            if (get_option($key) === false) {
                add_option($key, $value);
            }
        }
    }

    /**
     * Planifier les tâches cron
     */
    private static function schedule_cron_jobs() {
        // Vérification des jobs toutes les 5 minutes
        if (!wp_next_scheduled('teknup_check_pending_jobs')) {
            wp_schedule_event(time(), 'teknup_five_minutes', 'teknup_check_pending_jobs');
        }

        // Nettoyage quotidien des vieux fichiers
        if (!wp_next_scheduled('teknup_cleanup_old_files')) {
            wp_schedule_event(time(), 'daily', 'teknup_cleanup_old_files');
        }

        // Nettoyage quotidien des vieux jobs
        if (!wp_next_scheduled('teknup_cleanup_old_jobs')) {
            wp_schedule_event(time(), 'daily', 'teknup_cleanup_old_jobs');
        }
    }
}
