/**
 * Shared app helpers for MyAdmin / future CakePHP 5 templates.
 * Layout-level: safe to load on every admin page.
 *
 * cakephp-template: layout-js
 *
 * Translated strings: window.MyAdmin.messages (set from PHP layout via __()).
 */
(function (window, $) {
	'use strict';

	var App = window.MyAdmin = window.MyAdmin || {};

	App.config = App.config || {};
	App.messages = App.messages || {};

	var t = function (key, fallback) {
		var messages = App.messages || {};
		return messages[key] || fallback;
	};

	App.initTooltips = function (selector) {
		selector = selector || '[data-bs-toggle="tooltip"]';
		$(selector).each(function () {
			bootstrap.Tooltip.getOrCreateInstance(this);
		});
	};

	/**
	 * Bootstrap 5 Modal FocusTrap ütközik a SweetAlert2-vel (fókusz visszarántás).
	 * Swal megnyitás előtt deaktiváljuk, bezáráskor vissza.
	 *
	 * @returns {Array<{activate: Function}>}
	 */
	App.pauseBootstrapModalFocusTraps = function () {
		var paused = [];
		document.querySelectorAll('.modal.show').forEach(function (el) {
			var instance = typeof bootstrap !== 'undefined' && bootstrap.Modal
				? bootstrap.Modal.getInstance(el)
				: null;
			if (instance && instance._focustrap && typeof instance._focustrap.deactivate === 'function') {
				instance._focustrap.deactivate();
				paused.push(instance._focustrap);
			}
		});
		return paused;
	};

	App.resumeBootstrapModalFocusTraps = function (paused) {
		(paused || []).forEach(function (trap) {
			if (trap && typeof trap.activate === 'function') {
				trap.activate();
			}
		});
	};

	/**
	 * SweetAlert2 a Bootstrap modalok fölött (z-index + focus trap).
	 *
	 * @param {object} swalOptions Swal.fire options
	 * @returns {Promise}
	 */
	App.swal = function (swalOptions) {
		swalOptions = swalOptions || {};
		var paused = App.pauseBootstrapModalFocusTraps();
		var resumed = false;
		var resumeOnce = function () {
			if (resumed) {
				return;
			}
			resumed = true;
			App.resumeBootstrapModalFocusTraps(paused);
		};
		var userDidOpen = swalOptions.didOpen;
		var userDidDestroy = swalOptions.didDestroy;

		swalOptions.heightAuto = swalOptions.heightAuto === true ? true : false;
		swalOptions.didOpen = function (popup) {
			var container = typeof Swal.getContainer === 'function' ? Swal.getContainer() : null;
			if (container) {
				container.style.zIndex = '20000';
			}
			if (typeof userDidOpen === 'function') {
				userDidOpen(popup);
			}
		};
		swalOptions.didDestroy = function () {
			resumeOnce();
			if (typeof userDidDestroy === 'function') {
				userDidDestroy();
			}
		};

		return Swal.fire(swalOptions).finally(function () {
			resumeOnce();
		});
	};

	/**
	 * SweetAlert2 leave confirmation (unsaved form changes).
	 *
	 * @param {object} options { title, text, confirmButtonText, cancelButtonText, onConfirm }
	 * @returns {Promise}
	 */
	App.confirmLeave = function (options) {
		options = options || {};

		return App.swal({
			icon: options.icon || 'warning',
			title: options.title || t('unsavedTitle', 'Unsaved changes'),
			text: options.text || t('unsavedConfirm', 'You have unsaved changes. Do you really want to leave this form?'),
			showCancelButton: true,
			focusCancel: true,
			confirmButtonText: options.confirmButtonText || t('leaveButton', 'Leave'),
			cancelButtonText: options.cancelButtonText || t('stayButton', 'Stay'),
			confirmButtonColor: options.confirmButtonColor || '#dc3545',
			cancelButtonColor: options.cancelButtonColor || '#6c757d',
			reverseButtons: true
		}).then(function (result) {
			if (result.isConfirmed && typeof options.onConfirm === 'function') {
				options.onConfirm();
			}
			return result;
		});
	};

	/**
	 * SweetAlert2 delete confirmation (question icon).
	 */
	App.confirmDelete = function (options) {
		options = options || {};
		var paused = App.pauseBootstrapModalFocusTraps();

		return Swal.fire({
			icon: options.icon || 'question',
			title: options.title || t('deleteTitle', 'Delete'),
			text: options.text || t('deleteConfirm', 'Do you really want to delete the selected record?'),
			showCancelButton: true,
			focusCancel: true,
			confirmButtonText: options.confirmButtonText || t('deleteButton', 'Delete'),
			cancelButtonText: options.cancelButtonText || t('cancelButton', 'Cancel'),
			confirmButtonColor: options.confirmButtonColor || '#dc3545',
			cancelButtonColor: options.cancelButtonColor || '#6c757d',
			reverseButtons: true,
			heightAuto: false,
			didOpen: function () {
				var container = typeof Swal.getContainer === 'function' ? Swal.getContainer() : null;
				if (container) {
					container.style.zIndex = '20000';
				}
			}
		}).then(function (result) {
			App.resumeBootstrapModalFocusTraps(paused);
			if (result.isConfirmed && typeof options.onConfirm === 'function') {
				options.onConfirm();
			}
			return result;
		}).catch(function (err) {
			App.resumeBootstrapModalFocusTraps(paused);
			throw err;
		});
	};

	/**
	 * SweetAlert2 message (replaces window.alert everywhere in Admin).
	 *
	 * @param {string|object} options Message string, or { icon, title, text, confirmButtonText }
	 * @returns {Promise}
	 */
	App.alert = function (options) {
		if (typeof options === 'string') {
			options = { text: options };
		}
		options = options || {};
		var icon = options.icon || 'error';
		var defaultTitle = icon === 'error'
			? t('errorTitle', 'Error')
			: (icon === 'success' ? t('successTitle', 'Success') : t('infoTitle', 'Info'));

		return App.swal({
			icon: icon,
			title: options.title || defaultTitle,
			text: options.text || options.message || '',
			confirmButtonText: options.confirmButtonText || t('okButton', 'OK'),
			confirmButtonColor: options.confirmButtonColor || '#0d6efd'
		});
	};

	/** Shortcut: error icon. */
	App.alertError = function (text, title) {
		return App.alert({
			icon: 'error',
			title: title,
			text: text
		});
	};

	/**
	 * Flash → SweetAlert2 (egyszerre egy modal; több Flash SWAL sorban jelenik meg).
	 *
	 * @param {object} options { icon, title, html|text, confirmButtonText }
	 * @returns {Promise}
	 */
	App.flashSwal = function (options) {
		options = options || {};
		var payload = {
			icon: options.icon || 'info',
			title: options.title || '',
			confirmButtonText: options.confirmButtonText || t('okButton', 'OK'),
			confirmButtonColor: options.confirmButtonColor || '#0d6efd',
			heightAuto: false
		};
		if (typeof options.html !== 'undefined' && options.html !== null && options.html !== '') {
			payload.html = options.html;
		} else {
			payload.text = options.text || options.message || '';
		}

		App._flashSwalChain = (App._flashSwalChain || Promise.resolve()).catch(function () {
			return undefined;
		}).then(function () {
			return App.swal(payload);
		});

		return App._flashSwalChain;
	};

	/**
	 * Place caret at end of a text/search input and focus it.
	 *
	 * @param {HTMLInputElement|null} el
	 * @returns {void}
	 */
	App.focusInputCaretEnd = function (el) {
		if (!el || typeof el.value !== 'string' || el.value === '') {
			return;
		}
		el.focus();
		var len = el.value.length;
		if (typeof el.setSelectionRange === 'function') {
			try {
				el.setSelectionRange(len, len);
			} catch (err) {
				/* ignore — some browsers restrict type=search */
			}
		}
	};

	/**
	 * After a search submit: focus the active search field with caret at end of query.
	 * Prefers page search, then index table search (not the header field alone).
	 *
	 * @returns {void}
	 */
	App.focusActiveSearchField = function () {
		var page = document.querySelector('.search-page-input');
		if (page && page.value) {
			App.focusInputCaretEnd(page);
			return;
		}
		var table = document.querySelector('#table-search-input');
		if (table && table.value) {
			App.focusInputCaretEnd(table);
		}
	};

	/**
	 * Fixed “back to top” control — visible only after scrolling down.
	 *
	 * @returns {void}
	 */
	App.initScrollTop = function () {
		var btn = document.getElementById('btn-scroll-top');
		if (!btn) {
			return;
		}
		var threshold = 200;
		var toggle = function () {
			var y = window.scrollY || document.documentElement.scrollTop || 0;
			if (y > threshold) {
				btn.classList.add('is-visible');
			} else {
				btn.classList.remove('is-visible');
			}
		};
		window.addEventListener('scroll', toggle, { passive: true });
		toggle();
		btn.addEventListener('click', function () {
			window.scrollTo({ top: 0, behavior: 'smooth' });
		});
	};

	$(function () {
		App.initTooltips();

		// Keresés után: kurzor a keresőmezőben, a szöveg végén (autofocus után)
		window.setTimeout(function () {
			App.focusActiveSearchField();
		}, 0);

		App.initScrollTop();

		$(document).on('click', '#btn-delete', function (e) {
			e.preventDefault();
			var $btn = $(this);
			if ($btn.prop('disabled') || $btn.hasClass('disabled') || $btn.attr('aria-disabled') === 'true') {
				return;
			}
			var formSel = $btn.attr('data-delete-form') || '#delete-form-current';
			App.confirmDelete({
				onConfirm: function () {
					var $form = $(formSel);
					if ($form.length && $form.is('form')) {
						$form.trigger('submit');
						return;
					}
					App.alertError(
						t('deleteFormMissing', 'Delete form not found for ID:') + ' ' + formSel
					);
				}
			});
		});
	});
})(window, jQuery);
