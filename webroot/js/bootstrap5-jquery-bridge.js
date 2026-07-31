/**
 * jQuery compatibility bridge for Bootstrap 5 components.
 * Allows legacy template plugins to keep using $.fn.tooltip/popover/modal APIs.
 */
(function ($) {
  'use strict';

  if (typeof bootstrap === 'undefined') {
    return;
  }

  function getOrCreate(Component, element, config) {
    var instance = Component.getInstance(element);
    if (!instance) {
      instance = new Component(element, config || {});
    }
    return instance;
  }

  $.fn.tooltip = function (config) {
    return this.each(function () {
      getOrCreate(bootstrap.Tooltip, this, typeof config === 'object' ? config : undefined);
    });
  };

  $.fn.popover = function (config) {
    return this.each(function () {
      getOrCreate(bootstrap.Popover, this, typeof config === 'object' ? config : undefined);
    });
  };

  $.fn.modal = function (option) {
    return this.each(function () {
      var instance = getOrCreate(bootstrap.Modal, this);

      if (typeof option === 'string') {
        if (typeof instance[option] === 'function') {
          instance[option]();
        }
      } else if (typeof option === 'object' && option !== null) {
        instance = bootstrap.Modal.getInstance(this);
        if (instance) {
          instance.dispose();
        }
        new bootstrap.Modal(this, option);
      }
    });
  };

  // Legacy BS4 event names used by older plugins (e.g. bootstrap-timepicker).
  $(document).on('hidden.bs.modal', '.modal', function () {
    $(this).trigger('hidden');
  });

  $(document).on('shown.bs.modal', '.modal', function () {
    $(this).trigger('shown');
  });

  // Auto-init tooltips and popovers declared via data attributes.
  $(function () {
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (el) {
      if (!bootstrap.Tooltip.getInstance(el)) {
        new bootstrap.Tooltip(el);
      }
    });

    document.querySelectorAll('[data-bs-toggle="popover"]').forEach(function (el) {
      if (!bootstrap.Popover.getInstance(el)) {
        new bootstrap.Popover(el);
      }
    });
  });
})(jQuery);
