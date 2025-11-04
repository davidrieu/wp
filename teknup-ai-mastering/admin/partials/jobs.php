<?php
/**
 * Template de gestion des jobs admin
 *
 * @package Teknup_AI_Mastering
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap teknup-admin-jobs">
    <h1><?php echo esc_html__('Teknup AI Mastering - Gestion des Jobs', 'teknup-ai-mastering'); ?></h1>

    <div class="teknup-jobs-filters">
        <select id="teknup-status-filter">
            <option value=""><?php echo esc_html__('Tous les statuts', 'teknup-ai-mastering'); ?></option>
            <option value="pending"><?php echo esc_html__('En attente', 'teknup-ai-mastering'); ?></option>
            <option value="uploaded"><?php echo esc_html__('Uploadé', 'teknup-ai-mastering'); ?></option>
            <option value="processing"><?php echo esc_html__('En traitement', 'teknup-ai-mastering'); ?></option>
            <option value="completed"><?php echo esc_html__('Complété', 'teknup-ai-mastering'); ?></option>
            <option value="failed"><?php echo esc_html__('Échoué', 'teknup-ai-mastering'); ?></option>
        </select>
        <button type="button" id="teknup-refresh-jobs" class="button"><?php echo esc_html__('Rafraîchir', 'teknup-ai-mastering'); ?></button>
    </div>

    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th><?php echo esc_html__('ID', 'teknup-ai-mastering'); ?></th>
                <th><?php echo esc_html__('Utilisateur', 'teknup-ai-mastering'); ?></th>
                <th><?php echo esc_html__('Fichier', 'teknup-ai-mastering'); ?></th>
                <th><?php echo esc_html__('Statut', 'teknup-ai-mastering'); ?></th>
                <th><?php echo esc_html__('Date', 'teknup-ai-mastering'); ?></th>
                <th><?php echo esc_html__('Actions', 'teknup-ai-mastering'); ?></th>
            </tr>
        </thead>
        <tbody id="teknup-jobs-list">
            <tr>
                <td colspan="6"><?php echo esc_html__('Chargement...', 'teknup-ai-mastering'); ?></td>
            </tr>
        </tbody>
    </table>

    <div class="teknup-pagination"></div>
</div>

<script>
jQuery(document).ready(function($) {
    let currentPage = 1;

    function loadJobs() {
        const status = $('#teknup-status-filter').val();

        $.ajax({
            url: teknupAdmin.ajaxUrl,
            type: 'GET',
            data: {
                action: 'teknup_admin_get_jobs',
                nonce: teknupAdmin.nonce,
                page: currentPage,
                status: status
            },
            success: function(response) {
                if (response.success) {
                    displayJobs(response.data.jobs);
                }
            }
        });
    }

    function displayJobs(jobs) {
        const tbody = $('#teknup-jobs-list');
        tbody.empty();

        if (jobs.length === 0) {
            tbody.append('<tr><td colspan="6">Aucun job trouvé</td></tr>');
            return;
        }

        jobs.forEach(function(job) {
            const user = job.user_id;
            const statusBadge = getStatusBadge(job.status);

            const row = `
                <tr>
                    <td>${job.id}</td>
                    <td>${user}</td>
                    <td>${job.original_filename}</td>
                    <td>${statusBadge}</td>
                    <td>${job.created_at}</td>
                    <td>
                        ${job.status === 'failed' ? '<button class="button button-small teknup-retry-job" data-job-id="' + job.id + '">Relancer</button>' : ''}
                        <button class="button button-small teknup-delete-job" data-job-id="${job.id}">Supprimer</button>
                    </td>
                </tr>
            `;
            tbody.append(row);
        });
    }

    function getStatusBadge(status) {
        const badges = {
            'pending': '<span class="teknup-badge teknup-badge-pending">En attente</span>',
            'uploaded': '<span class="teknup-badge teknup-badge-uploaded">Uploadé</span>',
            'processing': '<span class="teknup-badge teknup-badge-processing">En traitement</span>',
            'completed': '<span class="teknup-badge teknup-badge-completed">Complété</span>',
            'failed': '<span class="teknup-badge teknup-badge-failed">Échoué</span>'
        };
        return badges[status] || status;
    }

    $('#teknup-status-filter').on('change', loadJobs);
    $('#teknup-refresh-jobs').on('click', loadJobs);

    $(document).on('click', '.teknup-delete-job', function() {
        if (!confirm('Êtes-vous sûr de vouloir supprimer ce job ?')) {
            return;
        }

        const jobId = $(this).data('job-id');

        $.ajax({
            url: teknupAdmin.ajaxUrl,
            type: 'POST',
            data: {
                action: 'teknup_admin_delete_job',
                nonce: teknupAdmin.nonce,
                job_id: jobId
            },
            success: function() {
                loadJobs();
            }
        });
    });

    $(document).on('click', '.teknup-retry-job', function() {
        const jobId = $(this).data('job-id');

        $.ajax({
            url: teknupAdmin.ajaxUrl,
            type: 'POST',
            data: {
                action: 'teknup_admin_retry_job',
                nonce: teknupAdmin.nonce,
                job_id: jobId
            },
            success: function() {
                loadJobs();
            }
        });
    });

    // Charger les jobs au démarrage
    loadJobs();
});
</script>
