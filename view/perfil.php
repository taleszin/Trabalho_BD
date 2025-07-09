<?php
session_start();
require_once '../classes/config.php';
require_once '../classes/PerfilService.php';

if (!isset($_SESSION['id_usuario'])) {
    header("Location: login.php");
    exit;
}

$idAlunoLogado = $_SESSION['id_usuario'];
$perfilService = new PerfilService($conexao);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $action = $_POST['action'] ?? '';
    $response = ['success' => false, 'message' => 'Ação desconhecida.'];

    try {
        if ($action === 'update_nome') {
            $nome = filter_input(INPUT_POST, 'nome', FILTER_SANITIZE_STRING);
            if ($nome && $perfilService->updateNome($idAlunoLogado, $nome)) {
                $_SESSION['nome_usuario'] = $nome;
                $response = ['success' => true, 'message' => 'Nome atualizado com sucesso!', 'nome' => $nome];
            } else {
                throw new Exception('Não foi possível atualizar o nome.');
            }
        } elseif ($action === 'update_avatar') {
            $avatarUrl = $_POST['avatar_url'] ?? null;
            if ($avatarUrl) {
                if ($perfilService->updateAvatar($idAlunoLogado, $avatarUrl)) {
                    $response = ['success' => true, 'message' => 'Avatar atualizado!', 'avatar_url' => $avatarUrl];
                } else {
                    throw new Exception('Erro ao salvar o avatar selecionado.');
                }
            } elseif (isset($_FILES['avatar_upload']) && $_FILES['avatar_upload']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['avatar_upload'];
                $uploadDir = '../uploads/avatars/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0775, true);
                
                $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                if (!in_array(strtolower($extension), ['jpg', 'jpeg', 'png'])) {
                    throw new Exception('Formato de arquivo inválido. Use JPG ou PNG.');
                }

                $fileName = 'avatar_' . $idAlunoLogado . '_' . time() . '.' . $extension;
                $uploadFilePath = $uploadDir . $fileName;

                if (move_uploaded_file($file['tmp_name'], $uploadFilePath)) {
                    $webPath = 'uploads/avatars/' . $idAlunoLogado;
                    if ($perfilService->updateAvatar($idAlunoLogado, $webPath)) {
                        $response = ['success' => true, 'message' => 'Avatar atualizado!', 'avatar_url' => '../' . $webPath];
                    } else {
                        throw new Exception('Erro ao salvar o novo avatar no banco de dados.');
                    }
                } else {
                    throw new Exception('Falha ao mover o arquivo de avatar.');
                }
            } else {
                 throw new Exception('Nenhum avatar enviado ou selecionado.');
            }
        }
    } catch (Exception $e) {
        http_response_code(400);
        $response = ['success' => false, 'message' => $e->getMessage()];
    }
    echo json_encode($response);
    exit;
}

