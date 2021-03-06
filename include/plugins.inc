<?php
/**
* copyright            : (C) 2001-2010 Advanced Internet Designs Inc.
* email                : forum@prohost.org
* $Id: plugins.inc 5031 2010-10-09 10:13:39Z naudefj $
*
* This program is free software; you can redistribute it and/or modify it
* under the terms of the GNU General Public License as published by the
* Free Software Foundation; version 2 of the License.
**/

if ($GLOBALS['FUD_OPT_3'] & 4194304) {	// Plugins enabled?
	define('plugins', 1);
}

/* Add/register a plugin hook. */
function plugin_add_hook($type, $func) {
	global $plugin_hooks;
	$plugin_hooks[$type][] = $func;
}

/* Execute all registered plugins of a particular hook. */
function plugin_call_hook($type, $data=array()) {
	global $plugin_hooks;

	if (isset($plugin_hooks[$type])) {
		foreach ($plugin_hooks[$type] as $func) {
			$data = call_user_func($func, $data);
		}
	}
	return $data;
}

/* Load/activate all the plugins that are enabled. */
function plugin_activate_all_enbled() {
	global $plugin_hooks;

	$plugins = plugin_load_from_cache();
	foreach ($plugins as $plugin) {
		include_once $GLOBALS['PLUGIN_PATH'] . $plugin;
	}
}

/* Read list of enabled plugins from cache. */
function plugin_load_from_cache() {
	$cachefile = $GLOBALS['FORUM_SETTINGS_PATH'] .'plugin_cache';

	if (file_exists($cachefile)) {
		include $cachefile;
		if (!isset($plugins)) {
			$plugins = array();
		}
	} else {
		$plugins = array();
	}

	return $plugins;
}

/* Write list of enabled plugins to cache. */
function plugin_rebuild_cache($plugins) {
        $fp = fopen($GLOBALS['TMP'] .'plugin_cache', 'w');
        fwrite($fp, '<?php $plugins = '. var_export($plugins, 1) .'; ?>');
        fclose($fp);

        if (!strncasecmp('win', PHP_OS, 3)) {
                @unlink($GLOBALS['FORUM_SETTINGS_PATH'] .'plugin_cache');
        }
        rename($GLOBALS['TMP'] .'plugin_cache', $GLOBALS['FORUM_SETTINGS_PATH'] .'plugin_cache');
}

?>
