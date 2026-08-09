<?php
/**
 * Render trusted admin HTML (editor content) with embedded <style> intact.
 * Uses srcdoc iframe so Admin Bootstrap does not override colors/fonts/alignment.
 *
 * @var \App\View\AppView $this
 * @var string $html
 * @var string|null $class
 * @var string|null $empty
 */
$html = trim((string)($html ?? ''));
$class = trim('html-content-frame ' . (string)($class ?? ''));
$empty = $empty ?? '—';

if ($html === '') {
	echo h((string)$empty);

	return;
}

// Move <style> blocks into <head> so layout CSS (centering, fonts) always applies.
$headStyles = '';
$bodyHtml = (string)preg_replace_callback(
	'/<style\b[^>]*>(.*?)<\/style>/is',
	static function (array $m) use (&$headStyles): string {
		$headStyles .= "\n" . $m[1];

		return '';
	},
	$html
);
$bodyHtml = trim($bodyHtml);

$frameId = 'html-content-' . str_replace('.', '', uniqid('', true));
$doc = '<!DOCTYPE html><html><head><meta charset="utf-8">'
	. '<meta name="viewport" content="width=device-width, initial-scale=1">'
	. '<base target="_blank" rel="noopener">'
	. '<style>'
	. 'html,body{margin:0;padding:0;background:transparent;}'
	. 'img{max-width:100%;height:auto;}'
	. $headStyles
	. '</style>'
	. '</head><body>' . $bodyHtml . '</body></html>';
?>
<iframe
	id="<?= h($frameId) ?>"
	class="<?= h($class) ?>"
	title="<?= h(__('HTML content')) ?>"
	sandbox="allow-same-origin"
	srcdoc="<?= h($doc) ?>"
></iframe>
<?php
$this->Html->scriptBlock(
	'(function(){var f=document.getElementById(' . json_encode($frameId) . ');'
	. 'if(!f)return;'
	. 'var fit=function(){try{var d=f.contentDocument;if(!d)return;'
	. 'var h=Math.max(d.body?d.body.scrollHeight:0,d.documentElement?d.documentElement.scrollHeight:0);'
	. 'f.style.height=Math.max(120,h+12)+"px";}catch(e){}};'
	. 'f.addEventListener("load",fit);'
	. 'if(f.contentDocument&&f.contentDocument.readyState==="complete"){fit();}'
	. 'setTimeout(fit,50);setTimeout(fit,250);setTimeout(fit,800);})();',
	['block' => 'scriptBottom']
);
?>
