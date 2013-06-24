<?php
/**
* copyright            : (C) 2001-2010 Advanced Internet Designs Inc.
* email                : forum@prohost.org
* $Id: buddy_list.php.t 4994 2010-09-02 17:33:29Z naudefj $
*
* This program is free software; you can redistribute it and/or modify it
* under the terms of the GNU General Public License as published by the
* Free Software Foundation; version 2 of the License.
**/

if (_uid === '_uid') {
		exit('Sorry, you can not access this page.');
	}function buddy_add($user_id, $bud_id)
{
	q('INSERT INTO fud26_buddy (bud_id, user_id) VALUES ('. $bud_id .', '. $user_id .')');
	return buddy_rebuild_cache($user_id);
}

function buddy_delete($user_id, $bud_id)
{
	q('DELETE FROM fud26_buddy WHERE user_id='. $user_id .' AND bud_id='. $bud_id);
	return buddy_rebuild_cache($user_id);
}

function buddy_rebuild_cache($uid)
{
	$arr = array();
	$q = uq('SELECT bud_id FROM fud26_buddy WHERE user_id='. $uid);
	while ($ent = db_rowarr($q)) {
		$arr[$ent[0]] = 1;
	}
	unset($q);

	if ($arr) {
		q('UPDATE fud26_users SET buddy_list='. _esc(serialize($arr)) .' WHERE id='. $uid);
		return $arr;
	}
	q('UPDATE fud26_users SET buddy_list=NULL WHERE id='. $uid);
}function check_return($returnto)
{
	if ($GLOBALS['FUD_OPT_2'] & 32768 && !empty($_SERVER['PATH_INFO'])) {
		if (!$returnto || !strncmp($returnto, '/er/', 4)) {
			header('Location: '.$GLOBALS['WWW_ROOT'].'index.php/i/'. _rsidl);
		} else if ($returnto[0] == '/') { /* Unusual situation, path_info & normal themes are active. */
			header('Location: '.$GLOBALS['WWW_ROOT'].'index.php'. $returnto);
		} else {
			header('Location: '.$GLOBALS['WWW_ROOT'].'index.php?'. $returnto);
		}
	} else if (!$returnto || !strncmp($returnto, 't=error', 7)) {
		header('Location: '.$GLOBALS['WWW_ROOT'].'index.php?t=index&'. _rsidl);
	} else if (strpos($returnto, 'S=') === false && $GLOBALS['FUD_OPT_1'] & 128) {
		header('Location: '.$GLOBALS['WWW_ROOT'].'index.php?'. $returnto .'&S='. s);
	} else {
		header('Location: '.$GLOBALS['WWW_ROOT'].'index.php?'. $returnto);
	}
	exit;
}function alt_var($key)
{
	if (!isset($GLOBALS['_ALTERNATOR_'][$key])) {
		$args = func_get_args(); unset($args[0]);
		$GLOBALS['_ALTERNATOR_'][$key] = array('p' => 2, 't' => func_num_args(), 'v' => $args);
		return $args[1];
	}
	$k =& $GLOBALS['_ALTERNATOR_'][$key];
	if ($k['p'] == $k['t']) {
		$k['p'] = 1;
	}
	return $k['v'][$k['p']++];
}

	if (!_uid) {
		std_error('login');
	}

	if (isset($_POST['add_login']) && is_string($_POST['add_login'])) {
		if (!($buddy_id = q_singleval('SELECT id FROM fud26_users WHERE alias='. _esc(char_fix(htmlspecialchars($_POST['add_login'])))))) {
			error_dialog('���������� �������� ������������', '������������, �������� �� ��������� �������� � ������ ���������, �� ������.');
		}
		if ($buddy_id == _uid) {
			error_dialog('����������', '�� �� ������ �������� ������ ���� � ������ ���������');
		}
		if (q_singleval('SELECT id FROM fud26_user_ignore WHERE user_id='. $buddy_id .' AND ignore_id='. _uid)) {
			error_dialog('����������', '�� ���� �������� �������������, ������� ��� ����������.');
		}

		if (!empty($usr->buddy_list)) {
			$usr->buddy_list = unserialize($usr->buddy_list);
		}

		if (!isset($usr->buddy_list[$buddy_id]) && !q_singleval('SELECT id FROM fud26_user_ignore WHERE user_id='. $buddy_id .' AND ignore_id='. _uid)) {
			$usr->buddy_list = buddy_add(_uid, $buddy_id);
		} else {
			error_dialog('����������', '���� ������������ ��� ��� ������ � ��� ������ ��������� �����');
		}
	}

	/* incomming from message display page (add buddy link) */
	if (isset($_GET['add']) && ($_GET['add'] = (int)$_GET['add'])) {
		if (!sq_check(0, $usr->sq)) {
			check_return($usr->returnto);
		}

		if (!empty($usr->buddy_list)) {
			$usr->buddy_list = unserialize($usr->buddy_list);
		}

		if (($buddy_id = q_singleval('SELECT id FROM fud26_users WHERE id='. $_GET['add'])) && !isset($usr->buddy_list[$buddy_id]) && _uid != $buddy_id && !q_singleval('SELECT id FROM fud26_user_ignore WHERE user_id='. $buddy_id .' AND ignore_id='. _uid)) {
			buddy_add(_uid, $buddy_id);
		}
		check_return($usr->returnto);
	}

	if (isset($_GET['del']) && ($_GET['del'] = (int)$_GET['del'])) {
		if (!sq_check(0, $usr->sq)) {
			check_return($usr->returnto);
		}

		buddy_delete(_uid, $_GET['del']);
		/* needed for external links to this form */
		if (isset($_GET['redr'])) {
			check_return($usr->returnto);
		}
	}

	ses_update_status($usr->sid, '�������� ������ ���������');

