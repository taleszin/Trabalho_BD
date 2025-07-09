<?php
session_start();

if (isset($_SESSION['id_usuario'])) {
    session_unset();
    session_destroy();
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - banco Questoes</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/login.css">
    <style>
        .form-container form {
            display: flex;
            flex-direction: column;
            height: 100%;
        }
        .form-container {
            overflow: hidden;
        }
        .sign-up-container form {
            overflow-y: auto;
            padding-right: 10px;
        }
        .sign-up-container form::-webkit-scrollbar {
            width: 8px;
        }
        .sign-up-container form::-webkit-scrollbar-track {
            background: #f1f1f1;
        }
        .sign-up-container form::-webkit-scrollbar-thumb {
            background: #ccc;
            border-radius: 4px;
        }
        .sign-up-container form::-webkit-scrollbar-thumb:hover {
            background: #aaa;
        }
    </style>
</head>
<body>

<div class="modal fade" id="modalCadastroInfo" tabindex="-1" aria-labelledby="modalCadastroInfoLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content shadow-lg rounded-4">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title" id="modalCadastroInfoLabel">Funcionalidade em breve</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
      </div>
      <div class="modal-body text-center">
        <p>Estamos trabalhando para implementar essa funcionalidade.<br>Em breve, você poderá se cadastrar por aqui! 😊</p>
      </div>
      <div class="modal-footer justify-content-center">
        <button type="button" class="btn btn-outline-primary" data-bs-dismiss="modal">Entendi</button>
      </div>
    </div>
  </div>
</div>
    <div class="container-login" id="container">
        <div class="form-container sign-up-container">
            <form id="registerForm" novalidate>
                <div class="social-container">
                    <h3>Comece sua jornada com a gente</h3>
                </div>
                <span>ou use seu e-mail para o cadastro</span>
                <input type="text" id="registerName" placeholder="Nome Completo" required />
                <input type="email" id="registerEmail" placeholder="E-mail" required />
                <select id="registerInstituicao" class="form-select" required>
                    <option value="" selected disabled>Selecione sua Instituição</option>
                </select>
                <input type="password" id="registerPassword" placeholder="Senha" required />
                <div id="password-strength-status"></div>
                <input type="password" id="registerPasswordConfirm" placeholder="Confirme a Senha" required />
                <div id="registerAlert" class="alert mt-2 d-none" role="alert"></div>
                <button type="submit">COMEÇAR</button>
                <br>
            </form>
        </div>

        <div class="form-container sign-in-container">
            <form id="loginForm">
                <h1>Login</h1>
                <div class="social-container">
                    <a href="#" class="social"><i class="fab fa-google-plus-g"></i></a>
                    <a href="#" class="social"><i class="fab fa-linkedin-in"></i></a>
                </div>
                <span>ou use sua conta</span>
                <input type="email" id="email" name="email" placeholder="E-mail" required />
                <input type="password" id="senha" name="senha" placeholder="Senha" required />
                <a href="#">Esqueceu sua senha?</a>
                <button type="submit">Entrar</button>
                <div id="alert" class="alert mt-3 d-none" role="alert"></div>
            </form>
        </div>

        <div class="overlay-container">
            <div class="overlay">
                <div class="overlay-panel overlay-left">
                    <h1>Bem-vindo de Volta ao nosso banco de questões</h1>
                    <p>Continue sua preparação com simulados no estilo da sua prova, e foco no que mais importa.</p>
                    <button class="ghost" id="signIn">ENTRAR E CONTINUAR MEU TREINO</button>
                </div>
                <div class="overlay-panel overlay-right">
                    <h1>O seu novo jeito de estudar!</h1>
                    <p>Cadastre-se e comece agora.</p>
                    <button class="ghost" id="signUp">Cadastre-se</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    $(document).ready(function () {
        const signUpButton = $('#signUp');
        const signInButton = $('#signIn');
        const container = $('#container');

        signUpButton.on('click', function () {
            container.addClass('right-panel-active');
        });

        signInButton.on('click', function () {
            container.removeClass('right-panel-active');
        });

        function carregarInstituicoes() {
            $.ajax({
                url: '../classes/UsuarioService.php?action=getInstituicoes',
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    const select = $('#registerInstituicao');
                    if (response.success && Array.isArray(response.data)) {
                        select.empty().append('<option value="" selected disabled>Selecione sua Instituição</option>');
                        response.data.forEach(function(item) {
                            select.append($('<option>', {
                                value: item.idInstituicao,
                                text: `${item.nome} (${item.sigla})`
                            }));
                        });
                    } else {
                        select.empty().append('<option value="" selected disabled>Erro ao carregar</option>');
                    }
                },
                error: function() {
                    const select = $('#registerInstituicao');
                    select.empty().append('<option value="" selected disabled>Falha na comunicação</option>');
                }
            });
        }
        
        if ($('#registerForm').length) {
            carregarInstituicoes();
        }

        $('#loginForm').on('submit', function (e) {
            e.preventDefault();
            const email = $('#email').val();
            const senha = $('#senha').val();
            const alertBox = $('#alert');
            alertBox.removeClass('d-none');
            
            $.ajax({
                url: '../classes/LoginService.php',
                type: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({ email: email, senha: senha }),
                success: function (response) {
                    try {
                        const data = JSON.parse(response);
                          if (data.success) {
                            alertBox.removeClass('alert-danger').addClass('alert-success').text(data.message);
                            setTimeout(() => {
                                window.location.href = data.redirect;
                            }, 1000);
                        } else {
                            alertBox.removeClass('alert-success').addClass('alert-danger').text(data.error);
                        }
                    } catch(err) {
                        alertBox.removeClass('alert-success').addClass('alert-danger').text('Erro na resposta do servidor.');
                    }
                },
                error: function () {
                    alertBox.removeClass('alert-success').addClass('alert-danger').text('Erro de comunicação. Tente novamente.');
                }
            });
        });

        $('#registerPassword').on('keyup', function() {
            let number = /([0-9])/;
            let upper = /([A-Z])/;
            let lower = /([a-z])/;
            let special = /([~,!,@,#,$,%,^,&,*,-,_,+,=,?,>,<])/;
            let statusDiv = $('#password-strength-status');
            let pass = $(this).val();
            let strength = 0;
            if (pass.length >= 8) strength++;
            if (pass.match(number)) strength++;
            if (pass.match(upper) && pass.match(lower)) strength++;
            if (pass.match(special)) strength++;

            let statusText = "";
            let statusClass = "";
            switch (strength) {
                case 0:
                case 1: statusText = "Fraca"; statusClass = "text-danger"; break;
                case 2: statusText = "Média"; statusClass = "text-warning"; break;
                case 3: statusText = "Forte"; statusClass = "text-info"; break;
                case 4: statusText = "Excelente"; statusClass = "text-success"; break;
            }
            statusDiv.html(`<small class="${statusClass}">${statusText}</small>`);
        });

        $('#registerForm').on('submit', function(e) {
            e.preventDefault();
            const registerAlert = $('#registerAlert');
            registerAlert.removeClass('d-none alert-danger alert-success').text('');

            const senha = $('#registerPassword').val();
            const senhaConfirm = $('#registerPasswordConfirm').val();

            if (senha !== senhaConfirm) {
                alert('As senhas não coincidem. Por favor, tente novamente.');
                return;
            }
            if (senha.length < 8) {
                registerAlert.addClass('alert-danger').removeClass('d-none').text('A senha deve ter no mínimo 8 caracteres.');
                return;
            }

            const formData = {
                nome: $('#registerName').val(),
                email: $('#registerEmail').val(),
                idInstituicao: $('#registerInstituicao').val(),
                senha: senha
            };
            
            if (!formData.nome || !formData.email || !formData.idInstituicao || !formData.senha) {
                registerAlert.addClass('alert-danger').removeClass('d-none').text('Todos os campos são obrigatórios.');
                return;
            }

            $.ajax({
                url: '../classes/UsuarioService.php?action=registrar',
                type: 'POST',
                contentType: 'application/json',
                data: JSON.stringify(formData),
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        registerAlert.removeClass('alert-danger').addClass('alert-success').removeClass('d-none').text(response.message);
                        setTimeout(() => {
                            signInButton.click();
                            $('#registerForm')[0].reset();
                            registerAlert.addClass('d-none');
                            $('#password-strength-status').empty();
                        }, 2500);
                    } else {
                        registerAlert.removeClass('alert-success').addClass('alert-danger').removeClass('d-none').text(response.error);
                    }
                },
                error: function(xhr) {
                if (xhr.status === 409) { // E-mail já cadastrado
                registerAlert.addClass('alert-danger').removeClass('d-none').text('E-mail já cadastrado em nossa base de dados !');
                    } else {
                        registerAlert.addClass('alert-danger').removeClass('d-none').text('Erro de comunicação. Tente novamente mais tarde.');
                            }
        }
            });
        });
    });
</script>

</body>
</html>