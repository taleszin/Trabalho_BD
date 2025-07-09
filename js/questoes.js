function carregarQuestoes() {
    $.ajax({
        url: '../classes/QuestaoService.php',
        type: 'GET',
        data: { acao: 'listar' },
        success: function(response) {
            if (response.success && response.questoes) {
                exibirQuestoes(response.questoes);
            } else {
                alert('Nenhuma questão encontrada.');
            }
        },
        error: function(xhr, status, error) {
            console.error("Erro ao carregar questões:", error);
        }
    });
}

function exibirQuestoes(questoes) {
    const container = $('#questoesContainer');
    container.empty();

    questoes.forEach(function(questao) {
        const card = $('<div class="card mb-4"></div>');
        const cardBody = $('<div class="card-body"></div>');

        const titulo = $('<h5 class="card-title"></h5>').text("Questão " + questao.id);
        const enunciado = $('<p class="card-text"></p>').text(questao.enunciado);
        const info = $('<p class="text-muted small"></p>').text("Tipo: " + questao.tipo + " | Nível: " + questao.nivel + " | Categoria: " + questao.categoria);

        const listaAlternativas = $('<ul class="list-group list-group-flush"></ul>');
        questao.alternativas.forEach(function(alt, index) {
            const item = $('<li class="list-group-item"></li>').text(String.fromCharCode(65 + index) + ") " + alt.texto);
            listaAlternativas.append(item);
        });

        cardBody.append(titulo, enunciado, info);
        card.append(cardBody, listaAlternativas);
        container.append(card);
    });
}

$(document).ready(function() {
    carregarQuestoes();
});
