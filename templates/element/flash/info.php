<?php
/**
 * Default flash: Admin / login layout → Simple Notify toast; egyébként HTML.
 *
 * @var \App\View\AppView $this
 * @var array $params
 * @var string $message
 */
$escape = !isset($params['escape']) || $params['escape'] !== false;
if ($this->usesFlashToast()):
	$jsFlags = JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;
?>
flashMessage(<?= json_encode(__('Info'), $jsFlags) ?>, <?= json_encode((string)$message, $jsFlags) ?>, 'info');
<?php else: ?>
<div class="message info" onclick="this.classList.add('hidden')"><?= $escape ? h($message) : $message ?></div>
<?php endif; ?>
