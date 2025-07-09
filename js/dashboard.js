document.addEventListener('DOMContentLoaded', function() {
    const dadosGlobais = window.dadosDashboard;
    const disciplinaSelecionada = window.disciplinaSelecionadaParaGrafico;

    if (!dadosGlobais) {
        console.warn('Objeto window.dadosDashboard não encontrado ou está vazio.');
        const mainContainer = document.querySelector('.container.mt-5');
        if (mainContainer && !mainContainer.querySelector('.alert-danger')) {
            const p = document.createElement('div');
            p.className = 'alert alert-warning text-center';
            p.textContent = 'Não foi possível carregar os dados do dashboard. Tente recarregar a página ou contate o suporte.';
            const header = document.querySelector('.dashboard-header');
            if (header) header.insertAdjacentElement('afterend', p);
            else mainContainer.prepend(p);
        }
        return;
    }
    
    // Log para verificar os dados recebidos (descomente se precisar durante o desenvolvimento)
    // console.log("Dados Completos Recebidos no JS:", JSON.stringify(dadosGlobais, null, 2));
    // console.log("Disciplina Selecionada no JS:", disciplinaSelecionada);

    if (dadosGlobais.geral === null && (!dadosGlobais.porTemaGeral || dadosGlobais.porTemaGeral.length === 0) && !disciplinaSelecionada) {
        console.warn('Dados principais do dashboard não carregados ou incompletos para JS.');
        return;
    }

    // 1. Gráfico de Pizza de Acertos/Erros Gerais
    if (dadosGlobais.geral && dadosGlobais.geral.totalRespondidas > 0) {
        renderizarGraficoPizzaAcertosErros(dadosGlobais.geral);
    } else {
        const pizzaCanvas = document.getElementById('graficoAcertosErrosGeral');
        if (pizzaCanvas && pizzaCanvas.parentElement && !pizzaCanvas.parentElement.querySelector('.text-muted')) {
            // O PHP já trata a mensagem de "sem dados" no HTML
        }
    }

    // 2. Gráfico de desempenho por Disciplina da Questão (COLUNAS)
    // Usaremos 'porDisciplinaQuestao' do PHP que agora usa questao.disciplina
    if (dadosGlobais.porDisciplinaQuestao && dadosGlobais.porDisciplinaQuestao.length > 0) {
        renderizarGraficoDisciplinasGerais(dadosGlobais.porDisciplinaQuestao, dadosGlobais.mediaPorDisciplinaPlataforma || {});
    } else if (dadosGlobais.hasOwnProperty('porDisciplinaQuestao')) {
        const temasCanvas = document.getElementById('graficoDesempenhoDisciplinas'); // ID do canvas atualizado no HTML
        if (temasCanvas && temasCanvas.parentElement && !temasCanvas.parentElement.querySelector('.text-muted')) {
            // O PHP já trata a mensagem de "sem dados" no HTML
        }
    }

    // 3. Gráfico de evolução da DISCIPLINA (da tabela QUESTAO) selecionada
    if (disciplinaSelecionada) {
        if ((dadosGlobais.evolucaoDisciplinaPeriodo && dadosGlobais.evolucaoDisciplinaPeriodo.length > 0) ||
            (dadosGlobais.evolucaoDisciplinaPorProva && dadosGlobais.evolucaoDisciplinaPorProva.length > 0) ||
            (dadosGlobais.evolucaoDisciplinaMediaMovel && dadosGlobais.evolucaoDisciplinaMediaMovel.length > 0)) {
            
            renderizarGraficoEvolucaoDisciplina(
                dadosGlobais.evolucaoDisciplinaPeriodo || [],
                dadosGlobais.evolucaoDisciplinaPorProva || [],
                dadosGlobais.evolucaoDisciplinaMediaMovel || [],
                disciplinaSelecionada
            );
        }
    }
});

function renderizarGraficoPizzaAcertosErros(dadosGerais) {
    const ctx = document.getElementById('graficoAcertosErrosGeral');
    if (!ctx) {
        console.error("Canvas 'graficoAcertosErrosGeral' não encontrado.");
        return;
    }

    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Acertos', 'Erros'],
            datasets: [{
                label: 'Desempenho Geral',
                data: [dadosGerais.totalCorretas, dadosGerais.totalErradas],
                backgroundColor: ['rgba(40, 167, 69, 0.85)','rgba(220, 53, 69, 0.85)'],
                borderColor: ['rgba(40, 167, 69, 1)','rgba(220, 53, 69, 1)'],
                borderWidth: 2,
                hoverOffset: 8
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: { animateScale: true, animateRotate: true },
            plugins: {
                legend: { position: 'bottom', labels: { padding: 15, usePointStyle: true, pointStyle: 'circle', font: {size: 12}} },
                title: { display: true, text: `Desempenho Geral (${dadosGerais.totalRespondidas} Questões Respondidas)`, padding: { top: 5, bottom: 15 }, font: { size: 14, weight: '500' }},
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.label || '';
                            if (label) { label += ': '; }
                            if (context.raw !== null) { 
                                label += context.raw;
                                const total = dadosGerais.totalRespondidas;
                                if (total > 0) {
                                    const percentage = (context.raw / total * 100).toFixed(1);
                                    label += ` (${percentage}%)`;
                                }
                            }
                            return label;
                        }
                    }
                }
            }
        }
    });
}

