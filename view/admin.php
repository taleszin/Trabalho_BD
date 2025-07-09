<?php
session_start();
$currentPage = basename($_SERVER['SCRIPT_NAME']);
$isAdmin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;
$allowedAdminIds = [1, 4, 35];
$currentUserId = $_SESSION['id_usuario'];

if (!in_array($currentUserId, $allowedAdminIds) || !$isAdmin) {
  //  header("Location: login.php");
    //exit();
}
require_once '../classes/config.php';
require_once '../classes/AdminService.php';
require_once '../classes/LogService.php';
include_once 'header.php';
$endDate = $_GET['end'] ?? date('Y-m-d');
$startDate = $_GET['start'] ?? date('Y-m-d', strtotime('-29 days', strtotime($endDate)));

$adminService = new AdminService($conexao);
$logService = new LogService($conexao);

$kpiStats = $adminService->getKpiStats($startDate, $endDate);
$userGrowthData = $adminService->getNewUserGrowth($startDate, $endDate);
$topEngagedDisciplines = $adminService->getTopDisciplines($startDate, $endDate, 'total_respostas', 5);
$weeklyRetentionData = $adminService->getRetentionCohorts($startDate, $endDate, 'week');
$dailyRetentionData = $adminService->getRetentionCohorts($startDate, $endDate, 'day');
$topActiveUsers = $adminService->getTopActiveUsers($startDate, $endDate, 5);
$institutionDistribution = $adminService->getInstitutionDistribution();
$systemLogs = $logService->getLogs(10, 0);

function processCohorts(array $data, string $periodType, int $numPeriods): array
{
    if (empty($data)) return [];

    $periodKey = $periodType . '_number';
    $cohorts = [];
    $cohortBaseSizes = [];

    foreach ($data as $row) {
        $cohorts[$row['cohort']][$row[$periodKey]] = $row['total_users'];
        if ($row[$periodKey] == 0) {
            $cohortBaseSizes[$row['cohort']] = $row['total_users'];
        }
    }

    $retention = [];
    foreach ($cohorts as $cohortDate => $periods) {
        $baseSize = $cohortBaseSizes[$cohortDate] ?? 0;
        if ($baseSize === 0) continue;

        $retention[$cohortDate]['base_size'] = $baseSize;
        $retention[$cohortDate]['percentages'] = [];

        for ($i = 0; $i < $numPeriods; $i++) {
            $usersInPeriod = $periods[$i] ?? 0;
            $retention[$cohortDate]['percentages'][$i] = $usersInPeriod > 0 ? round(($usersInPeriod / $baseSize) * 100) : null;
        }
    }
    return $retention;
}

