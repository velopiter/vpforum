<?php
/**
* copyright            : (C) 2001-2010 Advanced Internet Designs Inc.
* email                : forum@prohost.org
* $Id: admsysinfo.php 4981 2010-08-21 07:22:05Z naudefj $
*
* This program is free software; you can redistribute it and/or modify it
* under the terms of the GNU General Public License as published by the
* Free Software Foundation; version 2 of the License.
**/

	require('./GLOBALS.php');
	fud_use('adm.inc', true);

function get_php_setting($val)
{
	$r =  (ini_get($val) == '1' ? 1 : 0);
	return $r ? 'ON' : 'OFF';
}

function get_server_software()
{
	if (isset($_SERVER['SERVER_SOFTWARE'])) {
		return $_SERVER['SERVER_SOFTWARE'];
	} else if (($sf = getenv('SERVER_SOFTWARE'))) {
		return $sf;
	} else {
		return 'n/a';
	}
}

	require($WWW_ROOT_DISK .'adm/header.php');
?>
<h2>System Configuration</h2>
<p>Overview of your system's configuration. Please include this when reporting bugs on the <a href="http://fudforum.org/">support forum</a>:</p>
<table class="datatable">
<tr class="field">
	<td><b>FUDforum version:</b></td>
	<td><?php echo $FORUM_VERSION; ?></td>
</tr>
<tr class="field">
	<td><b>PHP version:</b></td>
	<td><?php echo PHP_VERSION; ?></td>
</tr>
<tr class="field">
	<td><b>PHP built on:</b></td>
	<td><?php echo (@php_uname() ? php_uname() : 'n/a'); ?></td>
</tr>
<tr class="field">
	<td><b>Database type:</b></td>
	<td><?php echo __dbtype__ .' ('. $DBHOST_DBTYPE .')'; ?></td>
</tr>
<tr class="field">
	<td><b>Database version:</b></td>
	<td><?php echo db_version(); ?></td>
</tr>
<tr class="field">
	<td><b>Web server:</b></td>
	<td><?php echo get_server_software(); ?></td>
</tr>
<?php if (function_exists('sys_getloadavg') && ($load = sys_getloadavg()) ) { ?>
	<tr class="field">
		<td><b>Web Server load:</b></td>
		<td><?php echo $load[1]; ?></td>
	</tr>
<?php } ?>
<tr class="field">
	<td><b>Web server to PHP interface:</b></td>
	<td><?php echo php_sapi_name(); ?></td>
</tr>
<?php
	if (extension_loaded('posix')) {
echo '<tr class="field">
	<td><b>WebServer User/Group:</b></td>
	<td>'. posix_getgid().' / '.posix_getuid().'</td>
</tr>';
	}
?>

<tr class="field">
	<td valign="top"><b>Relevant PHP settings:</b></td>
	<td>
		<table cellspacing="1" cellpadding="1" border="0">
			<tr>
				<td>Safe mode:</td>
				<td><?php echo get_php_setting('safe_mode'); ?></td>
			</tr>
			<tr>
				<td>Open basedir:</td>
				<td><?php echo (($ob = ini_get('open_basedir')) ? $ob : 'none'); ?></td>
			</tr>
			<tr>
				<td>Display errors:</td>
				<td><?php echo get_php_setting('display_errors'); ?></td>
			</tr>
			<tr>
				<td>File uploads:</td>
				<td><?php echo get_php_setting('file_uploads'); ?></td>
			</tr>
			<tr>
				<td>Maximum file upload size:</td>
				<td><?php echo ini_get('upload_max_filesize'); ?></td>
			</tr>
			<tr>
				<td>Magic quotes:</td>
				<td><?php echo get_php_setting('magic_quotes_gpc'); ?></td>
			</tr>
			<tr>
				<td>Register globals:</td>
				<td><?php echo get_php_setting('register_globals'); ?></td>
			</tr>
			<tr>
				<td>Output buffering:</td>
				<td><?php echo (is_numeric(ini_get('output_buffering')) ? 'Yes' : 'No'); ?></td>
			</tr>
			<tr>
				<td>Disabled functions:</td>
				<td><?php echo (($df=str_replace(',', ', ', ini_get('disable_functions'))) ? $df : 'none'); ?></td>
			</tr>
			<tr>
				<td>PSpell support:</td>
				<td><?php echo extension_loaded('pspell') ? 'Yes' : 'No'; ?></td>
			</tr>
			<tr>
				<td>Zlib support:</td>
				<td><?php echo extension_loaded('zlib') ? 'Yes' : 'No'; ?></td>
			</tr>
		</table>
	</td>
</tr>
</table>

[ <a href="admphpinfo.php?<?php echo __adm_rsid; ?>">Detailed PHP info &raquo;</a> ]

<?php require($WWW_ROOT_DISK .'adm/footer.php'); ?>
