(
  function ($) {
    'use strict';

    var scAjaxForm = function ($form) {
      var SELF = this;

      SELF.processing = false;

      SELF.init = function () {
        SELF.$form = $form;

        if (!SELF.$form.length) {
          return;
        }

        SELF.$button = SELF.$form.find('input[type=submit]');
        SELF.$form.on('submit', SELF.handleSubmission);
      };

      SELF.handleSubmission = function (e) {
        e.preventDefault();

        if (SELF.processing) {
          return false;
        }

        SELF.data = {
          action  : 'sc_ajax_form',
          formdata: SELF.$form.serialize()
        };

        SELF.startProcessing();

        wp.ajax.send('sc_ajax_form', {
          success: SELF.response,
          error  : SELF.error,
          data   : SELF.data
        });

      };

      SELF.response = function (data) {
        SELF.finishProcessing(data.message, true);

        if (data.url) {
          window.location = data.url;
        }

      };

      SELF.error = function (data) {
        SELF.finishProcessing('Ooops! Something went wrong, please try again.', false);
        SELF.$form.prepend('<p class="rcp_error" data-alert>' + data.message + '</p>');
        console.log(data);
      };

      SELF.startProcessing = function () {
        SELF.processing = true;
        SELF.$form.find('.alert-box').remove();
        SELF.$button.val('Processing...').removeClass('secondary primary alert').addClass('processing secondary');
      };

      SELF.finishProcessing = function (value, success) {
        SELF.processing = false;
        SELF.$button.removeClass('processing secondary primary alert').val(value);

        if (success) {
          SELF.$button.addClass('primary');
        } else {
          SELF.$button.addClass('alert');
        }

      };

      SELF.init();
    };

    $(document).ready(function () {
      $('.ajax-form').each(function () {
        new scAjaxForm($(this));
      });

      var $restrictedContainer = $(document.getElementById('restricted-message'));
      if ($restrictedContainer.length) {
        var $loginContainer = $restrictedContainer.find('#login-body');
        var $registerContainer = $restrictedContainer.find('#start-now-body');

        $loginContainer.find('.switch').on('click', function (e) {
          $loginContainer.fadeOut(function () {
            $registerContainer.fadeIn();
          });
          return false;
        });

        $registerContainer.find('.switch').on('click', function (e) {
          $registerContainer.fadeOut(function () {
            $loginContainer.fadeIn();
          });
          return false;
        });
      }
    });

  }
)(jQuery);