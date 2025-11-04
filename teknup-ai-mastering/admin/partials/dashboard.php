<?php
/**
 * Template du dashboard admin
 *
 * @package Teknup_AI_Mastering
 */

if (!defined('ABSPATH')) {
    exit;
}

$stats = Teknup_Database::get_global_stats();
$storage_size = Teknup_File_Manager::get_storage_size();
?>

<div class="wrap teknup-admin-dashboard">
    <h1><?php echo esc_html__('Teknup AI Mastering - Dashboard', 'teknup-ai-mastering'); ?></h1>

    <div class="teknup-stats-grid">
        <div class="teknup-stat-card">
            <h3><?php echo esc_html__('Total Jobs', 'teknup-ai-mastering'); ?></h3>
            <p class="teknup-stat-number"><?php echo esc_html(number_format($stats['total_jobs'])); ?></p>
        </div>

        <div class="teknup-stat-card">
            <h3><?php echo esc_html__('Jobs Complétés', 'teknup-ai-mastering'); ?></h3>
            <p class="teknup-stat-number"><?php echo esc_html(number_format($stats['completed_jobs'])); ?></p>
        </div>

        <div class="teknup-stat-card">
            <h3><?php echo esc_html__('Jobs ce Mois', 'teknup-ai-mastering'); ?></h3>
            <p class="teknup-stat-number"><?php echo esc_html(number_format($stats['monthly_jobs'])); ?></p>
        </div>

        <div class="teknup-stat-card">
            <h3><?php echo esc_html__('Taux de Succès', 'teknup-ai-mastering'); ?></h3>
            <p class="teknup-stat-number"><?php echo esc_html($stats['success_rate']); ?>%</p>
        </div>

        <div class="teknup-stat-card">
            <h3><?php echo esc_html__('Temps Moyen', 'teknup-ai-mastering'); ?></h3>
            <p class="teknup-stat-number"><?php echo esc_html(round($stats['avg_processing_time'] / 60, 1)); ?> min</p>
        </div>

        <div class="teknup-stat-card">
            <h3><?php echo esc_html__('Stockage Utilisé', 'teknup-ai-mastering'); ?></h3>
            <p class="teknup-stat-number"><?php echo esc_html(Teknup_File_Manager::format_file_size($storage_size)); ?></p>
        </div>
    </div>

    <div class="teknup-chart-container">
        <h2><?php echo esc_html__('Activité des 30 derniers jours', 'teknup-ai-mastering'); ?></h2>
        <canvas id="teknup-activity-chart"></canvas>
    </div>

    <?php
    // Récupérer les stats quotidiennes pour le graphique
    $daily_stats = Teknup_Database::get_daily_stats(30);
    $chart_data = json_encode($daily_stats);
    ?>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const data = <?php echo $chart_data; ?>;
        const labels = data.map(d => d.date);
        const completed = data.map(d => parseInt(d.completed));
        const failed = data.map(d => parseInt(d.failed));

        const ctx = document.getElementById('teknup-activity-chart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Complétés',
                    data: completed,
                    borderColor: '#DC143C',
                    backgroundColor: 'rgba(220, 20, 60, 0.1)',
                    tension: 0.4
                }, {
                    label: 'Échoués',
                    data: failed,
                    borderColor: '#8B0000',
                    backgroundColor: 'rgba(139, 0, 0, 0.1)',
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'top',
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    });
    </script>
</div>
