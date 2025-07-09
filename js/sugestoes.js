document.addEventListener('DOMContentLoaded', function () {

    const buscarSugestao = async () => {
        try {
            const resposta = await fetch('../backend/sugestoes.php');
            
            if (!resposta.ok) {
                throw new Error(`Erro na rede: ${resposta.statusText}`);
            }
            
            const dados = await resposta.json();

            if (dados && dados.temSugestao) {
                exibirPopupSugestao(dados.disciplina, dados.assuntos);
            }

        } catch (erro) {
            console.error('Falha ao buscar sugestão de estudo:', erro);
        }
    };

    const exibirPopupSugestao = (disciplina, assuntos) => {
        const htmlAssuntos = `<span class="fw-bold">${assuntos.join(', ').replace(/, ([^,]*)$/, ' e $1')}</span>`;
        const htmlDisciplina = `<span class="fw-bold text-primary">${disciplina}</span>`;

        Swal.fire({
            title: '<i class="bi bi-lightbulb-fill text-warning"></i> Oportunidade de Reforço!',
            html: `
                <p class="text-start fs-6 lh-base">
                    Analisamos suas últimas respostas e notamos que em ${htmlDisciplina}, 
                    você pode fortalecer seu conhecimento em tópicos como ${htmlAssuntos}.
                </p>
                <p class="fs-6 fw-bold">Que tal um treino focado para dominar esses pontos?</p>
            `,
            icon: 'info',
            confirmButtonText: '<i class="bi bi-activity"></i> Sim, quero treinar!',
            confirmButtonColor: '#0d6efd',
            showCancelButton: true,
            cancelButtonText: 'Agora não',
            customClass: {
                title: 'h4',
                popup: 'rounded-3 shadow-lg',
            },
            showLoaderOnConfirm: true,
            preConfirm: async () => {
                try {
                    const resposta = await fetch('../backend/parametros_sugestoes.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({ disciplina, assuntos })
                    });

                    if (!resposta.ok) {
                        throw new Error('Não foi possível preparar o treino. Tente novamente.');
                    }

                    const resultado = await resposta.json();
                    if (!resultado.sucesso) {
                        throw new Error('Ocorreu um erro no servidor ao preparar seu treino.');
                    }

                    return true;

                } catch (erro) {
                    Swal.showValidationMessage(`Falha: ${erro.message}`);
                    return false;
                }
            },
            allowOutsideClick: () => !Swal.isLoading()

        }).then((resultado) => {
            if (resultado.isConfirmed) {
                window.location.href = '../view/parametros.php';
            }
        });
    };

    setTimeout(buscarSugestao, 1500);
});
