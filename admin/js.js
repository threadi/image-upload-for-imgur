jQuery(document).ready(function($) {
  // show rating hint.
  $( 'body.settings_page_iufi_settings div:not(.easy-setup-for-wordpress-title) > h1' ).each( function () {
    let button = document.createElement( 'a' );
    button.className = 'review-hint-button page-title-action';
    button.href = iufiJsVars.review_url;
    button.innerHTML = iufiJsVars.title_rate_us;
    button.target = '_blank';
    this.after( button );
  } )

  // save to hide transient-messages via ajax-request.
  $('.image-upload-for-imgur-transient[data-dismissible] button.notice-dismiss').on('click',
    function (event) {
      event.preventDefault();
      let $this = $(this);
      let attr_value, option_name, dismissible_length, data;
      attr_value = $this.closest('div[data-dismissible]').attr('data-dismissible').split('-');

      // Remove the dismissible length from the attribute value and rejoin the array.
      dismissible_length = attr_value.pop();
      option_name = attr_value.join('-');
      data = {
        'action': 'iufi_dismiss_admin_notice',
        'option_name': option_name,
        'dismissible_length': dismissible_length,
        'nonce': iufiJsVars.dismiss_nonce
      };

      // run ajax request to save this setting
      $.post(iufiJsVars.ajax_url, data);
      $this.closest('div[data-dismissible]').hide('slow');
    }
  );
});