$aluno = $perfilService->getAluno($idAlunoLogado);
if (!$aluno) {
    echo "Erro: não foi possível carregar os dados do aluno.";
    exit;
}
include_once 'header.php';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meu Perfil - MedLeap</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.css" rel="stylesheet">
    <style>
        :root { --primary-color: #0d6efd; --bg-light: #f8f9fa; --border-color: #dee2e6; }
        body { background-color: var(--bg-light); font-family: 'Poppins', sans-serif; }
        .profile-card { border: none; border-radius: 1rem; box-shadow: 0 8px 30px rgba(0,0,0,0.05); }
        .avatar-wrapper { position: relative; width: 120px; height: 120px; margin: 0 auto; }
        .avatar-img { width: 100%; height: 100%; border-radius: 50%; border: 4px solid white; object-fit: cover; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        .avatar-edit-overlay { position: absolute; top: 0; left: 0; width: 100%; height: 100%; border-radius: 50%; background-color: rgba(0,0,0,0.4); color: white; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; opacity: 0; transition: opacity 0.3s ease; cursor: pointer; }
        .avatar-wrapper:hover .avatar-edit-overlay { opacity: 1; }
        .form-control, .form-select { border-radius: 0.5rem; }
        .form-control:focus, .form-select:focus { box-shadow: 0 0 0 0.2rem rgba(var(--bs-primary-rgb), 0.25); border-color: #86b7fe; }
        .form-control[readonly] { background-color: #e9ecef; cursor: not-allowed; }
        .avatar-option { cursor: pointer; transition: transform 0.2s ease, box-shadow 0.2s ease; border-radius: 50%; border: 3px solid transparent; }
        .avatar-option:hover { transform: scale(1.05); }
        .avatar-option.selected { border-color: var(--primary-color); box-shadow: 0 0 15px rgba(13, 110, 253, 0.5); }
        #image-cropper-container { display: none; max-height: 40vh; margin-top: 1rem; }
        #image-to-crop { max-width: 100%; }
        .btn-primary { font-weight: 600; }
    </style>
</head>
<body>
    <div class="container my-5">
        <div class="text-center mb-5">
            <h1 class="fw-bold">Meu Perfil</h1>
            <p class="text-muted">Gerencie suas informações e personalize sua experiência na MedLeap.</p>
        </div>
        <div class="row justify-content-center">
            <div class="col-lg-10 col-xl-8">
                <div class="card profile-card">
                    <div class="card-body p-4 p-md-5">
                        <div class="text-center mb-4">
                            <div class="avatar-wrapper" data-bs-toggle="modal" data-bs-target="#avatarModal">
                                <img src="<?= htmlspecialchars($aluno['avatar_url'] ? ((substr($aluno['avatar_url'], 0, 4) === 'http') ? $aluno['avatar_url'] : '../' . $aluno['avatar_url']) : 'https://api.dicebear.com/8.x/micah/svg?seed=medleap') ?>" alt="Avatar do Usuário" class="avatar-img" id="currentAvatar">
                                <div class="avatar-edit-overlay"><i class="bi bi-pencil-fill"></i></div>
                            </div>
                        </div>
                        <form id="updateNomeForm">
                            <input type="hidden" name="action" value="update_nome">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="nome" class="form-label fw-bold">Nome</label>
                                    <input type="text" class="form-control" id="nome" name="nome" value="<?= htmlspecialchars($aluno['nome']) ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="email" class="form-label fw-bold">E-mail</label>
                                    <input type="email" class="form-control" id="email" value="<?= htmlspecialchars($aluno['email']) ?>" readonly>
                                </div>
                                <div class="col-12">
                                    <label for="instituicao" class="form-label fw-bold">Instituição</label>
                                    <input type="text" class="form-control" id="instituicao" value="<?= htmlspecialchars($aluno['nome_instituicao'] ?? 'Não informada') ?>" readonly>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary mt-4">Salvar Nome</button>
                        </form>
                        <hr class="my-4">
                        <h4 class="fw-bold mb-3">Alterar Senha</h4>
                        <form id="updateSenhaForm">
                            <input type="hidden" name="action" value="update_senha">
                            <div class="row g-3">
                                <div class="col-md-4"><label for="currentPassword" class="form-label fw-bold">Senha Atual</label><input type="password" class="form-control" id="currentPassword" name="senha_atual" required></div>
                                <div class="col-md-4"><label for="newPassword" class="form-label fw-bold">Nova Senha</label><input type="password" class="form-control" id="newPassword" name="nova_senha" required></div>
                                <div class="col-md-4"><label for="confirmPassword" class="form-label fw-bold">Confirmar Senha</label><input type="password" class="form-control" id="confirmPassword" name="confirma_senha" required></div>
                            </div>
                            <button type="submit" class="btn btn-outline-primary mt-3" disabled>Alterar Senha (Em breve)</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="avatarModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header"><h5 class="modal-title">Personalize seu Avatar</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <ul class="nav nav-tabs nav-fill mb-3" id="avatar-type-tab">
                        <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#predefined-avatars">Avatares</a></li>
                        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#upload-avatar-tab">Enviar Foto</a></li>
                    </ul>
                    <div class="tab-content">
                        <div class="tab-pane fade show active" id="predefined-avatars">
                            <p class="text-muted text-center">Escolha um dos nossos avatares minimalistas.</p>
                            <div class="row g-3 text-center" id="avatar-options">
                                <?php $seeds = ['Socks', 'Salem', 'Max', 'Mimi', 'Leo', 'Garfield', 'Loki', 'Muffin']; ?>
                                <?php foreach ($seeds as $seed): ?>
                                    <div class="col-3"><img src="https://api.dicebear.com/8.x/micah/svg?seed=<?= $seed ?>" class="img-fluid avatar-option" data-avatar-url="https://api.dicebear.com/8.x/micah/svg?seed=<?= $seed ?>"></div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="tab-pane fade" id="upload-avatar-tab">
                             <p class="text-muted text-center">Envie uma foto quadrada para melhores resultados.</p>
                             <input type="file" id="upload-input" class="form-control" accept="image/png, image/jpeg">
                             <div id="image-cropper-container"><img id="image-to-crop"></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" id="saveAvatarBtn">Salvar Avatar</button>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            gsap.from('.profile-card', { duration: 0.8, y: 50, opacity: 0, ease: 'power3.out' });

            const handleAjaxForm = async (form, button) => {
                const originalButtonText = button.innerHTML;
                button.disabled = true;
                button.innerHTML = `<span class="spinner-border spinner-border-sm"></span> Salvando...`;
                try {
                    const response = await fetch('perfil.php', { method: 'POST', body: new FormData(form) });
                    const result = await response.json();
                    if (!response.ok || !result.success) throw new Error(result.message || 'Ocorreu um erro.');
                    Swal.fire({ icon: 'success', title: 'Sucesso!', text: result.message, timer: 2000, showConfirmButton: false });
                    if(result.nome) { document.querySelector('h4.fw-bold').textContent = result.nome; }
                    return result;
                } catch (error) {
                    Swal.fire({ icon: 'error', title: 'Oops...', text: error.message });
                    return null;
                } finally {
                    button.disabled = false;
                    button.innerHTML = originalButtonText;
                }
            };
            
            document.getElementById('updateNomeForm').addEventListener('submit', async function(e) {
                e.preventDefault();
                await handleAjaxForm(this, this.querySelector('button[type="submit"]'));
            });

            let selectedAvatarUrl = '<?= htmlspecialchars($aluno['avatar_url'] ?? '') ?>';
            let cropper = null;
            let uploadMode = false;
            const avatarOptions = document.querySelectorAll('.avatar-option');
            const uploadInput = document.getElementById('upload-input');
            const imageToCrop = document.getElementById('image-to-crop');
            const cropperContainer = document.getElementById('image-cropper-container');
            const saveAvatarBtn = document.getElementById('saveAvatarBtn');

            document.querySelectorAll('#avatar-type-tab a').forEach(tabEl => {
                tabEl.addEventListener('shown.bs.tab', event => {
                    uploadMode = (event.target.getAttribute('href') === '#upload-avatar-tab');
                    if (!uploadMode && cropper) {
                        cropper.destroy();
                        cropper = null;
                        cropperContainer.style.display = 'none';
                    }
                });
            });
            
            avatarOptions.forEach(img => {
                if (img.dataset.avatarUrl === selectedAvatarUrl) img.classList.add('selected');
                img.addEventListener('click', function() {
                    avatarOptions.forEach(opt => opt.classList.remove('selected'));
                    this.classList.add('selected');
                    selectedAvatarUrl = this.dataset.avatarUrl;
                });
            });

            uploadInput.addEventListener('change', function(e) {
                const files = e.target.files;
                if (files && files.length > 0) {
                    const reader = new FileReader();
                    reader.onload = function(event) {
                        imageToCrop.src = event.target.result;
                        cropperContainer.style.display = 'block';
                        if (cropper) cropper.destroy();
                        cropper = new Cropper(imageToCrop, { aspectRatio: 1, viewMode: 1, background: false, autoCropArea: 1 });
                    };
                    reader.readAsDataURL(files[0]);
                }
            });

            saveAvatarBtn.addEventListener('click', function() {
                const button = this;
                const originalButtonText = button.innerHTML;
                button.disabled = true;
                button.innerHTML = `<span class="spinner-border spinner-border-sm"></span> Salvando...`;

                const formData = new FormData();
                formData.append('action', 'update_avatar');

                const submitForm = async (formData) => {
                    try {
                        const response = await fetch('perfil.php', { method: 'POST', body: formData });
                        const result = await response.json();
                        if (!response.ok || !result.success) throw new Error(result.message || 'Ocorreu um erro.');
                        
                        let finalUrl = result.avatar_url;
                        if (!finalUrl.startsWith('http') && !finalUrl.startsWith('../')) {
                            finalUrl = '../' + finalUrl;
                        }
                        
                        document.getElementById('currentAvatar').src = finalUrl;
                        bootstrap.Modal.getInstance(document.getElementById('avatarModal')).hide();
                        Swal.fire({ icon: 'success', title: 'Sucesso!', text: result.message, timer: 2000, showConfirmButton: false });
                    } catch (error) {
                        Swal.fire({ icon: 'error', title: 'Oops...', text: error.message });
                    } finally {
                        button.disabled = false;
                        button.innerHTML = originalButtonText;
                    }
                };

                if (uploadMode && cropper) {
                    cropper.getCroppedCanvas({ width: 250, height: 250, imageSmoothingQuality: 'high' })
                    .toBlob(blob => {
                        formData.append('avatar_upload', blob, "avatar.jpg");
                        submitForm(formData);
                    }, 'image/jpeg');
                } else if (!uploadMode && selectedAvatarUrl) {
                    formData.append('avatar_url', selectedAvatarUrl);
                    submitForm(formData);
                } else {
                    button.disabled = false;
                    button.innerHTML = originalButtonText;
                }
            });
        });
    </script>
</body>
</html>
