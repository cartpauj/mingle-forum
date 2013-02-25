// Surrounds the selected text with text1 and text2.
function surroundText(tag1, tag2, myarea)
{
  if (document.selection) //IE
  {
    myarea.focus();
    var sel = document.selection.createRange();
    sel.text = tag1 + sel.text + tag2;
  }
  else //Other Browsers
  {
    var len = myarea.value.length;
    var start = myarea.selectionStart;
    var end = myarea.selectionEnd;
    var scrollTop = myarea.scrollTop;
    var scrollLeft = myarea.scrollLeft;
    var sel = myarea.value.substring(start, end);
    var rep = tag1 + sel + tag2;
    myarea.value = myarea.value.substring(0, start) + rep + myarea.value.substring(end, len);
    myarea.scrollTop = scrollTop;
    myarea.scrollLeft = scrollLeft;
  }
}

// Invert all checkboxes at once by clicking a single checkbox.
function invertAll(headerfield, checkform, mask)
{
  for (var i = 0; i < checkform.length; i++)
  {
    if (typeof(checkform[i].name) === "undefined" || (typeof(mask) !== "undefined" && checkform[i].name.substr(0, mask.length) !== mask))
      continue;

    if (!checkform[i].disabled)
      checkform[i].checked = headerfield.checked;
  }
}

function uncheckglobal(headerfield, checkform) {
  checkform.mod_global.checked = false;
}

function wpf_confirm() {
  var answer = confirm('Are you sure you want to remove this?');
  if (!answer)
    return false;
  else
    return true;
}

(function($) {
  $(document).ready(function() {
    //Cookie Array Handler
    var cookieList = function(cookieName) {
      var cookie = $.cookie(cookieName);
      var items = cookie ? cookie.split(/,/) : new Array();

      return {
        "add": function(val) {
            items.push(val);
            $.cookie(cookieName, items.join(','));
        },
        "remove": function (val) { 
            indx = items.indexOf(val); 
            if(indx!=-1) items.splice(indx, 1); 
            $.cookie(cookieName, items.join(','));
        },
        "clear": function() {
            items = null;
            $.cookie(cookieName, null);
        },
        "items": function() {
            return items;
        }
      }
    }

    //Show/Hide groups
    var groups_cookie = new cookieList('mf_groups');
    //Loop through the cookie and hide categories that have been hidden before
    groups_cookie.items().map(function(id) {
      $('tr.group-shrink-' + id).hide();
      $('a#shown-' + id).hide();
      $('a#hidden-' + id).show();
    });

    $('a.wpf_click_me').click(function() {
      var id = $(this).attr('data-value');

      if ($(this).hasClass('show-hide-hidden')) {
        $('tr.group-shrink-' + id).fadeIn(800);
        $('a#shown-' + id).show();
        groups_cookie.remove(id);
        $(this).hide();
      } else {
        $('tr.group-shrink-' + id).fadeOut(200);
        $('a#hidden-' + id).show();
        groups_cookie.add(id);
        $(this).hide();
      }

      return false;
    });

    //Add a placeholder to the input boxes
    //Username
    //Load initial text
    if ($('.mf_uname').val() == '')
      $('.mf_uname').val(MFl10n.uname);
    //Empty when clicked
    $('.mf_uname').focus(function() {
      if ($(this).val() == MFl10n.uname) {
        $(this).val('');
      }
    });
    //Fill again if empty on blur
    $('.mf_uname').blur(function() {
      if ($(this).val() == '') {
        $(this).val(MFl10n.uname);
      }
    });
    //Password
    //Load initial text
    if ($('.mf_pwd').val() == '')
      $('.mf_pwd').val('********');
    //Empty when clicked
    $('.mf_pwd').focus(function() {
      if ($(this).val() == '********') {
        $(this).val('');
      }
    });
    //Fill again if empty on blur
    $('.mf_pwd').blur(function() {
      if ($(this).val() == '') {
        $(this).val('********');
      }
    });
    //Search
    //Load initial text
    if ($('.mf_search').val() == '')
      $('.mf_search').val(MFl10n.search);
    //Empty when clicked
    $('.mf_search').focus(function() {
      if ($(this).val() == MFl10n.search) {
        $(this).val('');
      }
    });
    //Fill again if empty on blur
    $('.mf_search').blur(function() {
      if ($(this).val() == '') {
        $(this).val(MFl10n.search);
      }
    });
  });
})(jQuery);
