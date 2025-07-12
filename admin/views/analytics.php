    <?php if (!empty($chart_data)): ?>
    var ctx = document.getElementById('daily-usage-chart').getContext('2d');
    var chart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: [<?php echo '"' . implode('","', array_column($chart_data, 'date')) . '"'; ?>],
            datasets: [{
                label: '<?php _e("Conversations", "srs-ai-chatbot"); ?>',
                data: [<?php echo implode(',', array_column($chart_data, 'sessions')); ?>],
                borderColor: '#0073aa',
                backgroundColor: 'rgba(0, 115, 170, 0.1)',
                tension: 0.4
            }, {
                label: '<?php _e("Messages", "srs-ai-chatbot"); ?>',
                data: [<?php echo implode(',', array_column($chart_data, 'messages')); ?>],
                borderColor: '#46b450',
                backgroundColor: 'rgba(70, 180, 80, 0.1)',
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                },
                title: {
                    display: true,
                    text: '<?php printf(__("Usage Trends - Last %d Days", "srs-ai-chatbot"), $period); ?>'
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    }
                }
            }
        }
    });
    <?php else: ?>
    $('#daily-usage-chart').parent().html('<p><?php _e("No chart data available for the selected period.", "srs-ai-chatbot"); ?></p>');
    <?php endif; ?>
});
</script>

<!-- Load Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
