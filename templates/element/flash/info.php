<?php
/**
 * Default flash: Admin / login layout → Simple Notify toast; egyébként HTML.
 *
 * @var \App\View\AppView $this
 * @var array $params
 * @var string $message
 */
if (!isset($params['escape']) || $params['escape'] !== false) {
	$message = h($message);
}
if ($this->usesFlashToast()):
	$jsFlags = JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;
?>
flashMessage(<?= json_encode(__('Info'), $jsFlags) ?>, <?= json_encode($message, $jsFlags) ?>, 'info');
<?php else: ?>
<div class="message info" onclick="this.classList.add('hidden')"><?= $message ?></div>
<?php endif; ?>