// MODIFICADO: Nome da função e chaves de dados para usar 'disciplina' da tabela 'questao'
function renderizarGraficoDisciplinasGerais(desempenhoAlunoDisciplina, mediaPlataformaDisciplina) {
    const ctx = document.getElementById('graficoDesempenhoDisciplinas'); // ID do canvas ATUALIZADO NO HTML
    if (!ctx) {
        console.error("Canvas 'graficoDesempenhoDisciplinas' não encontrado.");
        return;
    }

    const labels = desempenhoAlunoDisciplina.map(item => item.disciplina); // USA 'disciplina'
    const dataAluno = desempenhoAlunoDisciplina.map(item => parseFloat(item.percentualCorretas));
    
    const dataMediaPlataforma = labels.map(disciplinaLabel => {
        return mediaPlataformaDisciplina[disciplinaLabel] !== undefined ? parseFloat(mediaPlataformaDisciplina[disciplinaLabel]) : null; 
    });

    const datasets = [{
        label: 'Seu Aproveitamento (%)',
        data: dataAluno,
        backgroundColor: 'rgba(67, 100, 247, 0.75)',
        borderColor: 'rgba(67, 100, 247, 1)',
        borderWidth: 1, borderRadius: {topLeft: 6, topRight: 6}, barPercentage: 0.5, categoryPercentage: 0.7
    }];

    const temDadosMediaPlataforma = Object.keys(mediaPlataformaDisciplina).length > 0 && dataMediaPlataforma.some(d => d !== null);
    if (temDadosMediaPlataforma) {
        datasets.push({
            label: 'Média da Plataforma (%)',
            data: dataMediaPlataforma,
            backgroundColor: 'rgba(0, 150, 136, 0.65)',
            borderColor: 'rgba(0, 150, 136, 1)',
            borderWidth: 1, borderRadius: {topLeft: 6, topRight: 6}, barPercentage: 0.5, categoryPercentage: 0.7
        });
    }

    new Chart(ctx, {
        type: 'bar', 
        data: { labels: labels, datasets: datasets },
        options: {
            responsive: true, maintainAspectRatio: false,
            indexAxis: 'x', // Para barras verticais (colunas)
            scales: {
                y: { 
                    beginAtZero: true, max: 100,
                    title: { display: true, text: 'Percentual de Acerto (%)', font: {weight: '500'} },
                    ticks: { padding: 5, callback: function(value) { return value + "%"; } }
                },
                x: { 
                    title: { display: true, text: 'Disciplinas das Questões', font: {weight: '500'} },
                    ticks: { autoSkip: false, maxRotation: 30, minRotation: 0, font: {size: 11}, padding: 5 } 
                }
            },
            plugins: { 
                legend: { position: 'top', labels: {padding: 15, usePointStyle: true, font: {size: 12}} },
                tooltip: {
                    mode: 'index', intersect: false,
                    callbacks: {
                        label: function(context) {
                            let label = context.dataset.label || '';
                            if (label) { label += ': '; }
                            if (context.parsed.y !== null) {
                                label += context.parsed.y.toFixed(1) + '%';
                                if (context.dataset.label.includes('Seu Aproveitamento')) {
                                    const disciplinaData = desempenhoAlunoDisciplina.find(d => d.disciplina === context.label); 
                                    if(disciplinaData) {
                                         label += ` (${disciplinaData.totalCorretas}/${disciplinaData.totalRespondidas} acertos)`;
                                    }
                                }
                            }
                            return label;
                        }
                    }
                }
            }
        }
    });
}

