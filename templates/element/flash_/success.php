<?php
/**
 * Legacy flash_: jquery-toastmessage (JeffAdmin5 flash_ mappa).
 * Használat: $this->Flash->success($msg, ['element' => 'flash_/success']);
 * Layoutban külön kell tölteni a jquery-toastmessage CSS/JS-t.
 *
 * @var \App\View\AppView $this
 * @var array $params
 * @var string $message
 */
if (!isset($params['escape']) || $params['escape'] !== false) {
	$message = h($message);
}
$jsFlags = JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;
?>
$().toastmessage('showSuccessToast', <?= json_encode($message, $jsFlags) ?>);
