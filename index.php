<?php

// Inclui o arquivo de conexão com o banco de dados
include("conexao.php");

// Verifica se foi solicitada a exclusão de um arquivo
if (isset($_GET['deletar'])) {
    $id = intval($_GET['deletar']);

    // Consulta o banco de dados para obter os detalhes do arquivo a ser excluído
    $sql_query = $mysqli->query("SELECT * FROM arquivos WHERE id = '$id'") or die($mysqli->error);
    $arquivo = $sql_query->fetch_assoc();

    // Tenta excluir o arquivo do servidor
    if (unlink($arquivo['path'])) {
        // Se o arquivo foi excluído do servidor com sucesso, remove-o do banco de dados
        $deu_certo = $mysqli->query("DELETE FROM arquivos WHERE id = '$id'") or die($mysqli->error);
        if ($deu_certo) {
            echo "<p>Arquivo excluído com sucesso!!</p>";
        }
    }
}

// Função para lidar com o envio de arquivos
function enviarArquivo($error, $size, $name, $tmp_name)
{
    include("conexao.php");

    // Verifica se houve algum erro durante o envio
    if ($error) {
        die("Falha ao enviar arquivo");
    }

    // Verifica se o tamanho do arquivo é aceitável (2MB neste caso)
    if ($size > 2097152) {
        die("Arquivo muito grande!! Max: 2MB ");
    }

    // Define o diretório de destino para os arquivos enviados
    $pasta = "arquivos/";

    // Obtém o nome do arquivo original
    $nomeDoArquivo = $name;

    // Gera um nome único para o arquivo
    $novoNomeDoArquivo = uniqid();

    // Obtém a extensão do arquivo e a converte para minúsculas
    $extensao = strtolower(pathinfo($nomeDoArquivo, PATHINFO_EXTENSION));

    // Verifica se o tipo de arquivo é permitido (apenas jpg e png neste caso)
    if ($extensao != "jpg" && $extensao != "png") {
        die("Tipo de arquivo não aceito");
    }

    // Define o caminho completo do arquivo no servidor
    $path = $pasta . $novoNomeDoArquivo . '.' . $extensao;

    // Move o arquivo para o diretório de destino
    $deu_certo = move_uploaded_file($tmp_name, $path);

    // Se o arquivo foi movido com sucesso, insere seus detalhes no banco de dados
    if ($deu_certo) {
        $mysqli->query("INSERT INTO arquivos (nome, path) VALUES('$nomeDoArquivo', '$path')") or die($mysqli->error());
        return true;
    } else {
        return false;
    }
}

// Verifica se arquivos foram enviados através do formulário
if (isset($_FILES['arquivos'])) {
    $arquivos = $_FILES['arquivos'];

    $tudo_certo = true;

    // Itera sobre cada arquivo enviado e os envia para a função enviarArquivo()
    foreach ($arquivos['name'] as $index => $arq) {
        $deu_certo = enviarArquivo($arquivos['error'][$index], $arquivos['size'][$index], $arquivos['name'][$index], $arquivos["tmp_name"][$index]);

        // Verifica se o envio do arquivo foi bem-sucedido
        if (!$deu_certo) {
            $tudo_certo = false;
        }
    }

    // Exibe mensagem de sucesso ou falha após o envio dos arquivos
    if ($tudo_certo) {
        echo "<p>Todos os arquivos foram enviados com sucesso!</p>";
    } else {
        echo "<p>Falha ao enviar um ou mais arquivos!</p>";
    }
}

// Consulta o banco de dados para recuperar os arquivos enviados anteriormente
$sql_query = $mysqli->query("SELECT * FROM arquivos") or die($mysqli->error);
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload de Arquivo</title>
</head>

<body>
    <!-- Formulário HTML para enviar arquivos -->
    <form method="POST" action="" enctype="multipart/form-data">
        <p>
            <label for="">Selecione o arquivo</label>
            <input multiple name="arquivos[]" type="file">
        </p>
        <button name="upload" type="submit">
            Enviar Arquivo
        </button>
    </form>

    <!-- Tabela HTML para exibir os arquivos enviados -->
    <table border="1" cellpadding="10">
        <thead>
            <th>Preview</th>
            <th>Arquivo</th>
            <th>Data de Envio</th>
        </thead>
        <tbody>
            <?php
            // Loop para exibir cada arquivo recuperado do banco de dados
            while ($arquivo = $sql_query->fetch_assoc()) {
                ?>
                <tr>
                    <!-- Exibe uma miniatura do arquivo se for uma imagem -->
                    <td><img height="50" src="<?php echo $arquivo['path']; ?>" alt=""></td>
                    <!-- Exibe o nome do arquivo e um link para visualizá-lo -->
                    <td><a target="_blank" href="<?php echo $arquivo['path']; ?>">
                            <?php echo $arquivo['nome'] ?>
                        </a></td>
                    <!-- Exibe a data de envio do arquivo -->
                    <td>
                        <?php echo date("d/m/Y H:i", strtotime($arquivo['data_upload'])) ?>
                    </td>
                    <!-- Fornece um link para excluir o arquivo -->
                    <th>
                        <a href="index.php?deletar=<?php echo $arquivo['id']; ?>">Deletar</a>
                    </th>
                </tr>
                <?php
            }
            ?>
        </tbody>
    </table>

</body>

</html>