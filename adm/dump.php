<?php
/**
* copyright            : (C) 2001-2006 Advanced Internet Designs Inc.
* email                : forum@prohost.org
* $Id: admdump.php,v 1.65 2005/12/07 18:07:46 hackie Exp $
*
* This program is free software; you can redistribute it and/or modify it
* under the terms of the GNU General Public License as published by the
* Free Software Foundation; either version 2 of the License, or
* (at your option) any later version.
**/

	@set_time_limit(6000000000);

	ini_set('max_execution_time', 6000000);
	ini_set('error_reporting', E_ALL);
	ini_set('display_errors', 1);


function backup_dir($dirp, $fp, $write_func, $keep_dir, $p=0)
{
	global $BUF_SIZE;

	$dirs = array(realpath($dirp));
	$repl = realpath($GLOBALS[$keep_dir]);
	$is_win = !strncasecmp('win', PHP_OS, 3);
	
	while (list(,$v) = each($dirs)) {
		if (!is_readable($v)) {
			echo 'Could not open "'.$v.'" for reading<br>';
			return;
		}
		echo 'Processing directory: '.$v.'<br>';

		if (!($files = glob($v . '/{.h*,.p*,.n*,.m*,*}', GLOB_BRACE|GLOB_NOSORT))) {
			continue;
		}
		if ($is_win) {
			$v = str_replace("\\", '/', $v);
		}
		$dpath = trim(str_replace($repl, $keep_dir, $v), '/') . '/';

		if ($p) {
			$write_func($fp, '||WWW_ROOT_DISK/blank.gif||' . filesize($GLOBALS['WWW_ROOT_DISK'].'blank.gif') . "||\n" . file_get_contents($GLOBALS['WWW_ROOT_DISK'].'lib.js') . "\n");
			$write_func($fp, '||WWW_ROOT_DISK/lib.js||' . filesize($GLOBALS['WWW_ROOT_DISK'].'lib.js') . "||\n" . file_get_contents($GLOBALS['WWW_ROOT_DISK'].'blank.gif') . "\n");
			$p = 0;
		}

		foreach ($files as $f) {
			if (is_link($f)) {
				continue;
			}
			$name = basename($f);

			if (is_dir($f)) {
				if ($name == 'tmp' || $name == 'theme') {
					continue;
				} else if ($keep_dir == 'DATA_DIR' && ($name == 'adm' || $name == 'images')) {
					continue;
				}
				$dirs[] = $f;
				continue;
			}
			if ($name == 'GLOBALS.php' || ($keep_dir == 'DATA_DIR' && ($name == 'lib.js' || $name == 'blank.gif'))) {
				continue;
			}
			if (!is_readable($f)) {
				echo "WARNING: unable to open '".$f."' for reading<br>\n";
				continue;
			}
			$ln = filesize($f);
			if ($ln < $BUF_SIZE) {
				$write_func($fp, '||' . $dpath . $name . '||' . $ln . "||\n" . file_get_contents($f) . "\n");
			} else {
				$write_func($fp, '||' . $dpath . $name . '||' . $ln . "||\n");
				$fp2 = fopen($f, 'rb');
				while (($buf = fread($fp2, $BUF_SIZE))) {
					$write_func($fp, $buf);
				}
				fclose($fp2);
				$write_func($fp, "\n");
			}
		}
	}
}

	require('./GLOBALS.php');
	fud_use('db.inc');
	fud_use('mem_limit.inc', true);
	// uncomment the lines below if you wish to run this script via command line
	// fud_use('adm_cli.inc', 1); // this contains cli_execute() function.
	// cli_execute('');
	// when using this the script accepts 2 arguments
	// php admdump.php /path/to/dump_file [compress]
	// compress is optional and should only be specified if you want to datadump to be compressed

	/* check for cli arguments */
	if (defined('forum_debug')) {
		if (empty($_SERVER['argv'][1])) {
			exit("Usage: php admdump.php /path/to/dump_file [compress]\n");
		}
		$_POST['submitted'] = 1;
		$_POST['path'] = $_SERVER['argv'][1];
		if (!empty($_SERVER['argv'][2])) {
			$_POST['compress'] = 1;
		}
	}

	/*
	 * Check for HTTP AUTH, before going for the usual cookie/session auth
	 * this is done to allow for easier running of this process via an
	 * automated cronjob.
	 */

		fud_use('adm.inc', true);

	require($WWW_ROOT_DISK . 'adm/admpanel.php');
		
		$_POST['path']='FUD.backup.fud';

			//$fp = gzopen($_POST['path'], 'wb9');
			//$write_func = 'gzwrite';
			$fp = fopen($_POST['path'], 'wb');
			$write_func = 'fwrite';

		echo "Compressing forum datafiles<br>\n";
		$write_func($fp, "\n----FILES_START----\n");
		backup_dir($DATA_DIR, $fp, $write_func, 'DATA_DIR');
		backup_dir($WWW_ROOT_DISK.'images/', $fp, $write_func, 'WWW_ROOT_DISK', 1);
		backup_dir($WWW_ROOT_DISK.'adm/', $fp, $write_func, 'WWW_ROOT_DISK');

		$write_func($fp, "\n----FILES_END----\n");

		$write_func($fp, "\n----SQL_START----\n");

		/* read sql table defenitions */
		
		if (!($files = glob($DATA_DIR . 'sql/*.tbl', GLOB_NOSORT))) {
			exit('Failed to open SQL directory "'.$DATA_DIR.'sql/"');
		}
		foreach ($files as $f) {
			$sql_data = file_get_contents($f);
			$sql_data = preg_replace(array("!\#.*?\n!s","!\s+!s"), array("\n"," "), $sql_data);
			$sql_data = str_replace(";", "\n", $sql_data);
			$write_func($fp, $sql_data . "\n");
		}
		unset($files);

		$sql_table_list = get_fud_table_list();
		db_lock(implode(' WRITE, ', $sql_table_list) . ' WRITE');

		foreach($sql_table_list as $tbl_name) {
			/* not needed, will be rebuilt by consistency checker */
			if (!strncmp($tbl_name, $DBHOST_TBL_PREFIX.'tv_', strlen($DBHOST_TBL_PREFIX.'tv_')) || 
				$tbl_name == $DBHOST_TBL_PREFIX . 'ses' ||
				!strncmp($tbl_name, $DBHOST_TBL_PREFIX.'fl_', strlen($DBHOST_TBL_PREFIX.'fl_'))
			) {
				continue;
			}
			$num_entries = q_singleval('SELECT count(*) FROM '.$tbl_name);

			echo 'Processing table: '.$tbl_name.' ('.$num_entries.') .... ';
			if ($num_entries) {
				$db_name = preg_replace('!^'.preg_quote($DBHOST_TBL_PREFIX).'!', '', $tbl_name);
				$write_func($fp, "\0\0\0\0".$db_name."\n");
				
				$c = uq('SELECT * FROM '.$tbl_name);
				while ($r = db_rowarr($c)) {
					$tmp = '';
					foreach ($r as $v) {
						$tmp .= _esc($v).',';
					}
					/* make sure new lines inside queries don't cause problems */
					if (strpos($tmp, "\n") !== false) {
						$tmp = str_replace("\n", '\n', $tmp);
					}
					$write_func($fp, "(".substr($tmp, 0, -1).")\n");
				}
				unset($c);
			}

			echo "DONE<br>\n";
		}

		$write_func($fp, "\n----SQL_END----\n");

		/* backup GLOBALS.php */
		fud_use('glob.inc', true);
		$skip = array_flip(array('WWW_ROOT','COOKIE_PATH','COOKIE_DOMAIN','COOKIE_NAME',
			'DBHOST','DBHOST_USER','DBHOST_PASSWORD','DBHOST_DBNAME','DBHOST_TBL_PREFIX',
			'ADMIN_EMAIL','DATA_DIR','WWW_ROOT_DISK','INCLUDE','ERROR_PATH',
			'MSG_STORE_DIR','TMP','FILE_STORE','FORUM_SETTINGS_PATH'));
		$vars = array();
		foreach (read_help() as $k => $v) {
			if ($v[1] != NULL || isset($skip[$k])) {
				continue;
			}
			$vars[$k] = $$k;
		}
		$write_func($fp, "\n\$global_vals = ".var_export($vars, 1).";\n");

		if (isset($_POST['compress'])) {
			gzclose($fp);
		} else {
			fclose($fp);
		}

		db_unlock();

		echo "Backup Process is Complete<br>";
		echo "Backup file can be found at: <b>".$_POST['path']."</b>, it is occupying ".filesize($_POST['path'])." bytes<br>\n";
?>
<h2>FUDforum Backup</h2>
<form method="post" action="admdump.php">
<?php echo _hs; ?>
<table class="datatable solidtable">
<tr class="field">
	<td>Backup Save Path<br><font size="-1">path on the disk, where you wish the forum data dump to be saved.</font></td>
	<td><?php echo $path_error; ?><input type="text" value="<?php echo $path; ?>" name="path" size=40></td>
</tr>
<?php if($gz) { ?>
<tr class="field">
	<td>Use Gzip Compression<br><font size="-1">if you choose this option, the backup files will be compressed using Gzip compression. This may make the backup process a little slower, but will save a lot of harddrive space.</font></td>
	<td><input type="checkbox" name="compress" value="1" <?php echo $compress; ?>> Yes</td>
</tr>
<?php } ?>
<tr class="fieldaction"><td colspan=2 align=right><input type="submit" name="btn_submit" value="Make Backup"></td></tr>
<input type="hidden" name="submitted" value="1">
</form>
</table>
<?php
	} /* isset($_POST['submitted']) */

	require($WWW_ROOT_DISK . 'adm/admclose.html');
?>
