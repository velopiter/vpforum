<?php
/**
* copyright            : (C) 2001-2010 Advanced Internet Designs Inc.
* email                : forum@prohost.org
* $Id: admmime.php 5019 2010-10-07 17:35:21Z naudefj $
*
* This program is free software; you can redistribute it and/or modify it
* under the terms of the GNU General Public License as published by the
* Free Software Foundation; version 2 of the License.
**/

	require('./GLOBALS.php');
	fud_use('adm.inc', true);
	fud_use('widgets.inc', true);

	$tbl = $GLOBALS['DBHOST_TBL_PREFIX'];

	if (isset($_GET['del'])) {
		q('DELETE FROM '. $tbl .'mime WHERE id='. (int)$_GET['del']);
	}

	if (isset($_GET['edit'])) {
		list($mime_descr, $mime_mime_hdr, $mime_fl_ext, $mime_icon) = db_saq('SELECT descr, mime_hdr, fl_ext, icon FROM '. $tbl .'mime WHERE id='. (int)$_GET['edit']);
		$edit = (int)$_GET['edit'];
	} else {
		$mime_icon = $edit = $mime_descr = $mime_mime_hdr = $mime_fl_ext = '';
	}

	if (isset($_FILES['icoul']) && $_FILES['icoul']['size'] && preg_match('!\.(jpg|jpeg|gif|png)$!i', $_FILES['icoul']['name'])) {
		move_uploaded_file($_FILES['icoul']['tmp_name'], $GLOBALS['WWW_ROOT_DISK'] .'images/mime/'. $_FILES['icoul']['name']);
		if (empty($_POST['mime_icon'])) {
			$_POST['mime_icon'] = $_FILES['icoul']['name'];
		}
	}

	if (isset($_POST['btn_update'], $_POST['edit'])) {
		q('UPDATE '. $tbl .'mime SET descr='. _esc($_POST['mime_descr']) .', mime_hdr='. _esc($_POST['mime_mime_hdr']) .', fl_ext='. _esc($_POST['mime_fl_ext']).', icon='._esc($_POST['mime_icon']).' WHERE id='. (int)$_POST['edit']);
	} else if (isset($_POST['btn_submit'])) {
		q('INSERT INTO '. $tbl .'mime (descr, mime_hdr, fl_ext, icon) VALUES ('. _esc($_POST['mime_descr']) .', '. _esc($_POST['mime_mime_hdr']) .', '. _esc($_POST['mime_fl_ext']) .', '. _esc($_POST['mime_icon']) .')');
	}

	require($WWW_ROOT_DISK .'adm/header.php');
?>
<h2>MIME Management System</h2>

<h3>Upload MIME icon</h3>
<form action="admmime.php" id="frm_mime" method="post" enctype="multipart/form-data">
<?php echo _hs; ?>
<table class="datatable solidtable">
<?php if (@is_writeable($GLOBALS['WWW_ROOT_DISK'] .'images/mime/')) { ?>
<tr class="field">
	<td>Icon to upload:<br /><font size="-1">Only .gif, *.jpg, *.jpeg and *.png files are allowed.</font></td>
	<td><input type="file" name="icoul" /> <input type="submit" name="btn_upload" value="Upload" /><input type="hidden" name="tmp_f_val" value="1" /></td>
</tr>
<?php } else { ?>
<tr class="fieldtopic">
	<td colspan="2"><span style="color:red;">Web server does not have write permissions to <b>'<?php echo $GLOBALS['WWW_ROOT_DISK']; ?>images/mime/'</b>, mime icon upload disabled.</span></td>
</tr>
<?php } ?>
</table>

<h3><?php echo $edit ? '<a name="edit">Edit MIME definition</a>' : 'Add MIME definition'; ?></h3>
<table class="datatable solidtable">
<tr class="field">
	<td>MIME Description:</td>
	<td><input type="text" name="mime_descr" value="<?php echo htmlspecialchars($mime_descr); ?>" /></td>
</tr>

<tr class="field">
	<td>MIME Header:</td>
	<td><input type="text" name="mime_mime_hdr" value="<?php echo htmlspecialchars($mime_mime_hdr); ?>" /></td>
</tr>

<tr class="field">
	<td>File Extension:<br /><font size="-1">Files with this extension (case-insensitive) will be attributed to this MIME.</font></td>
	<td><input type="text" name="mime_fl_ext" value="<?php echo htmlspecialchars($mime_fl_ext); ?>" /></td>
</tr>

<tr class="field">
	<td valign="top"><a name="mime_sel">MIME Icon:</a></td>
	<td nowrap="nowrap"><input type="text" name="mime_icon" value="<?php echo htmlspecialchars($mime_icon); ?>" onchange="
				if (this.value.length) {
					document.prev_icon.src='<?php echo $GLOBALS['WWW_ROOT']; ?>images/mime/' + this.value;
				} else {
					document.prev_icon.src='../blank.gif';
				}" /> [<a href="#mime_sel" onclick="window.open('admiconsel.php?type=2&amp;<?php echo __adm_rsid; ?>', 'admmimesel', 'menubar=false,scrollbars=yes,resizable=yes,height=300,width=500,screenX=100,screenY=100');">select MIME icon</a>]</td>
</tr>

<tr class="field">
	<td valign="top">Preview Image:</td>
	<td>
		<table border="1" cellspacing="1" cellpadding="2" bgcolor="#ffffff">
		<tr><td align="center" valign="middle"><img src="<?php echo ($mime_icon ? $GLOBALS['WWW_ROOT'] .'images/mime/'. $mime_icon : '../blank.gif'); ?>" name="prev_icon" border="0" alt="Preview" /></td></tr>
		</table>
	</td>
</tr>

<tr class="fieldaction">
	<td colspan="2" align="right"><input type="submit" name="btn_cancel" value="Reset" />
<?php
	if (!$edit) {
		echo '<input type="submit" name="btn_submit" value="Add MIME" />';
	} else {
		echo '<input type="submit" name="btn_update" value="Update" />';
	}
?>
	</td>
</tr>
</table>
<input type="hidden" name="edit" value="<?php echo $edit; ?>" />
</form>

<h3>MIME definitions</h3>
<table class="resulttable fulltable">
<thead><tr class="resulttopic">
	<th>Icon</th>
	<th>MIME Header</th>
	<th>Description</th>
	<th>Extension</th>
	<th align="center">Action</th>
</tr></thead>
<?php
	$c = uq('SELECT id, icon, mime_hdr, fl_ext, descr FROM '. $tbl .'mime');
	$i = 1;
	while ($r = db_rowarr($c)) {
		$i++;
		$bgcolor = ($edit == $r[0]) ? ' class="resultrow3"' : (($i%2) ? ' class="resultrow1"' : ' class="resultrow2"');
		echo '<tr'. $bgcolor .' valign="top"><td><img src="'. $GLOBALS['WWW_ROOT'] .'images/mime/'. $r[1] .'" border="0" alt="'. $r[4] .'" /></td><td>'. $r[2] .'</td><td>'. $r[4] .'</td><td>'. $r[3] .'</td><td nowrap="nowrap">[<a href="admmime.php?edit='. $r[0] .'&amp;'. __adm_rsid .'#edit">Edit</a>] [<a href="admmime.php?del='. $r[0] .'&amp;'. __adm_rsid .'">Delete</a>]</td></tr>';
	}
	unset($c);
	if (!$i) {
		echo '<tr class="field"><td colspan="6"><center>No MIME deninitions found. Define some above.</center></td></tr>';
	}
?>
</table>
<?php require($WWW_ROOT_DISK .'adm/footer.php'); ?>
