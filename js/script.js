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
		myarea.value =  myarea.value.substring(0,start) + rep + myarea.value.substring(end,len);
		myarea.scrollTop = scrollTop;
		myarea.scrollLeft = scrollLeft;
	}
}

var current_header = false;

function shrinkHeader(mode){

	var val = "";
	document.getElementById("upshrinkHeader").style.display = mode ? "none" : "";
	document.getElementById("upshrinkHeader2").style.display = mode ? "none" : "";
	
	//document.getElementById("upshrink").src = skinurl+"/images" + (mode ? "/upshrink2.gif" : "/upshrink.gif");

	if(mode == true){
		val = "yes";
	}
	if(mode == false){
		val = "no";
	}
	
	setCookie("wpf_header_state", val, 0 ); 

	current_header = mode;
}


function setCookie(name, value, expires, path, domain, secure) { 
	document.cookie= name + "=" + escape(value) + 
	(expires? "; expires=" + expires.toGMTString(): "") + 
	(path? "; path=" + path: "") + 
	(domain? "; domain=" + domain: "") + 
	(secure? "; secure": ""); 
}

function fold(){
	
	var lol = getCookie("wpf_header_state");
	if(lol == "yes")
		shrinkHeader(true);
	if(lol == "no")
		shrinkHeader(false);
}

function getCookie(c_name)
{
if (document.cookie.length>0)
  {
  c_start=document.cookie.indexOf(c_name + "=");
  if (c_start!=-1)
    { 
    c_start=c_start + c_name.length+1; 
    c_end=document.cookie.indexOf(";",c_start);
    if (c_end==-1) c_end=document.cookie.length;
    return unescape(document.cookie.substring(c_start,c_end));
    } 
  }
return "";
}


function selectBoards(ids){
	var toggle = true;

	for (i = 0; i < ids.length; i++)
		toggle = toggle & document.forms.wpf_searchform["forum" + ids[i]].checked;

	for (i = 0; i < ids.length; i++)
		document.forms.wpf_searchform["forum" + ids[i]].checked = !toggle;
}

function collapseExpandGroups(group, mode){
	
}

function expandCollapseBoards(){
	var current = document.getElementById("searchBoardsExpand").style.display != "none";
	document.getElementById("search_coll").src = skinurl+"/images" + (current ? "/upshrink2.gif" : "/upshrink.gif");
	document.getElementById("searchBoardsExpand").style.display = current ? "none" : "";
}

// Invert all checkboxes at once by clicking a single checkbox.
function invertAll(headerfield, checkform, mask)
{
	for (var i = 0; i < checkform.length; i++)
	{
		if (typeof(checkform[i].name) == "undefined" || (typeof(mask) != "undefined" && checkform[i].name.substr(0, mask.length) != mask))
			continue;

		if (!checkform[i].disabled)
			checkform[i].checked = headerfield.checked;
	}
}

function uncheckglobal(headerfield, checkform){
	checkform.mod_global.checked = false;
}