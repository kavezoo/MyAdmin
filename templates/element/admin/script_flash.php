<?php
/**
 * Flash → Simple Notify helper (JeffAdmin5 mintájára).
 * Több üzenet is megjelenhet egyszerre (toast).
 *
 * Title + text → textContent (ne innerHTML): UTF-8 ékezetek és XSS-biztonság.
 *
 * @var \App\View\AppView $this
 */
?>
function flashMessage(title, text, status) {
	if (typeof Notify === 'undefined') {
		if (window.console && console.warn) {
			console.warn('Simple Notify is not loaded.', title, text);
		}
		return;
	}
	var notify = new Notify({
		status: status || 'info',
		title: title || '',
		text: text ? '\u00a0' : '',
		effect: 'slide',
		speed: 500,
		customClass: '',
		customIcon: '',
		showIcon: true,
		showCloseButton: true,
		autoclose: true,
		autotimeout: 5000,
		notificationsGap: null,
		notificationsPadding: null,
		position: 'bottom left',
		type: 'outline',
		customWrapper: ''
	});
	// Simple Notify sets text via innerHTML — use textContent for correct UTF-8 + no entity glitches.
	if (notify.wrapper && text) {
		var textEl = notify.wrapper.querySelector('.sn-notify-text');
		if (textEl) {
			textEl.textContent = text;
		}
	}
}
