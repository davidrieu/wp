<?php
/**
 * Classe de désactivation du plugin
 *
 * @package Teknup_AI_Mastering
 */

if (!defined('ABSPATH')) {
    exit;
}

class Teknup_Deactivator {

    /**
     * Actions à effectuer lors de la désactivation du plugin
     */
    public static function deactivate() {
        // Déprogrammer les tâches cron
        self::unschedule_cron_jobs();

        // Flush des rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Déprogrammer toutes les tâches cron
     */
    private static function unschedule_cron_jobs() {
        $cron_hooks = array(
            'teknup_check_pending_jobs',
            'teknup_cleanup_old_files',
            'teknup_cleanup_old_jobs',
        );

        foreach ($cron_hooks as $hook) {
            $timestamp = wp_next_scheduled($hook);
            if ($timestamp) {
                wp_unschedule_event($timestamp, $hook);
            }
        }
    }
}
