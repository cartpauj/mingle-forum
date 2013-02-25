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
// Show/Hide  input value on focus - Search
function Search_Text(ele)  
{  
    if(ele.value == ele.defaultValue)  
    {  
        ele.value = ''; 
        ele.className = 'inputShow';  
  
    }  
    else if (ele.value == '')  
    {  
        ele.value = ele.defaultValue;  
        ele.className = 'inputDefault';
  
    }  
}
// Show/Hide  input value on focus - username, passworld
function up_Text(ele)  
{  
    if(ele.value == ele.defaultValue)  
    {  
        ele.value = ''; 
        ele.className = 'inputShow';  
  
    }  
    else if (ele.value == '')  
    {  
        ele.value = ele.defaultValue;  
        ele.className = 'wpf-input';
  
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
    //Show/Hide groups
    $('a.wpf_click_me').click(function() {
      var id = $(this).attr('data-value');

      if ($(this).hasClass('show-hide-hidden')) {
        $('tr.group-shrink-' + id).fadeIn(800);
        $('a#shown-' + id).show();
        $(this).hide();
      } else {
        $('tr.group-shrink-' + id).fadeOut(200);
        $('a#hidden-' + id).show();
        $(this).hide();
      }

      return false;
    });
  });
})(jQuery);
