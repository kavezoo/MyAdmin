<?php
/**
 * SWAL flash: SweetAlert2 modal (egyszerre egy; több üzenet sorban).
 *
 * Használat: $this->Flash->success($msg, ['element' => 'flash/success_swal']);
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
MyAdmin.flashSwal({
	icon: 'success',
	title: <?= json_encode(__('Success'), $jsFlags) ?>,
	html: <?= json_encode($message, $jsFlags) ?>
});