if (__fud_real_user__ && $FUD_OPT_1 & 1024) {	// PM_ENABLED
		$c = q_singleval('SELECT count(*) FROM fud26_pmsg WHERE duser_id='. _uid .' AND fldr=1 AND read_stamp=0');
		$private_msg = $c ? '<a href="index.php?t=pmsg&amp;'._rsid.'" class="UserControlPanel nw"><img src="theme/velopiter__old26/images/top_pm'.img_ext.'" alt="������ �����" /> � ��� ���� ������������� ��������� (<span class="GenText" style="color: #ff0000">('.$c.')</span>)</a>&nbsp;&nbsp;' : '<a href="index.php?t=pmsg&amp;'._rsid.'" class="UserControlPanel nw"><img src="theme/velopiter__old26/images/top_pm'.img_ext.'" alt="������ �����" /> ������ �����</a>&nbsp;&nbsp;';
	} else {
		$private_msg = '';
	}$tabs = '';
if (_uid) {
	$tablist = array(
'���������'=>'uc',
'���������'=>'register',
'��������'=>'subscribed',
'Закладки'=>'bookmarked',
'������������'=>'referals',
'������ ���������'=>'buddy_list',
'������ ������������'=>'ignore_list',
'�������� ��� ���������'=>'showposts'
);

	if (!($FUD_OPT_2 & 8192)) {
		unset($tablist['������������']);
	}

	if (isset($_POST['mod_id'])) {
		$mod_id_chk = $_POST['mod_id'];
	} else if (isset($_GET['mod_id'])) {
		$mod_id_chk = $_GET['mod_id'];
	} else {
		$mod_id_chk = null;
	}

	if (!$mod_id_chk) {
		if ($FUD_OPT_1 & 1024) {
			$tablist['������ �����'] = 'pmsg';
		}
		$pg = ($_GET['t'] == 'pmsg_view' || $_GET['t'] == 'ppost') ? 'pmsg' : $_GET['t'];

		foreach($tablist as $tab_name => $tab) {
			$tab_url = 'index.php?t='. $tab . (s ? '&amp;S='. s : '');
			if ($tab == 'referals') {
				if (!($FUD_OPT_2 & 8192)) {
					continue;
				}
				$tab_url .= '&amp;id='. _uid;
			} else if ($tab == 'showposts') {
				$tab_url .= '&amp;id='. _uid;
			}
			$tabs .= $pg == $tab ? '<td class="tabON"><div class="tabT"><a class="tabON" href="'.$tab_url.'">'.$tab_name.'</a></div></td>' : '<td class="tabI"><div class="tabT"><a href="'.$tab_url.'">'.$tab_name.'</a></div></td>';
		}

		$tabs = '<table cellspacing=1 cellpadding=0 class="tab">
<tr>'.$tabs.'</tr>
</table>';
	}
}

	$c = uq('SELECT b.bud_id, u.id, u.alias, u.join_date, u.birthday, '. q_bitand('u.users_opt', 32768) .', u.posted_msg_count, u.home_page, u.last_visit AS time_sec
		FROM fud26_buddy b INNER JOIN fud26_users u ON b.bud_id=u.id WHERE b.user_id='. _uid);

	$buddies = '';
	/* Result index
	 * 0 - bud_id	1 - user_id	2 - login	3 - join_date	4 - birthday	5 - users_opt	6 - msg_count
	 * 7 - home_page	8 - last_visit
	 */

	if (($r = db_rowarr($c))) {
		$dt = getdate(__request_timestamp__);
		$md = sprintf('%02d%02d', $dt['mon'], $dt['mday']);

		do {
			if ((!($r[5] & 32768) && $FUD_OPT_2 & 32) || $is_a) {
				$online_status = (($r[8] + $LOGEDIN_TIMEOUT * 60) > __request_timestamp__) ? '<img src="theme/velopiter__old26/images/online'.img_ext.'" title="'.$r[2].' ������ � �������" alt="'.$r[2].' ������ � �������" />' : '<img src="theme/velopiter__old26/images/offline'.img_ext.'" title="'.$r[2].' ������ �� � �������" alt="'.$r[2].' ������ �� � �������" />';
			} else {
				$online_status = '';
			}

			if ($r[4] && substr($r[4], 0, 4) == $md) {
				$age = $dt['year'] - (int)substr($r[4], 4);
				$bday_indicator = '<img src="blank.gif" alt="" width=10 height=1 /><img src="theme/velopiter__old26/images/bday.gif" alt="" />������� '.$r[2].' ����������� '.$age;
			} else {
				$bday_indicator = '';
			}

			$buddies .= '<tr class="'.alt_var('search_alt','RowStyleA','RowStyleB').'">
	<td class="ac">'.$online_status.'</td>
	<td class="GenText wa">'.($FUD_OPT_1 & 1024 ? '<a href="index.php?t=ppost&amp;'._rsid.'&amp;toi='.urlencode($r[0]).'">'.$r[2].'</a>' : '<a href="index.php?t=email&amp;toi='.$r[1].'&amp;'._rsid.'">'.$r[2].'</a>' ) .'&nbsp;<span class="SmallText">(<a href="index.php?t=buddy_list&amp;'._rsid.'&amp;del='.$r[0].'&amp;SQ='.$GLOBALS['sq'].'">�������</a>)</span>&nbsp;'.$bday_indicator.'</td>
	<td class="ac">'.$r[6].'</td>
	<td class="ac nw">'.strftime("%a, %d %B %Y %H:%M", $r[3]).'</td>
	<td class="GenText nw"><a href="index.php?t=usrinfo&amp;id='.$r[1].'&amp;'._rsid.'"><img src="theme/velopiter__old26/images/msg_about.gif" alt="" /></a>&nbsp;<a href="index.php?t=showposts&amp;'._rsid.'&amp;id='.$r[1].'"><img src="theme/velopiter__old26/images/show_posts.gif" alt="" /></a> '.($r[7] ? '<a href="'.$r[7].'" target="_blank"><img src="theme/velopiter__old26/images/homepage.gif" alt="" /></a>' : '' ) .'</td>
</tr>';
		} while (($r = db_rowarr($c)));
		$buddies = '<table cellspacing="1" cellpadding="2" class="ContentTable">
<tr><th>������</th><th>������ ���������</th><th class="nw ac">���������</th><th class="ac nw">���� �����������</th><th class="ac nw">��������</th></tr>
'.$buddies.'
</table>';
	}
	unset($c);

if ($FUD_OPT_2 & 2 || $is_a) {	// PUBLIC_STATS is enabled or Admin user.
	$page_gen_time = number_format(microtime(true) - __request_timestamp_exact__, 5);
	$page_stats = $FUD_OPT_2 & 2 ? '<br /><div class="SmallText al">�����, ����������� �� ��������� ��������: '.$page_gen_time.' ���.</div>' : '<br /><div class="SmallText al">�����, ����������� �� ��������� ��������: '.$page_gen_time.' ���.</div>';
} else {
	$page_stats = '';
}
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/1999/REC-html401-19991224/loose.dtd">
<html>
<head>
<title><?php echo $GLOBALS['FORUM_TITLE'].$TITLE_EXTRA; ?></title>
<meta http-equiv="Content-Type" content="text/html; charset=koi8-r" />
<BASE HREF="<?php echo $GLOBALS['WWW_ROOT']; ?>">
<link rel="StyleSheet" href="theme/velopiter__old26/forum.css" type="text/css" media="screen" title="Default FUDforum Theme">
</head>
<body>
<script language="javascript" src="lib.js" type="text/javascript"></script>
<link rel="StyleSheet" href="theme/velopiter__old26/forum.css" type="text/css" media="screen" title="Default FUDforum Theme">
</head>
<body>
<script language="javascript" src="lib.js" type="text/javascript"></script>

<table class="wa" border="0" cellspacing="3" cellpadding="5">
<tr ><td bgcolor=#6699cc>
<table width=100% cellspacing=0 cellpadding=0 border=0>
<tr height=100><td width=320>
<a href="/"><img src="/forum/logo.gif" width=312 height=79 border=0 alt="���������"></a>
</td>
<td align=right valign=bottom>
<table cellspacing=0 height=100% cellpadding=5>
<tr valign=top><td>
<? include("../newstape_inc.php"); ?>
</td></td></table>
</td>
<td align=right width=350>
<!-- banners start -->
<div align=right>
<table cellspacing=0 cellpadding=5>
<tr valign=top>

<td>

<a href="http://velodrive.ru/" target="_blank"><img src="http://velopiter.spb.ru/vdr.gif" border="0" width="200" height="100" alt="���������� ���������"></a>

</td>

<td><a href="http://pk-99.ru/" target="_blank">
  <img border="0" src="http://velopiter.spb.ru/pk.gif" alt="���-99"
width="100" height="100"></a></td>


<td width="100" height=100 align=right>
<a href="http://www.alienbike.ru"></a><object classid="clsid:d27cdb6e-ae6d-11cf-96b8-444553540000" codebase="http://fpdownload.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=6,0,0,0" width="100" height="100" id="090406_5" align="right">
<param name="allowScriptAccess" value="sameDomain" />
<param name="movie" value="http://velopiter.spb.ru/090406_5.swf" /><param name="loop" value="false" /><param name="menu" value="false" /><param name="quality" value="high" /><param name="bgcolor" value="#000000" /><embed src="http://velopiter.spb.ru/090406_5.swf" loop="false" menu="false" quality="high" bgcolor="#000000" width="100" height="100" name="../090406_5" align="middle" allowScriptAccess="sameDomain" type="application/x-shockwave-flash" pluginspage="http://www.macromedia.com/go/getflashplayer" />
</object></td>


</tr></table></div>
<!--- banners end-->

</td></tr></table>
</td>
</tr>

<tr><td class="ForumBackground">
<div class="UserControlPanel">

<a class="UserControlPanel nw" 
href="index.php?t=msg&th=102972&start=0&rid=691">
<img border=0 src="images/message_icons/icon4.gif">�������</a> 
 
<?php echo $private_msg; ?> <?php echo (($FUD_OPT_1 & 8388608 || (_uid && $FUD_OPT_1 & 4194304) || $usr->users_opt & 1048576) ? '<a class="UserControlPanel nw" href="index.php?t=finduser&amp;btn_submit=Find&amp;'._rsid.'"><img src="theme/velopiter__old26/images/top_members'.img_ext.'" alt="������������" /> ������������</a>&nbsp;&nbsp;' : ''); ?> <?php echo ($FUD_OPT_1 & 16777216 ? '<a class="UserControlPanel nw" href="index.php?t=search&amp;'._rsid.'"><img src="theme/velopiter__old26/images/top_search'.img_ext.'" alt="�����" /> �����</a>&nbsp;&nbsp;' : ''); ?> <a class="UserControlPanel nw" accesskey="h" href="index.php?t=help_index&amp;<?php echo _rsid; ?>"><img src="theme/velopiter__old26/images/top_help<?php echo img_ext; ?>" alt="F.A.Q." /> F.A.Q.</a> <?php echo (__fud_real_user__ ? '&nbsp;&nbsp;<a class="UserControlPanel nw" href="index.php?t=uc&amp;'._rsid.'"><img src="theme/velopiter__old26/images/top_profile'.img_ext.'" title="������� ��� �������� � ������ ����������" alt="���������" /> ���������</a>' : '&nbsp;&nbsp;<a class="UserControlPanel nw" href="index.php?t=register&amp;'._rsid.'"><img src="theme/velopiter__old26/images/top_register'.img_ext.'" alt="�����������" /> �����������</a>'); ?> <?php echo (__fud_real_user__ ? '&nbsp;&nbsp;<a class="UserControlPanel nw" href="index.php?t=login&amp;'._rsid.'&amp;logout=1&amp;SQ='.$GLOBALS['sq'].'"><img src="theme/velopiter__old26/images/top_logout'.img_ext.'" alt="�����" /> ����� [ '.$usr->alias.' ]</a>' : '&nbsp;&nbsp;<a class="UserControlPanel nw" href="index.php?t=login&amp;'._rsid.'"><img src="theme/velopiter__old26/images/top_login'.img_ext.'" alt="����" /> ����</a>'); ?>&nbsp;&nbsp; <a class="UserControlPanel nw" href="index.php?t=index&amp;<?php echo _rsid; ?>"><img src="theme/velopiter__old26/images/top_home<?php echo img_ext; ?>" alt="������" /> ������</a> <?php echo ($is_a ? '&nbsp;&nbsp;<a class="UserControlPanel nw" href="adm/admglobal.php?S='.s.'&amp;SQ='.$GLOBALS['sq'].'"><img src="theme/velopiter__old26/images/top_admin'.img_ext.'" alt="���������������� �����" /> ���������������� �����</a>' : ''); ?></div>
<?php echo $tabs; ?>
<?php echo $buddies; ?>
<br /><br />
<form name="buddy_add" action="index.php?t=buddy_list" method="post"><?php echo _hs; ?><div class="ctb">
<table cellspacing="1" cellpadding="2" class="MiniTable">
<tr><th nowrap>�������� � ������ ���������</th></tr>
<tr class="RowStyleA">
<td class="GenText nw Smalltext">������� ��� ������������, �������� �� ������ ��������.<?php echo (($FUD_OPT_1 & 8388608 || (_uid && $FUD_OPT_1 & 4194304)) ? '<br>��� ����������� ����������� <a href="javascript://" onClick="javascript: window_open(\&#39;'.$GLOBALS['WWW_ROOT'].'index.php?t=pmuserloc&amp;'._rsid.'&amp;js_redr=buddy_add.add_login&amp;overwrite=1\&#39;, \&#39;user_list\&#39;, 325,250);">������</a> ��� ���������� ������� ������������.' : ''); ?><p>
<input type="text" tabindex="1" name="add_login" value="" maxlength=100 size=25> <input tabindex="2" type="submit" class="button" name="submit" value="��������"></td></tr>
</table></div></form>
<script language="javascript" type="text/javascript">
<!--
document.buddy_add.add_login.focus();
//-->
</script>
<br /><div class="ac"><span class="curtime"><b>������� �����:</b> <?php echo strftime("%a %b %e %H:%M:%S %Z %Y", __request_timestamp__); ?></span></div>
<?php echo $page_stats; ?>
</td></tr></table><div class="ForumBackground ac foot">
<b>.::</b> <a href="mailto:<?php echo $GLOBALS['ADMIN_EMAIL']; ?>">�������� �����</a> <b>::</b> <a href="index.php?t=index&amp;<?php echo _rsid; ?>">������</a> <b>::.</b>
<p>
<span class="SmallText">Powered by: FUDforum <?php echo $GLOBALS['FORUM_VERSION']; ?>.<br />Copyright &copy;2001-2006 <a href="http://fudforum.org/">FUD Forum Bulletin Board Software</a></span>

</div>
<div align=right>
<span class="SmallText">

<!-- SpyLOG v2 f:0211 -->
<script language="javascript">
u="u166.09.spylog.com";d=document;nv=navigator;na=nv.appName;p=0;j="N";
d.cookie="b=b";c=0;bv=Math.round(parseFloat(nv.appVersion)*100);
if (d.cookie) c=1;n=(na.substring(0,2)=="Mi")?0:1;rn=Math.random();
z="p="+p+"&rn="+rn+"&c="+c;if (self!=top) {fr=1;} else {fr=0;} sl="1.0";
</script>
<script language="javascript1.1">
pl="";sl="1.1";j = (navigator.javaEnabled()?"Y":"N");</script>
<script language=javascript1.2>sl="1.2";s=screen;px=(n==0)?s.colorDepth:s.pixelDepth;
z+="&wh="+s.width+'x'+s.height+"&px="+px;</script>
<script language=javascript1.3>sl="1.3"</script>
<script language="javascript">y="";y+="<a href='http://"+u+"/cnt?f=3&p="+p+"&rn="+rn+"' target=_blank>";
y+="<img src='http://"+u+"/cnt?"+z+"&j="+j+"&sl="+sl+ "&r="+escape(d.referrer)+"&fr="+fr+"&pg="+escape(window.location.href); y+="' border=0 width=88 height=31 alt='SpyLOG'>"; y+="</a>";
d.write(y);if(!n) { d.write("<"+"!--"); }
//-->
</script>
<noscript><a href="http://u166.09.spylog.com/cnt?f=3&p=0" target=_blank><img src="http://u166.09.spylog.com/cnt?p=0" alt='SpyLOG' border='0' width=88 height=31></a></noscript>
<script language="javascript1.2"><!-- if(!n){ d.write("--"+">"); }
//--></script>
<!-- SpyLOG -->

 <!-- Yandex.Metrika -->
<script src="http://mc.yandex.ru/metrika/watch.js" type="text/javascript"></script>
<script type="text/javascript">
try { var yaCounter147212 = new Ya.Metrika(147212); } catch(e){}
</script>
<noscript><img src="http://mc.yandex.ru/watch/147212" style="position:absolute" alt="" /></noscript>
<!-- /Yandex.Metrika -->

<a href="http://www.vvv.ru/cnt.php3?id=99" target=_top><img
src="http://cnt.vvv.ru/cgi-bin/cnt?id=99" width=88 height=31 border=0
alt="������������� ������ VVV.RU"></a>
</span>

</div>
</body></html>
