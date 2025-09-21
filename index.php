<?php
// ========= HEALTHCHECK =========
if (($_SERVER['REQUEST_URI'] ?? '/') === '/health') {
  header('Content-Type: text/plain; charset=utf-8');
  http_response_code(200);
  echo 'ok';
  exit;
}

<?php
/* index.php — Gerador de PIX (copia e cola) com token da PushinPay
 *
 * ⚠️ Token fixo (do seu arquivo): 47525|lMigYjG2owjwmBCmPusi5WZ9G90ETU9zB2eg4HVmd79bbd68
 */

ini_set('display_errors', 0);
error_reporting(E_ALL);
header('Content-Type: text/html; charset=UTF-8');

// ========= CONFIG =========
const API_URL = 'https://api.pushinpay.com.br/api/pix/cashIn';
const TOKEN   = '47525|lMigYjG2owjwmBCmPusi5WZ9G90ETU9zB2eg4HVmd79bbd68';

// Valor em centavos (ex.: 1000 = R$10,00)
$VALOR_CENTAVOS = 1000;

// ========= Função simples de POST JSON =========
function post_json($url, $payload, $headers = [], $timeout = 20) {
  $ch = curl_init($url);
  $defaultHeaders = ['Accept: application/json', 'Content-Type: application/json'];
  $allHeaders = array_merge($defaultHeaders, $headers);

  curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => $timeout,
    CURLOPT_HTTPHEADER => $allHeaders,
    CURLOPT_POSTFIELDS => json_encode($payload),
  ]);

  $body = curl_exec($ch);
  $errno = curl_errno($ch);
  $err   = curl_error($ch);
  $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);

  return [$status, $body, $errno, $err];
}

// ========= Gera PIX =========
$erros = [];
$copyPaste = null;

$body = ['value' => (int)$VALOR_CENTAVOS, 'split_rules' => []];
[$st, $bd, $ce, $er] = post_json(API_URL, $body, ['Authorization: Bearer ' . TOKEN]);

if ($ce !== 0) {
  $erros[] = "Falha de conexão: $er";
} else {
  $json = json_decode($bd, true);
  if (!is_array($json)) {
    $erros[] = 'Resposta inválida (não é JSON).';
  } elseif ($st < 200 || $st >= 300) {
    $erros[] = "Erro ao criar PIX (HTTP $st). Resposta: $bd";
  } else {
    $copyPaste = $json['qr_code'] ?? null;
    if (!$copyPaste) {
      $erros[] = "Não encontrei o campo 'qr_code' na resposta.";
    }
  }
}
?>
<!doctype html>
<html lang="pt-br">
<head>
<meta charset="utf-8">
<title>Código PIX</title>
<style>
body { font-family: sans-serif; background:#0d0d14; color:#eee; display:flex; align-items:center; justify-content:center; min-height:100vh; }
.card { background:#1a1a25; padding:20px; border-radius:10px; max-width:800px; width:90%; }
textarea { width:100%; min-height:160px; border-radius:8px; padding:10px; font-family:monospace; font-size:14px; }
button { margin-top:10px; padding:10px 16px; border:none; border-radius:6px; background:#6aa0ff; font-weight:bold; cursor:pointer; }
.error { background:#331; padding:12px; border-radius:8px; color:#faa; margin-bottom:10px; }
</style>
</head>
<body>
  <div class="card">
    <h1>PIX Copia e Cola</h1>
    <?php if ($erros): ?>
      <div class="error"><?php echo implode("<br>", array_map('htmlspecialchars',$erros)); ?></div>
    <?php else: ?>
      <textarea id="pixCode" readonly><?php echo htmlspecialchars($copyPaste); ?></textarea>
      <button id="copyBtn">Copiar código PIX</button>
      <script>
        const btn = document.getElementById('copyBtn');
        const ta = document.getElementById('pixCode');
        btn.addEventListener('click', async () => {
          try {
            await navigator.clipboard.writeText(ta.value);
            btn.textContent = 'Copiado!';
            setTimeout(()=>btn.textContent='Copiar código PIX',1500);
          } catch {
            ta.select(); document.execCommand('copy');
          }
        });
      </script>
    <?php endif; ?>
  </div>
</body>
</html>