$weeklyRetentionCohorts = processCohorts($weeklyRetentionData, 'week', 5);
$dailyRetentionCohorts = processCohorts($dailyRetentionData, 'day', 7);

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Centro Operacional MedLeap</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
    <style>
        body { font-family: 'Poppins', sans-serif; background-color: #f4f7fc; }
        .stat-card { background-color: #fff; border: 1px solid #e9ecef; border-radius: 12px; }
        .stat-card-title { font-size: 1rem; color: #6c757d; font-weight: 500; }
        .stat-card-value { font-size: 2.2rem; color: #0d6efd; font-weight: 700; }
        .chart-card { background-color: #fff; border-radius: 12px; padding: 1.5rem; border: 1px solid #e9ecef; height: 100%;}
        .chart-card h5 { font-weight: 600; }
        .cohort-table { font-size: 0.85rem; }
        .cohort-table th, .cohort-table td { text-align: center; vertical-align: middle; }
        .list-group-item .badge { font-size: 0.9em; }
    </style>
</head>
<body>
    <div class="container-fluid p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h2">Centro Operacional MedLeap</h1>
            <form id="dateRangeForm" method="GET">
                <input type="text" id="dateRangePicker" class="form-control" style="width: 280px;">
                <input type="hidden" name="start" id="startDate" value="<?php echo htmlspecialchars($startDate); ?>">
                <input type="hidden" name="end" id="endDate" value="<?php echo htmlspecialchars($endDate); ?>">
            </form>
        </div>
        
        <div class="row g-4 mb-4">
            <div class="col-lg-3 col-md-6"><div class="stat-card p-3"><p class="stat-card-title mb-1">Total de Alunos</p><h3 class="stat-card-value"><?php echo $kpiStats['total_alunos']; ?></h3></div></div>
            <div class="col-lg-3 col-md-6"><div class="stat-card p-3"><p class="stat-card-title mb-1">Provas Geradas (de acordo com o período)</p><h3 class="stat-card-value"><?php echo $kpiStats['total_provas']; ?></h3></div></div>
            <div class="col-lg-3 col-md-6"><div class="stat-card p-3"><p class="stat-card-title mb-1">Questões no Banco</p><h3 class="stat-card-value"><?php echo $kpiStats['total_questoes']; ?></h3></div></div>
            <div class="col-lg-3 col-md-6"><div class="stat-card p-3"><p class="stat-card-title mb-1">Respostas (de acordo com o período)</p><h3 class="stat-card-value"><?php echo $kpiStats['total_respostas']; ?></h3></div></div>
        </div>

        <div class="row g-4">
            <div class="col-12">
                <div class="chart-card">
                    <h5 class="mb-3">Crescimento de Novos Alunos</h5>
                    <div style="height: 300px;"><canvas id="userGrowthChart"></canvas></div>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="chart-card">
                    <h5 class="mb-3">Top 5 Disciplinas Mais Engajadas</h5>
                    <div style="height: 350px;"><canvas id="topDisciplinesChart"></canvas></div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="chart-card">
                    <h5 class="mb-3">Alunos por Instituição</h5>
                    <div style="height: 350px;"><canvas id="institutionDistributionChart"></canvas></div>
                </div>
            </div>
            <div class="col-lg-3">
                <div class="chart-card">
                    <h5 class="mb-3">Top Alunos</h5>
                    <ul class="list-group list-group-flush">
                        <?php foreach($topActiveUsers as $user): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <?php echo htmlspecialchars($user['nome']); ?>
                                <span class="badge bg-primary rounded-pill"><?php echo $user['total_respostas']; ?> Questões</span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="chart-card">
                    <h5 class="mb-3">Análise de Retenção Diária (7 dias)</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered cohort-table">
                            <thead>
                                <tr><th>Coorte</th><th>N.</th><th>D0</th><th>D1</th><th>D2</th><th>D3</th><th>D4</th><th>D5</th><th>D6</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach($dailyRetentionCohorts as $cohort => $data): ?>
                                    <tr>
                                        <td><?php echo date('d/m', strtotime($cohort)); ?></td>
                                        <td><?php echo $data['base_size']; ?></td>
                                        <?php foreach($data['percentages'] as $percentage): ?>
                                            <td style="background-color: rgba(13, 110, 253, <?php echo ($percentage ?? 0) / 100; ?>); color: #fff;"><?php echo isset($percentage) ? $percentage . '%' : ''; ?></td>
                                        <?php endforeach; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="chart-card">
                    <h5 class="mb-3">Análise de Retenção Semanal</h5>
                     <div class="table-responsive">
                        <table class="table table-bordered cohort-table">
                            <thead>
                                <tr><th>Coorte</th><th>N.</th><th>S0</th><th>S1</th><th>S2</th><th>S3</th><th>S4</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach($weeklyRetentionCohorts as $cohort => $data): ?>
                                    <tr>
                                        <td><?php echo date('d/m', strtotime($cohort)); ?></td>
                                        <td><?php echo $data['base_size']; ?></td>
                                        <?php foreach($data['percentages'] as $percentage): ?>
                                            <td style="background-color: rgba(13, 110, 253, <?php echo ($percentage ?? 0) / 100; ?>); color: #fff;"><?php echo isset($percentage) ? $percentage . '%' : ''; ?></td>
                                        <?php endforeach; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <div class="col-12 mt-4">
                <div class="chart-card">
                    <h5 class="mb-3">Últimos Logs do Sistema</h5>
                    <ul class="list-group list-group-flush" id="logList">
                        <?php if (empty($systemLogs)): ?>
                            <li class="list-group-item">Nenhum log encontrado.</li>
                        <?php else: ?>
                            <?php foreach($systemLogs as $log): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center flex-wrap">
                                    <span class="mb-1 mb-sm-0">
                                        <span class="badge bg-secondary me-2" style="width: 80px; text-align: center;"><?php echo htmlspecialchars($log['acao']); ?></span>
                                        Realizada por: <strong><?php echo htmlspecialchars($log['nomeAluno'] ?? 'Sistema'); ?></strong>
                                    </span>
                                    <small class="text-muted">
                                        <?php echo date('d/m/Y H:i:s', strtotime($log['dataHora'])); ?>
                                    </small>
                                </li>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </ul>
                    <div class="text-center mt-3">
                        <a href id="expandLogs" class="btn btn-outline-primary btn-sm">Expandir para ver mais</a>
                    </div>
                </div>
            </div>
            </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/jquery/latest/jquery.min.js"></script>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
    
    <script>
    $(function() {
        const start = moment('<?php echo $startDate; ?>');
        const end = moment('<?php echo $endDate; ?>');

        function cb(start, end) {
            $('#dateRangePicker').html(start.format('DD/MM/YYYY') + ' - ' + end.format('DD/MM/YYYY'));
            $('#startDate').val(start.format('YYYY-MM-DD'));
            $('#endDate').val(end.format('YYYY-MM-DD'));
        }

        $('#dateRangePicker').daterangepicker({
            startDate: start,
            endDate: end,
            ranges: {
               'Hoje': [moment(), moment()],
               'Ontem': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
               'Últimos 7 Dias': [moment().subtract(6, 'days'), moment()],
               'Últimos 30 Dias': [moment().subtract(29, 'days'), moment()],
               'Este Mês': [moment().startOf('month'), moment().endOf('month')],
               'Mês Passado': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
            },
            "locale": { "format": "DD/MM/YYYY", "separator": " - ", "applyLabel": "Aplicar", "cancelLabel": "Cancelar", "fromLabel": "De", "toLabel": "Para", "daysOfWeek": ["Dom","Seg","Ter","Qua","Qui","Sex","Sáb"], "monthNames": ["Janeiro","Fevereiro","Março","Abril","Maio","Junho","Julho","Agosto","Setembro","Outubro","Novembro","Dezembro"], "firstDay": 1 }
        }, cb);

        cb(start, end);

        $('#dateRangePicker').on('apply.daterangepicker', function(ev, picker) {
            $('#dateRangeForm').submit();
        });
    });

    let logsLoaded = 10;
    const expandBtn = document.getElementById('expandLogs');
    const logList = document.getElementById('logList');

    expandBtn.addEventListener('click', function(e) {
        e.preventDefault();
        expandBtn.disabled = true;
        fetch('../backend/get_logs.php?offset=' + logsLoaded + '&limit=10')
            .then(res => res.json())
            .then(data => {
                if (data.length === 0) {
                    expandBtn.textContent = 'Não há mais logs';
                    expandBtn.disabled = true;
                    return;
                }
                data.forEach(log => {
                    const li = document.createElement('li');
                    li.className = 'list-group-item d-flex justify-content-between align-items-center flex-wrap';
                    li.innerHTML = `
                        <span class="mb-1 mb-sm-0">
                            <span class="badge bg-secondary me-2" style="width: 80px; text-align: center;">${log.acao}</span>
                            Realizada por: <strong>${log.nomeAluno ?? 'Sistema'}</strong>
                        </span>
                        <small class="text-muted">${(new Date(log.dataHora)).toLocaleString('pt-BR')}</small>
                    `;
                    logList.appendChild(li);
                });
                logsLoaded += data.length;
                expandBtn.disabled = false;
            })
            .catch(() => {
                expandBtn.textContent = 'Erro ao carregar';
            });
    });

    document.addEventListener('DOMContentLoaded', function () {
        Chart.defaults.font.family = 'Poppins';
        Chart.defaults.maintainAspectRatio = false;

        new Chart(document.getElementById('userGrowthChart'), {
            type: 'line',
            data: { 
                labels: <?php echo json_encode(array_column($userGrowthData, 'dia')); ?>, 
                datasets: [{ 
                    label: 'Novos Alunos', 
                    data: <?php echo json_encode(array_column($userGrowthData, 'novos_alunos')); ?>, 
                    borderColor: '#0d6efd', 
                    backgroundColor: 'rgba(13, 110, 253, 0.1)', 
                    fill: true, 
                    tension: 0.4 
                }] 
            },
            options: { plugins: { legend: { display: false } } }
        });

        new Chart(document.getElementById('topDisciplinesChart'), {
            type: 'bar',
            data: { 
                labels: <?php echo json_encode(array_column($topEngagedDisciplines, 'disciplina')); ?>, 
                datasets: [{ 
                    label: 'Questões Respondidas', 
                    data: <?php echo json_encode(array_column($topEngagedDisciplines, 'total_respostas')); ?>, 
                    backgroundColor: 'rgba(13, 110, 253, 0.7)', 
                    borderRadius: 4 
                }] 
            },
            options: { 
                indexAxis: 'y', 
                plugins: { legend: { display: false } }, 
                scales: { y: { ticks: { font: { size: 10 } } } } 
            }
        });

        new Chart(document.getElementById('institutionDistributionChart'), {
            type: 'pie',
            data: {
                labels: <?php echo json_encode(array_column($institutionDistribution, 'sigla')); ?>,
                datasets: [{
                    label: 'Alunos',
                    data: <?php echo json_encode(array_column($institutionDistribution, 'total_alunos')); ?>,
                    backgroundColor: ['#0d6efd', '#6c757d', '#198754', '#dc3545', '#ffc107', '#0dcaf0', '#212529']
                }]
            },
            options: {
                plugins: {
                    legend: {
                        position: 'top',
                    }
                }
            }
        });
    });
    </script>
</body>
</html>