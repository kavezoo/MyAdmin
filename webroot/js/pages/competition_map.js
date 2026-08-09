/**
 * Competition map enlarge / close (Esc).
 */
(function (window, $) {
	'use strict';

	function closeLightbox($lb) {
		$lb.attr('hidden', 'hidden');
		$('body').removeClass('competition-map-lightbox-open');
	}

	function openLightbox($widget) {
		var $lb = $widget.nextAll('[data-competition-map-lightbox]').first();
		if (!$lb.length) {
			$lb = $widget.closest('.competition-description-rendered, .card-body')
				.find('[data-competition-map-lightbox]').first();
		}
		if (!$lb.length) {
			return;
		}
		$lb.removeAttr('hidden');
		$('body').addClass('competition-map-lightbox-open');
	}

	$(function () {
		$(document).on('click', '.js-competition-map-expand', function (e) {
			e.preventDefault();
			openLightbox($(this).closest('[data-competition-map]'));
		});
		$(document).on('click', '.js-competition-map-close', function (e) {
			e.preventDefault();
			closeLightbox($(this).closest('[data-competition-map-lightbox]'));
		});
		$(document).on('click', '[data-competition-map-lightbox]', function (e) {
			if (e.target === this) {
				closeLightbox($(this));
			}
		});
		$(document).on('keydown', function (e) {
			if (e.key !== 'Escape') {
				return;
			}
			$('[data-competition-map-lightbox]:not([hidden])').each(function () {
				closeLightbox($(this));
			});
		});
	});
})(window, jQuery);
