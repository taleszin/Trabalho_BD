$(document).ready(function () {
    $('#uploadForm').on('submit', function (event) {
        event.preventDefault(); // Impede o envio padrão do formulário

        var formData = new FormData(this); // Cria um FormData com o conteúdo do formulário
        var arquivo = formData.get('arquivo'); // Obtém o arquivo do FormData

        // Verifica se o arquivo foi selecionado e se o tamanho excede 5MB
        if (arquivo && arquivo.size > 5 * 1024 * 1024) { // 5MB em bytes
            $('#fileSizeModal').modal('show'); // Exibe o modal de alerta
            return; // Interrompe o processamento do restante do código
        }

        // Se o arquivo for válido, continua o envio via AJAX
        $.ajax({
            url: 'backend/processar_pdf.php', // URL do script PHP que processa o PDF
            type: 'POST',
            data: formData,
            processData: false, // Impede o jQuery de tentar processar os dados
            contentType: false, // Impede o jQuery de definir o tipo de conteúdo
            success: function (response) {
                try {
                    var data = JSON.parse(response.json);

                    $('#resultado').html('');
                    var subAreaCard = `
                        <div class="card mb-3">
                            <div class="card-body">
                                <h5 class="card-title">Disciplina Principal</h5>
                                <div class="card-text">
                                    <span class="badge bg-secondary me-2">
                                        ${data.subarea}
                                    </span>
                                </div>
                            </div>
                        </div>
                    `;
                    $('#resultado').append(subAreaCard);

                    var disciplinasCard = `
                        <div class="card mb-3">
                            <div class="card-body">
                                <h5 class="card-title">Conteúdos</h5>
                                <div class="card-text">
                                    ${data.disciplinas.map(d => `
                                        <span class="badge bg-info me-2">
                                            ${d}
                                        </span>
                                    `).join('')}
                                </div>
                            </div>
                        </div>
                    `;
                    $('#resultado').append(disciplinasCard);

                    var resumoCard = `
                        <div class="card mb-3">
                            <div class="card-body">
                                <h5 class="card-title">Resumo da Prova</h5>
                                <p class="card-text">${data.resumo_prova}</p>
                            </div>
                        </div>
                    `;
                    $('#resultado').append(resumoCard);

                    const card = document.querySelector('#gerarCard');
                    if (card) {
                        card.classList.remove('d-none');
                    } else {
                        console.error('Elemento não encontrado! Verifique o seletor.');
                    }

                } catch (e) {
                    console.error('Erro ao processar o JSON:', e);
                    $('#resultado').html('<p>Ocorreu um erro ao processar os dados recebidos.</p>');
                }
            },
            error: function () {
                $('#fileErrorModal').modal('show'); // Exibe o modal de erro
                $('#resultado').html('<p>O arquivo enviado não foi reconhecido como texto pela nossa IA. Por favor, envie um arquivo válido.</p>');
            }
        });
    });
});

function removeTag(element) {
    element.parentElement.remove();
}