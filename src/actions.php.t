<?php
/**
* copyright            : (C) 2001-2010 Advanced Internet Designs Inc.
* email                : forum@prohost.org
* $Id: actions.php.t 5059 2010-10-24 15:51:36Z naudefj $
*
* This program is free software; you can redistribute it and/or modify it
* under the terms of the GNU General Public License as published by the
* Free Software Foundation; version 2 of the License.
**/

/*{PRE_HTML_PHP}*/

	if (!($FUD_OPT_1 & 536870912) || (!_uid && $FUD_OPT_3 & 131072)) {
		std_error('disabled');
	}

	ses_update_status($usr->sid, '{TEMPLATE: actions_update}');

/*{POST_HTML_PHP}*/

	if (isset($_GET['o'])) {
		switch ($_GET['o']) {
			case 'alias':		$o = 'u.alias'; break;
			case 'time':
			default:		$o = 's.time_sec';
		}	
	} else {
		$o = 'u.alias';
	}

	if (isset($_GET['s']) && $_GET['s'] == 'a') {
		$s = 'ASC';
	} else {
		$s = 'DESC';
	}

	$limit = &get_all_read_perms(_uid, ($usr->users_opt & 524288));

	$c = uq('SELECT
			s.action, s.user_id, s.forum_id,
			u.alias, u.custom_color, s.time_sec, u.users_opt,
			m.id, m.subject, m.post_stamp,
			t.forum_id,
			mm1.id, mm2.id
		FROM {SQL_TABLE_PREFIX}ses s
		LEFT JOIN {SQL_TABLE_PREFIX}users u ON s.user_id=u.id
		LEFT JOIN {SQL_TABLE_PREFIX}msg m ON u.u_last_post_id=m.id
		LEFT JOIN {SQL_TABLE_PREFIX}thread t ON m.thread_id=t.id
		LEFT JOIN {SQL_TABLE_PREFIX}mod mm1 ON mm1.forum_id=t.forum_id AND mm1.user_id='. _uid .'
		LEFT JOIN {SQL_TABLE_PREFIX}mod mm2 ON mm2.forum_id=s.forum_id AND mm2.user_id='. _uid .'
		WHERE s.time_sec>'. (__request_timestamp__ - ($LOGEDIN_TIMEOUT * 60)) .' AND s.user_id!='. _uid .'
		ORDER BY '. $o .' '. $s);

	$action_data = ''; $uc = 0;
	while ($r = db_rowarr($c)) {
		++$uc; // Update loggedin user count.

		if ($r[6] & 32768 && !$is_a) {
			continue;
		}

		if ($r[3]) {
			$user_login = '{TEMPLATE: reg_user_link}';

			if (!$r[9]) {
				$last_post = '{TEMPLATE: last_post_na}';
			} else {
				$last_post = (!$is_a && !$r[11] && empty($limit[$r[10]])) ? '{TEMPLATE: no_view_perm}' : '{TEMPLATE: last_post}';
			}
		} else {
			$user_login = '{TEMPLATE: anon_user}';
			$last_post = '{TEMPLATE: last_post_na}';
		}

		if (!$r[2] || ($is_a || !empty($limit[$r[2]]) || $r[12])) {
			if ($FUD_OPT_2 & 32768) {	// USE_PATH_INFO
				if (($p = strpos($r[0], 'href="')) !== false) {
					$p += 6;
					$p = substr($r[0], $p, (strpos($r[0], '"', $p) - $p));

					if ($p{strlen($p) - 1} == '/') {
						$tmp = explode('/', substr(str_replace('{ROOT}', '', $p), 1, -1));
						if ($FUD_OPT_1 & 128) {	// SESSION_USE_URL
							array_pop($tmp);
						}
						if ($FUD_OPT_2 & 8192) {	// TRACK_REFERRALS
							array_pop($tmp);
						}
						$tmp[] = _rsid;
						$sn = '{ROOT}/'. implode('/', $tmp);
					} else {
						$sn = $p .'/'. _rsid;
					}
					$action = str_replace($p, $sn, $r[0]);
				} else {
					$action = $r[0];
				}
			} else {
				if (($p = strpos($r[0], '?')) !== false) {
					$action = substr_replace($r[0], '?'. _rsid .'&', $p, 1);
				} else if (($p = strpos($r[0], '.php')) !== false) {
					$action = substr_replace($r[0], '.php?'. _rsid .'&', $p, 4);
				} else {
					$action = $r[0];
				}
			}
		} else {
			$action = '{TEMPLATE: no_view_perm}';
		}

		$action_data .= '{TEMPLATE: action_entry}';
	}
	unset($c);

/*{POST_PAGE_PHP_CODE}*/
?>
{TEMPLATE: ACTION_PAGE}
<?php
	/* Update loggedin user stats if needed. */
	q('UPDATE {SQL_TABLE_PREFIX}stats_cache SET most_online='. $uc .', most_online_time='. __request_timestamp__ .' WHERE most_online < '. $uc);
?>