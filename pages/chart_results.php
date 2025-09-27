<?php
require_once '../includes/auth.php';
require_once '../config/database.php';

$auth = new Auth();
$auth->redirectIfNotLoggedIn();

if (!isset($_GET['poll_id'])) {
    header('Location: my_polls.php');
    exit;
}

$pollId = intval($_GET['poll_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Advanced Results - Voting System</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f5f5f5; }
        .navbar { background: #333; color: white; padding: 1rem 2rem; display: flex; justify-content: space-between; }
        .nav-links a { color: white; text-decoration: none; margin-left: 1rem; padding: 0.5rem 1rem; }
        .container { max-width: 1200px; margin: 2rem auto; padding: 0 2rem; }
        .chart-container { 
            background: white; 
            padding: 2rem; 
            margin: 1rem 0; 
            border-radius: 10px; 
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .chart-row { display: flex; flex-wrap: wrap; gap: 2rem; }
        .chart-box { flex: 1; min-width: 300px; }
        canvas { max-height: 400px; }
    </style>
</head>
<body>
    <nav class="navbar">
        <h1>📈 Advanced Results</h1>
        <div class="nav-links">
            <a href="results.php?poll_id=<?php echo $pollId; ?>">← Basic Results</a>
            <a href="my_polls.php">My Polls</a>
            <a href="../api/auth/logout.php">Logout</a>
        </div>
    </nav>
    
    <div class="container">
        <div id="loading" style="text-align: center; padding: 3rem;">
            <h3>Loading charts...</h3>
        </div>
        
        <div id="chartsContent" style="display: none;">
            <div class="chart-container">
                <h2 id="pollQuestion" style="text-align: center; margin-bottom: 2rem;"></h2>
                
                <div class="chart-row">
                    <div class="chart-box">
                        <h3>Bar Chart</h3>
                        <canvas id="barChart"></canvas>
                    </div>
                    <div class="chart-box">
                        <h3>Pie Chart</h3>
                        <canvas id="pieChart"></canvas>
                    </div>
                </div>
                
                <div class="chart-row">
                    <div class="chart-box">
                        <h3>Doughnut Chart</h3>
                        <canvas id="doughnutChart"></canvas>
                    </div>
                    <div class="chart-box">
                        <h3>Horizontal Bar</h3>
                        <canvas id="horizontalBarChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const pollId = <?php echo $pollId; ?>;
        let chartInstances = [];
        
        async function loadChartData() {
            try {
                const response = await fetch(`../api/polls/results.php?pollId=${pollId}`);
                const result = await response.json();
                
                if (result.success) {
                    displayCharts(result);
                } else {
                    document.getElementById('loading').innerHTML = '<h3 style="color: red;">Error loading data</h3>';
                }
            } catch (error) {
                document.getElementById('loading').innerHTML = '<h3 style="color: red;">Network error</h3>';
            }
        }
        
        function displayCharts(data) {
            document.getElementById('loading').style.display = 'none';
            document.getElementById('chartsContent').style.display = 'block';
            document.getElementById('pollQuestion').textContent = data.poll.question;
            
            const labels = data.options.map(opt => opt.option_text);
            const votes = data.options.map(opt => opt.vote_count);
            const totalVotes = data.totalVotes;
            
            // Generate colors
            const colors = generateColors(data.options.length);
            
            // Destroy existing charts
            chartInstances.forEach(chart => chart.destroy());
            chartInstances = [];
            
            // Bar Chart
            const barCtx = document.getElementById('barChart').getContext('2d');
            chartInstances.push(new Chart(barCtx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Votes',
                        data: votes,
                        backgroundColor: colors,
                        borderColor: colors.map(c => c.replace('0.7', '1')),
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: { display: false },
                        title: { display: true, text: 'Vote Distribution' }
                    }
                }
            }));
            
            // Pie Chart
            const pieCtx = document.getElementById('pieChart').getContext('2d');
            chartInstances.push(new Chart(pieCtx, {
                type: 'pie',
                data: {
                    labels: labels,
                    datasets: [{
                        data: votes,
                        backgroundColor: colors
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: { position: 'right' }
                    }
                }
            }));
            
            // Doughnut Chart
            const doughnutCtx = document.getElementById('doughnutChart').getContext('2d');
            chartInstances.push(new Chart(doughnutCtx, {
                type: 'doughnut',
                data: {
                    labels: labels,
                    datasets: [{
                        data: votes,
                        backgroundColor: colors
                    }]
                },
                options: {
                    responsive: true,
                    cutout: '50%'
                }
            }));
            
            // Horizontal Bar Chart
            const horizontalCtx = document.getElementById('horizontalBarChart').getContext('2d');
            chartInstances.push(new Chart(horizontalCtx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Votes',
                        data: votes,
                        backgroundColor: colors
                    }]
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    plugins: {
                        legend: { display: false }
                    }
                }
            }));
        }
        
        function generateColors(count) {
            const colors = [];
            for (let i = 0; i < count; i++) {
                const hue = (i * 360 / count) % 360;
                colors.push(`hsla(${hue}, 70%, 60%, 0.7)`);
            }
            return colors;
        }
        
        // Load charts when page loads
        loadChartData();
    </script>
</body>
</html>