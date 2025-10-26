
document.addEventListener('DOMContentLoaded', () => {
    const canvas = document.getElementById('interactive-hero-chart');
    if (!canvas) return;

    const ctx = canvas.getContext('2d');
    let chart;

    const createChart = (history) => {
        const labels = history.map(h => h.date);
        const prices = history.map(h => h.price);

        const gradient = ctx.createLinearGradient(0, 0, 0, canvas.height);
        gradient.addColorStop(0, 'rgba(0, 246, 255, 0.25)');
        gradient.addColorStop(1, 'rgba(0, 246, 255, 0.01)');

        chart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Price',
                    data: prices,
                    borderColor: '#00f6ff',
                    borderWidth: 2.5,
                    pointRadius: 0,
                    fill: true,
                    backgroundColor: gradient,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: {
                    duration: 1200,
                    easing: 'easeInOutCubic'
                },
                legend: {
                    display: false
                },
                scales: {
                    xAxes: [{
                        display: false,
                    }],
                    yAxes: [{
                        display: false,
                    }]
                },
                tooltips: {
                    enabled: true,
                    mode: 'index',
                    intersect: false,
                    callbacks: {
                        label: function(tooltipItem, data) {
                            let label = data.datasets[tooltipItem.datasetIndex].label || '';
                            if (label) {
                                label += ': ';
                            }
                            label += '$' + tooltipItem.yLabel.toFixed(2);
                            return label;
                        }
                    }
                }
            }
        });
    };

    const fetchHistory = async () => {
        try {
            const apiUrl = `http://74.208.158.229/stock-api/history?symbol=NVDA&days=30`;
            const res = await fetch(apiUrl, { cache: 'no-store' });
            if (!res.ok) throw new Error(`HTTP ${res.status}`);
            const data = await res.json();
            if (!Array.isArray(data) || data.length < 2) {
                throw new Error("Insufficient historical data");
            }
            createChart(data);
        } catch (err) {
            console.error("Failed to fetch chart history", err);
            // You could draw a fallback "error" state on the canvas here
        }
    };

    // Listen for new data from the main stock fetcher
    document.addEventListener('stockDataLoaded', (e) => {
        if (!chart) return;

        const nvdaData = e.detail.find(stock => stock.symbol === 'NVDA');
        if (!nvdaData || typeof nvdaData.price !== 'number') return;

        const lastDataPoint = chart.data.datasets[0].data[chart.data.datasets[0].data.length - 1];

        // Only update if the price has changed to avoid needless re-renders
        if (nvdaData.price.toFixed(2) !== lastDataPoint.toFixed(2)) {
            chart.data.labels.push(new Date().toLocaleTimeString());
            chart.data.datasets[0].data.push(nvdaData.price);

            // Keep the chart from getting too crowded
            if (chart.data.labels.length > 100) {
                chart.data.labels.shift();
                chart.data.datasets[0].data.shift();
            }

            chart.update({
                preservation: true // Faster update
            });
        }
    });

    fetchHistory();
});
