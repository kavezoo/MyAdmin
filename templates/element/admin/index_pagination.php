<?= $this->Paginator->numbers([
	'before' => '<nav aria-label="' . h(__('Pagination')) . '"><ul class="pagination mb-0">',
	'after' => '</ul></nav>',
	'modulus' => 3,
	'first' => false,
	'last' => false,
	'prev' => __('Previous'),
	'next' => __('Next'),
	'templates' => [
		'number' => '<li class="page-item"><a class="page-link" href="{{url}}">{{text}}</a></li>',
		'current' => '<li class="page-item active" aria-current="page"><a class="page-link" href="{{url}}">{{text}}</a></li>',
		'prevActive' => '<li class="page-item"><a class="page-link" href="{{url}}">{{text}}</a></li>',
		'prevDisabled' => '<li class="page-item disabled"><a class="page-link" href="#" tabindex="-1" aria-disabled="true">{{text}}</a></li>',
		'nextActive' => '<li class="page-item"><a class="page-link" href="{{url}}">{{text}}</a></li>',
		'nextDisabled' => '<li class="page-item disabled"><a class="page-link" href="#" tabindex="-1" aria-disabled="true">{{text}}</a></li>',
		'ellipsis' => '<li class="page-item disabled"><span class="page-link">&hellip;</span></li>',
	],
]) ?>
