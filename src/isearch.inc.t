<?php
/**
* copyright            : (C) 2001-2010 Advanced Internet Designs Inc.
* email                : forum@prohost.org
* $Id: isearch.inc.t 4994 2010-09-02 17:33:29Z naudefj $
*
* This program is free software; you can redistribute it and/or modify it
* under the terms of the GNU General Public License as published by the
* Free Software Foundation; version 2 of the License.
**/

function text_to_worda($text)
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
	while (preg_match('!{TEMPLATE: post_html_quote_start_p1}(.*?){TEMPLATE: post_html_quote_start_p2}(.*?){TEMPLATE: post_html_quote_end}!is', $body)) {
		$body = preg_replace('!{TEMPLATE: post_html_quote_start_p1}(.*?){TEMPLATE: post_html_quote_start_p2}(.*?){TEMPLATE: post_html_quote_end}!is', '', $body);
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

	ins_m('{SQL_TABLE_PREFIX}search', 'word', 'text', $w2);
	if ($subj && $w1) {
		db_li('INSERT INTO {SQL_TABLE_PREFIX}title_index (word_id, msg_id) SELECT id, '. $msg_id .' FROM {SQL_TABLE_PREFIX}search WHERE word IN('. implode(',', $w1) .')', $ef);
	}
	db_li('INSERT INTO {SQL_TABLE_PREFIX}index (word_id, msg_id) SELECT id, '. $msg_id .' FROM {SQL_TABLE_PREFIX}search WHERE word IN('. implode(',', $w2) .')', $ef);
}
?>