function renderizarGraficoEvolucaoDisciplina(dadosPeriodo, dadosProva, dadosMediaMovel, nomeDisciplina) {
    const ctx = document.getElementById('graficoEvolucaoDisciplina');
    if (!ctx) {
        console.error("Canvas 'graficoEvolucaoDisciplina' não encontrado.");
        return;
    }

    const datasets = [];

    if (dadosPeriodo && dadosPeriodo.length > 0) {
        datasets.push({
            label: `Aproveitamento Diário (${nomeDisciplina})`,
            data: dadosPeriodo.map(item => ({ x: item.periodo, y: parseFloat(item.percentualCorretas), raw: item })),
            borderColor: 'rgba(255, 159, 64, 0.9)', 
            backgroundColor: 'rgba(255, 159, 64, 0.1)',
            tension: 0.3, fill: false, pointRadius: 3, pointHoverRadius: 5, yAxisID: 'yPercentual',
            parsing: { xAxisKey: 'x', yAxisKey: 'y' }
        });
    }

    if (dadosProva && dadosProva.length > 0) {
        datasets.push({
            label: `Desempenho Por Prova (${nomeDisciplina})`,
            data: dadosProva.map(item => ({ x: item.dataReferencia, y: parseFloat(item.percentualCorretas), rotuloOriginal: item.rotuloEixoX, raw: item })),
            borderColor: 'rgba(67, 100, 247, 1)', 
            backgroundColor: 'rgba(67, 100, 247, 0.1)',
            fill: false, pointRadius: 5, pointHoverRadius: 7, borderWidth: 2.5, yAxisID: 'yPercentual',
            showLine: true,
            parsing: { xAxisKey: 'x', yAxisKey: 'y' }
        });
    }
    
    if (dadosMediaMovel && dadosMediaMovel.length > 0) {
        datasets.push({
            label: `Média Móvel (${nomeDisciplina}, 3 Provas)`,
            data: dadosMediaMovel.map(item => ({ x: item.dataReferencia, y: parseFloat(item.mediaMovelPercentual), raw: item })),
            borderColor: 'rgba(40, 167, 69, 0.9)', 
            borderDash: [5, 5], fill: false, tension: 0.4, pointRadius: 3, pointHoverRadius: 5, yAxisID: 'yPercentual',
            parsing: { xAxisKey: 'x', yAxisKey: 'y' }
        });
    }
    
    if (datasets.length === 0) {
        return;
    }

    new Chart(ctx, {
        type: 'line',
        data: { datasets: datasets },
        options: {
            responsive: true, maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false, axis: 'x' },
            scales: {
                x: {
                    type: 'time',
                    time: {
                        tooltipFormat: 'dd/MM/yyyy HH:mm',
                        unit: 'day', 
                        adapters: { date: { locale: 'ptBR' } }, 
                        displayFormats: {
                            millisecond: 'HH:mm:ss.SSS', second: 'HH:mm:ss', minute: 'HH:mm', hour: 'HH:00',
                            day: 'dd/MM/yy', week: 'dd/MM', month: 'MMM yy', quarter: 'QQQ yy', year: 'yyyy'
                        }
                    },
                    title: { display: true, text: 'Data', font: {weight: 'bold'} },
                    ticks: { source: 'auto', autoSkip: true, maxRotation: 30, minRotation: 0, font: {size: 10}, padding: 5 }
                },
                yPercentual: {
                    type: 'linear', display: true, position: 'left',
                    min: 0, max: 100,
                    title: { display: true, text: `Aproveitamento em ${nomeDisciplina} (%)`, font: {weight: 'bold'} },
                    ticks: { stepSize: 10, callback: function(value) { return value + "%"; } }
                }
            },
            plugins: {
                legend: { position: 'top', labels: { usePointStyle: true, padding: 15, font: {size: 11}} },
                tooltip: {
                    callbacks: {
                        title: function(tooltipItems) {
                            if (tooltipItems.length > 0) {
                                const item = tooltipItems[0];
                                const dataPoint = item.dataset.data[item.dataIndex];
                                if (dataPoint && dataPoint.raw && dataPoint.raw.rotuloEixoX && item.dataset.label.includes('Por Prova')) {
                                    return dataPoint.raw.rotuloEixoX;
                                }
                                const timestamp = item.parsed.x;
                                if (timestamp) {
                                     const date = new Date(timestamp);
                                     if (item.dataset.label.includes('Diário')) {
                                        return `${date.getDate().toString().padStart(2, '0')}/${(date.getMonth() + 1).toString().padStart(2, '0')}/${date.getFullYear()}`;
                                     }
                                     return date.toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' });
                                }
                            }
                            return '';
                        },
                        label: function(context) {
                            let originalLabel = context.dataset.label || '';
                            let shortLabel = originalLabel.replace(` em ${nomeDisciplina}`, '').replace(' (3 Provas)', '');
                            if (shortLabel) { shortLabel += ': '; }

                            const dataPoint = context.dataset.data[context.dataIndex];
                            if (dataPoint && dataPoint.y !== undefined && dataPoint.y !== null) {
                                shortLabel += parseFloat(dataPoint.y).toFixed(1) + '%';
                                const rawData = dataPoint.raw;
                                if (rawData) {
                                    if (rawData.totalRespondidas !== undefined && rawData.totalCorretas !== undefined) {
                                        shortLabel += ` (${rawData.totalCorretas}/${rawData.totalRespondidas} questões)`;
                                    } else if (rawData.totalRespondidasNaDisciplina !== undefined && rawData.totalCorretasNaDisciplina !== undefined) {
                                        shortLabel += ` (${rawData.totalCorretasNaDisciplina}/${rawData.totalRespondidasNaDisciplina} questões)`;
                                    }
                                }
                            } else if (context.parsed.y !== null) {
                                shortLabel += context.parsed.y.toFixed(1) + '%';
                            }
                            return shortLabel;
                        }
                    }
                }
            }
        }
    });
}