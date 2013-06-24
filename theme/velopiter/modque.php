<?php
/**
* copyright            : (C) 2001-2010 Advanced Internet Designs Inc.
* email                : forum@prohost.org
* $Id: modque.php.t 4994 2010-09-02 17:33:29Z naudefj $
*
* This program is free software; you can redistribute it and/or modify it
* under the terms of the GNU General Public License as published by the
* Free Software Foundation; version 2 of the License.
**/

if (_uid === '_uid') {
		exit('Sorry, you can not access this page.');
	}/* Handle poll votes if any are present. */
function register_vote(&$options, $poll_id, $opt_id, $mid)
{
	/* Invalid option or previously voted. */
	if (!isset($options[$opt_id]) || q_singleval('SELECT id FROM fud26_poll_opt_track WHERE poll_id='. $poll_id .' AND user_id='. _uid)) {
		return;
	}

	if (db_li('INSERT INTO fud26_poll_opt_track(poll_id, user_id, poll_opt) VALUES('. $poll_id .', '. _uid .', '. $opt_id .')', $a)) {
		q('UPDATE fud26_poll_opt SET count=count+1 WHERE id='. $opt_id);
		q('UPDATE fud26_poll SET total_votes=total_votes+1 WHERE id='. $poll_id);
		$options[$opt_id][1] += 1;
		q('UPDATE fud26_msg SET poll_cache='. _esc(serialize($options)) .' WHERE id='. $mid);
	}

	return 1;
}

$GLOBALS['__FMDSP__'] = array();

/* Needed for message threshold & reveling messages. */
if (isset($_GET['rev'])) {
	$_GET['rev'] = htmlspecialchars((string)$_GET['rev']);
	foreach (explode(':', $_GET['rev']) as $v) {
		$GLOBALS['__FMDSP__'][(int)$v] = 1;
	}
	if ($GLOBALS['FUD_OPT_2'] & 32768) {
		define('reveal_lnk', '/'. $_GET['rev']);
	} else {
		define('reveal_lnk', '&amp;rev='. $_GET['rev']);
	}
} else {
	define('reveal_lnk', '');
}

/* Initialize buddy & ignore list for registered users. */
if (_uid) {
	if ($usr->buddy_list) {
		$usr->buddy_list = unserialize($usr->buddy_list);
	}
	if ($usr->ignore_list) {
		$usr->ignore_list = unserialize($usr->ignore_list);
		if (isset($usr->ignore_list[1])) {
			$usr->ignore_list[0] =& $usr->ignore_list[1];
		}
	}

	/* Handle temporarily un-hidden users. */
	if (isset($_GET['reveal'])) {
		$_GET['reveal'] = htmlspecialchars((string)$_GET['reveal']);
		foreach(explode(':', $_GET['reveal']) as $v) {
			$v = (int) $v;
			if (isset($usr->ignore_list[$v])) {
				$usr->ignore_list[$v] = 0;
			}
		}
		if ($GLOBALS['FUD_OPT_2'] & 32768) {
			define('unignore_tmp', '/'. $_GET['reveal']);
		} else {
			define('unignore_tmp', '&amp;reveal='. $_GET['reveal']);
		}
	} else {
		define('unignore_tmp', '');
	}
} else {
	define('unignore_tmp', '');
	if (isset($_GET['reveal'])) {
		unset($_GET['reveal']);
	}
}

if ($GLOBALS['FUD_OPT_2'] & 2048) {
	$GLOBALS['affero_domain'] = parse_url($WWW_ROOT);
	$GLOBALS['affero_domain'] = $GLOBALS['affero_domain']['host'];
}

$_SERVER['QUERY_STRING_ENC'] = htmlspecialchars($_SERVER['QUERY_STRING']);

function make_tmp_unignore_lnk($id)
{
	if ($GLOBALS['FUD_OPT_2'] & 32768 && strpos($_SERVER['QUERY_STRING_ENC'], '?') === false) {
		$_SERVER['QUERY_STRING_ENC'] .= '?1=1';
	}

	if (!isset($_GET['reveal'])) {
		return $_SERVER['QUERY_STRING_ENC'] .'&amp;reveal='. $id;
	} else {
		return str_replace('&amp;reveal='. $_GET['reveal'], unignore_tmp .':'. $id, $_SERVER['QUERY_STRING_ENC']);
	}
}

function make_reveal_link($id)
{
	if ($GLOBALS['FUD_OPT_2'] & 32768 && strpos($_SERVER['QUERY_STRING_ENC'], '?') === false) {
		$_SERVER['QUERY_STRING_ENC'] .= '?1=1';
	}

	if (empty($GLOBALS['__FMDSP__'])) {
		return $_SERVER['QUERY_STRING_ENC'] .'&amp;rev='. $id;
	} else {
		return str_replace('&amp;rev='. $_GET['rev'], reveal_lnk .':'. $id, $_SERVER['QUERY_STRING_ENC']);
	}
}

/* Draws a message, needs a message object, user object, permissions array,
 * flag indicating wether or not to show controls and a variable indicating
 * the number of the current message (needed for cross message pager)
 * last argument can be anything, allowing forms to specify various vars they
 * need to.
 */
function tmpl_drawmsg($obj, $usr, $perms, $hide_controls, &$m_num, $misc)
{
	$o1 =& $GLOBALS['FUD_OPT_1'];
	$o2 =& $GLOBALS['FUD_OPT_2'];
	$a = (int) $obj->users_opt;
	$b =& $usr->users_opt;

	$next_page = $next_message = $prev_message = '';
	/* Draw next/prev message controls. */
	if (!$hide_controls && $misc) {
		/* Tree view is a special condition, we only show 1 message per page. */
		if ($_GET['t'] == 'tree' || $_GET['t'] == 'tree_msg') {
			$prev_message = $misc[0] ? '<a href="javascript://" onclick="changeMsgFocus('.$misc[0].')"><img src="theme/velopiter/images/up'.img_ext.'" title="Переход к предыдущему сообщения" alt="Переход к предыдущему сообщения" width="16" height="11" /></a>' : '';
			$next_message = $misc[1] ? '<a href="javascript://" onclick="changeMsgFocus('.$misc[1].')"><img alt="Переход к предыдущему сообщения" title="Переход к следующему сообщения" src="theme/velopiter/images/down'.img_ext.'" width="16" height="11" /></a>' : '';
		} else {
			/* Handle previous link. */
			if (!$m_num && $obj->id > $obj->root_msg_id) { /* prev link on different page */
				$prev_message = '<a href="index.php?t='.$_GET['t'].'&amp;'._rsid.'&amp;prevloaded=1&amp;th='.$obj->thread_id.'&amp;start='.($misc[0] - $misc[1]).reveal_lnk.unignore_tmp.'"><img src="theme/velopiter/images/up'.img_ext.'" title="Переход к предыдущему сообщения" alt="Переход к предыдущему сообщения" width="16" height="11" /></a>';
			} else if ($m_num) { /* Inline link, same page. */
				$prev_message = '<a href="javascript://" onclick="chng_focus(\'#msg_num_'.$m_num.'\');"><img alt="Переход к предыдущему сообщения" title="Переход к предыдущему сообщения" src="theme/velopiter/images/up'.img_ext.'" width="16" height="11" /></a>';
			}

			/* Handle next link. */
			if ($obj->id < $obj->last_post_id) {
				if ($m_num && !($misc[1] - $m_num - 1)) { /* next page link */
					$next_message = '<a href="index.php?t='.$_GET['t'].'&amp;'._rsid.'&amp;prevloaded=1&amp;th='.$obj->thread_id.'&amp;start='.($misc[0] + $misc[1]).reveal_lnk.unignore_tmp.'"><img alt="Переход к предыдущему сообщения" title="Переход к следующему сообщения" src="theme/velopiter/images/down'.img_ext.'" width="16" height="11" /></a>';
					$next_page = '<a href="index.php?t='.$_GET['t'].'&amp;'._rsid.'&amp;prevloaded=1&amp;th='.$obj->thread_id.'&amp;start='.($misc[0] + $misc[1]).reveal_lnk.unignore_tmp.'">Следующая страница <img src="theme/velopiter/images/goto.gif" alt="" /></a>';
				} else {
					$next_message = '<a href="javascript://" onclick="chng_focus(\'#msg_num_'.($m_num + 2).'\');"><img alt="Переход к следующему сообщения" title="Переход к следующему сообщения" src="theme/velopiter/images/down'.img_ext.'" width="16" height="11" /></a>';
				}
			}
		}
		++$m_num;
	}

	$user_login = $obj->user_id ? $obj->login : $GLOBALS['ANON_NICK'];

	/* Check if the message should be ignored and it is not temporarily revelead. */
	if ($usr->ignore_list && !empty($usr->ignore_list[$obj->poster_id]) && !isset($GLOBALS['__FMDSP__'][$obj->id])) {
		return !$hide_controls ? '<tr><td><table border="0" cellspacing="0" cellpadding="0" class="MsgTable"><tr><td class="MsgIg al">
<a name="msg_num_'.$m_num.'"></a>
<a name="msg_'.$obj->id.'"></a>
'.($obj->user_id ? 'Сообщение от <a href="index.php?t=usrinfo&amp;'._rsid.'&amp;id='.$obj->user_id.'">'.$obj->login.'</a> игнорировано' : $GLOBALS['ANON_NICK'].' игнорирован' )  .'&nbsp;
[<a href="index.php?'. make_reveal_link($obj->id).'">показать сообщение</a>]&nbsp;
[<a href="index.php?'.make_tmp_unignore_lnk($obj->poster_id).'">показать все сообщения от '.$user_login.'</a>]&nbsp;
[<a href="index.php?t=ignore_list&amp;del='.$obj->poster_id.'&amp;redr=1&amp;'._rsid.'&amp;SQ='.$GLOBALS['sq'].'">прекратить игнорирование участника</a>]</td>
<td class="MsgIg" align="right">'.$prev_message.$next_message.'</td></tr>
</table></td></tr>' : '<tr class="MsgR1 GenText">
<td><a name="msg_num_'.$m_num.'"></a> <a name="msg_'.$obj->id.'"></a>Post by '.$user_login.' is ignored&nbsp;</td>
</tr>';
	}

	if ($obj->user_id && !$hide_controls) {
		$custom_tag = $obj->custom_status ? '<br />'.$obj->custom_status : '';
		$c = (int) $obj->level_opt;

		if ($obj->avatar_loc && $a & 8388608 && $b & 8192 && $o1 & 28 && !($c & 2)) {
			if (!($c & 1)) {
				$level_name =& $obj->level_name;
				$level_image = $obj->level_img ? '&nbsp;<img src="images/'.$obj->level_img.'" alt="" />' : '';
			} else {
				$level_name = $level_image = '';
			}
		} else {
			$level_image = $obj->level_img ? '&nbsp;<img src="images/'.$obj->level_img.'" alt="" />' : '';
			$obj->avatar_loc = '';
			$level_name =& $obj->level_name;
		}
		$avatar = ($obj->avatar_loc || $level_image) ? '<td class="avatarPad wo">'.$obj->avatar_loc.$level_image.'</td>' : '';
		$dmsg_tags = ($custom_tag || $level_name) ? '<div class="ctags">'.$level_name.$custom_tag.'</div>' : '';

		if (($o2 & 32 && !($a & 32768)) || $b & 1048576) {
			$online_indicator = (($obj->time_sec + $GLOBALS['LOGEDIN_TIMEOUT'] * 60) > __request_timestamp__) ? '<img src="theme/velopiter/images/online'.img_ext.'" alt="онлайн" title="онлайн" />&nbsp;' : '<img src="theme/velopiter/images/offline'.img_ext.'" alt="оффлайн" title="оффлайн" />&nbsp;';
		} else {
			$online_indicator = '';
		}

		$user_link = '<a href="index.php?t=usrinfo&amp;id='.$obj->user_id.'&amp;'._rsid.'">'.$user_login.'</a>';

		$location = $obj->location ? '<br /><b>Город: </b> <br />'.(strlen($obj->location) > $GLOBALS['MAX_LOCATION_SHOW'] ? substr($obj->location, 0, $GLOBALS['MAX_LOCATION_SHOW']) . '...' : $obj->location) : '';

		if (_uid && _uid != $obj->user_id) {
			$buddy_link	= !isset($usr->buddy_list[$obj->user_id]) ? '<a href="index.php?t=buddy_list&amp;add='.$obj->user_id.'&amp;'._rsid.'&amp;SQ='.$GLOBALS['sq'].'">в контакты</a><br />' : '<a href="index.php?t=buddy_list&amp;del='.$obj->user_id.'&amp;redr=1&amp;'._rsid.'&amp;SQ='.$GLOBALS['sq'].'">удалить из контактов</a><br />';
			$ignore_link	= !isset($usr->ignore_list[$obj->user_id]) ? '<a href="index.php?t=ignore_list&amp;add='.$obj->user_id.'&amp;'._rsid.'&amp;SQ='.$GLOBALS['sq'].'">игнорировать все сообщения<br />от этого участника</a>' : '<a href="index.php?t=ignore_list&amp;del='.$obj->user_id.'&amp;redr=1&amp;'._rsid.'&amp;SQ='.$GLOBALS['sq'].'">прекратить игнорировать сообщения этого участника</a>';
			$dmsg_bd_il	= $buddy_link.$ignore_link.'<br />';
		} else {
			$dmsg_bd_il = '';
		}

		/* Show im buttons if need be. */
		if ($b & 16384) {
			$im = '';
			if ($obj->icq) {
				$im .= '<a href="index.php?t=usrinfo&amp;id='.$obj->poster_id.'&amp;'._rsid.'#icq_msg"><img title="'.$obj->icq.'" src="theme/velopiter/images/icq'.img_ext.'" alt="" /></a>';
			}
			if ($obj->aim) {
				$im .= '<a href="aim:goim?screenname='.$obj->aim.'&amp;message=Hi.+Are+you+there?"><img alt="" src="theme/velopiter/images/aim'.img_ext.'" title="'.$obj->aim.'" /></a>';
			}
			if ($obj->yahoo) {
				$im .= '<a href="http://edit.yahoo.com/config/send_webmesg?.target='.$obj->yahoo.'&amp;.src=pg"><img alt="" src="theme/velopiter/images/yahoo'.img_ext.'" title="'.$obj->yahoo.'" /></a>';
			}
			if ($obj->msnm) {
				$im .= '<a href="mailto: '.$obj->msnm.'"><img alt="" src="theme/velopiter/images/msnm'.img_ext.'" title="'.$obj->msnm.'" /></a>';
			}
			if ($obj->jabber) {
				$im .=  '<img src="theme/velopiter/images/jabber'.img_ext.'" title="'.$obj->jabber.'" alt="" />';
			}
			if ($obj->google) {
				$im .= '<img src="theme/velopiter/images/google'.img_ext.'" title="'.$obj->google.'" alt="" />';
			}
			if ($obj->skype) {
				$im .=  '<a href="callto://'.$obj->skype.'"><img src="theme/velopiter/images/skype'.img_ext.'" title="'.$obj->skype.'" alt="" /></a>';
			}
			if ($obj->twitter) {
				$im .=  '<a href="http://twitter.com/'.$obj->twitter.'"><img src="theme/velopiter/images/twitter'.img_ext.'" title="'.$obj->twitter.'" alt="" /></a>';
			}
			if ($o2 & 2048) {
				if ($obj->affero) {
					$im .= '<a href="http://svcs.affero.net/rm.php?r='.$obj->affero.'&amp;ll='.$obj->forum_id.'.'.$GLOBALS['affero_domain'].'&amp;lp='.$obj->forum_id.'.'.urlencode($GLOBALS['affero_domain']['host']).'&amp;ls='.urlencode($obj->subject).'"><img alt="" src="theme/velopiter/images/affero_reg.gif" /></a>';
				} else {
					$im .= '<a href="http://svcs.affero.net/rm.php?m='.urlencode($obj->email).'&amp;ll='.$obj->forum_id.'.'.$GLOBALS['affero_domain'].'&amp;lp='.$obj->forum_id.'.'.urlencode($GLOBALS['affero_domain']['host']).'&amp;ls='.urlencode($obj->subject).'"><img alt="" src="theme/velopiter/images/affero_noreg.gif" /></a>';
				}
			}
			if ($im) {
				$dmsg_im_row = $im.'<br />';
			} else {
				$dmsg_im_row = '';
			}
		} else {
			$dmsg_im_row = '';
		}
	} else {
		$user_link = $obj->user_id ? $user_login : $user_login;
		$dmsg_tags = $dmsg_im_row = $dmsg_bd_il = $location = $online_indicator = $avatar = '';
	}

	/* Display message body.
	 * If we have message threshold & the entirity of the post has been revelead show a
	 * preview otherwise if the message body exists show an actual body.
	 * If there is no body show a 'no-body' message.
	 */
	if (!$hide_controls && $obj->message_threshold && $obj->length_preview && $obj->length > $obj->message_threshold && !isset($GLOBALS['__FMDSP__'][$obj->id])) {
		$msg_body = '<span class="MsgBodyText">'.read_msg_body($obj->offset_preview, $obj->length_preview, $obj->file_id_preview).'</span>
...<br /><br /><div class="ac">[ <a href="index.php?'.make_reveal_link($obj->id).'">Показать остальное</a> ]</div>';
	} else if ($obj->length) {
		$msg_body = '<span class="MsgBodyText">'.read_msg_body($obj->foff, $obj->length, $obj->file_id).'</span>';
	} else {
		$msg_body = 'Нет текста сообщения';
	}

	/* Draw file attachments if there are any. */
	$drawmsg_file_attachments = '';
	if ($obj->attach_cnt && !empty($obj->attach_cache)) {
		$atch = unserialize($obj->attach_cache);
		if (!empty($atch)) {
			foreach ($atch as $v) {
				$sz = $v[2] / 1024;
				$drawmsg_file_attachments .= '<li><a href="index.php?t=getfile&amp;id='.$v[0].'&amp;'._rsid.'"><img alt="" src="images/mime/'.$v[4].'" class="at" /></a>
<span class="GenText fb">Вложение:</span> <a href="index.php?t=getfile&amp;id='.$v[0].'&amp;'._rsid.'">'.$v[1].'</a><br />
<span class="SmallText">(Размер: '.($sz < 1000 ? number_format($sz, 2).'KB' : number_format($sz/1024, 2).'MB').', Загружено '.convertPlural($v[3], array(''.$v[3].' раз',''.$v[3].' раза',''.$v[3].' раз')).')</span></li>';
			}
			$drawmsg_file_attachments = '<ul class="AttachmentsList">
'.$drawmsg_file_attachments.'
</ul>';
		}
		/* Append session to getfile. */
		if (_uid) {
			if ($o1 & 128 && !isset($_COOKIE[$GLOBALS['COOKIE_NAME']])) {
				$msg_body = str_replace('<img src="index.php?t=getfile', '<img src="index.php?t=getfile&amp;S='. s, $msg_body);
				$tap = 1;
			}
			if ($o2 & 32768 && (isset($tap) || $o2 & 8192)) {
				$pos = 0;
				while (($pos = strpos($msg_body, '<img src="index.php/fa/', $pos)) !== false) {
					$pos = strpos($msg_body, '"', $pos + 11);
					$msg_body = substr_replace($msg_body, _rsid, $pos, 0);
				}
			}
		}
	}

	if ($obj->poll_cache) {
		$obj->poll_cache = unserialize($obj->poll_cache);
	}

	/* Handle poll votes. */
	if (!empty($_POST['poll_opt']) && ($_POST['poll_opt'] = (int)$_POST['poll_opt']) && !($obj->thread_opt & 1) && $perms & 512) {
		if (register_vote($obj->poll_cache, $obj->poll_id, $_POST['poll_opt'], $obj->id)) {
			$obj->total_votes += 1;
			$obj->cant_vote = 1;
		}
		unset($_GET['poll_opt']);
	}

	/* Display poll if there is one. */
	if ($obj->poll_id && $obj->poll_cache) {
		/* We need to determine if we allow the user to vote or see poll results. */
		$show_res = 1;

		if (isset($_GET['pl_view']) && !isset($_POST['pl_view'])) {
			$_POST['pl_view'] = $_GET['pl_view'];
		}

		/* Various conditions that may prevent poll voting. */
		if (!$hide_controls && !$obj->cant_vote &&
			(!isset($_POST['pl_view']) || $_POST['pl_view'] != $obj->poll_id) &&
			($perms & 512 && (!($obj->thread_opt & 1) || $perms & 4096)) &&
			(!$obj->expiry_date || ($obj->creation_date + $obj->expiry_date) > __request_timestamp__) &&
			/* Check if the max # of poll votes was reached. */
			(!$obj->max_votes || $obj->total_votes < $obj->max_votes)
		) {
			$show_res = 0;
		}

		$i = 0;

		$poll_data = '';
		foreach ($obj->poll_cache as $k => $v) {
			++$i;
			if ($show_res) {
				$length = ($v[1] && $obj->total_votes) ? round($v[1] / $obj->total_votes * 100) : 0;
				$poll_data .= '<tr class="'.alt_var('msg_poll_alt_clr','RowStyleB','RowStyleA').'"><td>'.$i.'.</td><td>'.$v[0].'</td><td><img src="theme/velopiter/images/poll_pix.gif" alt="" height="10" width="'.$length.'" /> '.$v[1].' / '.$length.'%</td></tr>';
			} else {
				$poll_data .= '<tr class="'.alt_var('msg_poll_alt_clr','RowStyleB','RowStyleA').'"><td>'.$i.'.</td><td colspan="2"><label><input type="radio" name="poll_opt" value="'.$k.'" />&nbsp;&nbsp;'.$v[0].'</label></td></tr>';
			}
		}

		if (!$show_res) {
			$poll = '<br />
<form action="index.php?'.htmlspecialchars($_SERVER['QUERY_STRING']).'#msg_'.$obj->id.'" method="post">'._hs.'
<table cellspacing="1" cellpadding="2" class="PollTable">
<tr><th class="nw" colspan="3">'.$obj->poll_name.'<span class="ptp">[ '.$obj->total_votes.' '.convertPlural($obj->total_votes, array('голос','голоса','голосов')).' ]</span></th></tr>
'.$poll_data.'
<tr class="'.alt_var('msg_poll_alt_clr','RowStyleB','RowStyleA').' ar"><td colspan="3"><input type="submit" class="button" name="pl_vote" value="Проголосовать" />&nbsp;'.($obj->total_votes ? '<input type="submit" class="button" name="pl_res" value="Просмотр результатов" />' : '' )  .'</td></tr>
</table><input type="hidden" name="pl_view" value="'.$obj->poll_id.'" /></form><br />';
		} else {
			$poll = '<br /><table cellspacing="1" cellpadding="2" class="PollTable">
<tr><th class="nw" colspan="3">'.$obj->poll_name.'<span class="vt">[ '.$obj->total_votes.' '.convertPlural($obj->total_votes, array('голос','голоса','голосов')).' ]</span></th></tr>
'.$poll_data.'
</table><br />';
		}

		if (($p = strpos($msg_body, '{POLL}')) !== false) {
			$msg_body = substr_replace($msg_body, $poll, $p, 6);
		} else {
			$msg_body = $poll . $msg_body;
		}
	}

	/* Determine if the message was updated and if this needs to be shown. */
	if ($obj->update_stamp) {
		if ($obj->updated_by != $obj->poster_id && $o1 & 67108864) {
			$modified_message = '<br /><p class="fl">[Обновления: '.strftime("%a, %d %B %Y %H:%M", $obj->update_stamp).'] от Модератора</p>';
		} else if ($obj->updated_by == $obj->poster_id && $o1 & 33554432) {
			$modified_message = '<br /><p class="fl">[Обновления: '.strftime("%a, %d %B %Y %H:%M", $obj->update_stamp).']</p>';
		} else {
			$modified_message = '';
		}
	} else {
		$modified_message = '';
	}

	if ($_GET['t'] != 'tree' && $_GET['t'] != 'msg') {
		$lnk = d_thread_view;
	} else {
		$lnk =& $_GET['t'];
	}

	$rpl = '';
	if (!$hide_controls) {

		/* Show reply links, eg: [message #1 is a reply to message #2]. */
		if ($o2 & 536870912) {
			if ($obj->reply_to && $obj->reply_to != $obj->id) {
				$rpl = '<span class="SmallText">[<a href="index.php?t='.$lnk.'&amp;th='.$obj->thread_id.'&amp;goto='.$obj->id.'&amp;'._rsid.'#msg_'.$obj->id.'">сообщение #'.$obj->id.'</a> является ответом на <a href="index.php?t='.$lnk.'&amp;th='.$obj->thread_id.'&amp;goto='.$obj->reply_to.'&amp;'._rsid.'#msg_'.$obj->reply_to.'">сообщение #'.$obj->reply_to.'</a>]</span>';
			} else {
				$rpl = '<span class="SmallText">[<a href="index.php?t='.$lnk.'&amp;th='.$obj->thread_id.'&amp;goto='.$obj->id.'&amp;'._rsid.'#msg_'.$obj->id.'">сообщение #'.$obj->id.'</a>]</span>';
			}
		}

		/* Little trick, this variable will only be available if we have a next link leading to another page. */
		if (empty($next_page)) {
			$next_page = '&nbsp;';
		}

		if (_uid && ($perms & 16 || (_uid == $obj->poster_id && (!$GLOBALS['EDIT_TIME_LIMIT'] || __request_timestamp__ - $obj->post_stamp < $GLOBALS['EDIT_TIME_LIMIT'] * 60)))) {
			$edit_link = '<a href="index.php?t=post&amp;msg_id='.$obj->id.'&amp;'._rsid.'"><img alt="edit" src="theme/velopiter/images/msg_edit.gif" /></a>&nbsp;&nbsp;&nbsp;&nbsp;';
		} else {
			$edit_link = '';
		}

		if (!($obj->thread_opt & 1) || $perms & 4096) {
			$reply_link = '<a href="index.php?t=post&amp;reply_to='.$obj->id.'&amp;'._rsid.'"><img alt="reply" src="theme/velopiter/images/msg_reply.gif" /></a>&nbsp;';
			$quote_link = '<a href="index.php?t=post&amp;reply_to='.$obj->id.'&amp;quote=true&amp;'._rsid.'"><img alt="quote" src="theme/velopiter/images/msg_quote.gif" /></a>';
		} else {
			$reply_link = $quote_link = '';
		}
	}

	return '<tr><td class="MsgSpacer"><table cellspacing="0" cellpadding="0" class="MsgTable">
<tr>
<td colspan="2" class="MsgR1"><table cellspacing="0" cellpadding="0" class="ContentTable"><tr><td class="MsgR1 vt al MsgSubText"><a name="msg_num_'.$m_num.'"></a><a name="msg_'.$obj->id.'"></a>'.($obj->icon && !$hide_controls ? '<img src="images/message_icons/'.$obj->icon.'" alt="'.$obj->icon.'" />&nbsp;&nbsp;' : '' )  .$obj->subject.$rpl.'</td>
<td class="MsgR1 vt ar"><span class="DateText">'.strftime("%a, %d %B %Y %H:%M", $obj->post_stamp).'</span> '.$prev_message.$next_message.'</td></tr></table></td></tr>

<tr class="MsgR2">
<td class="MsgR2" width="15%" valign="top">
<table cellspacing="0" cellpadding="0" class="ContentTable"><tr class="MsgR2">'.$online_indicator.$user_link.(!$hide_controls ? ($obj->user_id ? '<br />'.$avatar.'<tr class="MsgR2"><td class="msgud">'.$dmsg_tags.'</td></tr><tr class="MsgR2"> <td class="msgud">Сообщений:'.$obj->posted_msg_count.'<br />
Зарегистрирован:'.strftime("%B %Y", $obj->join_date).' '.$location : '' )   : '' )  .'</td></tr><tr class="MsgR2"><td class="msgud">'.$dmsg_bd_il.$dmsg_im_row.(!$hide_controls ? (($obj->host_name && $o1 & 268435456) ? 'От:'.$obj->host_name.'<br />' : '' )  .(($b & 1048576 || $usr->md || $o1 & 134217728) ? 'IP: <a href="index.php?t=ip&amp;ip='.$obj->ip_addr.'&amp;'._rsid.'" target="_blank">'.$obj->ip_addr.'</a>' : '' )   : '' )  .'</td></tr></table></td>

<td class="MsgR3" width="85%" valign="top">'.$msg_body.$drawmsg_file_attachments.'
'.$modified_message.(!$hide_controls ? (($obj->sig && $o1 & 32768 && $obj->msg_opt & 1 && $b & 4096 && !($a & 67108864)) ? '<br /><br /><hr class="sig" />'.$obj->sig : '' )  .'<p class="fr"><a href="index.php?t=report&amp;msg_id='.$obj->id.'&amp;'._rsid.'" rel="nofollow">Известить модератора</a></p>' : '' )  .'
</td></tr>
'.(!$hide_controls ? '<tr><td colspan="2" class="MsgToolBar"><table border="0" cellspacing="0" cellpadding="0" class="wa"><tr>
<td class="al nw">'.($obj->user_id ? '<a href="index.php?t=usrinfo&amp;id='.$obj->user_id.'&amp;'._rsid.'"><img alt="" src="theme/velopiter/images/msg_about.gif" /></a>&nbsp;'.(($o1 & 4194304 && $a & 16) ? '<a href="index.php?t=email&amp;toi='.$obj->user_id.'&amp;'._rsid.'" rel="nofollow"><img alt="" src="theme/velopiter/images/msg_email.gif" /></a>&nbsp;' : '' )  .($o1 & 1024 ? '<a href="index.php?t=ppost&amp;toi='.$obj->user_id.'&amp;rmid='.$obj->id.'&amp;'._rsid.'"><img alt="Отправить личное сообщение этому участнику" title="Отправить личное сообщение этому участнику" src="theme/velopiter/images/msg_pm.gif" /></a>' : '' )   : '' )  .'</td>
<td class="GenText wa ac">'.$next_page.'</td>
<td class="nw ar">'.($perms & 32 ? '<a href="index.php?t=mmod&amp;del='.$obj->id.'&amp;'._rsid.'"><img alt="" src="theme/velopiter/images/msg_delete.gif" /></a>&nbsp;' : '' )  .$edit_link.$reply_link.$quote_link.'</td>
</tr></table></td></tr>' : '' )  .'
</table></td></tr>';
}class fud_msg
{
	var $id, $thread_id, $poster_id, $reply_to, $ip_addr, $host_name, $post_stamp, $subject, $attach_cnt, $poll_id,
	    $update_stamp, $icon, $apr, $updated_by, $login, $length, $foff, $file_id, $msg_opt,
	    $file_id_preview, $length_preview, $offset_preview, $body, $mlist_msg_id;
}

