<?php
/**
* copyright            : (C) 2001-2010 Advanced Internet Designs Inc.
* email                : forum@prohost.org
* $Id: mklist.php.t 4898 2010-01-25 21:30:30Z naudefj $
*
* This program is free software; you can redistribute it and/or modify it
* under the terms of the GNU General Public License as published by the
* Free Software Foundation; version 2 of the License.
**/

	define('plain_form', 1);

if (_uid === '_uid') {
		exit('Sorry, you can not access this page.');
	}



	if (!empty($_GET['tp']) && $_GET['tp'] == 'OL:1') {
		$def_list_type = '1';
	} else {
		$def_list_type = 'square';
	}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr" lang="ru" xml:lang="ru">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title><?php echo $GLOBALS['FORUM_TITLE'].$TITLE_EXTRA; ?></title>
<base href="<?php echo $GLOBALS['WWW_ROOT']; ?>" />
<script type="text/javascript" src="js/jquery.js"></script>
<script type="text/javascript" src="js/lib.js"></script>
<link rel="stylesheet" href="theme/velopiter/forum.css" type="text/css" />
</head>
<body>
<table class="wa" border="0" cellspacing="3" cellpadding="5"><tr><td class="ForumBackground">
<script type="text/javascript">
/* <![CDATA[ */ 
var opt_count = 0;
function changeListType(type)
{
	document.getElementById('ll').setAttribute('type', type);
	if (type == '1') {
		document.getElementById('ll').setAttribute('style', 'list-style-type: decimal');
	} else if (type == 'a') {
		document.getElementById('ll').setAttribute('style', 'list-style-type: lower-alpha');
	} else {
		document.getElementById('ll').setAttribute('style', 'list-style-type: '+type);
	}
}
function addOption()
{
	var li;
	var dl;

	if (document.forms['list'].opt.value.length < 1) {
		return;
	}

	if (!document.all || OPERA) {
		li = document.createElement('li');
		li.setAttribute('id', 'opt_'+opt_count);
	} else {
		li = document.createElement('<li id="opt_'+opt_count+'"></li>');
	}
	li.appendChild(document.createTextNode(document.forms['list'].opt.value));

	if (!document.all || OPERA) {
		dl = document.createElement('a');
		dl.setAttribute('href', 'javascript://');
		dl.setAttribute('onclick', 'delOption(\'opt_'+opt_count+'\')');
	} else {
		dl = document.createElement('<a href="javascript://" onclick="delOption(\'opt_'+opt_count+'\')"></a>');
	}
	dl.appendChild(document.createTextNode('Удалить'));
	
	li.appendChild(document.createTextNode(' [ '));
	li.appendChild(dl);
	li.appendChild(document.createTextNode(' ] '));

	document.getElementById('ll').appendChild(li);
	document.forms['list'].opt.value = '';
	document.forms['list'].opt.focus();
	opt_count++;
}

function delOption(id)
{
	var p = document.getElementById(id).parentNode;
	p.removeChild(document.getElementById(id));
}

function updatePostForm()
{
	var t = window.opener.document.getElementById('txtb');
	var txt = '\n[LIST TYPE='+document.getElementById('ll').getAttribute('type')+']\n';
	for (var i = 0; i < opt_count; i++) {
		var val = document.getElementById('opt_'+i);
		if (val) {
			txt += '[*] '+val.firstChild.nodeValue+'\n';
		}
	}
	txt += '[/LIST]\n';

	if (window.opener.document.selection) { // IE
		window.opener.document.selection.createRange();	
		if (t.createTextRange && t.caretPos) {
			var caretPos = t.caretPos;
			caretPos.text = txt + caretPos.text;
		} else {
			t.value += txt;
		}
	} else {
		var n = t.value.substring(0, t.selectionStart) + txt + t.value.substring(t.selectionStart, t.value.length);
		t.value = n;
	}

	t.focus();
	window.close();
}
/* ]]> */
</script>
<form id="list">
<table cellspacing="2" cellpadding="0" width="99%" class="dashed">
<tr>
	<td>Тип:</td>
	<td>
		<select name="tp" onchange="changeListType(this.options[this.selectedIndex].value);">
			<option value="1">Нумерованный - цифры</option>
			<option value="a">Нумерованный - буквы</option>
			<option value="square"<?php echo ($def_list_type == 'square' ? ' selected="selected"' : ''); ?>>Бюллетень - квадратики</option>
			<option value="disc">Бюллетень - диски</option>
			<option value="circle">Бюллетень - кружки</option>
		</select>
	</td>
</tr>
<tr>
	<td>Элемент:</td>
	<td class="nw">
		<input tabindex="1" type="text" spellcheck="true" name="opt" size="20" />
		<input tabindex="2" type="button" class="button" name="btn_submit" onclick="addOption();" value="Добавить" />
	</td>
</tr>
<tr>
	<td colspan="2" id="example"><ul id="ll"></ul>
<script type="text/javascript">
/* <![CDATA[ */ 
	changeListType('<?php echo $def_list_type; ?>');
/* ]]> */
</script>	
	</td>
</tr>
<tr>
<td colspan="2" class="ar">
<input type="button" class="button" name="go" value="Применить" onclick="updatePostForm();" />
<input type="button" class="button" name="close" value="Закрыть" onclick="window.close();" />
</td></tr>
</table>
</form>
</td></tr></table></body></html>