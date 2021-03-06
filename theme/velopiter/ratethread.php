<?php
/**
* copyright            : (C) 2001-2010 Advanced Internet Designs Inc.
* email                : forum@prohost.org
* $Id: ratethread.php.t 5073 2010-11-11 18:01:28Z naudefj $
*
* This program is free software; you can redistribute it and/or modify it
* under the terms of the GNU General Public License as published by the
* Free Software Foundation; version 2 of the License.
**/

if (_uid === '_uid') {
		exit('Sorry, you can not access this page.');
	}


	if (isset($_GET['rate_thread_id'], $_GET['sel_vote']) && ($rt = (int) $_GET['sel_vote'])) {
		$th = (int) $_GET['rate_thread_id'];

		/* Determine if the user has permission to rate the thread. */
		if (!q_singleval('SELECT t.id
				FROM fud26_thread t
				LEFT JOIN fud26_mod m ON t.forum_id=m.forum_id AND m.user_id='. _uid .'
				INNER JOIN fud26_group_cache g1 ON g1.user_id='. (_uid ? 2147483647 : 0) .' AND g1.resource_id=t.forum_id
				'.(_uid ? ' LEFT JOIN fud26_group_cache g2 ON g2.user_id='. _uid .' AND g2.resource_id=t.forum_id ' : '').'
				WHERE t.id='. $th . ($is_a ? '' : ' AND (m.id IS NOT NULL OR '. q_bitand(_uid ? 'COALESCE(g2.group_cache_opt, g1.group_cache_opt)' : 'g1.group_cache_opt', 1024) .' > 0)') .' LIMIT 1')) {
			std_error('access');
		}

		if (db_li('INSERT INTO fud26_thread_rate_track (thread_id, user_id, stamp, rating) VALUES('. $th .', '. _uid .', '. __request_timestamp__ .', '. $rt .')', $ef)) {
			$rt = db_saq('SELECT count(*), ROUND(AVG(rating)) FROM fud26_thread_rate_track WHERE thread_id='.$th);
			q('UPDATE fud26_thread SET rating='. (int)$rt[1] .', n_rating='. (int)$rt[0] .' WHERE id='. $th);

			if ($is_a) {
				$MOD = 1;
			} else {
				$MOD = q_singleval('SELECT m.id FROM fud26_thread t INNER JOIN fud26_mod m ON m.forum_id=t.forum_id WHERE t.id='. $th .' AND m.user_id='. _uid);
			}

			$frm = new StdClass;
			$frm->id = $th;
			$frm->n_rating = (int) $rt[0];
			$frm->rating = (int) $rt[1];

			exit('&nbsp;('.($MOD ? '<a href="javascript://" onclick="window_open(\''.$GLOBALS['WWW_ROOT'].'index.php?t=ratingtrack&amp;'._rsid.'&amp;th='.$frm->id.'\', \'th_rating_track\', 300, 400);">' : '' )  .'<img src="theme/velopiter/images/'.$frm->rating.'stars.gif" title="'.$frm->rating.' из '.convertPlural($frm->n_rating, array(''.$frm->n_rating.' голоса',''.$frm->n_rating.' голосов',''.$frm->n_rating.' голосов')).'" alt=""/>'.($MOD ? '</a>' : '' )  .') '.convertPlural($frm->n_rating, array(''.$frm->n_rating.' голос',''.$frm->n_rating.' голоса',''.$frm->n_rating.' голосов')).'');
		}
	}
