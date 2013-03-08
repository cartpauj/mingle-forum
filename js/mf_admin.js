(function($) {
  $(document).ready(function() {
    //Start the accordion
    $('div#mf-options-accordion').accordion({
      heightStyle: "content"
    });
    
    //Show/Hide ads areas
    $('.mf_ad_enable').each(function() {
      if($(this).is(":checked")) {
        var who = $(this).attr('data-value');
        $('div#' + who).show();
      } else {
        var who = $(this).attr('data-value');
        $('div#' + who).hide();
      }
    });
    $('.mf_ad_enable').click(function() {
      var who = $(this).attr('data-value');
      $('div#' + who).slideToggle('fast');
    });
    
  });
})(jQuery);
