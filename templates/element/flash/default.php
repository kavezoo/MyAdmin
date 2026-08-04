<?php
/**
 * Default flash: Admin / login layout → Simple Notify toast; egyébként HTML.
 *
 * @var \App\View\AppView $this
 * @var array $params
 * @var string $message
 */
$class = 'message';
if (!empty($params['class'])) {
	$class .= ' ' . $params['class'];
}
if (!isset($params['escape']) || $params['escape'] !== false) {
	$message = h($message);
}
if ($this->usesFlashToast()):
	$status = 'info';
	if (!empty($params['class'])) {
		$pc = (string)$params['class'];
		if (str_contains($pc, 'success')) {
			$status = 'success';
		} elseif (str_contains($pc, 'error') || str_contains($pc, 'danger')) {
			$status = 'error';
		} elseif (str_contains($pc, 'warning')) {
			$status = 'warning';
		}
	}
	$jsFlags = JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;
?>
flashMessage(<?= json_encode(__('Message'), $jsFlags) ?>, <?= json_encode($message, $jsFlags) ?>, <?= json_encode($status, $jsFlags) ?>);
<?php else: ?>
<div class="<?= h($class) ?>" onclick="this.classList.add('hidden')"><?= $message ?></div>
<?php endif; ?>
