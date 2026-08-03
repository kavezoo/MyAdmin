<?php
/**
 * Shared Admin pagination: « ‹ numbers › » (FA icons; enabled/disabled).
 *
 * @var \App\View\AppView $this
 */
$labelFirst = __('First');
$labelPrev = __('Previous');
$labelNext = __('Next');
$labelLast = __('Last');

$iconFirst = '<i class="fa fa-angle-double-left" aria-hidden="true"></i>';
$iconPrev = '<i class="fa fa-angle-left" aria-hidden="true"></i>';
$iconNext = '<i class="fa fa-angle-right" aria-hidden="true"></i>';
$iconLast = '<i class="fa fa-angle-double-right" aria-hidden="true"></i>';

$this->Paginator->setTemplates([
	'number' => '<li class="page-item"><a class="page-link" href="{{url}}">{{text}}</a></li>',
	'current' => '<li class="page-item active" aria-current="page"><a class="page-link" href="{{url}}">{{text}}</a></li>',
	'ellipsis' => '<li class="page-item disabled"><span class="page-link">&hellip;</span></li>',
	'prevActive' => '<li class="page-item"><a class="page-link" rel="prev" href="{{url}}" title="{{text}}" aria-label="{{text}}">' . $iconPrev . '</a></li>',
	'prevDisabled' => '<li class="page-item disabled"><a class="page-link" href="#" tabindex="-1" aria-disabled="true" title="{{text}}" aria-label="{{text}}">' . $iconPrev . '</a></li>',
	'nextActive' => '<li class="page-item"><a class="page-link" rel="next" href="{{url}}" title="{{text}}" aria-label="{{text}}">' . $iconNext . '</a></li>',
	'nextDisabled' => '<li class="page-item disabled"><a class="page-link" href="#" tabindex="-1" aria-disabled="true" title="{{text}}" aria-label="{{text}}">' . $iconNext . '</a></li>',
	'first' => '<li class="page-item"><a class="page-link" href="{{url}}" title="{{text}}" aria-label="{{text}}">' . $iconFirst . '</a></li>',
	'last' => '<li class="page-item"><a class="page-link" href="{{url}}" title="{{text}}" aria-label="{{text}}">' . $iconLast . '</a></li>',
]);

$onFirst = !$this->Paginator->hasPrev();
$onLast = !$this->Paginator->hasNext();
$numbers = $this->Paginator->numbers(['modulus' => 3]);
if ($numbers === '') {
	$current = (int)$this->Paginator->current();
	if ($current < 1) {
		$current = 1;
	}
	$numbers = '<li class="page-item active" aria-current="page"><a class="page-link" href="#">'
		. h((string)$current)
		. '</a></li>';
}
?>
<nav aria-label="<?= h(__('Pagination')) ?>">
	<ul class="pagination mb-0">
		<?php if ($onFirst): ?>
			<li class="page-item disabled">
				<a class="page-link" href="#" tabindex="-1" aria-disabled="true" title="<?= h($labelFirst) ?>" aria-label="<?= h($labelFirst) ?>"><?= $iconFirst ?></a>
			</li>
		<?php else: ?>
			<?= $this->Paginator->first($labelFirst) ?>
		<?php endif; ?>

		<?= $this->Paginator->prev($labelPrev) ?>
		<?= $numbers ?>
		<?= $this->Paginator->next($labelNext) ?>

		<?php if ($onLast): ?>
			<li class="page-item disabled">
				<a class="page-link" href="#" tabindex="-1" aria-disabled="true" title="<?= h($labelLast) ?>" aria-label="<?= h($labelLast) ?>"><?= $iconLast ?></a>
			</li>
		<?php else: ?>
			<?= $this->Paginator->last($labelLast) ?>
		<?php endif; ?>
	</ul>
</nav>
