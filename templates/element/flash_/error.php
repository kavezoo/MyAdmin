<?php
/**
 * Legacy flash_: jquery-toastmessage.
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
$().toastmessage('showErrorToast', <?= json_encode($message, $jsFlags) ?>);
