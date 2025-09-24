<?php
session_start();
include 'sidebar.php';  // assuming this outputs the sidebar HTML
include 'api/db.php';

if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit;
}

$stmt = $conn->prepare("SELECT username, profile_pic FROM users WHERE id=?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$currentUser = $result->fetch_assoc();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Analytics Dashboard</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/SW/service-worker.js')
        .then(reg => console.log('Service Worker Registered', reg))
        .catch(err => console.log('SW registration failed', err));
    });
}
</script>

<style>
body {
    background: #f4f6f9;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    overflow-x: hidden;
}
.navbar-custom {
    background-color: #4e73df;
}
.navbar-custom .navbar-brand,
.navbar-custom .nav-link,
.navbar-custom .btn {
    color: #fff;
}
.card {
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}
.avatar {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid #fff;
}
.chart-card {
    padding: 20px;
    min-height: 350px;
}
.chart-card canvas {
    max-width: 100%;
    height: 250px !important;
}
h4 {
    font-weight: 600;
}
.main-content {
    margin-left: 250px; /* adjust according to sidebar width */
    padding: 20px;
}
@media(max-width:768px){
    .main-content {
        margin-left:0;
    }
}
</style>
</head>
<body>

<!-- Sidebar is included via sidebar.php -->



<div class="main-content">
    <!-- Engagement Chart -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card chart-card">
                <h4 class="mb-3">Engagement Over Time</h4>
                <canvas id="engagementChart"></canvas>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <!-- Subject Chart -->
        <div class="col-lg-6 mb-4">
            <div class="card chart-card">
                <h4 class="mb-3">Performance by Subject</h4>
                <canvas id="subjectChart"></canvas>
            </div>
        </div>

        <!-- Top Users Chart -->
        <div class="col-lg-6 mb-4">
            <div class="card chart-card">
                <h4 class="mb-3">Top Active Users (Last 7 Days)</h4>
                <canvas id="topUsersChart"></canvas>
            </div>
        </div>
    </div>
</div>

<script>
let engagementChart, subjectChart, topUsersChart;

function fetchAnalyticsData(){
    fetch('analytics_data.php')
    .then(res => res.json())
    .then(data => {
        if(!data.days) return;

        // Engagement Line Chart
        const ctx1 = document.getElementById('engagementChart').getContext('2d');
        if(!engagementChart){
            engagementChart = new Chart(ctx1, {
                type: 'line',
                data: {
                    labels: data.days,
                    datasets: [
                        { label: 'Quizzes Attempted', data: data.quizzesPerDay, borderColor: '#4e73df', backgroundColor: '#4e73dfa0', yAxisID: 'y' },
                        { label: 'Accuracy (%)', data: data.accuracyPerDay, borderColor: '#1cc88a', backgroundColor: '#1cc88aa0', yAxisID: 'y1' }
                    ]
                },
                options: {
                    responsive:true,
                    interaction:{mode:'index',intersect:false},
                    stacked:false,
                    scales:{
                        y:{ type:'linear', position:'left', title:{display:true,text:'Quizzes'}, beginAtZero:true },
                        y1:{ type:'linear', position:'right', title:{display:true,text:'Accuracy %'}, beginAtZero:true },
                        x:{}
                    },
                    plugins:{ legend:{ position:'top' } }
                }
            });
        } else {
            engagementChart.data.labels = data.days;
            engagementChart.data.datasets[0].data = data.quizzesPerDay;
            engagementChart.data.datasets[1].data = data.accuracyPerDay;
            engagementChart.update();
        }

        // Subject Doughnut Chart
        const ctx2 = document.getElementById('subjectChart').getContext('2d');
        if(!subjectChart){
            subjectChart = new Chart(ctx2, {
                type: 'doughnut',
                data: {
                    labels: data.subjects,
                    datasets: [{
                        data: data.subjectAttempts,
                        backgroundColor: ['#FF6384','#36A2EB','#FFCE56','#4BC0C0','#9966FF','#FF9F40']
                    }]
                },
                options:{
                    responsive:true,
                    maintainAspectRatio: false,
                    plugins:{ legend:{ position:'bottom', labels:{ padding:20, boxWidth:15 } } }
                }
            });
        } else {
            subjectChart.data.labels = data.subjects;
            subjectChart.data.datasets[0].data = data.subjectAttempts;
            subjectChart.update();
        }

        // Top Users Bar Chart
        const ctx3 = document.getElementById('topUsersChart').getContext('2d');
        if(!topUsersChart){
            topUsersChart = new Chart(ctx3, {
                type: 'bar',
                data: {
                    labels: data.topUsers.map(u => u.username),
                    datasets: [{
                        label: 'Quizzes Attempted',
                        data: data.topUsers.map(u => u.quizzes),
                        backgroundColor: '#4e73df'
                    }]
                },
                options:{
                    responsive:true,
                    maintainAspectRatio: false,
                    plugins:{ legend:{ display:false } },
                    scales:{ y:{ beginAtZero:true, title:{ display:true, text:'Quizzes' } } }
                }
            });
        } else {
            topUsersChart.data.labels = data.topUsers.map(u => u.username);
            topUsersChart.data.datasets[0].data = data.topUsers.map(u => u.quizzes);
            topUsersChart.update();
        }

    })
    .catch(err => console.error(err));
}

// Fetch data initially and every 10 seconds
fetchAnalyticsData();
setInterval(fetchAnalyticsData, 10000);
</script>

</body>
</html>
