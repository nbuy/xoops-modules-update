<?php
# update module onUpdate proceeding.
# $Id: onupdate.php,v 1.1 2007/07/16 05:18:30 nobu Exp $

global $xoopsDB;

// update_cache table add in 0.85 later
define('UCACHE', $xoopsDB->prefix('update_cache'));

$xoopsDB->query('SELECT * FROM '.UCACHE, 1);
if ($xoopsDB->errno()) { // check exists
    $msgs[] = "Update Database...";
    $msgs[] = "&nbsp;&nbsp; Add new table: <b>update_cache</b>";
    
    $xoopsDB->query("CREATE TABLE ".UCACHE." (
  cacheid  varchar(65) NOT NULL,
  mtime    integer NOT NULL default 0,
  content  text,
  PRIMARY KEY  (cacheid)
)");

    // clear old cache
    $dh = opendir(XOOPS_CACHE_PATH);
    while ($file = readdir($dh)) {
	if (preg_match('/^update[0-9a-f]+$/', $file)) {
	    $cache = XOOPS_CACHE_PATH.'/'.$file;
	    if ((time()-filemtime($cache))>$expire) {
		unlink($cache);
	    }
	}
    }
    closedir($dh);
}

function add_field($table, $field, $type, $after) {
    global $xoopsDB;
    $res = $xoopsDB->query("SELECT $field FROM $table", 1);
    if (empty($res) && $xoopsDB->errno()) { // check exists
	if ($after) $after = "AFTER $after";
	$res = $xoopsDB->query("ALTER TABLE $table ADD $field $type $after");
    } else return false;
    if (!$res) {
	echo "<div class='errorMsg'>".$xoopsDB->errno()."</div>\n";
    }
    return $res;
}
?>