$GLOBALS['CHARSET'] = 'utf-8';

class fud_msg_edit extends fud_msg
{
	function add_reply($reply_to, $th_id=null, $perm, $autoapprove=1)
	{
		if ($reply_to) {
			$this->reply_to = $reply_to;
			$fd = db_saq('SELECT t.forum_id, f.message_threshold, f.forum_opt FROM fud26_msg m INNER JOIN fud26_thread t ON m.thread_id=t.id INNER JOIN fud26_forum f ON f.id=t.forum_id WHERE m.id='. $reply_to);
		} else {
			$fd = db_saq('SELECT t.forum_id, f.message_threshold, f.forum_opt FROM fud26_thread t INNER JOIN fud26_forum f ON f.id=t.forum_id WHERE t.id='. $th_id);
		}

		return $this->add($fd[0], $fd[1], $fd[2], $perm, $autoapprove);
	}

	function add($forum_id, $message_threshold, $forum_opt, $perm, $autoapprove=1, $msg_tdescr='')
	{
		if (!$this->post_stamp) {
			$this->post_stamp = __request_timestamp__;
		}

		if (!isset($this->ip_addr)) {
			$this->ip_addr = get_ip();
		}
		$this->host_name = $GLOBALS['FUD_OPT_1'] & 268435456 ? _esc(get_host($this->ip_addr)) : 'NULL';
		$this->thread_id = isset($this->thread_id) ? $this->thread_id : 0;
		$this->reply_to = isset($this->reply_to) ? $this->reply_to : 0;
		$this->subject = substr($this->subject, 0, 100);	// Subject col is VARCHAR(100).

		if ($GLOBALS['FUD_OPT_3'] & 32768) {	// DB_MESSAGE_STORAGE
			$file_id = $file_id_preview = $length_preview = 0;
			$offset = $offset_preview = -1;
			$length = strlen($this->body);
		} else {
			$file_id = write_body($this->body, $length, $offset, $forum_id);

			/* Determine if preview needs building. */
			if ($message_threshold && $message_threshold < strlen($this->body)) {
				$thres_body = trim_html($this->body, $message_threshold);
				$file_id_preview = write_body($thres_body, $length_preview, $offset_preview, $forum_id);
			} else {
				$file_id_preview = $offset_preview = $length_preview = 0;
			}
		}

		/* Lookup country and flag. */
		if ($GLOBALS['FUD_OPT_3'] & 524288) {	// ENABLE_GEO_LOCATION.
			$flag = db_saq('SELECT cc, country FROM fud26_geoip WHERE '. sprintf('%u', 	ip2long($this->ip_addr)) .' BETWEEN ips AND ipe');
		}
		if (empty($flag)) {
			$flag = array(null, null);
		}

		$this->id = db_qid('INSERT INTO fud26_msg (
			thread_id,
			poster_id,
			reply_to,
			ip_addr,
			host_name,
			post_stamp,
			subject,
			attach_cnt,
			poll_id,
			icon,
			msg_opt,
			file_id,
			foff,
			length,
			file_id_preview,
			offset_preview,
			length_preview,
			mlist_msg_id,
			poll_cache,
			flag_cc,
			flag_country
		) VALUES(
			'. $this->thread_id .',
			'. $this->poster_id .',
			'. (int)$this->reply_to .',
			\''. $this->ip_addr .'\',
			'. $this->host_name .',
			'. $this->post_stamp .',
			'. ssn($this->subject) .',
			'. (int)$this->attach_cnt .',
			'. (int)$this->poll_id .',
			'. ssn($this->icon) .',
			'. $this->msg_opt .',
			'. $file_id .',
			'. (int)$offset .',
			'. (int)$length .',
			'. $file_id_preview .',
			'. $offset_preview .',
			'. $length_preview .',
			'. ssn($this->mlist_msg_id) .',
			'. ssn(poll_cache_rebuild($this->poll_id)) .',
			'. ssn($flag[0]) .',
			'. ssn($flag[1]) .'
		)');

		if ($GLOBALS['FUD_OPT_3'] & 32768) {	// DB_MESSAGE_STORAGE
			$file_id = db_qid('INSERT INTO fud26_msg_store (data) VALUES('. _esc($this->body) .')');
			if ($message_threshold && $length > $message_threshold) {
				$file_id_preview = db_qid('INSERT INTO fud26_msg_store (data) VALUES('. _esc(trim_html($this->body, $message_threshold)) .')');
			}
			q('UPDATE fud26_msg SET file_id='. $file_id .', file_id_preview='. $file_id_preview .' WHERE id='. $this->id);
		}

		$thread_opt = (int) ($perm & 4096 && isset($_POST['thr_locked']));

		if (!$this->thread_id) { /* New thread. */
			if ($perm & 64) {
				if (isset($_POST['thr_ordertype'], $_POST['thr_orderexpiry']) && (int)$_POST['thr_ordertype']) {
					$thread_opt |= (int)$_POST['thr_ordertype'];
					$thr_orderexpiry = (int)$_POST['thr_orderexpiry'];
				}
				if (!empty($_POST['thr_always_on_top'])) {
					$thread_opt |= 8;
				}
			}

			$this->thread_id = th_add($this->id, $forum_id, $this->post_stamp, $thread_opt, (isset($thr_orderexpiry) ? $thr_orderexpiry : 0), 0, 0, 0, $msg_tdescr);

			q('UPDATE fud26_msg SET thread_id='. $this->thread_id .' WHERE id='. $this->id);
		} else {
			th_lock($this->thread_id, $thread_opt & 1);
		}

		if ($autoapprove && $forum_opt & 2) {
			$this->approve($this->id);
		}

		return $this->id;
	}

