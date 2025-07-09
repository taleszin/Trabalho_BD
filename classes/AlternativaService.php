<?php
include('config.php');
$idQuestao = isset($_POST['idQuestao']) ? intval($_POST['idQuestao']) : 0;
$letraAlternativa = isset($_POST['letraAlternativa']) ? $_POST['letraAlternativa'] : '';

if ($idQuestao && $letraAlternativa) {
    global $conexao;

    $sql = "SELECT a.correta 
            FROM alternativa a
            WHERE a.idQuestao = $idQuestao AND a.letra = '$letraAlternativa'";

    $result = $conexao->query($sql);

    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $response = [
            'resultado' => $row['correta'] ? 'Resposta correta' : 'Resposta incorreta'
        ];
    } else {
        $response = ['resultado' => 'Alternativa não encontrada'];
    }

    echo json_encode($response);
} else {
    echo json_encode(['resultado' => 'Dados inválidos']);
}
?>
