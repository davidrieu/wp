<?php
/**
 * Classe d'intégration API Dolby.io
 *
 * @package Teknup_AI_Mastering
 */

if (!defined('ABSPATH')) {
    exit;
}

class Teknup_Dolby_API {

    /**
     * URL de base de l'API Dolby.io
     */
    const API_BASE_URL = 'https://api.dolby.com/media';

    /**
     * Version de l'API
     */
    const API_VERSION = 'v1';

    /**
     * Instance unique
     */
    private static $instance = null;

    /**
     * Clé API
     */
    private $api_key;

    /**
     * Mode debug
     */
    private $debug_mode;

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
        $this->api_key = get_option('teknup_dolby_api_key', '');
        $this->debug_mode = get_option('teknup_debug_mode', false);
    }

    /**
     * Vérifier si l'API est configurée
     *
     * @return bool
     */
    public function is_configured() {
        return !empty($this->api_key);
    }

    /**
     * Tester la connexion à l'API
     *
     * @return array|WP_Error Résultat du test
     */
    public function test_connection() {
        if (!$this->is_configured()) {
            return new WP_Error(
                'api_not_configured',
                __('Clé API Dolby.io non configurée.', 'teknup-ai-mastering')
            );
        }

        // Faire une requête simple pour tester
        $response = $this->make_request('GET', '/analyze');

        if (is_wp_error($response)) {
            return $response;
        }

        return array(
            'success' => true,
            'message' => __('Connexion à l\'API Dolby.io réussie.', 'teknup-ai-mastering'),
        );
    }

    /**
     * Démarrer un job de mastering
     *
     * @param string $input_url URL du fichier audio à masteriser
     * @param string $output_url URL de destination du fichier masterisé
     * @param array $settings Paramètres de mastering
     * @return array|WP_Error Job ID ou erreur
     */
    public function start_mastering_job($input_url, $output_url, $settings = array()) {
        if (!$this->is_configured()) {
            return new WP_Error(
                'api_not_configured',
                __('Clé API Dolby.io non configurée.', 'teknup-ai-mastering')
            );
        }

        // Paramètres par défaut
        $defaults = array(
            'intensity' => get_option('teknup_default_intensity', 'medium'),
            'genre' => 'electronic',
            'loudness' => array(
                'enable' => true,
                'target_level' => (float) get_option('teknup_default_lufs', -14),
            ),
        );

        $settings = wp_parse_args($settings, $defaults);

        // Construire le body de la requête
        $body = array(
            'input' => $input_url,
            'output' => $output_url,
            'content' => array(
                'type' => 'music',
            ),
            'audio' => array(
                'mastering' => array(
                    'enable' => true,
                    'master_preset' => $this->get_master_preset($settings),
                ),
            ),
        );

        // Ajouter les paramètres de loudness si spécifiés
        if (isset($settings['loudness']) && is_array($settings['loudness'])) {
            $body['audio']['loudness'] = $settings['loudness'];
        }

        $this->log('Starting mastering job', array(
            'input' => $input_url,
            'settings' => $settings,
        ));

        // Faire la requête
        $response = $this->make_request('POST', '/enhance', $body);

        if (is_wp_error($response)) {
            $this->log('Mastering job failed', array(
                'error' => $response->get_error_message(),
            ));
            return $response;
        }

        $this->log('Mastering job started', array(
            'job_id' => $response['job_id'] ?? null,
        ));

        return $response;
    }

    /**
     * Obtenir le statut d'un job
     *
     * @param string $job_id ID du job Dolby
     * @return array|WP_Error Statut du job ou erreur
     */
    public function get_job_status($job_id) {
        if (!$this->is_configured()) {
            return new WP_Error(
                'api_not_configured',
                __('Clé API Dolby.io non configurée.', 'teknup-ai-mastering')
            );
        }

        $response = $this->make_request('GET', "/jobs/{$job_id}");

        if (is_wp_error($response)) {
            $this->log('Failed to get job status', array(
                'job_id' => $job_id,
                'error' => $response->get_error_message(),
            ));
            return $response;
        }

        return $response;
    }

    /**
     * Annuler un job
     *
     * @param string $job_id ID du job Dolby
     * @return bool|WP_Error True si succès, WP_Error sinon
     */
    public function cancel_job($job_id) {
        if (!$this->is_configured()) {
            return new WP_Error(
                'api_not_configured',
                __('Clé API Dolby.io non configurée.', 'teknup-ai-mastering')
            );
        }

        $response = $this->make_request('DELETE', "/jobs/{$job_id}");

        if (is_wp_error($response)) {
            return $response;
        }

        $this->log('Job cancelled', array('job_id' => $job_id));

        return true;
    }

    /**
     * Faire une requête à l'API Dolby.io
     *
     * @param string $method Méthode HTTP
     * @param string $endpoint Endpoint de l'API
     * @param array $body Corps de la requête (optionnel)
     * @return array|WP_Error Réponse ou erreur
     */
    private function make_request($method, $endpoint, $body = null) {
        $url = self::API_BASE_URL . '/' . self::API_VERSION . $endpoint;

        $args = array(
            'method' => $method,
            'headers' => array(
                'x-api-key' => $this->api_key,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ),
            'timeout' => 60,
        );

        if ($body !== null) {
            $args['body'] = json_encode($body);
        }

        $this->log('API Request', array(
            'method' => $method,
            'url' => $url,
            'body' => $body,
        ));

        // Faire la requête avec retry
        $max_retries = 3;
        $retry_delay = 2; // secondes

        for ($i = 0; $i < $max_retries; $i++) {
            $response = wp_remote_request($url, $args);

            if (!is_wp_error($response)) {
                break;
            }

            if ($i < $max_retries - 1) {
                sleep($retry_delay);
                $retry_delay *= 2; // Exponential backoff
            }
        }

        if (is_wp_error($response)) {
            $this->log('API Request Failed', array(
                'error' => $response->get_error_message(),
            ));
            return $response;
        }

        $status_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);

        $this->log('API Response', array(
            'status_code' => $status_code,
            'body' => $response_body,
        ));

        // Vérifier le code de statut
        if ($status_code < 200 || $status_code >= 300) {
            $error_message = __('Erreur API Dolby.io', 'teknup-ai-mastering');

            $decoded = json_decode($response_body, true);
            if (isset($decoded['error'])) {
                $error_message .= ': ' . $decoded['error'];
            } elseif (isset($decoded['message'])) {
                $error_message .= ': ' . $decoded['message'];
            }

            return new WP_Error('api_error', $error_message, array('status_code' => $status_code));
        }

        // Décoder la réponse JSON
        $decoded = json_decode($response_body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return new WP_Error(
                'json_error',
                __('Erreur lors du décodage de la réponse JSON', 'teknup-ai-mastering')
            );
        }

        return $decoded;
    }

    /**
     * Obtenir le preset de mastering selon les paramètres
     *
     * @param array $settings Paramètres de mastering
     * @return string Preset
     */
    private function get_master_preset($settings) {
        $intensity = $settings['intensity'] ?? 'medium';
        $genre = $settings['genre'] ?? 'electronic';

        // Map des presets selon l'intensité et le genre
        $presets = array(
            'low' => 'music_low_intensity',
            'medium' => 'music_medium_intensity',
            'high' => 'music_high_intensity',
        );

        return $presets[$intensity] ?? 'music_medium_intensity';
    }

    /**
     * Logger un événement
     *
     * @param string $message Message à logger
     * @param array $context Contexte additionnel
     * @return void
     */
    private function log($message, $context = array()) {
        if (!$this->debug_mode) {
            return;
        }

        $log_entry = array(
            'timestamp' => current_time('mysql'),
            'message' => $message,
            'context' => $context,
        );

        // Logger dans le fichier de debug WordPress
        if (defined('WP_DEBUG') && WP_DEBUG && defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
            error_log('Teknup Dolby API: ' . print_r($log_entry, true));
        }

        // Stocker également dans une option pour l'affichage dans l'admin
        $logs = get_option('teknup_api_logs', array());
        $logs[] = $log_entry;

        // Limiter à 100 entrées
        if (count($logs) > 100) {
            $logs = array_slice($logs, -100);
        }

        update_option('teknup_api_logs', $logs);
    }

    /**
     * Obtenir les logs
     *
     * @return array
     */
    public function get_logs() {
        return get_option('teknup_api_logs', array());
    }

    /**
     * Vider les logs
     *
     * @return void
     */
    public function clear_logs() {
        delete_option('teknup_api_logs');
    }
}