	function sync($id, $frm_id, $message_threshold, $perm, $msg_tdescr='')
	{
		$this->subject = substr($this->subject, 0, 100);	// Subject col is VARCHAR(100).

		if ($GLOBALS['FUD_OPT_3'] & 32768) {	// DB_MESSAGE_STORAGE
			$file_id = $file_id_preview = $length_preview = 0;
			$offset = $offset_preview = -1;
			$length = strlen($this->body);
		} else {
			$file_id = write_body($this->body, $length, $offset, $frm_id);

			/* Determine if preview needs building. */
			if ($message_threshold && $message_threshold < strlen($this->body)) {
				$thres_body = trim_html($this->body, $message_threshold);
				$file_id_preview = write_body($thres_body, $length_preview, $offset_preview, $frm_id);
			} else {
				$file_id_preview = $offset_preview = $length_preview = 0;
			}
		}

		q('UPDATE fud26_msg SET
			file_id='. $file_id .',
			foff='. (int)$offset .',
			length='. (int)$length .',
			mlist_msg_id='. ssn($this->mlist_msg_id) .',
			file_id_preview='. $file_id_preview .',
			offset_preview='. $offset_preview .',
			length_preview='. $length_preview .',
			updated_by='. $id .',
			msg_opt='. $this->msg_opt .',
			attach_cnt='. (int)$this->attach_cnt .',
			poll_id='. (int)$this->poll_id .',
			update_stamp='. __request_timestamp__ .',
			icon='. ssn($this->icon) .' ,
			poll_cache='. ssn(poll_cache_rebuild($this->poll_id)) .',
			subject='. ssn($this->subject) .'
		WHERE id='. $this->id);

		if ($GLOBALS['FUD_OPT_3'] & 32768) {	// DB_MESSAGE_STORAGE
			q('DELETE FROM fud26_msg_store WHERE id IN('. $this->file_id .','. $this->file_id_preview .')');
			$file_id = db_qid('INSERT INTO fud26_msg_store (data) VALUES('. _esc($this->body) .')');
			if ($message_threshold && $length > $message_threshold) {
				$file_id_preview = db_qid('INSERT INTO fud26_msg_store (data) VALUES('. _esc(trim_html($this->body, $message_threshold)) .')');
			}
			q('UPDATE fud26_msg SET file_id='. $file_id .', file_id_preview='. $file_id_preview .' WHERE id='. $this->id);
		}

		/* Determine wether or not we should deal with locked & sticky stuff
		 * current approach may seem a little redundant, but for (most) users who
		 * do not have access to locking & sticky this eliminated a query.
		 */
		$th_data = db_saq('SELECT orderexpiry, thread_opt, root_msg_id, tdescr FROM fud26_thread WHERE id='. $this->thread_id);
		$locked = (int) isset($_POST['thr_locked']);
		if (isset($_POST['thr_ordertype'], $_POST['thr_orderexpiry']) || (($th_data[1] ^ $locked) & 1)) {
			$thread_opt = (int) $th_data[1];
			$orderexpiry = isset($_POST['thr_orderexpiry']) ? (int) $_POST['thr_orderexpiry'] : 0;

			/* Confirm that user has ability to change lock status of the thread. */
			if ($perm & 4096) {
				if ($locked && !($thread_opt & $locked)) {
					$thread_opt |= 1;
				} else if (!$locked && $thread_opt & 1) {
					$thread_opt &= ~1;
				}
			}

			/* Confirm that user has ability to change sticky status of the thread. */
			if ($th_data[2] == $this->id && isset($_POST['thr_ordertype'], $_POST['thr_orderexpiry']) && $perm & 64) {
				if (!$_POST['thr_ordertype'] && $thread_opt > 1) {
					$orderexpiry = 0;
					$thread_opt &= ~6;
				} else if ($thread_opt < 2 && (int) $_POST['thr_ordertype']) {
					$thread_opt |= $_POST['thr_ordertype'];
				} else if (!($thread_opt & (int) $_POST['thr_ordertype'])) {
					$thread_opt = $_POST['thr_ordertype'] | ($thread_opt & 1);
				}
			}

			if ($perm & 64) {
				if (!empty($_POST['thr_always_on_top'])) {
					$thread_opt |= 8;
				} else {
					$thread_opt &= ~8;
				}
			}

			/* Determine if any work needs to be done. */
			if ($thread_opt != $th_data[1] || $orderexpiry != $th_data[0]) {
				q('UPDATE fud26_thread SET '. ($th_data[2] == $this->id ? 'tdescr='. _esc($msg_tdescr) .',' : '') .' thread_opt='.$thread_opt.', orderexpiry='. $orderexpiry .' WHERE id='. $this->thread_id);
				/* Avoid rebuilding the forum view whenever possible, since it's a rather slow process.
				 * Only rebuild if expiry time has changed or message gained/lost sticky status.
				 */
				$diff = $thread_opt ^ $th_data[1];
				if (($diff > 1 && $diff & 14) || $orderexpiry != $th_data[0]) {
					rebuild_forum_view_ttl($frm_id);
				}
			} else if ($msg_tdescr != $th_data[3] && $th_data[2] == $this->id) {
				q('UPDATE fud26_thread SET tdescr='. _esc($msg_tdescr) .' WHERE id='. $this->thread_id);
			}
		} else if ($msg_tdescr != $th_data[3] && $th_data[2] == $this->id) {
			q('UPDATE fud26_thread SET tdescr='. _esc($msg_tdescr) .' WHERE id='. $this->thread_id);
		}

		if ($GLOBALS['FUD_OPT_1'] & 16777216) {	// FORUM_SEARCH enabled? If so, reindex message.
			q('DELETE FROM fud26_index WHERE msg_id='. $this->id);
			q('DELETE FROM fud26_title_index WHERE msg_id='. $this->id);
			index_text((!strncasecmp('Re: ', $this->subject, 4) ? '' : $this->subject), $this->body, $this->id);
		}
	}

	static function delete($rebuild_view=1, $mid=0, $th_rm=0)
	{
		if (!$mid) {
			$mid = $this->id;
		}

		if (!($del = db_sab('SELECT m.file_id, m.file_id_preview, m.id, m.attach_cnt, m.poll_id, m.thread_id, m.reply_to, m.apr, m.poster_id, t.replies, t.root_msg_id AS root_msg_id, t.last_post_id AS thread_lip, t.forum_id, f.last_post_id AS forum_lip 
					FROM fud26_msg m 
					LEFT JOIN fud26_thread t ON m.thread_id=t.id 
					LEFT JOIN fud26_forum f ON t.forum_id=f.id WHERE m.id='. $mid))) {
			return;
		}

		if (!db_locked()) {
			db_lock('fud26_msg_store WRITE, fud26_forum f WRITE, fud26_thr_exchange WRITE, fud26_tv_'. $del->forum_id .' WRITE, fud26_tv_'. $del->forum_id .' tv WRITE, fud26_msg m WRITE, fud26_thread t WRITE, fud26_level WRITE, fud26_forum WRITE, fud26_forum_read WRITE, fud26_thread WRITE, fud26_msg WRITE, fud26_attach WRITE, fud26_poll WRITE, fud26_poll_opt WRITE, fud26_poll_opt_track WRITE, fud26_users WRITE, fud26_thread_notify WRITE, fud26_bookmarks WRITE, fud26_msg_report WRITE, fud26_thread_rate_track WRITE, fud26_index WRITE, fud26_title_index WRITE');
			$ll = 1;
		}

		q('DELETE FROM fud26_msg WHERE id='. $mid);

		/* Attachments. */
		if ($del->attach_cnt) {
			$res = q('SELECT location FROM fud26_attach WHERE message_id='. $mid .' AND attach_opt=0');
			while ($loc = db_rowarr($res)) {
				@unlink($loc[0]);
			}
			unset($res);
			q('DELETE FROM fud26_attach WHERE message_id='. $mid .' AND attach_opt=0');
		}

		/* Remove message reports. */
		q('DELETE FROM fud26_msg_report WHERE msg_id='. $mid);

		/* Cleanup index entries. */
		if ($GLOBALS['FUD_OPT_1'] & 16777216) {	// FORUM_SEARCH enabled?
			q('DELETE FROM fud26_index WHERE msg_id='. $mid);
			q('DELETE FROM fud26_title_index WHERE msg_id='. $mid);
		}

		if ($del->poll_id) {
			poll_delete($del->poll_id);
		}

		/* Check if thread. */
		if ($del->root_msg_id == $del->id) {
			$th_rm = 1;
			/* Delete all messages in the thread if there is more than 1 message. */
			if ($del->replies) {
				$rmsg = q('SELECT id FROM fud26_msg WHERE thread_id='. $del->thread_id .' AND id != '. $del->id);
				while ($dim = db_rowarr($rmsg)) {
					fud_msg_edit::delete(0, $dim[0], 1);
				}
				unset($rmsg);
			}

			q('DELETE FROM fud26_thread_notify WHERE thread_id='. $del->thread_id);
			q('DELETE FROM fud26_bookmarks WHERE thread_id='. $del->thread_id);
			q('DELETE FROM fud26_thread WHERE id='. $del->thread_id);
			q('DELETE FROM fud26_thread_rate_track WHERE thread_id='. $del->thread_id);
			q('DELETE FROM fud26_thr_exchange WHERE th='. $del->thread_id);

			if ($del->apr) {
				/* We need to determine the last post id for the forum, it can be null. */
				$lpi = (int) q_singleval('SELECT t.last_post_id FROM fud26_thread t INNER JOIN fud26_msg m ON t.last_post_id=m.id AND m.apr=1 WHERE t.forum_id='.$del->forum_id.' AND t.moved_to=0 ORDER BY m.post_stamp DESC LIMIT 1');
				q('UPDATE fud26_forum SET last_post_id='. $lpi .', thread_count=thread_count-1, post_count=post_count-'. $del->replies .'-1 WHERE id='. $del->forum_id);
			}
		} else if (!$th_rm  && $del->apr) {
			q('UPDATE fud26_msg SET reply_to='. $del->reply_to .' WHERE thread_id='. $del->thread_id .' AND reply_to='. $mid);

			/* Check if the message is the last in thread. */
			if ($del->thread_lip == $del->id) {
				list($lpi, $lpd) = db_saq('SELECT id, post_stamp FROM fud26_msg WHERE thread_id='. $del->thread_id .' AND apr=1 ORDER BY post_stamp DESC LIMIT 1');
				q('UPDATE fud26_thread SET last_post_id='. $lpi .', last_post_date='. $lpd .', replies=replies-1 WHERE id='. $del->thread_id);
			} else {
				q('UPDATE fud26_thread SET replies=replies-1 WHERE id='. $del->thread_id);
			}

			/* Check if the message is the last in the forum. */
			if ($del->forum_lip == $del->id) {
				$page = q_singleval('SELECT seq FROM fud26_tv_'. $del->forum_id .' WHERE thread_id='. $del->thread_id);
				$lp = db_saq('SELECT t.last_post_id, t.last_post_date 
					FROM fud26_tv_'. $del->forum_id .' tv
					INNER JOIN fud26_thread t ON tv.thread_id=t.id 
					WHERE tv.seq IN('. $page .','. ($page - 1) .') AND t.moved_to=0 ORDER BY t.last_post_date DESC LIMIT 1');
				if (!isset($lpd) || $lp[1] > $lpd) {
					$lpi = $lp[0];
				}
				q('UPDATE fud26_forum SET post_count=post_count-1, last_post_id='. $lpi .' WHERE id='. $del->forum_id);
			} else {
				q('UPDATE fud26_forum SET post_count=post_count-1 WHERE id='. $del->forum_id);
			}
		}

		if ($del->apr) {
			if ($del->poster_id) {
				user_set_post_count($del->poster_id);
			}
			if ($rebuild_view) {
				if ($th_rm) {
					th_delete_rebuild($del->forum_id, $del->thread_id);
				} else if ($del->thread_lip == $del->id) {
					rebuild_forum_view_ttl($del->forum_id);
				}
			}
		}
		if (isset($ll)) {
			db_unlock();
		}

		if ($GLOBALS['FUD_OPT_3'] & 32768) {	// DB_MESSAGE_STORAGE
			q('DELETE FROM fud26_msg_store WHERE id IN('. $del->file_id .','. $del->file_id_preview .')');
		}

		if (!$del->apr || !$th_rm || ($del->root_msg_id != $del->id)) {
			return;
		}

		/* Needed for moved thread pointers. */
		$r = q('SELECT forum_id, id FROM fud26_thread WHERE root_msg_id='. $del->root_msg_id);
		while (($res = db_rowarr($r))) {
			q('DELETE FROM fud26_thread WHERE id='. $res[1]);
			q('UPDATE fud26_forum SET thread_count=thread_count-1 WHERE id='. $res[0]);
			th_delete_rebuild($res[0], $res[1]);
		}
		unset($r);
	}

	static function approve($id)
	{
		/* Fetch info about the message, poll (if one exists), thread & forum. */
		$mtf = db_sab('SELECT
					m.id, m.poster_id, m.apr, m.subject, m.foff, m.length, m.file_id, m.thread_id, m.poll_id, m.attach_cnt,
					m.post_stamp, m.reply_to, m.mlist_msg_id, m.msg_opt,
					t.forum_id, t.last_post_id, t.root_msg_id, t.last_post_date, t.thread_opt,
					m2.post_stamp AS frm_last_post_date,
					f.name AS frm_name, f.forum_opt,
					u.alias, u.email, u.sig, u.name as real_name,
					n.id AS nntp_id, ml.id AS mlist_id
				FROM fud26_msg m
				INNER JOIN fud26_thread t ON m.thread_id=t.id
				INNER JOIN fud26_forum f ON t.forum_id=f.id
				LEFT JOIN fud26_msg m2 ON f.last_post_id=m2.id
				LEFT JOIN fud26_users u ON m.poster_id=u.id
				LEFT JOIN fud26_mlist ml ON ml.forum_id=f.id AND '. q_bitand('ml.mlist_opt', 2) .' > 0
				LEFT JOIN fud26_nntp n ON n.forum_id=f.id AND '. q_bitand('n.nntp_opt', 2) .' > 0
				WHERE m.id='. $id .' AND m.apr=0');

		/* Nothing to do or bad message id. */
		if (!$mtf) {
			return;
		}

		if ($mtf->alias) {
			$mtf->alias = reverse_fmt($mtf->alias);
		} else {
			$mtf->alias = $GLOBALS['ANON_NICK'];
		}

		q('UPDATE fud26_msg SET apr=1 WHERE id='.$mtf->id);

		if ($mtf->poster_id) {
			user_set_post_count($mtf->poster_id);
		}

		if ($mtf->post_stamp > $mtf->frm_last_post_date) {
			$mtf->last_post_id = $mtf->id;
		}		

		if ($mtf->root_msg_id == $mtf->id) {	/* New thread. */
			th_new_rebuild($mtf->forum_id, $mtf->thread_id, $mtf->thread_opt & (2|4|8));
			$threads = 1;
		} else {				/* Reply to thread. */
			if ($mtf->post_stamp > $mtf->last_post_date) {
				th_inc_post_count($mtf->thread_id, 1, $mtf->id, $mtf->post_stamp);
			} else {
				th_inc_post_count($mtf->thread_id, 1);
			}
			th_reply_rebuild($mtf->forum_id, $mtf->thread_id, $mtf->thread_opt & (2|4|8));
			$threads = 0;
		}

		/* Update forum thread & post count as well as last_post_id field. */
		q('UPDATE fud26_forum SET post_count=post_count+1, thread_count=thread_count+'. $threads .', last_post_id='. $mtf->last_post_id .' WHERE id='. $mtf->forum_id);

		if ($mtf->poll_id) {
			poll_activate($mtf->poll_id, $mtf->forum_id);
		}

		$mtf->body = read_msg_body($mtf->foff, $mtf->length, $mtf->file_id);

		if ($GLOBALS['FUD_OPT_1'] & 16777216) {	// FORUM_SEARCH enabled?
			index_text((strncasecmp($mtf->subject, 'Re: ', 4) ? $mtf->subject : ''), $mtf->body, $mtf->id);
		}

		/* Handle notifications. */
		if (!($GLOBALS['FUD_OPT_3'] & 1048576)) {	// not DISABLE_NOTIFICATION_EMAIL
			if ($mtf->root_msg_id == $mtf->id || $GLOBALS['FUD_OPT_3'] & 16384) {	// FORUM_NOTIFY_ALL
				if (empty($mtf->frm_last_post_date)) {
					$mtf->frm_last_post_date = 0;
				}

				/* Send new thread notifications to forum subscribers. */
				$to = db_all('SELECT u.email
						FROM fud26_forum_notify fn
						INNER JOIN fud26_users u ON fn.user_id=u.id AND '. q_bitand('u.users_opt', 134217728) .' = 0
						INNER JOIN fud26_group_cache g1 ON g1.user_id=2147483647 AND g1.resource_id='. $mtf->forum_id .
						($GLOBALS['FUD_OPT_3'] & 64 ? ' LEFT JOIN fud26_forum_read r ON r.forum_id=fn.forum_id AND r.user_id=fn.user_id ' : '').
						' LEFT JOIN fud26_group_cache g2 ON g2.user_id=fn.user_id AND g2.resource_id='. $mtf->forum_id .
						' LEFT JOIN fud26_mod mm ON mm.forum_id='. $mtf->forum_id .' AND mm.user_id=u.id
					WHERE
						fn.forum_id='. $mtf->forum_id .' AND fn.user_id!='. (int)$mtf->poster_id .
						($GLOBALS['FUD_OPT_3'] & 64 ? ' AND (CASE WHEN (r.last_view IS NULL AND (u.last_read=0 OR u.last_read >= '. $mtf->frm_last_post_date .')) OR r.last_view > '. $mtf->frm_last_post_date .' THEN 1 ELSE 0 END)=1 ' : '').
						' AND ('. q_bitand('COALESCE(g2.group_cache_opt, g1.group_cache_opt)', 2) .' > 0 OR '. q_bitand('u.users_opt', 1048576) .' > 0 OR mm.id IS NOT NULL)'.
						' AND '. q_bitand('u.users_opt', 65536) .' = 0');
				if ($GLOBALS['FUD_OPT_3'] & 16384) {
					$notify_type = 'thr';
				} else {
					$notify_type = 'frm';
				}
			} else {
				$to = array();
			}
			if ($mtf->root_msg_id != $mtf->id) {
				/* Send new reply notifications to thread subscribers. */
				$tmp = db_all('SELECT u.email
						FROM fud26_thread_notify tn
						INNER JOIN fud26_users u ON tn.user_id=u.id AND '. q_bitand('u.users_opt', 134217728) .' = 0
						INNER JOIN fud26_group_cache g1 ON g1.user_id=2147483647 AND g1.resource_id='. $mtf->forum_id .
						($GLOBALS['FUD_OPT_3'] & 64 ? ' LEFT JOIN fud26_read r ON r.thread_id=tn.thread_id AND r.user_id=tn.user_id ' : '').
						' LEFT JOIN fud26_group_cache g2 ON g2.user_id=tn.user_id AND g2.resource_id='. $mtf->forum_id .
						' LEFT JOIN fud26_mod mm ON mm.forum_id='. $mtf->forum_id .' AND mm.user_id=u.id
					WHERE
						tn.thread_id='. $mtf->thread_id .' AND tn.user_id!='. (int)$mtf->poster_id .
						($GLOBALS['FUD_OPT_3'] & 64 ? ' AND (r.msg_id='. $mtf->last_post_id .' OR (r.msg_id IS NULL AND '. $mtf->post_stamp .' > u.last_read)) ' : '').
						' AND ('. q_bitand('COALESCE(g2.group_cache_opt, g1.group_cache_opt)', 2) .' > 0 OR '. q_bitand('u.users_opt', 1048576) .' > 0 OR mm.id IS NOT NULL)'.
						' AND '. q_bitand('u.users_opt', 65536) .' = 0');
				$to = !$to ? $tmp : array_unique(array_merge($to, $tmp));
				$notify_type = 'thr';
			}

			if ($mtf->forum_opt & 64) {	// always_notify_mods
				$tmp = db_all('SELECT u.email FROM fud26_mod mm INNER JOIN fud26_users u ON u.id=mm.user_id WHERE mm.forum_id='. $mtf->forum_id);
				$to = !$to ? $tmp : array_unique(array_merge($to, $tmp));
			}

			if ($to) {
				send_notifications($to, $mtf->id, $mtf->subject, $mtf->alias, $notify_type, ($notify_type == 'thr' ? $mtf->thread_id : $mtf->forum_id), $mtf->frm_name, $mtf->forum_id);
			}
		}

		// Handle Mailing List and/or Newsgroup syncronization.
		if (($mtf->nntp_id || $mtf->mlist_id) && !$mtf->mlist_msg_id) {
			fud_use('email_msg_format.inc', 1);

			$from = $mtf->poster_id ? reverse_fmt($mtf->real_name) .' <'. $mtf->email .'>' : $GLOBALS['ANON_NICK'] .' <'. $GLOBALS['NOTIFY_FROM'] .'>';
			$body = $mtf->body . (($mtf->msg_opt & 1 && $mtf->sig) ? "\n-- \n" . $mtf->sig : '');
			$body = plain_text($body, '<cite>', '</cite><blockquote>', '</blockquote>');
			$mtf->subject = reverse_fmt($mtf->subject);

			if ($mtf->reply_to) {
				// Get the parent message's Message-ID:
				if ( !($replyto_id = q_singleval('SELECT mlist_msg_id FROM fud26_msg WHERE id='. $mtf->reply_to))) {
					fud_logerror('WARNING: Send reply with no Message-ID. The import script is not running or may be lagging.', 'fud_errors');
				}
			} else {
				$replyto_id = 0;
			}

			if ($mtf->attach_cnt) {
				$r = uq('SELECT a.id, a.original_name, COALESCE(m.mime_hdr, \'application/octet-stream\')
						FROM fud26_attach a
						LEFT JOIN fud26_mime m ON a.mime_type=m.id
						WHERE a.message_id='. $mtf->id .' AND a.attach_opt=0');
				while ($ent = db_rowarr($r)) {
					$attach[$ent[1]] = file_get_contents($GLOBALS['FILE_STORE'] . $ent[0] .'.atch');
					if ($mtf->mlist_id) {
						$attach_mime[$ent[1]] = $ent[2];
					}
				}
				unset($r);
			} else {
				$attach_mime = $attach = null;
			}

			if ($mtf->nntp_id) {	// Push out to usenet group.
				fud_use('nntp.inc', true);

				$nntp_adm = db_sab('SELECT * FROM fud26_nntp WHERE id='. $mtf->nntp_id);
				if (!empty($nntp_adm->custom_sig)) {	// Add signature marker.
					$nntp_adm->custom_sig = "\n-- \n". $nntp_adm->custom_sig;
				}

				$nntp = new fud_nntp;
				$nntp->server = $nntp_adm->server;
				$nntp->newsgroup = $nntp_adm->newsgroup;
				$nntp->port = $nntp_adm->port;
				$nntp->timeout = $nntp_adm->timeout;
				$nntp->nntp_opt = $nntp_adm->nntp_opt;
				$nntp->user = $nntp_adm->login;
				$nntp->pass = $nntp_adm->pass;

				define('sql_p', 'fud26_');

				$lock = $nntp->get_lock();
				$nntp->post_message($mtf->subject, $body.$nntp_adm->custom_sig, $from, $mtf->id, $replyto_id, $attach);
				$nntp->close_connection();
				$nntp->release_lock($lock);
			} else {	// Push out to mailing list.
				fud_use('mlist_post.inc', true);

				$r = db_saq('SELECT name, additional_headers, custom_sig FROM fud26_mlist WHERE id='. $mtf->mlist_id);
				if (!empty($r[2])) {	// Add signature marker.
					$r[2] = "\n-- \n". $r[2];
				}
				mail_list_post($r[0], $from, $mtf->subject, $body.$r[2], $mtf->id, $replyto_id, $attach, $attach_mime, $r[1]);
			}
		}
	}
}

function write_body($data, &$len, &$offset, $fid)
{
	$MAX_FILE_SIZE = 2140000000;

	$len = strlen($data);
	$i = 1;

	db_lock('fud26_fl_'. $fid .' WRITE');

	$s = $fid * 10000;
	$e = $s + 100;
	
	while ($s < $e) {
		$fp = fopen($GLOBALS['MSG_STORE_DIR'] .'msg_'. $s, 'ab');
		if (!$fp) {
			exit('FATAL ERROR: could not open message store for forum id#'. $s ."<br />\n");
		}
		fseek($fp, 0, SEEK_END);
		if (!($off = ftell($fp))) {
			$off = __ffilesize($fp);
		}
		if (!$off || ($off + $len) < $MAX_FILE_SIZE) {
			break;
		}
		fclose($fp);
		$s++;
	}

	if (fwrite($fp, $data) !== $len) {
		if ($fid) {
			db_unlock();
		}
		exit("FATAL ERROR: system has ran out of disk space.<br />\n");
	}
	fclose($fp);

	db_unlock();

	if (!$off) {
		@chmod('msg_'. $s, ($GLOBALS['FUD_OPT_2'] & 8388608 ? 0600 : 0666));
	}
	$offset = $off;

	return $s;
}

function trim_html($str, $maxlen)
{
	$n = strlen($str);
	$ln = 0;
	$tree = array();
	for ($i = 0; $i < $n; $i++) {
		if ($str[$i] != '<') {
			$ln++;
			if ($ln > $maxlen) {
				break;
			}
			continue;
		}

		if (($p = strpos($str, '>', $i)) === false) {
			break;
		}

		for ($k = $i; $k < $p; $k++) {
			switch ($str[$k]) {
				case ' ':
				case "\r":
				case "\n":
				case "\t":
				case '>':
					break 2;
			}
		}

		if ($str[$i+1] == '/') {
			$tagname = strtolower(substr($str, $i+2, $k-$i-2));
			if (@end($tagindex[$tagname])) {
				$k = key($tagindex[$tagname]);
				unset($tagindex[$tagname][$k], $tree[$k]);
			}
		} else {
			$tagname = strtolower(substr($str, $i+1, $k-$i-1));
			switch ($tagname) {
				case 'br':
				case 'img':
				case 'meta':
					break;
				default:
					$tree[] = $tagname;
					end($tree);
					$tagindex[$tagname][key($tree)] = 1;
			}
		}
		$i = $p;
	}

	$data = substr($str, 0, $i);
	if ($tree) {
		foreach (array_reverse($tree) as $v) {
			$data .= '</'. $v .'>';
		}
	}

	return $data;
}

function make_email_message(&$body, &$obj, $iemail_unsub)
{
	$TITLE_EXTRA = $iemail_poll = $iemail_attach = '';
	if ($obj->poll_cache) {
		$pl = unserialize($obj->poll_cache);
		if (!empty($pl)) {
			foreach ($pl as $k => $v) {
				$length = ($v[1] && $obj->total_votes) ? round($v[1] / $obj->total_votes * 100) : 0;
				$iemail_poll .= '<tr class="'.alt_var('msg_poll_alt_clr','RowStyleB','RowStyleA').'"><td>'.$k.'.</td><td>'.$v[0].'</td><td><img src="theme/velopiter/images/poll_pix.gif" alt="" height="10" width="'.$length.'" /> '.$v[1].' / '.$length.'%</td></tr>';
			}
			$iemail_poll = '<table cellspacing="1" cellpadding="2" class="PollTable">
<tr><th colspan="3">'.$obj->poll_name.'<img src="blank.gif" alt="" height="1" width="10" style="white-space: nowrap" /><span class="small">[ '.$obj->total_votes.' '.convertPlural($obj->total_votes, array('голос','голоса','голосов')).' ]</span></th></tr>
'.$iemail_poll.'
</table><br /><br />';
		}
	}
	if ($obj->attach_cnt && $obj->attach_cache) {
		$atch = unserialize($obj->attach_cache);
		if (!empty($atch)) {
			foreach ($atch as $v) {
				$sz = $v[2] / 1024;
				$sz = $sz < 1000 ? number_format($sz, 2) .'KB' : number_format($sz/1024, 2) .'MB';
				$iemail_attach .= '<tr>
<td class="vm"><a href="index.php?t=getfile&amp;id='.$v[0].'"><img alt="" src="'.$GLOBALS['WWW_ROOT'].'images/mime/'.$v[4].'" /></a></td>
<td><span class="GenText fb">Вложение:</span> <a href="index.php?t=getfile&amp;id='.$v[0].'">'.$v[1].'</a><br />
<span class="SmallText">(Размер: '.$sz.', Загружено '.convertPlural($v[3], array(''.$v[3].' раз',''.$v[3].' раза',''.$v[3].' раз')).')</span></td></tr>';
			}
			$iemail_attach = '<br /><br />
<table border="0" cellspacing="0" cellpadding="2">
'.$iemail_attach.'
</table>';
		}
	}

	if ($GLOBALS['FUD_OPT_2'] & 32768 && defined('_rsid')) {
		$pfx = str_repeat('/', substr_count(_rsid, '/'));
	}

	// Remove all JavaScript. Spam filters like SpamAssassin don't like them.
	return preg_replace('#<script[^>]*>.*?</script>#is', '', '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr" lang="ru" xml:lang="ru">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>'.$GLOBALS['FORUM_TITLE'].$TITLE_EXTRA.'</title>
<base href="'.$GLOBALS['WWW_ROOT'].'" />
<script type="text/javascript" src="js/jquery.js"></script>
<script type="text/javascript" src="js/lib.js"></script>
<link rel="stylesheet" href="theme/velopiter/forum.css" type="text/css" />
</head>
<body>
<table class="wa" border="0" cellspacing="3" cellpadding="5"><tr><td class="ForumBackground">
<table cellspacing="1" cellpadding="2" class="ContentTable">
<tr class="RowStyleB">
	<td width="33%"><b>Тема:</b> '.$obj->subject.'</td>
	<td width="33%"><b>Автор:</b> '.$obj->alias.'</td>
	<td width="33%"><b>Дата:</b> '.strftime("%a, %d %B %Y %H:%M", $obj->post_stamp).'</td>
</tr>
<tr class="RowStyleA">
	<td colspan="3">
	'.$iemail_poll.'
	'.$body.'
	'.$iemail_attach.'
	</td>
</tr>
<tr class="RowStyleB">
	<td colspan="3">
	[ <a href="index.php?t=post&reply_to='.$obj->id.'">Ответ</a> ][ <a href="index.php?t=post&reply_to='.$obj->id.'&quote=true">Цитата</a> ][ <a href="index.php?t=rview&goto='.$obj->id.'#msg_'.$obj->id.'">Просмотр темы/сообщения</a> ]'.$iemail_unsub.'
	</td>
</tr>
</table>
</td></tr></table></body></html>');
}

function poll_cache_rebuild($poll_id)
{
	if (!$poll_id) {
		return;
	}

	$data = array();
	$c = uq('SELECT id, name, count FROM fud26_poll_opt WHERE poll_id='. $poll_id);
	while ($r = db_rowarr($c)) {
		$data[$r[0]] = array($r[1], $r[2]);
	}
	unset($c);

	if ($data) {
		return serialize($data);
	} else {
		return;
	}
}

function send_notifications($to, $msg_id, $thr_subject, $poster_login, $id_type, $id, $frm_name, $frm_id)
{
	if (!$to) {
		return;
	}

	$goto_url['email'] = ''.$GLOBALS['WWW_ROOT'].'index.php?t=rview&goto='. $msg_id .'#msg_'. $msg_id;
	$CHARSET = $GLOBALS['CHARSET'];
	if ($GLOBALS['FUD_OPT_2'] & 64) {	// NOTIFY_WITH_BODY
		$munge_newlines = 0;
		$obj = db_sab('SELECT p.total_votes, p.name AS poll_name, m.reply_to, m.subject, m.id, m.post_stamp, m.poster_id, m.foff, m.length, m.file_id, u.alias, m.attach_cnt, m.attach_cache, m.poll_cache FROM fud26_msg m LEFT JOIN fud26_users u ON m.poster_id=u.id LEFT JOIN fud26_poll p ON m.poll_id=p.id WHERE m.id='. $msg_id .' AND m.apr=1');

		if (!$obj->alias) { /* anon user */
			$obj->alias = htmlspecialchars($GLOBALS['ANON_NICK']);
		}

		$headers  = "MIME-Version: 1.0\r\n";
		if ($obj->reply_to) {
			$headers .= 'In-Reply-To: '. $obj->reply_to ."\r\n";
		}
		$headers .= 'List-Id: '. $frm_id .'.'. (isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : 'localhost') ."\r\n";
		$split = get_random_value(128);
		$headers .= "Content-Type: multipart/alternative;\n  boundary=\"------------". $split ."\"\r\n";
		$boundry = "\r\n--------------". $split ."\r\n";

		$pfx = '';
		if ($GLOBALS['FUD_OPT_2'] & 32768 && !empty($_SERVER['PATH_INFO'])) {
			if ($GLOBALS['FUD_OPT_1'] & 128) {
				$pfx .= '0/';
			}
			if ($GLOBALS['FUD_OPT_2'] & 8192) {
				$pfx .= '0/';
			}
		}

		$plain_text = read_msg_body($obj->foff, $obj->length, $obj->file_id);
		$iemail_unsub = html_entity_decode($id_type == 'thr' ? '[ <a href="index.php?t=rview&th='.$id.'">Отписаться от этой темы</a> ]' : '[ <a href="index.php?t=rview&frm_id='.$id.'">Отписаться от этого форума</a> ]');

		$body_email = $boundry .'Content-Type: text/plain; charset='. $CHARSET ."; format=flowed\r\nContent-Transfer-Encoding: 8bit\r\n\r\n" . html_entity_decode(strip_tags($plain_text)) . "\r\n\r\n" . html_entity_decode('Для участия в дискуссии следуйте по указанной ссылке:') .' '. ''.$GLOBALS['WWW_ROOT'].'index.php?t=rview&'. ($id_type == 'thr' ? 'th' : 'frm_id') .'='. $id ."\r\n".
				$boundry .'Content-Type: text/html; charset='. $CHARSET ."\r\nContent-Transfer-Encoding: 8bit\r\n\r\n". make_email_message($plain_text, $obj, $iemail_unsub) ."\r\n". substr($boundry, 0, -2) ."--\r\n";
	} else {
		$munge_newlines = 1;
		$headers = '';
	}

	$thr_subject = reverse_fmt($thr_subject);
	$poster_login = reverse_fmt($poster_login);

	if ($id_type == 'thr') {
		$subj = html_entity_decode('Новый ответ на сообщение '.$thr_subject.' от '.$poster_login);

		if (!isset($body_email)) {
			$unsub_url['email'] = ''.$GLOBALS['WWW_ROOT'].'index.php?t=rview&th='. $id .'&notify=1&opt=off';
			$body_email = html_entity_decode('Для просмотра непрочитанных ответов следуйте по указанной ссылке\n'.$goto_url['email'].'\n\nЕсли вы не хотите получать извещения об ответах в этой теме в дальнейшем, следуйте по этой ссылке:\n'.$unsub_url['email']);
		}
	} else if ($id_type == 'frm') {
		$frm_name = reverse_fmt($frm_name);

		$subj = html_entity_decode('Новая тема в форуме '.$frm_name.', озаглавленная '.$thr_subject.' от '.$poster_login);

		if (!isset($body_email)) {
			$unsub_url['email'] = ''.$GLOBALS['WWW_ROOT'].'index.php?t=rview&unsub=1&frm_id='. $id;
			$body_email = html_entity_decode('Для просмотра темы следуйте по указанной ссылке:\n'.$goto_url['email'].'\n\nЕсли вы не хотите получать извещения о новых темах в этом форуме в дальнейшем, следуйте по этой ссылке:\n'.$unsub_url['email']);
		}
	}

	send_email($GLOBALS['NOTIFY_FROM'], $to, $subj, $body_email, $headers, $munge_newlines);
}function logaction($user_id, $res, $res_id=0, $action=null)
{
	q('INSERT INTO fud26_action_log (logtime, logaction, user_id, a_res, a_res_id)
		VALUES('. __request_timestamp__ .', '. ssn($action) .', '. $user_id .', '. ssn($res) .', '. (int)$res_id .')');
}function th_lock($id, $lck)
{
	q('UPDATE fud26_thread SET thread_opt=('. (!$lck ? q_bitand('thread_opt', ~1) : q_bitor('thread_opt', 1)) .') WHERE id='. $id);
}

function th_inc_view_count($id)
{
	global $plugin_hooks;
	if (isset($plugin_hooks['CACHEGET'], $plugin_hooks['CACHESET'])) {
		// Increment view counters in cache.
		$th_views = call_user_func($plugin_hooks['CACHEGET'][0], 'th_views');
		$th_views[$id] = (!empty($th_views) && array_key_exists($id, $th_views)) ? $th_views[$id]+1 : 1;

		if ($th_views[$id] > 10 || count($th_views) > 100) {
			call_user_func($plugin_hooks['CACHESET'][0], 'th_views', array());	// Clear cache.
			// Start delayed database updating.
			foreach($th_views as $id => $views) {
				q('UPDATE fud26_thread SET views=views+'. $views .' WHERE id='. $id);
			}
		} else {
			call_user_func($plugin_hooks['CACHESET'][0], 'th_views', $th_views);
		}
	} else {
		// No caching plugins available.
		q('UPDATE fud26_thread SET views=views+1 WHERE id='. $id);
	}
}

function th_inc_post_count($id, $r, $lpi=0, $lpd=0)
{
	if ($lpi && $lpd) {
		q('UPDATE fud26_thread SET replies=replies+'. $r .', last_post_id='. $lpi .', last_post_date='. $lpd .' WHERE id='. $id);
	} else {
		q('UPDATE fud26_thread SET replies=replies+'. $r .' WHERE id='. $id);
	}
}$GLOBALS['__SML_CHR_CHK__'] = array("\n"=>1, "\r"=>1, "\t"=>1, ' '=>1, ']'=>1, '['=>1, '<'=>1, '>'=>1, '\''=>1, '"'=>1, '('=>1, ')'=>1, '.'=>1, ','=>1, '!'=>1, '?'=>1);

function smiley_to_post($text)
{
	$text_l = strtolower($text);
	include $GLOBALS['FORUM_SETTINGS_PATH'] .'sp_cache';

	/* remove all non-formatting blocks */
	foreach (array('</pre>'=>'<pre>', '</span>' => '<span name="php">') as $k => $v) {
		$p = 0;
		while (($p = strpos($text_l, $v, $p)) !== false) {
			if (($e = strpos($text_l, $k, $p)) === false) {
				$p += 5;
				continue;
			}
			$text_l = substr_replace($text_l, str_repeat(' ', $e - $p), $p, ($e - $p));
			$p = $e;
		}
	}

	foreach ($SML_REPL as $k => $v) {
		$a = 0;
		$len = strlen($k);
		while (($a = strpos($text_l, $k, $a)) !== false) {
			if ((!$a || isset($GLOBALS['__SML_CHR_CHK__'][$text_l[$a - 1]])) && ((@$ch = $text_l[$a + $len]) == '' || isset($GLOBALS['__SML_CHR_CHK__'][$ch]))) {
				$text_l = substr_replace($text_l, $v, $a, $len);
				$text = substr_replace($text, $v, $a, $len);
				$a += strlen($v) - $len;
			} else {
				$a += $len;
			}
		}
	}

	return $text;
}

function post_to_smiley($text)
{
	/* include once since draw_post_smiley_cntrl() may use it too */
	include_once $GLOBALS['FORUM_SETTINGS_PATH'].'ps_cache';
	if (isset($PS_SRC)) {
		$GLOBALS['PS_SRC'] = $PS_SRC;
		$GLOBALS['PS_DST'] = $PS_DST;
	} else {
		$PS_SRC = $GLOBALS['PS_SRC'];
		$PS_DST = $GLOBALS['PS_DST'];
	}

	/* check for emoticons */
	foreach ($PS_SRC as $k => $v) {
		if (strpos($text, $v) === false) {
			unset($PS_SRC[$k], $PS_DST[$k]);
		}
	}

	return $PS_SRC ? str_replace($PS_SRC, $PS_DST, $text) : $text;
}function read_msg_body($off, $len, $id)
{
	if ($off == -1) {	// Fetch from DB and return.
		return q_singleval('SELECT data FROM fud26_msg_store WHERE id='. $id);
	}

	if (!$len) {	// Empty message.
		return;
	}

	// Open file if it's not already open.
	if (!isset($GLOBALS['__MSG_FP__'][$id])) {
		$GLOBALS['__MSG_FP__'][$id] = fopen($GLOBALS['MSG_STORE_DIR'] .'msg_'. $id, 'rb');
	}

	// Read from file.
	fseek($GLOBALS['__MSG_FP__'][$id], $off);
	return fread($GLOBALS['__MSG_FP__'][$id], $len);
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
}function poll_delete($id)
{
	if (!$id) {
		return;
	}

	q('UPDATE fud26_msg SET poll_id=0 WHERE poll_id='. $id);
	q('DELETE FROM fud26_poll_opt WHERE poll_id='. $id);
	q('DELETE FROM fud26_poll_opt_track WHERE poll_id='. $id);
	q('DELETE FROM fud26_poll WHERE id='. $id);
}

function poll_fetch_opts($id)
{
	$a = array();
	$c = uq('SELECT id,name FROM fud26_poll_opt WHERE poll_id='. $id);
	while ($r = db_rowarr($c)) {
		$a[$r[0]] = $r[1];
	}
	unset($c);

	return $a;
}

function poll_del_opt($id, $poll_id)
{
	q('DELETE FROM fud26_poll_opt WHERE poll_id='. $poll_id .' AND id='. $id);
	q('DELETE FROM fud26_poll_opt_track WHERE poll_id='. $poll_id .' AND poll_opt='. $id);
	q('UPDATE fud26_poll SET total_votes=(SELECT SUM(count) FROM fud26_poll_opt WHERE poll_id='. $poll_id .') WHERE id='. $poll_id);
}

function poll_activate($poll_id, $frm_id)
{
	q('UPDATE fud26_poll SET forum_id='. $frm_id .' WHERE id='. $poll_id);
}

function poll_sync($poll_id, $name, $max_votes, $expiry)
{
	q('UPDATE fud26_poll SET name='. _esc(htmlspecialchars($name)) .', expiry_date='. (int)$expiry .', max_votes='. (int)$max_votes .' WHERE id='. $poll_id);
}

function poll_add($name, $max_votes, $expiry, $uid=_uid)
{
	return db_qid('INSERT INTO fud26_poll (name, owner, creation_date, expiry_date, max_votes) VALUES ('. _esc(htmlspecialchars($name)) .', '. $uid .', '. __request_timestamp__ .', '. (int)$expiry .', '. (int)$max_votes .')');
}

function poll_opt_sync($id, $name)
{
	q('UPDATE fud26_poll_opt SET name='. _esc($name) .' WHERE id='. $id);
}

function poll_opt_add($name, $poll_id)
{
	return db_qid('INSERT INTO fud26_poll_opt (poll_id,name) VALUES('. $poll_id .', '. _esc($name) .')');
}

function poll_validate($poll_id, $msg_id)
{
	if (($mid = (int) q_singleval('SELECT id FROM fud26_msg WHERE poll_id='. $poll_id)) && $mid != $msg_id) {
		return 0;
	}
	return $poll_id;
}function safe_attachment_copy($source, $id, $ext)
{
	$loc = $GLOBALS['FILE_STORE'] . $id .'.atch';
	if (!$ext && !move_uploaded_file($source, $loc)) {
		error_dialog('unable to move uploaded file', 'From: '. $source .' To: '. $loc, 'ATCH');
	} else if ($ext && !copy($source, $loc)) {
		error_dialog('unable to handle file attachment', 'From: '. $source .' To: '. $loc, 'ATCH');
	}
	@unlink($source);

	@chmod($loc, ($GLOBALS['FUD_OPT_2'] & 8388608 ? 0600 : 0666));

	return $loc;
}

function attach_add($at, $owner, $attach_opt=0, $ext=0)
{
	$id = db_qid('INSERT INTO fud26_attach (location, message_id, original_name, owner, attach_opt, mime_type,fsize) '.
		q_limit('SELECT \'\' AS location, 0 AS message_id, '. _esc($at['name']) .' AS original_name, '. $owner .' AS owner, '. $attach_opt .' AS attach_opt, id AS mime_type, '. $at['size'] .' AS fsize 
			FROM fud26_mime WHERE fl_ext IN(\'*\', '. _esc(substr(strrchr($at['name'], '.'), 1)) .')
			ORDER BY fl_ext DESC'
		, 1)
	);

	safe_attachment_copy($at['tmp_name'], $id, $ext);

	return $id;
}

function attach_finalize($attach_list, $mid, $attach_opt=0)
{
	$id_list = '';
	$attach_count = 0;

	$tbl = !$attach_opt ? 'msg' : 'pmsg';

	foreach ($attach_list as $key => $val) {
		if (!$val) {
			@unlink($GLOBALS['FILE_STORE'] . (int)$key .'.atch');
		} else {
			$attach_count++;
			$id_list .= (int)$key .',';
		}
	}

	if ($id_list) {
		$id_list = substr($id_list, 0, -1);
		$cc = q_concat(_esc($GLOBALS['FILE_STORE']), 'id', _esc('.atch'));
		q('UPDATE fud26_attach SET location='. $cc .', message_id='. $mid .' WHERE id IN('. $id_list .') AND attach_opt='. $attach_opt);
		$id_list = ' AND id NOT IN('. $id_list .')';
	} else {
		$id_list = '';
	}

	/* delete any unneeded (removed, temporary) attachments */
	q('DELETE FROM fud26_attach WHERE message_id='. $mid .' '. $id_list);

	if (!$attach_opt && ($atl = attach_rebuild_cache($mid))) {
		q('UPDATE fud26_msg SET attach_cnt='. $attach_count .', attach_cache='. _esc(serialize($atl)) .' WHERE id='. $mid);
	}

	if (!empty($GLOBALS['usr']->sid)) {
		ses_putvar((int)$GLOBALS['usr']->sid, null);
	}
}

function attach_rebuild_cache($id)
{
	$ret = array();
	$c = uq('SELECT a.id, a.original_name, a.fsize, a.dlcount, COALESCE(m.icon, \'unknown.gif\') FROM fud26_attach a LEFT JOIN fud26_mime m ON a.mime_type=m.id WHERE message_id='. $id .' AND attach_opt=0');
	while ($r = db_rowarr($c)) {
		$ret[] = $r;
	}
	unset($c);
	return $ret;
}

function attach_inc_dl_count($id, $mid)
{
	q('UPDATE fud26_attach SET dlcount=dlcount+1 WHERE id='. $id);
	if (($a = attach_rebuild_cache($mid))) {
		q('UPDATE fud26_msg SET attach_cache='. _esc(serialize($a)) .' WHERE id='. $mid);
	}
}function validate_email($email)
{
	$bits = explode('@', $email);
	if (count($bits) != 2) {
		return 1;
	}
	$doms = explode('.', $bits[1]);
	$last = array_pop($doms);

	// Validate domain extension 2-4 characters A-Z
	if (!preg_match('!^[A-Za-z]{2,4}$!', $last)) {
		return 1;
	}

	// (Sub)domain name 63 chars long max A-Za-z0-9_
	foreach ($doms as $v) {
		if (!$v || strlen($v) > 63 || !preg_match('!^[A-Za-z0-9_-]+$!', $v)) {
			return 1;
		}
	}

	// Now the hard part, validate the e-mail address itself.
	if (!$bits[0] || strlen($bits[0]) > 255 || !preg_match('!^[-A-Za-z0-9_.+{}~\']+$!', $bits[0])) {
		return 1;
	}
}

function encode_subject($text)
{
	if (preg_match('![\x7f-\xff]!', $text)) {
		$text = '=?utf-8?B?'. base64_encode($text) .'?=';
	}

	return $text;
}

function send_email($from, $to, $subj, $body, $header='', $munge_newlines=1)
{
	if (empty($to)) {
		return;
	}

	/* HTML entities check. */
	if (strpos($subj, '&') !== false) {
		$subj = html_entity_decode($subj);
	}

	if ($header) {
		$header = "\n" . str_replace("\r", '', $header);
	}
	$extra_header = '';
	if (strpos($header, 'MIME-Version') === false) {
		$extra_header = "\nMIME-Version: 1.0\nContent-Type: text/plain; charset=utf-8\nContent-Transfer-Encoding: 8bit". $header;
	}
	$header = 'From: '. $from ."\nErrors-To: ". $from ."\nReturn-Path: ". $from ."\nX-Mailer: FUDforum v". $GLOBALS['FORUM_VERSION']. $extra_header. $header;

	$body = str_replace("\r", '', $body);
	if ($munge_newlines) {
		$body = str_replace('\n', "\n", $body);
	}
	$subj = encode_subject($subj);

	if (defined('forum_debug')) {
		logaction(_uid, 'SEND EMAIL', 0, 'To=['. implode(',', (array)$to) .']<br />Subject=['. $subj .']<br />Headers=['. str_replace("\n", '<br />', htmlentities($header)) .']<br />Message=['. $body .']');
	}

	if ($GLOBALS['FUD_OPT_1'] & 512) {
		if (!class_exists('fud_smtp')) {
			fud_use('smtp.inc');
		}
		$smtp = new fud_smtp;
		$smtp->msg = str_replace(array('\n', "\n."), array("\n", "\n.."), $body);
		$smtp->subject = encode_subject($subj);
		$smtp->to = $to;
		$smtp->from = $from;
		$smtp->headers = $header;
		$smtp->send_smtp_email();
		return;
	}

	foreach ((array)$to as $email) {
		if (!@mail($email, $subj, $body, $header)) {
			fud_logerror('Your system didn\'t accept E-mail ['. $subj .'] to ['. $email .'] for delivery.', 'fud_errors');
		}
	}
}$GLOBALS['__revfs'] = array('&quot;', '&lt;', '&gt;', '&amp;');
$GLOBALS['__revfd'] = array('"', '<', '>', '&');

function reverse_fmt($data)
{
	$s = $d = array();
	foreach ($GLOBALS['__revfs'] as $k => $v) {
		if (strpos($data, $v) !== false) {
			$s[] = $v;
			$d[] = $GLOBALS['__revfd'][$k];
		}
	}

	return $s ? str_replace($s, $d, $data) : $data;
}function get_host($ip)
{
	if (!$ip || $ip == '0.0.0.0') {
		return;
	}

	$name = gethostbyaddr($ip);

	if ($name == $ip) {
		$name = substr($name, 0, strrpos($name, '.')) .'*';
	} else if (substr_count($name, '.') > 1) {
		$name = '*'. substr($name, strpos($name, '.')+1);
	}

	return $name;
}function text_to_worda($text)
{
	$a = array();
	$text = strtolower(strip_tags(reverse_fmt($text)));
	$lang = $GLOBALS['usr']->lang;

	if (@preg_match('/\p{L}/u', 'a') == 1) {	// PCRE unicode support is turned on
		// Match utf-8 words (remove the \p{N} if you don't want to index words with numbers).
		preg_match_all("/\p{L}[\p{L}\p{N}\p{Mn}\p{Pd}'\x{2019}]*/u", $text, $t1);
		foreach ($t1[0] as $v) {
			if ($lang != 'chinese' && $lang != 'japanese' && $lang != 'korean') {
				if (isset($v[51]) || !isset($v[2])) continue;   // Word too short or long.
			}
			$a[] = _esc($v);
		}
		return $a;
	}

	/* PCRE unicode support is turned off, fallback to old non-utf8 algorithm. */
	$t1 = array_unique(str_word_count($text, 1));
	foreach ($t1 as $v) {
		if (isset($v[51]) || !isset($v[2])) continue;	// Word too short or long.
		$a[] = _esc($v);
	}
	return $a;
}

function index_text($subj, $body, $msg_id)
{
	/* Remove stuff in [quote] tags. */
	while (preg_match('!<cite>(.*?)</cite><blockquote>(.*?)</blockquote>!is', $body)) {
		$body = preg_replace('!<cite>(.*?)</cite><blockquote>(.*?)</blockquote>!is', '', $body);
	}

	if ($subj && ($w1 = text_to_worda($subj))) {
		$w2 = array_merge($w1, text_to_worda($body));
	} else {
		$w2 = text_to_worda($body);
	}

	if (!$w2) {
		return;
	}

	$w2 = array_unique($w2);

	ins_m('fud26_search', 'word', 'text', $w2);
	if ($subj && $w1) {
		db_li('INSERT INTO fud26_title_index (word_id, msg_id) SELECT id, '. $msg_id .' FROM fud26_search WHERE word IN('. implode(',', $w1) .')', $ef);
	}
	db_li('INSERT INTO fud26_index (word_id, msg_id) SELECT id, '. $msg_id .' FROM fud26_search WHERE word IN('. implode(',', $w2) .')', $ef);
}function th_add($root, $forum_id, $last_post_date, $thread_opt, $orderexpiry, $replies=0, $views=0, $lpi=0, $descr='')
{
	if (!$lpi) {
		$lpi = $root;
	}

	return db_qid('INSERT INTO
		fud26_thread
			(forum_id, root_msg_id, last_post_date, replies, views, rating, last_post_id, thread_opt, orderexpiry, tdescr)
		VALUES
			('. $forum_id .', '. $root .', '. $last_post_date .', '. $replies .', '. $views .', 0, '. $lpi .', '. $thread_opt .', '. $orderexpiry.','. _esc($descr) .')');
}

function th_move($id, $to_forum, $root_msg_id, $forum_id, $last_post_date, $last_post_id, $descr)
{
	if (!db_locked()) {
		if ($to_forum != $forum_id) {
			$lock = 'fud26_tv_'. $to_forum .' WRITE, fud26_tv_'. $forum_id;
		} else {
			$lock = 'fud26_tv_'. $to_forum;
		}
		
		db_lock('fud26_poll WRITE, '. $lock .' WRITE, fud26_thread WRITE, fud26_forum WRITE, fud26_msg WRITE');
		$ll = 1;
	}
	$msg_count = q_singleval('SELECT count(*) FROM fud26_thread LEFT JOIN fud26_msg ON fud26_msg.thread_id=fud26_thread.id WHERE fud26_msg.apr=1 AND fud26_thread.id='. $id);

	q('UPDATE fud26_thread SET forum_id='. $to_forum .' WHERE id='. $id);
	q('UPDATE fud26_forum SET post_count=post_count-'. $msg_count .' WHERE id='. $forum_id);
	q('UPDATE fud26_forum SET thread_count=thread_count+1,post_count=post_count+'. $msg_count .' WHERE id='. $to_forum);
	q('DELETE FROM fud26_thread WHERE forum_id='. $to_forum .' AND root_msg_id='. $root_msg_id .' AND moved_to='. $forum_id);
	if (($aff_rows = db_affected())) {
		q('UPDATE fud26_forum SET thread_count=thread_count-'. $aff_rows .' WHERE id='. $to_forum);
	}
	q('UPDATE fud26_thread SET moved_to='. $to_forum .' WHERE id!='. $id .' AND root_msg_id='. $root_msg_id);

	q('INSERT INTO fud26_thread
		(forum_id, root_msg_id, last_post_date, last_post_id, moved_to, tdescr)
	VALUES
		('. $forum_id .', '. $root_msg_id .', '. $last_post_date .', '. $last_post_id .', '. $to_forum .','. _esc($descr) .')');

	rebuild_forum_view_ttl($forum_id);
	rebuild_forum_view_ttl($to_forum);

	$p = db_all('SELECT poll_id FROM fud26_msg WHERE thread_id='. $id .' AND apr=1 AND poll_id>0');
	if ($p) {
		q('UPDATE fud26_poll SET forum_id='. $to_forum .' WHERE id IN('. implode(',', $p) .')');
	}

	if (isset($ll)) {
		db_unlock();
	}
}

function __th_cron_emu($forum_id, $run=1)
{
	/* Let's see if we have sticky threads that have expired. */
	$exp = db_all('SELECT fud26_thread.id FROM fud26_tv_'. $forum_id .'
			INNER JOIN fud26_thread ON fud26_thread.id=fud26_tv_'. $forum_id .'.thread_id
			INNER JOIN fud26_msg ON fud26_thread.root_msg_id=fud26_msg.id
			WHERE fud26_tv_'. $forum_id .'.id>'. (q_singleval('SELECT seq FROM fud26_tv_'. $forum_id .' ORDER BY seq DESC LIMIT 1') - 50).' 
				AND fud26_tv_'. $forum_id .'.iss>0
				AND fud26_thread.thread_opt>=2 
				AND (fud26_msg.post_stamp+fud26_thread.orderexpiry)<='. __request_timestamp__);
	if ($exp) {
		q('UPDATE fud26_thread SET orderexpiry=0, thread_opt=(thread_opt & ~(2|4)) WHERE id IN('. implode(',', $exp) .')');
		$exp = 1;
	}

	/* Remove expired moved thread pointers. */
	q('DELETE FROM fud26_thread WHERE forum_id='. $forum_id .' AND moved_to>0 AND last_post_date<'.(__request_timestamp__ - 86400 * $GLOBALS['MOVED_THR_PTR_EXPIRY']));
	if (($aff_rows = db_affected())) {
		q('UPDATE fud26_forum SET thread_count=thread_count-'. $aff_rows .' WHERE thread_count>0 AND id='. $forum_id);
		if (!$exp) {
			$exp = 1;
		}
	}

	if ($exp && $run) {
		rebuild_forum_view_ttl($forum_id,1);
	}

	return $exp;
}

function rebuild_forum_view_ttl($forum_id, $skip_cron=0)
{
	if (!$skip_cron) {
		__th_cron_emu($forum_id, 0);
	}

	if (!db_locked()) {
		$ll = 1;
		db_lock('fud26_tv_'. $forum_id .' WRITE, fud26_thread READ, fud26_msg READ');
	}

	q('DELETE FROM fud26_tv_'. $forum_id);

	q('INSERT INTO fud26_tv_'. $forum_id .' (thread_id,iss,seq) SELECT id, iss, '. q_rownum() .' FROM
		(SELECT fud26_thread.id AS id, '. q_bitand('thread_opt', (2|4|8)) .' AS iss FROM fud26_thread 
		INNER JOIN fud26_msg ON fud26_thread.root_msg_id=fud26_msg.id 
		WHERE forum_id='. $forum_id .' AND fud26_msg.apr=1 
		ORDER BY (CASE WHEN thread_opt>=2 THEN (4294967294 + (('. q_bitand('thread_opt', 8) .') * 100000000) + fud26_thread.last_post_date) ELSE fud26_thread.last_post_date END) ASC) q1');

	if (__dbtype__ == 'sqlite') {
		// q_rownum() is not implemented for SQLite.
		// If we empty a table (the DELETE above), the ID (internal sequence) will be reset to 0. We will misuse it as the ROWNUM.
		q('UPDATE fud26_tv_'. $forum_id .' SET seq=id');
	}

	if (isset($ll)) {
		db_unlock();
	}
}

function th_delete_rebuild($forum_id, $th)
{
	if (!db_locked()) {
		$ll = 1;
		db_lock('fud26_tv_'. $forum_id .' WRITE');
	}

	/* Get position. */
	if (($pos = q_singleval('SELECT seq FROM fud26_tv_'. $forum_id .' WHERE thread_id='. $th))) {
		q('DELETE FROM fud26_tv_'. $forum_id .' WHERE thread_id='. $th);
		/* Move every one down one, if placed after removed topic. */
		q('UPDATE fud26_tv_'. $forum_id .' SET seq=seq-1 WHERE seq>'. $pos);
	}

	if (isset($ll)) {
		db_unlock();
	}
}

function th_new_rebuild($forum_id, $th, $sticky)
{
	if (__th_cron_emu($forum_id)) {
		return;
	}

	if (!db_locked()) {
		$ll = 1;
		db_lock('fud26_tv_'. $forum_id .' WRITE');
	}

	list($max,$iss) = db_saq('SELECT seq,iss FROM fud26_tv_'. $forum_id .' ORDER BY seq DESC LIMIT 1');
	if ((!$sticky && $iss) || $iss >=8) { /* Sub-optimal case, non-sticky topic and thre are stickies in the forum. */
		/* Find oldest sticky message. */
		if ($sticky && $iss >= 8) {
			$iss = q_singleval('SELECT seq FROM fud26_tv_'. $forum_id .' WHERE seq>'. ($max - 50) .' AND iss>=8 ORDER BY seq ASC LIMIT 1');
		} else {
			$iss = q_singleval('SELECT seq FROM fud26_tv_'. $forum_id .' WHERE seq>'. ($max - 50) .' AND iss>0 ORDER BY seq ASC LIMIT 1');
		}
		/* Move all stickies up one. */
		q('UPDATE fud26_tv_'. $forum_id .' SET seq=seq+1 WHERE seq>='. $iss);
		/* We do this, since in optimal case we just do ++max. */
		$max = --$iss;
	}
	q('INSERT INTO fud26_tv_'. $forum_id .' (thread_id,iss,seq) VALUES('. $th .','. (int)$sticky .','. (++$max) .')');

	if (isset($ll)) {
		db_unlock();
	}
}

function th_reply_rebuild($forum_id, $th, $sticky)
{
	if (!db_locked()) {
		$ll = 1;
		db_lock('fud26_tv_'. $forum_id .' WRITE');
	}

	list($max,$tid,$iss) = db_saq('SELECT seq,thread_id,iss FROM fud26_tv_'. $forum_id .' ORDER BY seq DESC LIMIT 1');

	if ($tid == $th) {
		/* NOOP: quick elimination, topic is already 1st. */
	} else if (!$iss || ($sticky && $iss < 8)) { /* Moving to the very top. */
		/* Get position. */
		$pos = q_singleval('SELECT seq FROM fud26_tv_'. $forum_id .' WHERE thread_id='. $th);
		/* Move everyone ahead, 1 down. */
		q('UPDATE fud26_tv_'. $forum_id .' SET seq=seq-1 WHERE seq>'. $pos);
		/* Move to top of the stack. */
		q('UPDATE fud26_tv_'. $forum_id .' SET seq='. $max .' WHERE thread_id='. $th);
	} else {
		/* Get position. */
		$pos = q_singleval('SELECT seq FROM fud26_tv_'. $forum_id .' WHERE thread_id='. $th);
		/* Find oldest sticky message. */
		$iss = q_singleval('SELECT seq FROM fud26_tv_'. $forum_id .' WHERE seq>'. ($max - 50) .' AND iss>'. ($sticky && $iss >= 8 ? '=8' : '0') .' ORDER BY seq ASC LIMIT 1');
		/* Move everyone ahead, unless sticky, 1 down. */
		q('UPDATE fud26_tv_'. $forum_id .' SET seq=seq-1 WHERE seq BETWEEN '. ($pos + 1) .' AND '. ($iss - 1));
		/* Move to top of the stack. */
		q('UPDATE fud26_tv_'. $forum_id .' SET seq='. ($iss - 1) .' WHERE thread_id='. $th);
	}

	if (isset($ll)) {
		db_unlock();
	}
}class fud_smtp
{
	var $fs, $last_ret, $msg, $subject, $to, $from, $headers;

	function get_return_code($cmp_code='250')
	{
		if (!($this->last_ret = @fgets($this->fs, 515))) {
			return;
		}
		if ((int)$this->last_ret == $cmp_code) {
			return 1;
		}
		return;
	}

	function wts($string)
	{
		/* Write to stream. */
		fwrite($this->fs, $string ."\r\n");
	}

	function open_smtp_connex()
	{
		if( !($this->fs = @fsockopen($GLOBALS['FUD_SMTP_SERVER'], $GLOBALS['FUD_SMTP_PORT'], $errno, $errstr, $GLOBALS['FUD_SMTP_TIMEOUT'])) ) {
			fud_logerror('ERROR: SMTP server at '. $GLOBALS['FUD_SMTP_SERVER'] ." is not available<br />\n". ($errno ? "Additional Problem Info: $errno -> $errstr <br />\n" : ''), 'fud_errors');
			return;
		}
		if (!$this->get_return_code(220)) {	// 220 == Ready to speak SMTP.
			return;
		}

		$es = strpos($this->last_ret, 'ESMTP') !== false;
		$smtp_srv = $_SERVER['SERVER_NAME'];
		if ($smtp_srv == 'localhost' || $smtp_srv == '127.0.0.1') {
			$smtp_srv = 'FUDforum SMTP server';
		}

		$this->wts(($es ? 'EHLO ' : 'HELO ') . $smtp_srv);
		if (!$this->get_return_code()) {
			return;
		}

		/* Scan all lines and look for TLS support. */
		$tls = false;
		if ($es) {
			while($str = @fgets($this->fs, 515)) {
				if (substr($str, 0, 12) == '250-STARTTLS') $tls = true;
				if (substr($str, 3,  1) == ' ') break;	// Done reading if 4th char is a space.

			}
		}

		/* Do SMTP Auth if needed. */
		if ($GLOBALS['FUD_SMTP_LOGIN']) {
			if ($tls) {
				/*  Initiate TSL communication with server. */
				$this->wts('STARTTLS');
				if (!$this->get_return_code(220)) {
					return;
				}
				/* Encrypt the connection. */
				if (!stream_socket_enable_crypto($this->fs, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
					return false;
				} 
				/* Say hi again. */
				$this->wts(($es ? 'EHLO ' : 'HELO ').$smtp_srv);
				if (!$this->get_return_code()) {
					return;
				}
				/* we need to scan all other lines */
				while($str = @fgets($this->fs, 515)) {
					if (substr($str, 3, 1) == ' ') break;
				}
			}

			$this->wts('AUTH LOGIN');
			if (!$this->get_return_code(334)) {
				return;
			}
			$this->wts(base64_encode($GLOBALS['FUD_SMTP_LOGIN']));
			if (!$this->get_return_code(334)) {
				return;
			}
			$this->wts(base64_encode($GLOBALS['FUD_SMTP_PASS']));
			if (!$this->get_return_code(235)) {
				return;
			}
		}

		return 1;
	}

	function send_from_hdr()
	{
		$this->wts('MAIL FROM: <'. $GLOBALS['NOTIFY_FROM'] .'>');
		return $this->get_return_code();
	}

	function send_to_hdr()
	{
		$this->to = (array) $this->to;

		foreach ($this->to as $to_addr) {
			$this->wts('RCPT TO: <'. $to_addr .'>');
			if (!$this->get_return_code()) {
				return;
			}
		}
		return 1;
	}

	function send_data()
	{
		$this->wts('DATA');
		if (!$this->get_return_code(354)) {
			return;
		}

		/* This is done to ensure what we comply with RFC requiring each line to end with \r\n */
		$this->msg = preg_replace('!(\r)?\n!si', "\r\n", $this->msg);

		if( empty($this->from) ) $this->from = $GLOBALS['NOTIFY_FROM'];

		$this->wts('Subject: '. $this->subject);
		$this->wts('Date: '. date('r'));
		$this->wts('To: '. (count($this->to) == 1 ? $this->to[0] : $GLOBALS['NOTIFY_FROM']));
		$this->wts('From: '. $this->from);
		$this->wts('X-Mailer: FUDforum v'. $GLOBALS['FORUM_VERSION']);
		$this->wts($this->headers ."\r\n");
		$this->wts($this->msg);
		$this->wts('.');

		return $this->get_return_code();
	}

	function close_connex()
	{
		$this->wts('QUIT');
		fclose($this->fs);
	}

	function send_smtp_email()
	{
		if (!$this->open_smtp_connex()) {
			if ($this->last_ret) {
				fud_logerror('Open SMTP connection - invalid return code: '. $this->last_ret, 'fud_errors');
			}
			return false;
		}
		if (!$this->send_from_hdr()) {
			fud_logerror('Send "From:" header - invalid SMTP return code: '. $this->last_ret, 'fud_errors');
			$this->close_connex();
			return false;
		}
		if (!$this->send_to_hdr()) {
			fud_logerror('Send "To:" header - invalid SMTP return code: '. $this->last_ret, 'fud_errors');
			$this->close_connex();
			return false;
		}
		if (!$this->send_data()) {
			fud_logerror('Send data - invalid SMTP return code: '. $this->last_ret, 'fud_errors');
			$this->close_connex();
			return false;
		}

		$this->close_connex();
		return true;
	}
}$GLOBALS['seps'] = array(' '=>' ', "\n"=>"\n", "\r"=>"\r", '\''=>'\'', '"'=>'"', '['=>'[', ']'=>']', '('=>'(', ';'=>';', ')'=>')', "\t"=>"\t", '='=>'=', '>'=>'>', '<'=>'<');

function fud_substr_replace($str, $newstr, $pos, $len)
{
        return substr($str, 0, $pos) . $newstr . substr($str, $pos+$len);
}

function url_check($url)
{
	$url = preg_replace('!\s+!', '', $url);

	if (strpos($url, '&amp;#') !== false) {
		return preg_replace('!&#([0-9]{2,3});!e', "chr(\\1)", char_fix($url));
	}
	return $url;
}

function tags_to_html($str, $allow_img=1, $no_char=0)
{
	if (!$no_char) {
		$str = htmlspecialchars($str);
	}

	$str = nl2br($str);

	$ostr = '';
	$pos = $old_pos = 0;

	// Call all BBcode to HTML conversion plugins.
	if (defined('plugins')) {
		list($str) = plugin_call_hook('BBCODE2HTML', array($str));
	}

	while (($pos = strpos($str, '[', $pos)) !== false) {
		if (isset($str[$pos + 1], $GLOBALS['seps'][$str[$pos + 1]])) {
			++$pos;
			continue;
		}

		if (($epos = strpos($str, ']', $pos)) === false) {
			break;
		}
		if (!($epos-$pos-1)) {
			$pos = $epos + 1;
			continue;
		}
		$tag = substr($str, $pos+1, $epos-$pos-1);
		if (($pparms = strpos($tag, '=')) !== false) {
			$parms = substr($tag, $pparms+1);
			if (!$pparms) { /*[= exception */
				$pos = $epos+1;
				continue;
			}
			$tag = substr($tag, 0, $pparms);
		} else {
			$parms = '';
		}

		if (!$parms && ($tpos = strpos($tag, '[')) !== false) {
			$pos += $tpos;
			continue;
		}
		$tag = strtolower($tag);

		switch ($tag) {
			case 'quote title':
				$tag = 'quote';
				break;
			case 'list type':
				$tag = 'list';
				break;
			case 'hr':
				$str{$pos} = '<';
				$str{$pos+1} = 'h';
				$str{$pos+2} = 'r';
				$str{$epos} = '>';
				continue 2;
		}

		if ($tag[0] == '/') {
			if (isset($end_tag[$pos])) {
				if( ($pos-$old_pos) ) $ostr .= substr($str, $old_pos, $pos-$old_pos);
				$ostr .= $end_tag[$pos];
				$pos = $old_pos = $epos+1;
			} else {
				$pos = $epos+1;
			}

			continue;
		}

		$cpos = $epos;
		$ctag = '[/'. $tag .']';
		$ctag_l = strlen($ctag);
		$otag = '['. $tag;
		$otag_l = strlen($otag);
		$rf = 1;
		$nt_tag = 0;
		while (($cpos = strpos($str, '[', $cpos)) !== false) {
			if (isset($end_tag[$cpos]) || isset($GLOBALS['seps'][$str[$cpos + 1]])) {
				++$cpos;
				continue;
			}

			if (($cepos = strpos($str, ']', $cpos)) === false) {
				if (!$nt_tag) {
					break 2;
				} else {
					break;
				}
			}

			if (strcasecmp(substr($str, $cpos, $ctag_l), $ctag) == 0) {
				--$rf;
			} else if (strcasecmp(substr($str, $cpos, $otag_l), $otag) == 0) {
				++$rf;
			} else {
				$nt_tag++;
				++$cpos;
				continue;
			}

			if (!$rf) {
				break;
			}
			$cpos = $cepos;
		}

		if (!$cpos || ($rf && $str[$cpos] == '<')) { /* Left over [ handler. */
			++$pos;
			continue;
		}

		if ($cpos !== false) {
			if (($pos-$old_pos)) {
				$ostr .= substr($str, $old_pos, $pos-$old_pos);
			}
			switch ($tag) {
				case 'notag':
					$ostr .= '<span name="notag">'. substr($str, $epos+1, $cpos-1-$epos) .'</span>';
					$epos = $cepos;
					break;
				case 'url':
					if (!$parms) {
						$url = substr($str, $epos+1, ($cpos-$epos)-1);
					} else {
						$url = $parms;
					}

					$url = url_check($url);
					$url = str_replace('&quot;', '', $url); // Remove quotes from URL.

					if (!strncasecmp($url, 'www.', 4)) {
						$url = 'http&#58;&#47;&#47;'. $url;
					} else if (strpos(strtolower($url), 'script:') !== false) {
						$ostr .= substr($str, $pos, $cepos - $pos + 1);
						$epos = $cepos;
						$str[$cpos] = '<';
						break;
					} else {
						$url = str_replace('://', '&#58;&#47;&#47;', $url);
					}

					if ( strtolower(substr($str, $epos+1, 6)) == '[/url]' ) {
						$end_tag[$cpos] = $url .'</a>';  // Fill empty link.
					} else {
						$end_tag[$cpos] = '</a>';
					}
					$ostr .= '<a href="'. $url .'" target="_blank">';
					break;
				case 'i':
				case 'u':
				case 'b':
				case 's':
				case 'sub':
				case 'sup':
				case 'del':
					$end_tag[$cpos] = '</'. $tag .'>';
					$ostr .= '<'. $tag .'>';
					break;
				case 'h1':
				case 'h2':
				case 'h3':
				case 'h4':
					$end_tag[$cpos] = '</'.$tag.'>';
					$ostr .= '<'.$tag.'>';
					break;
				case 'email':
					if (!$parms) {
						$parms = str_replace('@', '&#64;', substr($str, $epos+1, ($cpos-$epos)-1));
						$ostr .= '<a href="mailto:'. $parms .'" target="_blank">'. $parms .'</a>';
						$epos = $cepos;
						$str[$cpos] = '<';
					} else {
						$end_tag[$cpos] = '</a>';
						$ostr .= '<a href="mailto:'. str_replace('@', '&#64;', $parms) .'" target="_blank">';
					}
					break;
				case 'color':
				case 'size':
				case 'font':
					if ($tag == 'font') {
						$tag = 'face';
					}
					$end_tag[$cpos] = '</font>';
					$ostr .= '<font '. $tag .'="'. $parms .'">';
					break;
				case 'code':
					$param = substr($str, $epos+1, ($cpos-$epos)-1);

					$ostr .= '<div class="pre"><pre>'. reverse_nl2br($param) .'</pre></div>';
					$epos = $cepos;
					$str[$cpos] = '<';
					break;
				case 'pre':
					$param = substr($str, $epos+1, ($cpos-$epos)-1);

					$ostr .= '<pre>'. reverse_nl2br($param) .'</pre>';
					$epos = $cepos;
					$str[$cpos] = '<';
					break;
				case 'php':
					$param = trim(reverse_fmt(reverse_nl2br(substr($str, $epos+1, ($cpos-$epos)-1))));

					if (strncmp($param, '<?php', 5)) {
						if (strncmp($param, '<?', 2)) {
							$param = "<?php\n". $param;
						} else {
							$param = "<?php\n". substr($param, 3);
						}
					}
					if (substr($param, -2) != '?>') {
						$param .= "\n?>";
					}

					$ostr .= '<SPAN name="php">'. trim(@highlight_string($param, true)) .'</SPAN>';
					$epos = $cepos;
					$str[$cpos] = '<';
					break;
				case 'img':
				case 'imgl':
				case 'imgr':
					if (!$allow_img) {
						$ostr .= substr($str, $pos, ($cepos-$pos)+1);
					} else {
						$class = ($tag == 'img') ? '' : 'class="'. $tag{3} .'" ';

						if (!$parms) {
							$parms = substr($str, $epos+1, ($cpos-$epos)-1);
							if (strpos(strtolower(url_check($parms)), 'script:') === false) {
								$ostr .= '<img '. $class .'src="'. $parms .'" border="0" alt="'. $parms .'" />';
							} else {
								$ostr .= substr($str, $pos, ($cepos-$pos)+1);
							}
						} else {
							if (strpos(strtolower(url_check($parms)), 'script:') === false) {
								$ostr .= '<img '. $class .'src="'. $parms .'" border="0" alt="'. substr($str, $epos+1, ($cpos-$epos)-1) .'" />';
							} else {
								$ostr .= substr($str, $pos, ($cepos-$pos)+1);
							}
						}
					}
					$epos = $cepos;
					$str[$cpos] = '<';
					break;
				case 'quote':
					if (!$parms) {
						$parms = 'Цитата:';
					} else {
						$parms = str_replace(array('@', ':'), array('&#64;', '&#58;'), $parms);
					}
					$ostr .= '<cite>'.$parms.'</cite><blockquote>';
					$end_tag[$cpos] = '</blockquote>';
					break;
				case 'align':
					$end_tag[$cpos] = '</div>';
					$ostr .= '<div align="'. $parms .'">';
					break;
				case 'list':
					$tmp = substr($str, $epos, ($cpos-$epos));
					$tmp_l = strlen($tmp);
					$tmp2 = str_replace('[*]', '<li>', $tmp);
					$tmp2_l = strlen($tmp2);
					$str = str_replace($tmp, $tmp2, $str);

					$diff = $tmp2_l - $tmp_l;
					$cpos += $diff;

					if (isset($end_tag)) {
						foreach($end_tag as $key => $val) {
							if ($key < $epos) {
								continue;
							}

							$end_tag[$key+$diff] = $val;
						}
					}

					switch (strtolower($parms)) {
						case '1':
						case 'decimal':
						case 'a':
							$end_tag[$cpos] = '</ol>';
							$ostr .= '<ol type="'. $parms .'">';
							break;
						case 'square':
						case 'circle':
						case 'disc':
							$end_tag[$cpos] = '</ul>';
							$ostr .= '<ul type="'. $parms .'">';
							break;
						default:
							$end_tag[$cpos] = '</ul>';
							$ostr .= '<ul>';
					}
					break;
				case 'spoiler':
					$rnd = rand();
					$end_tag[$cpos] = '</div></div>';
					$ostr .= '<div class="dashed" style="padding: 3px;" align="center"><a href="javascript://" onclick="javascript: layerVis(\'s'. $rnd .'\', 1);">'
						.($parms ? $parms : 'Показать скрытый текст') .'</a><div align="left" id="s'. $rnd .'" style="display: none;">';
					break;
				case 'acronym':
					$end_tag[$cpos] = '</acronym>';
					$ostr .= '<acronym title="'. ($parms ? $parms : ' ') .'">';
					break;
				case 'wikipedia':
					$end_tag[$cpos] = '</a>';
					$url = substr($str, $epos+1, ($cpos-$epos)-1);
					if ($parms && preg_match('!^[A-Za-z]+$!', $parms)) {
						$parms .= '.';
					} else {
						$parms = '';
					}
					$ostr .= '<a href="http://'. $parms .'wikipedia.com/wiki/'. $url .'" name="WikiPediaLink" target="_blank">';
					break;
			}

			$str[$pos] = '<';
			$pos = $old_pos = $epos+1;
		} else {
			$pos = $epos+1;
		}
	}
	$ostr .= substr($str, $old_pos, strlen($str)-$old_pos);

	/* URL paser. */
	$pos = 0;
	$ppos = 0;
	while (($pos = @strpos($ostr, '://', $pos)) !== false) {
		if ($pos < $ppos) {
			break;
		}
		// Check if it's inside any tag.
		$i = $pos;
		while (--$i && $i > $ppos) {
			if ($ostr[$i] == '>' || $ostr[$i] == '<') {
				break;
			}
		}
		if (!$pos || $ostr[$i] == '<') {
			$pos += 3;
			continue;
		}

		// Check if it's inside the a tag.
		if (($ts = strpos($ostr, '<a ', $pos)) === false) {
			$ts = strlen($ostr);
		}
		if (($te = strpos($ostr, '</a>', $pos)) == false) {
			$te = strlen($ostr);
		}
		if ($te < $ts) {
			$ppos = $pos += 3;
			continue;
		}

		// Check if it's inside the PRE tag.
		if (($ts = strpos($ostr, '<pre>', $pos)) === false) {
			$ts = strlen($ostr);
		}
		if (($te = strpos($ostr, '</pre>', $pos)) == false) {
			$te = strlen($ostr);
		}
		if ($te < $ts) {
			$ppos = $pos += 3;
			continue;
		}

		// Check if it's inside the SPAN tag
		if (($ts = strpos($ostr, '<span>', $pos)) === false) {
			$ts = strlen($ostr);
		}
		if (($te = strpos($ostr, '</span>', $pos)) == false) {
			$te = strlen($ostr);
		}
		if ($te < $ts) {
			$ppos = $pos += 3;
			continue;
		}

		$us = $pos;
		$l = strlen($ostr);
		while (1) {
			--$us;
			if ($ppos > $us || $us >= $l || isset($GLOBALS['seps'][$ostr[$us]])) {
				break;
			}
		}

		unset($GLOBALS['seps']['=']);
		$ue = $pos;
		while (1) {
			++$ue;
			if ($ue >= $l || isset($GLOBALS['seps'][$ostr[$ue]])) {
				break;
			}

			if ($ostr[$ue] == '&') {
				if ($ostr[$ue+4] == ';') {
					$ue += 4;
					continue;
				}
				if ($ostr[$ue+3] == ';' || $ostr[$ue+5] == ';') {
					break;
				}
			}

			if ($ue >= $l || isset($GLOBALS['seps'][$ostr[$ue]])) {
				break;
			}
		}
		$GLOBALS['seps']['='] = '=';

		$url = url_check(substr($ostr, $us+1, $ue-$us-1));
		if (strpos($url, 'script', strlen('script')) !== false || ($ue - $us - 1) < 9) {
			$pos = $ue;
			continue;
		}
		$html_url = '<a href="'. $url .'" target="_blank">'. $url .'</a>';
		$html_url_l = strlen($html_url);
		$ostr = fud_substr_replace($ostr, $html_url, $us+1, $ue-$us-1);
		$ppos = $pos;
		$pos = $us+$html_url_l;
	}

	/* E-mail parser. */
	$pos = 0;
	$ppos = 0;

	$er = array_flip(array_merge(range(0,9), range('A', 'Z'), range('a','z'), array('.', '-', '\'', '_')));

	while (($pos = @strpos($ostr, '@', $pos)) !== false) {
		if ($pos < $ppos) {
			break;
		}

		// Check if it's inside any tag.
		$i = $pos;
		while (--$i && $i>$ppos) {
			if ( $ostr[$i] == '>' || $ostr[$i] == '<') {
				break;
			}
		}
		if ($i < 0 || $ostr[$i]=='<') {
			++$pos;
			continue;
		}


		// Check if it's inside the a tag.
		if (($ts = strpos($ostr, '<a ', $pos)) === false) {
			$ts = strlen($ostr);
		}
		if (($te = strpos($ostr, '</a>', $pos)) == false) {
			$te = strlen($ostr);
		}
		if ($te < $ts) {
			$ppos = $pos += 1;
			continue;
		}

		// Check if it's inside the PRE tag.
		if (($ts = strpos($ostr, '<div class="pre"><pre>', $pos)) === false) {
			$ts = strlen($ostr);
		}
		if (($te = strpos($ostr, '</pre></div>', $pos)) == false) {
			$te = strlen($ostr);
		}
		if ($te < $ts) {
			$ppos = $pos += 1;
			continue;
		}

		for ($es = ($pos - 1); $es > ($ppos - 1); $es--) {
			if (isset($er[ $ostr[$es] ])) continue;
			++$es;
			break;
		}
		if ($es == $pos) {
			$ppos = $pos += 1;
			continue;
		}
		if ($es < 0) {
			$es = 0;
		}

		for ($ee = ($pos + 1); @isset($ostr[$ee]); $ee++) {
			if (isset($er[ $ostr[$ee] ])) continue;
			break;
		}
		if ($ee == ($pos+1)) {
			$ppos = $pos += 1;
			continue;
		}

		$email = str_replace('@', '&#64;', substr($ostr, $es, $ee-$es));
		if (strpos( substr($email, 1, -1), '.') === false) {	// E-mail mostly have dots in them.
			$ppos = $pos += 1; continue;
		}
		$email_url = '<a href="mailto:'. $email .'" target="_blank">'. $email .'</a>';
		$email_url_l = strlen($email_url);
		$ostr = fud_substr_replace($ostr, $email_url, $es, $ee-$es);
		$ppos =	$es+$email_url_l;
		$pos = $ppos;
	}

	return $ostr;
}

function html_to_tags($fudml)
{
	// Call all HTML to BBcode conversion plugins.
	if (defined('plugins')) {
		list($fudml) = plugin_call_hook('HTML2BBCODE', array($fudml));
	}

	// PHP code blocks.
	while (preg_match('!<span name="php">(.*?)</span>!is', $fudml, $res)) {
		$tmp = trim(html_entity_decode(strip_tags(str_replace('<br />', "\n", $res[1]))));
		$m = md5($tmp);
		$php[$m] = $tmp;
		$fudml = str_replace($res[0], "[php]\n". $m ."\n[/php]", $fudml);
	}

	// Wikipedia tags.
	while (preg_match('!<a href="http://(?:([A-ZA-z]+)?\.)?wikipedia.com/wiki/([^"]+)"( target="_blank")? name="WikiPediaLink">(.*?)</a>!s', $fudml, $res)) {
		if (count($res) == 5) {
			$fudml = str_replace($res[0], '[wikipedia='. $res[1] .']'. $res[2] .'[/wikipedia]', $fudml);
		} else {
			$fudml = str_replace($res[0], '[wikipedia]'. $res[2] .'[/wikipedia]', $fudml);
		}
	}

	// Quote tags.
	if (strpos($fudml, '<cite>') !== false) {
               $fudml = str_replace(array('<cite>','</cite><blockquote>','</blockquote>'), array('[quote title=', ']', '[/quote]'), $fudml);
	}
	// Old bad quote tags.
	if (preg_match('!class="quote"!', $fudml)) { 
		$fudml = preg_replace('!<table border="0" align="center" width="90%" cellpadding="3" cellspacing="1">(<tbody>)?<tr><td class="SmallText"><b>!', '[quote title=', $fudml);
		$fudml = preg_replace('!</b></td></tr><tr><td class="quote">(<br>)?!', ']', $fudml);
		$fudml = preg_replace('!(<br>)?</td></tr>(</tbody>)?</table>!', '[/quote]', $fudml);
	}

	/* Spoiler tags. */	
	if (preg_match('!<div class="dashed" style="padding: 3px;" align="center"( width="100%")?><a href="javascript://" OnClick="javascript: layerVis\(\'.*?\', 1\);">.*?</a><div align="left" id="(.*?)" style="display: none;">!is', $fudml)) {
		$fudml = preg_replace('!\<div class\="dashed" style\="padding: 3px;" align\="center"( width\="100%")?\>\<a href\="javascript://" OnClick\="javascript: layerVis\(\'.*?\', 1\);">(.*?)\</a\>\<div align\="left" id\=".*?" style\="display: none;"\>!is', '[spoiler=\2]', $fudml);
		$fudml = str_replace('</div></div>', '[/spoiler]', $fudml);
	}
	/* Old bad spoiler format. */
	if (preg_match('!<div class="dashed" style="padding: 3px;" align="center" width="100%"><a href="javascript://" OnClick="javascript: layerVis\(\'.*?\', 1\);">.*?</a><div align="left" id="(.*?)" style="visibility: hidden;">!is', $fudml)) {
		$fudml = preg_replace('!\<div class\="dashed" style\="padding: 3px;" align\="center" width\="100%"\>\<a href\="javascript://" OnClick\="javascript: layerVis\(\'.*?\', 1\);">(.*?)\</a\>\<div align\="left" id\=".*?" style\="visibility: hidden;"\>!is', '[spoiler=\1]', $fudml);
		$fudml = str_replace('</div></div>', '[/spoiler]', $fudml);
	}

	// Color, font and size tags.
	$fudml = str_replace('<font face=', '<font font=', $fudml);
	foreach (array('color', 'font', 'size') as $v) {
		while (preg_match('!<font '. $v .'=".+?">.*?</font>!is', $fudml, $m)) {
			$fudml = preg_replace('!<font '. $v .'="(.+?)">(.*?)</font>!is', '['. $v .'=\1]\2[/'. $v .']', $fudml);
		}
	}

	// Acronym tags.
	while (preg_match('!<acronym title=".+?">.*?</acronym>!is', $fudml)) {
		$fudml = preg_replace('!<acronym title="(.+?)">(.*?)</acronym>!is', '[acronym=\1]\2[/acronym]', $fudml);
	}

	// List tags.
	while (preg_match('!<(o|u)l type=".+?">.*?</\\1l>!is', $fudml)) {
		$fudml = preg_replace('!<(o|u)l type="(.+?)">(.*?)</\\1l>!is', '[list type=\2]\3[/list]', $fudml);
	}

	$fudml = str_replace(
	array(
		'<b>', '</b>', '<i>', '</i>', '<u>', '</u>', '<s>', '</s>', '<sub>', '</sub>', '<sup>', '</sup>', '<del>', '</del>',
		'<div class="pre"><pre>', '</pre></div>', '<div align="center">', '<div align="left">', '<div align="right">', '</div>',
		'<ul>', '</ul>', '<span name="notag">', '</span>', '<li>', '&#64;', '&#58;&#47;&#47;', '<br />', '<pre>', '</pre>','<hr>',
		'<h1>', '</h1>', '<h2>', '</h2>', '<h3>', '</h3>', '<h4>', '</h4>'
	),
	array(
		'[b]', '[/b]', '[i]', '[/i]', '[u]', '[/u]', '[s]', '[/s]', '[sub]', '[/sub]', '[sup]', '[/sup]', '[del]', '[/del]', 
		'[code]', '[/code]', '[align=center]', '[align=left]', '[align=right]', '[/align]', '[list]', '[/list]',
		'[notag]', '[/notag]', '[*]', '@', '://', '', '[pre]', '[/pre]','[hr]',
		'[h1]', '[/h1]', '[h2]', '[/h2]', '[h3]', '[/h3]', '[h4]', '[/h4]'
	),
	$fudml);

	while (preg_match('!<img src="(.*?)" border="?0"? alt="\\1" ?/?>!is', $fudml)) {
                $fudml = preg_replace('!<img src="(.*?)" border="?0"? alt="\\1" ?/?>!is', '[img]\1[/img]', $fudml);
	}
	while (preg_match('!<img class="(r|l)" src="(.*?)" border="?0"? alt="\\2" ?/?>!is', $fudml)) {
                $fudml = preg_replace('!<img class="(r|l)" src="(.*?)" border="?0"? alt="\\2" ?/?>!is', '[img\1]\2[/img\1]', $fudml);
	}
	while (preg_match('!<a href="mailto:(.+?)"( target="_blank")?>\\1</a>!is', $fudml)) {
		$fudml = preg_replace('!<a href="mailto:(.+?)"( target="_blank")?>\\1</a>!is', '[email]\1[/email]', $fudml);
	}
	while (preg_match('!<a href="(.+?)"( target="_blank")?>\\1</a>!is', $fudml)) {
		$fudml = preg_replace('!<a href="(.+?)"( target="_blank")?>\\1</a>!is', '[url]\1[/url]', $fudml);
	}

	if (strpos($fudml, '<img src="') !== false) {
                $fudml = preg_replace('!<img src="(.*?)" border="?0"? alt="(.*?)" ?/?>!is', '[img=\1]\2[/img]', $fudml);
	}
	if (strpos($fudml, '<img class="') !== false) {
                $fudml = preg_replace('!<img class="(r|l)" src="(.*?)" border="?0"? alt="(.*?)" ?/?>!is', '[img\1=\2]\3[/img\1]', $fudml);
	}
	if (strpos($fudml, '<a href="mailto:') !== false) {
		$fudml = preg_replace('!<a href="mailto:(.+?)"( target="_blank")?>(.+?)</a>!is', '[email=\1]\3[/email]', $fudml);
	}
	if (strpos($fudml, '<a href="') !== false) {
		$fudml = preg_replace('!<a href="(.+?)"( target="_blank")?>(.+?)</a>!is', '[url=\1]\3[/url]', $fudml);
	}

	if (isset($php)) {
		$fudml = str_replace(array_keys($php), array_values($php), $fudml);
	}

	/* Un-htmlspecialchars. */
	return reverse_fmt($fudml);
}

function filter_ext($file_name)
{
	include $GLOBALS['FORUM_SETTINGS_PATH'] .'file_filter_regexp';
	if (empty($GLOBALS['__FUD_EXT_FILER__'])) {
		return;
	}
	if (($p = strrpos($file_name, '.')) === false) {
		return 1;
	}
	return !in_array(strtolower(substr($file_name, ($p + 1))), $GLOBALS['__FUD_EXT_FILER__']);
}

function reverse_nl2br($data)
{
	if (strpos($data, '<br />') !== false) {
		return str_replace('<br />', '', $data);
	}
	return $data;
}

	/* Only admins & moderators have access to this control panel. */
	if (!_uid) {
		std_error('login');
	} else if (!($usr->users_opt & (1048576|524288))) {
		std_error('perms');
	}

	$appr = isset($_GET['appr']) ? (int) $_GET['appr'] : 0;
	$del = isset($_GET['del']) ? (int) $_GET['del'] : 0;

	/* We need to determine wether or not the message exists & if the user has access to approve/delete it. */
	if ($appr || $del) {
		if (!q_singleval('SELECT CASE WHEN ('. q_bitand($usr->users_opt, 1048576) .' = 0) THEN mm.id ELSE 1 END FROM fud26_msg m INNER JOIN fud26_thread t ON m.thread_id=t.id LEFT JOIN fud26_mod mm ON t.forum_id=mm.forum_id AND mm.user_id='. _uid .' WHERE m.id='. ($appr ? $appr : $del))) {
			if (db_affected()) {
				std_error('perms');
			} else {
				$del = $appr = 0;
			}
		}

		if (sq_check(0, $usr->sq)) {
			if ($appr) {
				logaction($usr->id, 'APPROVEMSG', $appr);
				fud_msg_edit::approve($appr);
			} else if ($del) {
				fud_msg_edit::delete(false, $del);
			}
		}
	}

	ses_update_status($usr->sid, '', 0);

	if (_uid) {
	$admin_cp = $accounts_pending_approval = $group_mgr = $reported_msgs = $custom_avatar_queue = $mod_que = $thr_exch = '';

	if ($usr->users_opt & 524288 || $is_a) {	// is_mod or admin.
		if ($is_a) {
			// Approval of custom Avatars.
			if ($FUD_OPT_1 & 32 && ($avatar_count = q_singleval('SELECT count(*) FROM fud26_users WHERE users_opt>=16777216 AND '. q_bitand('users_opt', 16777216) .' > 0'))) {
				$custom_avatar_queue = '| <a href="adm/admapprove_avatar.php?S='.s.'&amp;SQ='.$GLOBALS['sq'].'">Очередь внешних картинок</a> <span class="GenTextRed">('.$avatar_count.')</span>';
			}

			// All reported messages.
			if ($report_count = q_singleval('SELECT count(*) FROM fud26_msg_report')) {
				$reported_msgs = '| <a href="index.php?t=reported&amp;'._rsid.'" rel="nofollow">Извещения о сообщениях</a> <span class="GenTextRed">('.$report_count.')</span>';
			}

			// All thread exchange requests.
			if ($thr_exchc = q_singleval('SELECT count(*) FROM fud26_thr_exchange')) {
				$thr_exch = '| <a href="index.php?t=thr_exch&amp;'._rsid.'">Перенос темы</a> <span class="GenTextRed">('.$thr_exchc.')</span>';
			}

			// All account approvals.
			if ($FUD_OPT_2 & 1024 && ($accounts_pending_approval = q_singleval('SELECT count(*) FROM fud26_users WHERE users_opt>=2097152 AND '. q_bitand('users_opt', 2097152) .' > 0 AND id > 0'))) {
				$accounts_pending_approval = '| <a href="adm/admaccapr.php?S='.s.'&amp;SQ='.$GLOBALS['sq'].'">Учётные записи, ожидающие утверждения</a> <span class="GenTextRed">('.$accounts_pending_approval.')</span>';
			} else {
				$accounts_pending_approval = '';
			}

			$q_limit = '';
		} else {
			// Messages reported in moderated forums.
			if ($report_count = q_singleval('SELECT count(*) FROM fud26_msg_report mr INNER JOIN fud26_msg m ON mr.msg_id=m.id INNER JOIN fud26_thread t ON m.thread_id=t.id INNER JOIN fud26_mod mm ON t.forum_id=mm.forum_id AND mm.user_id='. _uid)) {
				$reported_msgs = '| <a href="index.php?t=reported&amp;'._rsid.'" rel="nofollow">Извещения о сообщениях</a> <span class="GenTextRed">('.$report_count.')</span>';
			}

			// Thread move requests in moderated forums.
			if ($thr_exchc = q_singleval('SELECT count(*) FROM fud26_thr_exchange te INNER JOIN fud26_mod m ON m.user_id='. _uid .' AND te.frm=m.forum_id')) {
				$thr_exch = '| <a href="index.php?t=thr_exch&amp;'._rsid.'">Перенос темы</a> <span class="GenTextRed">('.$thr_exchc.')</span>';
			}

			$q_limit = ' INNER JOIN fud26_mod mm ON f.id=mm.forum_id AND mm.user_id='. _uid;
		}

		// Messages requiring approval.
		if ($approve_count = q_singleval('SELECT count(*) FROM fud26_msg m INNER JOIN fud26_thread t ON m.thread_id=t.id INNER JOIN fud26_forum f ON t.forum_id=f.id '. $q_limit .' WHERE m.apr=0 AND f.forum_opt>=2')) {
			$mod_que = '<a href="index.php?t=modque&amp;'._rsid.'">Очередь модератора</a> <span class="GenTextRed">('.$approve_count.')</span>';
		}
	} else if ($usr->users_opt & 268435456 && $FUD_OPT_2 & 1024 && ($accounts_pending_approval = q_singleval('SELECT count(*) FROM fud26_users WHERE users_opt>=2097152 AND '. q_bitand('users_opt', 2097152) .' > 0 AND id > 0'))) {
		$accounts_pending_approval = '| <a href="adm/admaccapr.php?S='.s.'&amp;SQ='.$GLOBALS['sq'].'">Учётные записи, ожидающие утверждения</a> <span class="GenTextRed">('.$accounts_pending_approval.')</span>';
	} else {
		$accounts_pending_approval = '';
	}
	if ($is_a || $usr->group_leader_list) {
		$group_mgr = '| <a href="index.php?t=groupmgr&amp;'._rsid.'">Менеджер групп</a>';
	}

	if ($thr_exch || $accounts_pending_approval || $group_mgr || $reported_msgs || $custom_avatar_queue || $mod_que) {
		$admin_cp = '<br /><span class="GenText fb">Админ:</span> '.$mod_que.' '.$reported_msgs.' '.$thr_exch.' '.$custom_avatar_queue.' '.$group_mgr.' '.$accounts_pending_approval.'<br />';
	}
} else {
	$admin_cp = '';
}if (__fud_real_user__ && $FUD_OPT_1 & 1024) {	// PM_ENABLED
		$c = q_singleval('SELECT count(*) FROM fud26_pmsg WHERE duser_id='. _uid .' AND fldr=1 AND read_stamp=0');
		$private_msg = $c ? '<a href="index.php?t=pmsg&amp;'._rsid.'" class="UserControlPanel nw" title="Личная почта"><img src="theme/velopiter/images/top_pm'.img_ext.'" alt="" /> У вас <span class="GenTextRed">'.$c.'</span> '.convertPlural($c, array('непрочитанное личное сообщение','непрочитанных личных сообщения','непрочитанных личных сообщений')).'</a>&nbsp;&nbsp;' : '<a href="index.php?t=pmsg&amp;'._rsid.'" class="UserControlPanel nw" title="Личная почта"><img src="theme/velopiter/images/top_pm'.img_ext.'" alt="" /> Личная почта</a>&nbsp;&nbsp;';
	} else {
		$private_msg = '';
	}

	/* For sanity sake, we only select up to POSTS_PER_PAGE messages, simply because otherwise the form will
	 * become unmanageable.
	 */
	$r = q('SELECT
		m.*, COALESCE(m.flag_cc, u.flag_cc) AS disp_flag_cc, COALESCE(m.flag_country, u.flag_country) AS disp_flag_country,
		t.thread_opt, t.root_msg_id, t.last_post_id, t.forum_id,
		f.message_threshold, f.name AS frm_name,
		c.name AS cat_name,
		u.id AS user_id, u.alias AS login, u.avatar_loc, u.email, u.posted_msg_count, u.join_date, u.location,
		u.sig, u.custom_status, u.icq, u.jabber, u.affero, u.aim, u.msnm, u.yahoo, u.google, u.skype, u.twitter, u.last_visit AS time_sec, u.users_opt,
		l.name AS level_name, l.level_opt, l.img AS level_img,
		p.max_votes, p.expiry_date, p.creation_date, p.name AS poll_name, p.total_votes,
		pot.id AS cant_vote
	FROM
		fud26_msg m
	INNER JOIN fud26_thread t ON m.thread_id=t.id
	INNER JOIN fud26_forum f ON t.forum_id=f.id
	INNER JOIN fud26_fc_view v ON v.f=f.id
	'. ($is_a ? '' : ' INNER JOIN fud26_mod mm ON f.id=mm.forum_id AND mm.user_id='. _uid .' ') .'
	INNER JOIN fud26_cat c ON f.cat_id=c.id
	LEFT JOIN fud26_users u ON m.poster_id=u.id
	LEFT JOIN fud26_level l ON u.level_id=l.id
	LEFT JOIN fud26_poll p ON m.poll_id=p.id
	LEFT JOIN fud26_poll_opt_track pot ON pot.poll_id=p.id AND pot.user_id='. _uid .'
	WHERE f.forum_opt>=2 AND m.apr=0
	ORDER BY v.id, m.post_stamp DESC LIMIT '. $POSTS_PER_PAGE);

	$modque_message = '';
	$m_num = 0;

	/* Quick cheat to give us full access to the messages ;) */
	$perms = 2147483647;
	$_GET['start'] = 0;

	$usr->md = 1;
	while ($obj = db_rowobj($r)) {
		$modque_message .= '<tr class="RowStyleB"><td>
<b>Категория</b> '.$obj->cat_name.' :: <b>Форум</b> '.$obj->frm_name.' [<a href="index.php?t=modque&amp;appr='.$obj->id.'&amp;'._rsid.'&amp;SQ='.$GLOBALS['sq'].'">Утвердить</a>]&nbsp;&nbsp;[<a href="index.php?t=modque&amp;del='.$obj->id.'&amp;'._rsid.'&amp;SQ='.$GLOBALS['sq'].'">Удалить</a>]
</td></tr>
'.tmpl_drawmsg($obj, $usr, $perms, false, $m_num, null);
	}
	unset($r);

	if (!$modque_message) {
		$modque_message = '<tr class="RowStyleA"><td>Нет ожидающих утверждения сообщений</td></tr>';
	}

if ($FUD_OPT_2 & 2 || $is_a) {	// PUBLIC_STATS is enabled or Admin user.
	$page_gen_time = number_format(microtime(true) - __request_timestamp_exact__, 5);
	$page_stats = $FUD_OPT_2 & 2 ? '<br /><div class="SmallText al">Общее время, затраченное на создание страницы: '.convertPlural($page_gen_time, array(''.$page_gen_time.' секунда',''.$page_gen_time.' секунд')).'</div>' : '<br /><div class="SmallText al">Общее время, затраченное на создание страницы: '.convertPlural($page_gen_time, array(''.$page_gen_time.' секунда',''.$page_gen_time.' секунд')).'</div>';
} else {
	$page_stats = '';
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr" lang="ru" xml:lang="ru">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title><?php echo $GLOBALS['FORUM_TITLE'].$TITLE_EXTRA; ?></title>
<meta name="description" content="<?php echo (!empty($META_DESCR) ? $META_DESCR.'' : $GLOBALS['FORUM_DESCR'].''); ?>" />
<base href="<?php echo $GLOBALS['WWW_ROOT']; ?>" />
<script type="text/javascript" src="js/jquery.js"></script>
<script type="text/javascript" src="js/lib.js"></script>
<link rel="stylesheet" href="theme/velopiter/forum.css" type="text/css" media="screen" title="Default Forum Theme" />
<link rel="search" type="application/opensearchdescription+xml" title="<?php echo $GLOBALS['FORUM_TITLE']; ?> Search" href="<?php echo $GLOBALS['WWW_ROOT']; ?>open_search.php" />
<?php echo $RSS; ?>
</head>
<body>
<table class="wa" border="0" cellspacing="3" cellpadding="5"><tr><td class="ForumBackground" valign="top">
<!--  -->
<div class="ForumBackground vpheader">
<span class="headdescr">
<table width=100% cellspacing=0 cellpadding=0 border=0>
<tr height=100><td width=320 valign=bottom>
<a href="/"><img src="/forum/logo.gif" width=312 height=79 border=0 alt="ВелоПитер"></a>
</td><td valign=bottom>
<?php echo ($GLOBALS['FUD_OPT_1'] & 1 && $GLOBALS['FUD_OPT_1'] & 16777216 &0 ? '
  <div class="headsearch">
    <form id="headsearch" method="get" action="index.php">'._hs.'
      <br /><label accesskey="f" title="Поиск в форумах">Поиск в форумах:<br />
      <input type="text" name="srch" value="" size="15" placeholder="Поиск в форумах" /></label>
      <input type="hidden" name="t" value="search" />
      <input type="submit" name="btn_submit" value="Поиск" class="headbutton" />&nbsp;
    </form>
</div>
' : ''); ?>
</td>
<td align=left valign=bottom cellpadding=5 width="25%">
 <? include("../newstape_inc.php"); ?></td>
<td align=right width=350 valign=bottom>
<!-- banners start -->
<div align=right>
<table cellspacing=5 cellpadding=5>
<tr valign=top>
<td width=210>
<a href="http://velodrive.ru/" target="_blank"><img src="http://velopiter.spb.ru/vdr.gif" border="0" width="200" height="100" alt="Велосипеды Велодрайв"></a>
</td>

<td><a href="http://pk-99.ru/" target="_blank">
  <img border="0" src="http://velopiter.spb.ru/pk.gif" alt="ПИК-99"
width="100" height="100"></a></td>

<td width="100" height=100 align=right>
<a href="http://www.alienbike.ru"></a><object classid="clsid:d27cdb6e-ae6d-11cf-96b8-444553540000" codebase="http://fpdownload.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=6,0,0,0" width="100" height="100" id="090406_5" align="right">
<param name="allowScriptAccess" value="sameDomain" />
<param name="movie" value="http://velopiter.spb.ru/090406_5.swf" /><param name="loop" value="false" /><param name="menu" value="false" /><param name="quality" value="high" /><param name="bgcolor" value="#000000" /><embed src="http://velopiter.spb.ru/090406_5.swf" loop="false" menu="false" quality="high" bgcolor="#000000" width="100" height="100" name="../090406_5" align="middle" allowScriptAccess="sameDomain" type="application/x-shockwave-flash" pluginspage="http://www.macromedia.com/go/getflashplayer" />
</object></td>
</tr></table></div>
<!--- banners end-->
</td></tr></table>
</span>
</div>
<div class="UserControlPanel"><?php echo $private_msg; ?> 
  <?php echo (($FUD_OPT_1 & 8388608 || (_uid && $FUD_OPT_1 & 4194304) || $usr->users_opt & 1048576) ? '<a class="UserControlPanel nw" href="index.php?t=finduser&amp;btn_submit=Find&amp;'._rsid.'" title="Участники"><img src="theme/velopiter/images/top_members'.img_ext.'" alt="" /> Участники</a>&nbsp;&nbsp;' : ''); ?>
  <?php echo ($FUD_OPT_3 & 134217728 ? '<a class="UserControlPanel nw" href="index.php?t=cal&amp;'._rsid.'" title="Календарь"><img src="theme/velopiter/images/calendar'.img_ext.'" alt="" /> Календарь</a>&nbsp;&nbsp;' : ''); ?>
  <?php echo ($FUD_OPT_1 & 16777216 ? '<a class="UserControlPanel nw" href="index.php?t=search'.(isset($frm->forum_id) ? '&amp;forum_limiter='.(int)$frm->forum_id.'' : '' )  .'&amp;'._rsid.'" title="Поиск"><img src="theme/velopiter/images/top_search'.img_ext.'" alt="" /> Поиск</a>&nbsp;&nbsp;' : ''); ?>
  &nbsp;&nbsp;<a class="UserControlPanel nw" accesskey="h" href="index.php?t=help_index&amp;<?php echo _rsid; ?>" title="F.A.Q."><img src="theme/velopiter/images/top_help<?php echo img_ext; ?>" alt="" /> F.A.Q.</a>
  <?php echo (__fud_real_user__ ? '&nbsp;&nbsp;<a class="UserControlPanel nw" href="index.php?t=uc&amp;'._rsid.'" title="Доступ к панели управления пользователя"><img src="theme/velopiter/images/top_profile'.img_ext.'" alt="" /> Настройки</a>' : '&nbsp;&nbsp;<a class="UserControlPanel nw" href="index.php?t=register&amp;'._rsid.'" title="Регистрация"><img src="theme/velopiter/images/top_register'.img_ext.'" alt="" /> Регистрация</a>'); ?>
  <?php echo (__fud_real_user__ ? '&nbsp;&nbsp;<a class="UserControlPanel nw" href="index.php?t=login&amp;'._rsid.'&amp;logout=1&amp;SQ='.$GLOBALS['sq'].'" title="Выход"><img src="theme/velopiter/images/top_logout'.img_ext.'" alt="" /> Выход [ '.$usr->alias.' ]</a>' : '&nbsp;&nbsp;<a class="UserControlPanel nw" href="index.php?t=login&amp;'._rsid.'" title="Вход"><img src="theme/velopiter/images/top_login'.img_ext.'" alt="" /> Вход</a>'); ?>
  &nbsp;&nbsp;<a class="UserControlPanel nw" href="index.php?t=index&amp;<?php echo _rsid; ?>" title="Начало"><img src="theme/velopiter/images/top_home<?php echo img_ext; ?>" alt="" /> Начало</a>
  <?php echo ($is_a || ($usr->users_opt & 268435456) ? '&nbsp;&nbsp;<a class="UserControlPanel nw" href="adm/index.php?S='.s.'&amp;SQ='.$GLOBALS['sq'].'" title="Административный центр"><img src="theme/velopiter/images/top_admin'.img_ext.'" alt="" /> Административный центр</a>' : ''); ?>
</div>
<br /><?php echo $admin_cp; ?>
<table cellspacing="0" cellpadding="2" class="ContentTable">
<tr><th>Ожидающие утверждения сообщения</th></tr>
<?php echo $modque_message; ?>
</table>
<br /><div class="ac"><span class="curtime"><b>Текущее время:</b> <?php echo strftime("%a %b %#d %H:%M:%S %Z %Y", __request_timestamp__); ?></span></div>
<?php echo $page_stats; ?>
</td>
<!-- <td class="ForumBackground" valign="top"></td> -->
</tr></table>

<div class="ForumBackground ac foot">
<b>.::</b> <a href="mailto:<?php echo $GLOBALS['ADMIN_EMAIL']; ?>">Обратная связь</a> <b>::</b> <a href="index.php?t=index&amp;<?php echo _rsid; ?>">Начало</a> <b>::.</b>
<p>
<span class="SmallText">При поддержке: FUDforum <?php echo $GLOBALS['FORUM_VERSION']; ?>.<br /> Copyright © 2001-2010 <a href="http://fudforum.org/">FUDforum Bulletin Board Software</a></span>
</p>
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
alt="Экстремальный портал VVV.RU"></a>
</span>

</div>
</body></html>
