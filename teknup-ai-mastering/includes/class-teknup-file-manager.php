<?php
/**
 * Classe de gestion des fichiers
 *
 * @package Teknup_AI_Mastering
 */

if (!defined('ABSPATH')) {
    exit;
}

class Teknup_File_Manager {

    /**
     * Formats audio acceptés
     */
    const ALLOWED_FORMATS = array('wav', 'mp3', 'flac', 'aiff', 'aif');

    /**
     * MIME types acceptés
     */
    const ALLOWED_MIME_TYPES = array(
        'audio/wav',
        'audio/x-wav',
        'audio/wave',
        'audio/mpeg',
        'audio/mp3',
        'audio/flac',
        'audio/x-flac',
        'audio/aiff',
        'audio/x-aiff',
    );

    /**
     * Valider un fichier uploadé
     *
     * @param array $file Fichier depuis $_FILES
     * @return array|WP_Error Résultat de validation ou WP_Error
     */
    public static function validate_upload($file) {
        // Vérifier s'il y a une erreur d'upload
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return new WP_Error(
                'upload_error',
                __('Erreur lors de l\'upload du fichier.', 'teknup-ai-mastering')
            );
        }

        // Vérifier la taille du fichier
        $max_size = get_option('teknup_max_file_size', 500) * 1024 * 1024; // Convertir MB en bytes
        if ($file['size'] > $max_size) {
            return new WP_Error(
                'file_too_large',
                sprintf(
                    __('Le fichier est trop volumineux. Taille maximum : %d MB', 'teknup-ai-mastering'),
                    get_option('teknup_max_file_size', 500)
                )
            );
        }

