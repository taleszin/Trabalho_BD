<?php
session_start();
require_once '../classes/config.php';
require_once '../classes/DashboardService.php';
if (!isset($_SESSION['id_usuario'])) {
    header("Location: login");
    exit;
}
$idAluno = (int)$_SESSION['id_usuario'];

$dashboardService = new DashboardService($conexao);

$nomeAluno = $dashboardService->getStudentName($idAluno);
$overallStats = $dashboardService->getOverall($idAluno);
$disciplineStats = $dashboardService->getByDiscipline($idAluno);
$evolutionStats = $dashboardService->getPerformanceEvolution($idAluno);
$comparativeStats = $dashboardService->getComparativePerformance($idAluno);

$disciplineLabels = array_column($disciplineStats, 'disciplina');
$disciplineData = array_column($disciplineStats, 'percentual');

$evolutionLabels = array_column($evolutionStats, 'data');
$evolutionData = array_column($evolutionStats, 'percentual');

$comparativeLabels = array_column($comparativeStats, 'disciplina');
$comparativeUserData = array_column($comparativeStats, 'media_aluno');
$comparativePlatformData = array_column($comparativeStats, 'media_plataforma');
include_once 'header.php';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard de Desempenho - MedLeap</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/dashboard.css">
</head>
<body>

<div class="container-fluid p-4">
    <header class="main-header mb-4">
        <h2>Dashboard de Desempenho</h2>
        <p class="lead">Olá, <?php echo htmlspecialchars($nomeAluno); ?>! Acompanhe sua jornada para se tornar referência.</p>
    </header>

    <?php if ($overallStats['total'] == 0): ?>
        <div class="alert alert-info text-center">
            <h4 class="alert-heading">Tudo pronto para começar!</h4>
            <p>Você ainda não respondeu nenhuma questão. Responda algumas questões no Banco de Questões para que possamos gerar suas métricas de desempenho.</p>
        </div>
    <?php else: ?>
        <div class="row g-4 mb-4">
            <div class="col-lg-4 col-md-6"><div class="stat-card"><div class="stat-value"><?php echo $overallStats['total']; ?></div><div class="stat-label">Questões Respondidas</div></div></div>
            <div class="col-lg-4 col-md-6"><div class="stat-card"><div class="stat-value text-success"><?php echo $overallStats['acertos']; ?></div><div class="stat-label">Acertos Totais</div></div></div>
            <div class="col-lg-4 col-md-12"><div class="stat-card"><div class="stat-value"><?php echo $overallStats['percentual']; ?>%</div><div class="stat-label">Percentual Geral de Acerto</div></div></div>
        </div>

        <div class="row g-4">
            <div class="col-lg-5">
                <div class="chart-card">
                    <h3>Desempenho Geral</h3>
                    <div class="chart-container"><canvas id="pieChart"></canvas></div>
                </div>
            </div>
            <div class="col-lg-7">
                <div class="chart-card">
                    <h3>Seu aproveitamento por disciplina</h3>
                     <div class="chart-container"><canvas id="disciplineBarChart"></canvas></div>
                </div>
            </div>
            <div class="col-lg-7">
                <div class="chart-card">
                    <h3>Evolução de Desempenho</h3>
                     <div class="chart-container"><canvas id="evolutionLineChart"></canvas></div>
                </div>
            </div>
             <div class="col-lg-5">
                <div class="chart-card">
                    <h3>Comparativo de Desempenho (Seu Foco)</h3>
                     <div class="chart-container"><canvas id="comparativeBarChart"></canvas></div>
                </div>
            </div>
        </div>
    <?php endif; ?>

</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const primaryColor = '#005DFF', primaryColorLight = 'rgba(0, 93, 255, 0.1)';
    const successColor = '#03A678', dangerColor = '#dc3545', warningColor = '#ffc107', neutralColor = '#6c757d';

    Chart.defaults.font.family = 'Poppins';
    Chart.defaults.plugins.legend.position = 'bottom';
    Chart.defaults.maintainAspectRatio = false;

    <?php if ($overallStats['total'] > 0): ?>
    
    new Chart(document.getElementById('pieChart'), { type: 'doughnut', data: { labels: ['Acertos', 'Erros'], datasets: [{ data: [<?php echo $overallStats['acertos']; ?>, <?php echo $overallStats['erros']; ?>], backgroundColor: [successColor, dangerColor], borderColor: '#ffffff', borderWidth: 4, hoverOffset: 8 }] }, options: { responsive: true, cutout: '70%' } });

    new Chart(document.getElementById('disciplineBarChart'), {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($disciplineLabels); ?>,
            datasets: [{ label: '% de Acerto', data: <?php echo json_encode($disciplineData); ?>, backgroundColor: (ctx) => (ctx.raw < 50 ? dangerColor : ctx.raw < 75 ? warningColor : successColor), borderRadius: 6 }]
        },
        options: {
            indexAxis: 'y', responsive: true, plugins: { legend: { display: false } },
            scales: {
                x: { beginAtZero: true, max: 100, ticks: { callback: (v) => v + '%' } },
                y: { ticks: { font: { weight: '500' }, color: '#343a40' } }
            }
        }
    });

    new Chart(document.getElementById('evolutionLineChart'), {
        type: 'line',
        data: {
            labels: <?php echo json_encode($evolutionLabels); ?>,
            datasets: [{ label: 'Média de desempenho', data: <?php echo json_encode($evolutionData); ?>, borderColor: primaryColor, backgroundColor: primaryColorLight, fill: true, tension: 0.4 }]
        },
        options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, max: 100, ticks: { callback: (v) => v + '%' } } } }
    });

    new Chart(document.getElementById('comparativeBarChart'), {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($comparativeLabels); ?>,
            datasets: [
                {
                    label: 'Sua Média', data: <?php echo json_encode($comparativeUserData); ?>,
                    backgroundColor: primaryColor, borderRadius: 6, barPercentage: 0.6
                },
                {
                    label: 'Média da Plataforma', data: <?php echo json_encode($comparativePlatformData); ?>,
                    backgroundColor: '#dee2e6', borderRadius: 6, barPercentage: 0.6
                }
            ]
        },
        options: {
            responsive: true,
            scales: { y: { beginAtZero: true, max: 100, ticks: { callback: (v) => v + '%' } } },
            plugins: { tooltip: { mode: 'index', intersect: false } }
        }
    });

    <?php endif; ?>
});
</script>

</body>
<?php include_once 'footer.php' ?>
</html>
