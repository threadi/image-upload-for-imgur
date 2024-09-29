jQuery(document).ready(function($) {
  $( 'body.settings_page_imgur_image_upload_settings div:not(.wp-easy-setup__title) > h1' ).each( function () {
    let button = document.createElement( 'a' );
    button.className = 'review-hint-button page-title-action';
    button.href = imgurImageUploadJsVars.review_url;
    button.innerHTML = imgurImageUploadJsVars.title_rate_us;
    button.target = '_blank';
    this.after( button );
  } )
});
