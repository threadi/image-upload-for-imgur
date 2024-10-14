jQuery(document).ready(function($) {
  $( 'body.settings_page_iufi_settings div:not(.wp-easy-setup__title) > h1' ).each( function () {
    let button = document.createElement( 'a' );
    button.className = 'review-hint-button page-title-action';
    button.href = iufiJsVars.review_url;
    button.innerHTML = iufiJsVars.title_rate_us;
    button.target = '_blank';
    this.after( button );
  } )
});
