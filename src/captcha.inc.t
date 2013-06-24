<?php
/**
* copyright            : (C) 2001-2010 Advanced Internet Designs Inc.
* email                : forum@prohost.org
* $Id: captcha.inc.t 4942 2010-04-13 16:06:47Z naudefj $
*
* This program is free software; you can redistribute it and/or modify it
* under the terms of the GNU General Public License as published by the
* Free Software Foundation; version 2 of the License.
**/

/* Generate a CAPTCHA question to display. */
function generate_turing_val(&$rt)
{
	if (defined('plugins')) {
		@list($text, $rt) = plugin_call_hook('CAPTCHA');
		if (isset($text) && isset($rt)) {
			$rt = md5($rt);
			return $text;
		}
	}

	$t = array(
		array('..#####..','..#####..','.#.......','.#######.','..#####..','.#######.','..#####..','..#####..','....###....','.########..','..######..','.########.','.########.','..######...','.##.....##.','.####.','.......##.','.##....##.','.##.......','.##.....##.','.##....##.','.########..','..#######..','.########..','..######..','.########.','.##.....##.','.##.....##.','.##......##.','.##.....##.','.##....##.','.########.'),
		array('.#.....#.','.#.....#.','.#....#..','.#.......','.#.....#.','.#....#..','.#.....#.','.#.....#.','...##.##...','.##.....##.','.##....##.','.##.......','.##.......','.##....##..','.##.....##.','..##..','.......##.','.##...##..','.##.......','.###...###.','.###...##.','.##.....##.','.##.....##.','.##.....##.','.##....##.','....##....','.##.....##.','.##.....##.','.##..##..##.','..##...##..','..##..##..','......##..'),
		array('.......#.','.......#.','.#....#..','.#.......','.#.......','.....#...','.#.....#.','.#.....#.','..##...##..','.##.....##.','.##.......','.##.......','.##.......','.##........','.##.....##.','..##..','.......##.','.##..##...','.##.......','.####.####.','.####..##.','.##.....##.','.##.....##.','.##.....##.','.##.......','....##....','.##.....##.','.##.....##.','.##..##..##.','...##.##...','...####...','.....##...'),
		array('..#####..','....###..','.#....#..','.######..','.######..','....#....','..#####..','..######.','.##.....##.','.########..','.##.......','.######...','.######...','.##...####.','.#########.','..##..','.......##.','.#####....','.##.......','.##.###.##.','.##.##.##.','.########..','.##.....##.','.########..','..######..','....##....','.##.....##.','.##.....##.','.##..##..##.','....###....','....##....','....##....'),
		array('.#.......','.......#.','.#######.','.......#.','.#.....#.','...#.....','.#.....#.','.......#.','.#########.','.##.....##.','.##.......','.##.......','.##.......','.##....##..','.##.....##.','..##..','.##....##.','.##..##...','.##.......','.##.....##.','.##..####.','.##........','.##..##.##.','.##...##...','.......##.','....##....','.##.....##.','..##...##..','.##..##..##.','...##.##...','....##....','...##.....'),
		array('.#.......','.#.....#.','......#..','.#.....#.','.#.....#.','...#.....','.#.....#.','.#.....#.','.##.....##.','.##.....##.','.##....##.','.##.......','.##.......','.##....##..','.##.....##.','..##..','.##....##.','.##...##..','.##.......','.##.....##.','.##...###.','.##........','.##....##..','.##....##..','.##....##.','....##....','.##.....##.','...##.##...','.##..##..##.','..##...##..','....##....','..##......'),
		array('.#######.','..#####..','......#..','..#####..','..#####..','...#.....','..#####..','..#####..','.##.....##.','.########..','..######..','.########.','.##.......','..######...','.##.....##.','.####.','..######..','.##....##.','.########.','.##.....##.','.##....##.','.##........','..#####.##.','.##.....##.','..######..','....##....','..#######..','....###....','..###..###..','.##.....##.','....##....','.########.'),
		array('2','3','4','5','6','7','8','9','A','B','C','E','F','G','H','I','J','K','L','M','N','P','Q','R','S','T','U','V','W','X','Y','Z')
	);

	$rv = array_rand($t[0], 4);
	$captcha = $t[7][$rv[0]] . $t[7][$rv[1]] . $t[7][$rv[2]] . $t[7][$rv[3]];
	$rt = md5($captcha);

	if (($GLOBALS['FUD_OPT_3'] & 33554432) && extension_loaded('gd') && function_exists('imagecreate') ) {
		ses_putvar((int)$GLOBALS['usr']->sid, $captcha);
		return '{TEMPLATE: image_captcha_link}';
	} else {
		$bg_fill_chars = array(' ', '.', ',', '`', '_', '\'');
		$bg_fill = $bg_fill_chars[array_rand($bg_fill_chars)];
		$fg_fill_chars = array('&#35;', '&#64;', '&#36;', '&#42;', '&#88;');
		$fg_fill = $fg_fill_chars[array_rand($fg_fill_chars)];

		// Generate turing text.
		$text = '';
		for ($i = 0; $i < 7; $i++) {
			foreach ($rv as $v) {
				$text .= str_replace('#', $fg_fill, str_replace('.', $bg_fill, $t[$i][$v]));
			}
			$text .= '<br />';
		}
	 	return $text;
	}
}

/* Test if user entered a valid response to the CAPTCHA test. */
function test_turing_answer($test, $res)
{
	if (defined('plugins')) {
		$ok = plugin_call_hook('CAPTCHA_VALIDATE', array($test, $res));
	 	if ($ok == 0) {
			return false;
		} elseif ($ok == 1) {
			return true;
		}
	}

	if (empty($test) || empty($res)) {
		return false;
	}

	if (md5(strtoupper(trim($test))) != $res) {
		return false;
	} else {
		return true;
	}
}

?>