        // Vérifier l'extension
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, self::ALLOWED_FORMATS)) {
            return new WP_Error(
                'invalid_format',
                sprintf(
                    __('Format de fichier non supporté. Formats acceptés : %s', 'teknup-ai-mastering'),
                    implode(', ', self::ALLOWED_FORMATS)
                )
            );
        }

        // Vérifier le MIME type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mime_type, self::ALLOWED_MIME_TYPES)) {
            return new WP_Error(
                'invalid_mime_type',
                __('Le type de fichier n\'est pas valide.', 'teknup-ai-mastering')
            );
        }

        return array(
            'valid' => true,
            'extension' => $extension,
            'mime_type' => $mime_type,
            'size' => $file['size'],
        );
    }

    /**
     * Sauvegarder un fichier uploadé
     *
     * @param array $file Fichier depuis $_FILES
     * @param int $user_id ID de l'utilisateur
     * @return array|WP_Error Informations du fichier sauvegardé ou WP_Error
     */
    public static function save_upload($file, $user_id) {
        // Valider le fichier
        $validation = self::validate_upload($file);
        if (is_wp_error($validation)) {
            return $validation;
        }

        // Générer un nom de fichier unique et sécurisé
        $original_name = sanitize_file_name($file['name']);
        $extension = $validation['extension'];
        $unique_name = sprintf(
            '%d_%s_%s.%s',
            $user_id,
            time(),
            wp_generate_password(12, false),
            $extension
        );

        // Chemin de destination
        $upload_dir = TEKNUP_STORAGE_DIR . 'original/';
        $file_path = $upload_dir . $unique_name;

        // Déplacer le fichier
        if (!move_uploaded_file($file['tmp_name'], $file_path)) {
            return new WP_Error(
                'save_error',
                __('Impossible de sauvegarder le fichier.', 'teknup-ai-mastering')
            );
        }

        // Sécuriser les permissions
        chmod($file_path, 0644);

        return array(
            'original_filename' => $original_name,
            'stored_filename' => $unique_name,
            'filepath' => $file_path,
            'size' => $validation['size'],
            'mime_type' => $validation['mime_type'],
            'extension' => $extension,
        );
    }

    /**
     * Générer une URL temporaire publique pour Dolby.io
     *
     * @param string $file_path Chemin du fichier
     * @param int $expiration Expiration en secondes (défaut: 1 heure)
     * @return string URL temporaire
     */
    public static function generate_temporary_url($file_path, $expiration = 3600) {
        // Générer un token unique
        $token = wp_generate_password(32, false);

        // Stocker le token avec les infos du fichier en transient
        $transient_key = 'teknup_temp_url_' . $token;
        $transient_data = array(
            'file_path' => $file_path,
            'created_at' => time(),
        );

        set_transient($transient_key, $transient_data, $expiration);

        // Générer l'URL
        return add_query_arg(
            array(
                'teknup_download' => 'temp',
                'token' => $token,
            ),
            home_url('/')
        );
    }

    /**
     * Générer une URL de téléchargement sécurisée pour l'utilisateur
     *
     * @param int $job_id ID du job
     * @param int $user_id ID de l'utilisateur
     * @param int $expiration Expiration en secondes (défaut: 7 jours)
     * @return string URL de téléchargement
     */
    public static function generate_download_url($job_id, $user_id, $expiration = 604800) {
        // Générer un token unique
        $token = wp_generate_password(32, false);

        // Stocker le token avec les infos en transient
        $transient_key = 'teknup_download_' . $token;
        $transient_data = array(
            'job_id' => $job_id,
            'user_id' => $user_id,
            'created_at' => time(),
        );

        set_transient($transient_key, $transient_data, $expiration);

        // Générer l'URL
        return add_query_arg(
            array(
                'teknup_download' => 'secure',
                'token' => $token,
            ),
            home_url('/')
        );
    }

    /**
     * Vérifier et servir un téléchargement sécurisé
     *
     * @param string $token Token de téléchargement
     * @param string $type Type de téléchargement (temp ou secure)
     * @return void
     */
    public static function serve_download($token, $type = 'secure') {
        // Nettoyer le token
        $token = sanitize_text_field($token);

        // Récupérer les données du transient
        $transient_key = 'teknup_' . ($type === 'temp' ? 'temp_url_' : 'download_') . $token;
        $data = get_transient($transient_key);

        if (false === $data) {
            wp_die(
                __('Lien de téléchargement expiré ou invalide.', 'teknup-ai-mastering'),
                __('Erreur', 'teknup-ai-mastering'),
                array('response' => 403)
            );
        }

        // Pour les téléchargements sécurisés, vérifier les permissions
        if ($type === 'secure') {
            $job = Teknup_Database::get_job($data['job_id']);

            if (!$job || !$job->mastered_filepath || !file_exists($job->mastered_filepath)) {
                wp_die(
                    __('Fichier introuvable.', 'teknup-ai-mastering'),
                    __('Erreur', 'teknup-ai-mastering'),
                    array('response' => 404)
                );
            }

            // Vérifier que l'utilisateur a le droit de télécharger
            if ($job->user_id != $data['user_id'] && !current_user_can('manage_options')) {
                wp_die(
                    __('Vous n\'avez pas la permission de télécharger ce fichier.', 'teknup-ai-mastering'),
                    __('Erreur', 'teknup-ai-mastering'),
                    array('response' => 403)
                );
            }

            $file_path = $job->mastered_filepath;
            $filename = $job->original_filename;
        } else {
            // Téléchargement temporaire (pour Dolby)
            $file_path = $data['file_path'];
            $filename = basename($file_path);
        }

        // Servir le fichier
        self::stream_file($file_path, $filename);

        // Supprimer le transient après utilisation (pour les téléchargements sécurisés)
        if ($type === 'secure') {
            delete_transient($transient_key);
        }

        exit;
    }

    /**
     * Streamer un fichier avec les bons headers
     *
     * @param string $file_path Chemin du fichier
     * @param string $filename Nom du fichier pour le téléchargement
     * @return void
     */
    private static function stream_file($file_path, $filename) {
        if (!file_exists($file_path)) {
            status_header(404);
            wp_die(__('Fichier introuvable.', 'teknup-ai-mastering'));
        }

        // Nettoyer le buffer de sortie
        if (ob_get_level()) {
            ob_end_clean();
        }

        // Définir les headers
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $file_path);
        finfo_close($finfo);

        header('Content-Type: ' . $mime_type);
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($file_path));
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: 0');
        header('Pragma: public');

        // Lire et envoyer le fichier par chunks
        $handle = fopen($file_path, 'rb');
        if ($handle) {
            while (!feof($handle)) {
                echo fread($handle, 8192);
                flush();
            }
            fclose($handle);
        }
    }

    /**
     * Sauvegarder un fichier masterisé depuis une URL
     *
     * @param string $url URL du fichier
     * @param int $job_id ID du job
     * @param int $user_id ID de l'utilisateur
     * @return string|WP_Error Chemin du fichier sauvegardé ou WP_Error
     */
    public static function save_mastered_file($url, $job_id, $user_id) {
        // Télécharger le fichier depuis Dolby
        $response = wp_remote_get($url, array(
            'timeout' => 300, // 5 minutes
            'stream' => true,
            'filename' => tempnam(sys_get_temp_dir(), 'teknup_'),
        ));

        if (is_wp_error($response)) {
            return $response;
        }

        $temp_file = $response['filename'];

        // Générer un nom de fichier unique
        $extension = pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION);
        if (!$extension) {
            $extension = 'wav'; // Défaut
        }

        $unique_name = sprintf(
            '%d_%d_%s_mastered.%s',
            $user_id,
            $job_id,
            time(),
            $extension
        );

        // Chemin de destination
        $mastered_dir = TEKNUP_STORAGE_DIR . 'mastered/';
        $file_path = $mastered_dir . $unique_name;

        // Déplacer le fichier
        if (!rename($temp_file, $file_path)) {
            return new WP_Error(
                'save_error',
                __('Impossible de sauvegarder le fichier masterisé.', 'teknup-ai-mastering')
            );
        }

        // Sécuriser les permissions
        chmod($file_path, 0644);

        return $file_path;
    }

    /**
     * Supprimer les fichiers d'un job
     *
     * @param object $job Job de la base de données
     * @return void
     */
    public static function delete_job_files($job) {
        if ($job->original_filepath && file_exists($job->original_filepath)) {
            @unlink($job->original_filepath);
        }

        if ($job->mastered_filepath && file_exists($job->mastered_filepath)) {
            @unlink($job->mastered_filepath);
        }
    }

    /**
     * Nettoyer les vieux fichiers
     *
     * @param int $days Nombre de jours
     * @return int Nombre de fichiers supprimés
     */
    public static function cleanup_old_files($days = 30) {
        $count = 0;
        $time_threshold = time() - ($days * DAY_IN_SECONDS);

        $directories = array(
            TEKNUP_STORAGE_DIR . 'original/',
            TEKNUP_STORAGE_DIR . 'mastered/',
        );

        foreach ($directories as $dir) {
            if (!is_dir($dir)) {
                continue;
            }

            $files = scandir($dir);
            foreach ($files as $file) {
                if ($file === '.' || $file === '..' || $file === 'index.php') {
                    continue;
                }

                $file_path = $dir . $file;
                if (is_file($file_path) && filemtime($file_path) < $time_threshold) {
                    if (@unlink($file_path)) {
                        $count++;
                    }
                }
            }
        }

        return $count;
    }

    /**
     * Obtenir la taille totale du stockage utilisé
     *
     * @return int Taille en bytes
     */
    public static function get_storage_size() {
        $total_size = 0;

        $directories = array(
            TEKNUP_STORAGE_DIR . 'original/',
            TEKNUP_STORAGE_DIR . 'mastered/',
        );

        foreach ($directories as $dir) {
            if (!is_dir($dir)) {
                continue;
            }

            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)
            );

            foreach ($files as $file) {
                $total_size += $file->getSize();
            }
        }

        return $total_size;
    }

    /**
     * Formater une taille de fichier pour l'affichage
     *
     * @param int $bytes Taille en bytes
     * @return string Taille formatée
     */
    public static function format_file_size($bytes) {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));

        return round($bytes, 2) . ' ' . $units[$pow];
    }
}
