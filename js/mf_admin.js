(function($) {
  $(document).ready(function() {
/******************************** ADS STUFF ********************************/
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

    //Show/Hide ad aread when checkbox clicked
    $('.mf_ad_enable').click(function() {
      var who = $(this).attr('data-value');
      $('div#' + who).slideToggle('fast');
    });

/******************************** SORTABLE CATEGORIES ********************************/
    //Make Categories Sortable
    $('#sortable-categories').sortable({
      placeholder: "ui-state-highlight"
    });

    $('a#mf_add_new_category').click(function() {
      $('ol#sortable-categories').append(get_new_category_row());

      return false;
    });

    $('body').on('click', '.mf_remove_category', function() {
      //TODO NEED TO I18N THIS SHIZZ!!!
      var answer = confirm('WARNING: Deleting this Category will also PERMANENTLY DELETE ALL Forums, Topics, and Replies associated with it!!! Are you sure you want to delete this Category???');

      if(answer) {
        $(this).parent().fadeOut(500, function() {
          $(this).remove();
        });
      }

      return false;
    });

    function get_new_category_row() {
      var random_id = Math.floor(Math.random() * (1000000 - 100000)) + 100000;

      //TODO NEED TO I18N THIS SHIZZ!!!
      return '<li class="ui-state-default">\
                <input type="hidden" name="mf_category_id[]" value="new" />\
                &nbsp;&nbsp;\
                <label for="category-name-' + random_id + '">Category Name:</label>\
                <input type="text" name="category_name[]" id="category-name-' + random_id + '" value="" />\
                &nbsp;&nbsp;\
                <label for="category-description-' + random_id + '">Description:</label>\
                <input type="text" name="category_description[]" id="category-description-' + random_id + '" value="" size="50" />\
                <a href="#" class="mf_remove_category" title="Remove this Category">\
                  <img src="http://modforum.com/wp-content/plugins/mingle-forum/images/remove.png" width="24" />\
                </a>\
              </li>';
    }

/******************************** SORTABLE FORUMS ********************************/
    //Make Forums Sortable
    $('.sortable_forums').each(function() {
      $(this).sortable({
        placeholder: "ui-state-highlight"
      });
    });

    //Add New Forum Button
    $('.mf_add_new_forum').click(function() {
      var category_id = $(this).attr('data-value');

      $('ol#sortable-forums-' + category_id).append(get_new_forum_row(category_id));

      return false;
    });

    function get_new_forum_row(category_id) {
      var random_id = Math.floor(Math.random() * (1000000 - 100000)) + 100000;

      //TODO NEED TO I18N THIS SHIZZ!!!
      return '<li class="ui-state-default">\
                <input type="hidden" name="mf_forum_id[' + category_id + '][]" value="new" />\
                &nbsp;&nbsp;\
                <label for="forum-name-' + random_id + '">Forum Name:</label>\
                <input type="text" name="forum_name[' + category_id + '][]" id="forum-name-' + random_id + '" value="" />\
                &nbsp;&nbsp;\
                <label for="forum-description-' + random_id + '">Description:</label>\
                <input type="text" name="forum_description[' + category_id + '][]" id="forum-description-' + random_id + '" value="" size="50" />\
                <a href="#" class="mf_remove_forum" title="Remove this Forum">\
                  <img src="http://modforum.com/wp-content/plugins/mingle-forum/images/remove.png" width="24" />\
                </a>\
              </li>';
    }

    //Delete a Forum
    $('body').on('click', '.mf_remove_forum', function() {
      //TODO NEED TO I18N THIS SHIZZ!!!
      var answer = confirm('WARNING: Deleting this Forum will also PERMANENTLY DELETE ALL Topics, and Replies associated with it!!! Are you sure you want to delete this Forum???');

      if(answer) {
        $(this).parent().fadeOut(500, function() {
          $(this).remove();
        });
      }

      return false;
    });

  });
})(jQuery);
