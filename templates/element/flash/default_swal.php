<?php
/**
 * SWAL flash: SweetAlert2 modal (default).
 *
 * @var \App\View\AppView $this
 * @var array $params
 * @var string $message
 */
if (!isset($params['escape']) || $params['escape'] !== false) {
	$message = h($message);
}
$icon = 'info';
if (!empty($params['class'])) {
	$class = (string)$params['class'];
	if (str_contains($class, 'success')) {
		$icon = 'success';
	} elseif (str_contains($class, 'error') || str_contains($class, 'danger')) {
		$icon = 'error';
	} elseif (str_contains($class, 'warning')) {
		$icon = 'warning';
	}
}
$jsFlags = JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;
?>
MyAdmin.flashSwal({
	icon: <?= json_encode($icon, $jsFlags) ?>,
	title: <?= json_encode(__('Message'), $jsFlags) ?>,
	html: <?= json_encode($message, $jsFlags) ?>
});
