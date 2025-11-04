<?php
/**
 * Fichier de désinstallation du plugin
 *
 * @package Teknup_AI_Mastering
 */

// Si la désinstallation n'est pas appelée par WordPress, quitter
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Supprimer la table de base de données
global $wpdb;
$table_name = $wpdb->prefix . 'teknup_mastering_jobs';
$wpdb->query("DROP TABLE IF EXISTS {$table_name}");

// Supprimer toutes les options
$options = array(
    'teknup_dolby_api_key',
    'teknup_max_file_size',
    'teknup_default_intensity',
    'teknup_default_lufs',
    'teknup_cleanup_days',
    'teknup_debug_mode',
    'teknup_version',
    'teknup_api_logs',
);

foreach ($options as $option) {
    delete_option($option);
}

// Supprimer tous les transients Teknup
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_teknup_%'");
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_teknup_%'");

// Supprimer tous les user meta Teknup
$wpdb->query("DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE 'teknup_%'");

// Supprimer les fichiers de stockage
function teknup_delete_directory($dir) {
    if (!file_exists($dir)) {
        return true;
    }

    if (!is_dir($dir)) {
        return unlink($dir);
    }

    foreach (scandir($dir) as $item) {
        if ($item == '.' || $item == '..') {
            continue;
        }

        if (!teknup_delete_directory($dir . DIRECTORY_SEPARATOR . $item)) {
            return false;
        }
    }

    return rmdir($dir);
}

$storage_dir = WP_CONTENT_DIR . '/teknup-storage/';
teknup_delete_directory($storage_dir);

// Nettoyer les tâches cron
wp_clear_scheduled_hook('teknup_check_pending_jobs');
wp_clear_scheduled_hook('teknup_cleanup_old_files');
wp_clear_scheduled_hook('teknup_cleanup_old_jobs');
