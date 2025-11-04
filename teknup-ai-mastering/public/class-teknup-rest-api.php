<?php
/**
 * Classe REST API
 *
 * @package Teknup_AI_Mastering
 */

if (!defined('ABSPATH')) {
    exit;
}

class Teknup_REST_API {

    /**
     * Instance unique
     */
    private static $instance = null;

    /**
     * Namespace de l'API
     */
    const NAMESPACE = 'teknup/v1';

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
        add_action('rest_api_init', array($this, 'register_routes'));
        add_action('init', array($this, 'handle_download_request'));
        add_action('init', array($this, 'handle_webhook_request'));
    }

    /**
     * Enregistrer les routes REST API
     */
    public function register_routes() {
        // Obtenir les limites et usage de l'utilisateur
        register_rest_route(self::NAMESPACE, '/user/subscription', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_user_subscription'),
            'permission_callback' => array($this, 'check_user_permission'),
        ));

        // Upload de fichier
        register_rest_route(self::NAMESPACE, '/upload', array(
            'methods' => 'POST',
            'callback' => array($this, 'upload_file'),
            'permission_callback' => array($this, 'check_user_permission'),
        ));

        // Créer et démarrer un job
        register_rest_route(self::NAMESPACE, '/jobs/start', array(
            'methods' => 'POST',
            'callback' => array($this, 'start_job'),
            'permission_callback' => array($this, 'check_user_permission'),
        ));

        // Obtenir le statut d'un job
        register_rest_route(self::NAMESPACE, '/jobs/(?P<id>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_job_status'),
            'permission_callback' => array($this, 'check_user_permission'),
        ));

        // Lister les jobs de l'utilisateur
        register_rest_route(self::NAMESPACE, '/jobs', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_user_jobs'),
            'permission_callback' => array($this, 'check_user_permission'),
        ));

        // Supprimer un job
        register_rest_route(self::NAMESPACE, '/jobs/(?P<id>\d+)', array(
            'methods' => 'DELETE',
            'callback' => array($this, 'delete_job'),
            'permission_callback' => array($this, 'check_user_permission'),
        ));

        // Relancer un job
        register_rest_route(self::NAMESPACE, '/jobs/(?P<id>\d+)/retry', array(
            'methods' => 'POST',
            'callback' => array($this, 'retry_job'),
            'permission_callback' => array($this, 'check_user_permission'),
        ));

        // Générer une URL de téléchargement
        register_rest_route(self::NAMESPACE, '/jobs/(?P<id>\d+)/download', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_download_url'),
            'permission_callback' => array($this, 'check_user_permission'),
        ));

        // Obtenir les statistiques de l'utilisateur
        register_rest_route(self::NAMESPACE, '/user/stats', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_user_stats'),
            'permission_callback' => array($this, 'check_user_permission'),
        ));

        // Obtenir la liste des plans d'abonnement disponibles
        register_rest_route(self::NAMESPACE, '/plans', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_available_plans'),
            'permission_callback' => array($this, 'check_user_permission'),
        ));
    }

    /**
     * Vérifier les permissions utilisateur
     */
    public function check_user_permission() {
        return is_user_logged_in();
    }

    /**
     * Obtenir les informations d'abonnement de l'utilisateur
     */
    public function get_user_subscription(WP_REST_Request $request) {
        $user_id = get_current_user_id();
        $subscription_manager = Teknup_Subscription_Manager::get_instance();

        $sub_info = $subscription_manager->get_user_subscription_info($user_id);

        return new WP_REST_Response($sub_info, 200);
    }

    /**
     * Upload un fichier et créer un job
     */
    public function upload_file(WP_REST_Request $request) {
        $user_id = get_current_user_id();

        // Récupérer le fichier depuis $_FILES
        if (empty($_FILES['file'])) {
            return new WP_Error(
                'no_file',
                __('Aucun fichier n\'a été uploadé.', 'teknup-ai-mastering'),
                array('status' => 400)
            );
        }

        $file = $_FILES['file'];

        // Récupérer les paramètres
        $settings = array(
            'intensity' => $request->get_param('intensity') ?? 'medium',
            'genre' => $request->get_param('genre') ?? 'electronic',
        );

        // Ajouter LUFS si spécifié et si l'utilisateur a accès
        if ($request->get_param('lufs')) {
            $subscription_manager = Teknup_Subscription_Manager::get_instance();
            if ($subscription_manager->user_has_feature($user_id, 'lufs_control')) {
                $settings['loudness'] = array(
                    'enable' => true,
                    'target_level' => (float) $request->get_param('lufs'),
                );
            }
        }

        // Créer le job
        $job_manager = Teknup_Job_Manager::get_instance();
        $job_id = $job_manager->create_job($file, $settings, $user_id);

        if (is_wp_error($job_id)) {
            return $job_id;
        }

        // Démarrer le processing
        $start_result = $job_manager->start_processing($job_id);

        if (is_wp_error($start_result)) {
            return $start_result;
        }

        // Récupérer le job créé
        $job = Teknup_Database::get_job($job_id);

        return new WP_REST_Response(array(
            'success' => true,
            'job_id' => $job_id,
            'job' => $this->format_job_response($job),
        ), 201);
    }

    /**
     * Démarrer un job (si créé séparément)
     */
    public function start_job(WP_REST_Request $request) {
        $job_id = $request->get_param('job_id');

        if (!$job_id) {
            return new WP_Error(
                'missing_job_id',
                __('ID de job manquant.', 'teknup-ai-mastering'),
                array('status' => 400)
            );
        }

        $job_manager = Teknup_Job_Manager::get_instance();
        $result = $job_manager->start_processing($job_id);

        if (is_wp_error($result)) {
            return $result;
        }

        $job = Teknup_Database::get_job($job_id);

        return new WP_REST_Response(array(
            'success' => true,
            'job' => $this->format_job_response($job),
        ), 200);
    }

    /**
     * Obtenir le statut d'un job
     */
    public function get_job_status(WP_REST_Request $request) {
        $job_id = $request->get_param('id');
        $user_id = get_current_user_id();

        $job = Teknup_Database::get_job($job_id);

        if (!$job) {
            return new WP_Error(
                'job_not_found',
                __('Job introuvable.', 'teknup-ai-mastering'),
                array('status' => 404)
            );
        }

        // Vérifier que le job appartient à l'utilisateur
        if ($job->user_id != $user_id && !current_user_can('manage_options')) {
            return new WP_Error(
                'forbidden',
                __('Vous n\'avez pas accès à ce job.', 'teknup-ai-mastering'),
                array('status' => 403)
            );
        }

        return new WP_REST_Response(array(
            'success' => true,
            'job' => $this->format_job_response($job),
        ), 200);
    }

    /**
     * Obtenir la liste des jobs de l'utilisateur
     */
    public function get_user_jobs(WP_REST_Request $request) {
        $user_id = get_current_user_id();

        $args = array(
            'limit' => $request->get_param('per_page') ?? 20,
            'offset' => $request->get_param('offset') ?? 0,
            'status' => $request->get_param('status') ?? null,
            'order_by' => $request->get_param('order_by') ?? 'created_at',
            'order' => $request->get_param('order') ?? 'DESC',
        );

        $jobs = Teknup_Database::get_user_jobs($user_id, $args);
        $total = Teknup_Database::count_user_jobs($user_id, $args['status']);

        $formatted_jobs = array_map(array($this, 'format_job_response'), $jobs);

        return new WP_REST_Response(array(
            'success' => true,
            'jobs' => $formatted_jobs,
            'total' => $total,
            'page' => floor($args['offset'] / $args['limit']) + 1,
            'per_page' => $args['limit'],
        ), 200);
    }

    /**
     * Supprimer un job
     */
    public function delete_job(WP_REST_Request $request) {
        $job_id = $request->get_param('id');
        $user_id = get_current_user_id();

        $job = Teknup_Database::get_job($job_id);

        if (!$job) {
            return new WP_Error(
                'job_not_found',
                __('Job introuvable.', 'teknup-ai-mastering'),
                array('status' => 404)
            );
        }

        // Vérifier que le job appartient à l'utilisateur
        if ($job->user_id != $user_id && !current_user_can('manage_options')) {
            return new WP_Error(
                'forbidden',
                __('Vous n\'avez pas accès à ce job.', 'teknup-ai-mastering'),
                array('status' => 403)
            );
        }

        $job_manager = Teknup_Job_Manager::get_instance();
        $result = $job_manager->delete_job($job_id);

        if (is_wp_error($result)) {
            return $result;
        }

        return new WP_REST_Response(array(
            'success' => true,
            'message' => __('Job supprimé avec succès.', 'teknup-ai-mastering'),
        ), 200);
    }

    /**
     * Relancer un job échoué
     */
    public function retry_job(WP_REST_Request $request) {
        $job_id = $request->get_param('id');
        $user_id = get_current_user_id();

        $job = Teknup_Database::get_job($job_id);

        if (!$job) {
            return new WP_Error(
                'job_not_found',
                __('Job introuvable.', 'teknup-ai-mastering'),
                array('status' => 404)
            );
        }

        // Vérifier que le job appartient à l'utilisateur
        if ($job->user_id != $user_id && !current_user_can('manage_options')) {
            return new WP_Error(
                'forbidden',
                __('Vous n\'avez pas accès à ce job.', 'teknup-ai-mastering'),
                array('status' => 403)
            );
        }

        $job_manager = Teknup_Job_Manager::get_instance();
        $result = $job_manager->retry_job($job_id);

        if (is_wp_error($result)) {
            return $result;
        }

        $job = Teknup_Database::get_job($job_id);

        return new WP_REST_Response(array(
            'success' => true,
            'job' => $this->format_job_response($job),
        ), 200);
    }

    /**
     * Obtenir une URL de téléchargement
     */
    public function get_download_url(WP_REST_Request $request) {
        $job_id = $request->get_param('id');
        $user_id = get_current_user_id();

        $job = Teknup_Database::get_job($job_id);

        if (!$job) {
            return new WP_Error(
                'job_not_found',
                __('Job introuvable.', 'teknup-ai-mastering'),
                array('status' => 404)
            );
        }

        // Vérifier que le job appartient à l'utilisateur
        if ($job->user_id != $user_id && !current_user_can('manage_options')) {
            return new WP_Error(
                'forbidden',
                __('Vous n\'avez pas accès à ce job.', 'teknup-ai-mastering'),
                array('status' => 403)
            );
        }

        if ($job->status !== 'completed' || !$job->mastered_filepath) {
            return new WP_Error(
                'job_not_completed',
                __('Le job n\'est pas encore terminé.', 'teknup-ai-mastering'),
                array('status' => 400)
            );
        }

        $download_url = Teknup_File_Manager::generate_download_url($job_id, $user_id);

        return new WP_REST_Response(array(
            'success' => true,
            'download_url' => $download_url,
        ), 200);
    }

    /**
     * Obtenir les statistiques de l'utilisateur
     */
    public function get_user_stats(WP_REST_Request $request) {
        $user_id = get_current_user_id();
        $job_manager = Teknup_Job_Manager::get_instance();

        $stats = $job_manager->get_user_stats($user_id);

        return new WP_REST_Response($stats, 200);
    }

    /**
     * Obtenir la liste des plans d'abonnement disponibles
     */
    public function get_available_plans(WP_REST_Request $request) {
        $user_id = get_current_user_id();
        $subscription_manager = Teknup_Subscription_Manager::get_instance();

        // Récupérer le plan actuel de l'utilisateur
        $current_plan = $subscription_manager->get_user_plan($user_id);

        // Récupérer les IDs des produits Teknup
        $product_ids = get_option('teknup_product_ids', array());

        if (empty($product_ids)) {
            return new WP_REST_Response(array(
                'success' => false,
                'message' => __('Aucun plan disponible. Veuillez contacter l\'administrateur.', 'teknup-ai-mastering'),
                'plans' => array(),
            ), 200);
        }

        $plans = array();

        foreach ($product_ids as $plan_slug => $product_id) {
            $product = wc_get_product($product_id);

            if (!$product) {
                continue;
            }

            $plan_info = $subscription_manager->get_plan_info($plan_slug);

            if (!$plan_info) {
                continue;
            }

            $plans[] = array(
                'slug' => $plan_slug,
                'name' => $plan_info['name'],
                'price' => $product->get_price(),
                'price_html' => $product->get_price_html(),
                'limit' => $plan_info['limit'],
                'features' => $plan_info['features'],
                'product_url' => get_permalink($product_id),
                'is_current' => $current_plan === $plan_slug,
                'description' => $product->get_short_description(),
            );
        }

        // Trier par prix
        usort($plans, function($a, $b) {
            return floatval($a['price']) - floatval($b['price']);
        });

        return new WP_REST_Response(array(
            'success' => true,
            'plans' => $plans,
            'current_plan' => $current_plan,
        ), 200);
    }

    /**
     * Formater un job pour la réponse API
     */
    private function format_job_response($job) {
        if (!$job) {
            return null;
        }

        return array(
            'id' => (int) $job->id,
            'filename' => $job->original_filename,
            'status' => $job->status,
            'file_size' => (int) $job->file_size,
            'file_size_formatted' => Teknup_File_Manager::format_file_size($job->file_size),
            'settings' => $job->settings,
            'error_message' => $job->error_message,
            'created_at' => $job->created_at,
            'uploaded_at' => $job->uploaded_at,
            'processing_started_at' => $job->processing_started_at,
            'completed_at' => $job->completed_at,
            'has_mastered_file' => !empty($job->mastered_filepath) && file_exists($job->mastered_filepath),
        );
    }

    /**
     * Gérer les requêtes de téléchargement
     */
    public function handle_download_request() {
        if (!isset($_GET['teknup_download']) || !isset($_GET['token'])) {
            return;
        }

        $type = sanitize_text_field($_GET['teknup_download']);
        $token = sanitize_text_field($_GET['token']);

        Teknup_File_Manager::serve_download($token, $type);
    }

    /**
     * Gérer les webhooks de Dolby
     */
    public function handle_webhook_request() {
        if (!isset($_GET['teknup_webhook']) || $_GET['teknup_webhook'] !== 'dolby_output') {
            return;
        }

        // Lire le body JSON
        $body = file_get_contents('php://input');
        $data = json_decode($body, true);

        if (!$data || !isset($_GET['job_id'])) {
            wp_die('Invalid webhook', 'Error', array('response' => 400));
        }

        $job_id = (int) $_GET['job_id'];

        // Logger le webhook
        if (get_option('teknup_debug_mode', false)) {
            error_log('Teknup Webhook received: ' . print_r($data, true));
        }

        // Traiter la complétion du job
        $job_manager = Teknup_Job_Manager::get_instance();

        if (isset($data['output']) && isset($data['status']) && $data['status'] === 'Success') {
            $job_manager->handle_job_completion($job_id, $data['output']);
        } elseif (isset($data['status']) && $data['status'] === 'Failed') {
            $error = $data['error'] ?? __('Erreur inconnue', 'teknup-ai-mastering');
            Teknup_Database::update_job_status($job_id, 'failed', $error);
            Teknup_Notifications::get_instance()->send_failure_notification($job_id);
        }

        wp_die('Webhook processed', 'Success', array('response' => 200));
    }
}
