/**
 * Competition text template form — insert {{placeholders}} into Summernote at caret.
 */
(function (window, $) {
	'use strict';

	var App = window.MyAdmin = window.MyAdmin || {};
	var lastEditor$ = null;

	function descriptionEditors() {
		return $('#tabPanelCompetitionDescription textarea.editor, #form-horizontal textarea.editor-tall, #form-horizontal textarea.editor');
	}

	function activeEditor() {
		if (lastEditor$ && lastEditor$.length && lastEditor$.closest('body').length) {
			var $pane = lastEditor$.closest('.tab-pane');
			if (!$pane.length || $pane.hasClass('active') || $pane.hasClass('show')) {
				return lastEditor$;
			}
		}
		var $activePane = $('#tabPanelCompetitionDescription .tab-pane.active, #tabPanelCompetitionDescription .tab-pane.show.active, #formLanguageTabsDescription-content .tab-pane.active, #formLanguageTabsDescription-content .tab-pane.show.active');
		var $ed = $activePane.find('textarea.editor').first();
		if ($ed.length) {
			return $ed;
		}
		$ed = descriptionEditors().filter(function () {
			var $p = $(this).closest('.tab-pane');
			return !$p.length || $p.hasClass('active') || $p.hasClass('show');
		}).first();
		if ($ed.length) {
			return $ed;
		}
		return descriptionEditors().first();
	}

	function insertAtTextarea(el, text) {
		var start = el.selectionStart != null ? el.selectionStart : el.value.length;
		var end = el.selectionEnd != null ? el.selectionEnd : start;
		var value = el.value || '';
		el.value = value.slice(0, start) + text + value.slice(end);
		var pos = start + text.length;
		el.focus();
		if (typeof el.setSelectionRange === 'function') {
			el.setSelectionRange(pos, pos);
		}
		$(el).trigger('input').trigger('change');
	}

	function insertPlaceholder(token) {
		var $ta = activeEditor();
		if (!$ta || !$ta.length) {
			return;
		}
		var html = '<strong>' + token + '</strong>';
		if ($ta.hasClass('editor') && $ta.next('.note-editor').length && typeof $ta.summernote === 'function') {
			$ta.summernote('focus');
			$ta.summernote('pasteHTML', html);
			$ta.val($ta.summernote('code'));
			$ta.trigger('change');
			return;
		}
		if ($ta.hasClass('editor') && $ta.data('trumbowyg') && typeof $ta.trumbowyg === 'function') {
			$ta.trumbowyg('focus');
			try {
				document.execCommand('insertHTML', false, html);
			} catch (err) {
				$ta.trumbowyg('html', ($ta.trumbowyg('html') || '') + html);
			}
			$ta.trigger('change');
			return;
		}
		insertAtTextarea($ta.get(0), token);
	}

	function bindEditorFocus() {
		descriptionEditors().each(function () {
			var $ta = $(this);
			$ta.off('summernote.focus.placeholderInsert').on('summernote.focus.placeholderInsert', function () {
				lastEditor$ = $ta;
			});
			$ta.off('focus.placeholderInsert').on('focus.placeholderInsert', function () {
				lastEditor$ = $ta;
			});
			var $note = $ta.next('.note-editor');
			if ($note.length) {
				$note.off('mousedown.placeholderInsert').on('mousedown.placeholderInsert', function () {
					lastEditor$ = $ta;
				});
			}
		});
	}

	$(function () {
		var $root = $('#competitionPlaceholders');
		if (!$root.length) {
			return;
		}
		bindEditorFocus();
		window.setTimeout(bindEditorFocus, 400);

		$root.on('click', '.competition-placeholder-chip', function (e) {
			e.preventDefault();
			var token = $(this).attr('data-placeholder') || '';
			if (!token) {
				return;
			}
			insertPlaceholder(token);
		});

		$('#formLanguageTabsDescription, #competitionTextTemplateFormTabs, #competitionFormTabs')
			.on('shown.bs.tab', function () {
				window.setTimeout(bindEditorFocus, 50);
			});
	});
})(window, jQuery);
