<?php
// PukiWiki - Yet another WikiWikiWeb clone.
// pukiwiki.skin.php
// Copyright
//   2019 OpenSquareJP
//   2002-2017 PukiWiki Development Team
//   2001-2002 Originally written by yu-ji
// License: GPL v2 or (at your option) any later version
//
// PukiWiki Minimal skin

// ------------------------------------------------------------
// Settings (define before here, if you want)

// Set site identities
$_IMAGE['skin']['logo']     = 'pukiwiki.png';
$_IMAGE['skin']['favicon']  = ''; // Sample: 'image/favicon.ico';

// SKIN_DEFAULT_DISABLE_TOPICPATH
//   1 = Show reload URL
//   0 = Show topicpath
if (! defined('SKIN_DEFAULT_DISABLE_TOPICPATH'))
	define('SKIN_DEFAULT_DISABLE_TOPICPATH', 1); // 1, 0

// Show / Hide navigation bar UI at your choice
// NOTE: This is not stop their functionalities!
if (! defined('PKWK_SKIN_SHOW_NAVBAR'))
	define('PKWK_SKIN_SHOW_NAVBAR', 1); // 1, 0

// Show / Hide toolbar UI at your choice
// NOTE: This is not stop their functionalities!
if (! defined('PKWK_SKIN_SHOW_TOOLBAR'))
	define('PKWK_SKIN_SHOW_TOOLBAR', 1); // 1, 0

// ------------------------------------------------------------
// Code start

// Prohibit direct access
if (! defined('UI_LANG')) die('UI_LANG is not set');
if (! isset($_LANG)) die('$_LANG is not set');
if (! defined('PKWK_READONLY')) die('PKWK_READONLY is not set');

$lang  = & $_LANG['skin'];
$link  = & $_LINK;
$image = & $_IMAGE['skin'];
$rw    = ! PKWK_READONLY;

// MenuBar
$menu = arg_check('read') && exist_plugin_convert('menu') ? do_plugin_convert('menu') : FALSE;

// ------------------------------------------------------------
// Output

// HTTP headers
pkwk_common_headers();
header('Cache-control: no-cache');
header('Pragma: no-cache');
header('Content-Type: text/html; charset=' . CONTENT_CHARSET);

?>
<!DOCTYPE html>
<html lang="ja">

<head>
  <meta charset="utf-8">
  <title><?php echo $title ?> - <?php echo $page_title ?></title>
  <link rel="SHORTCUT ICON" href="<?php echo $image['favicon'] ?>" />
  <link rel="stylesheet" href="skin/css/lightbox.min.css" media="screen" />
  <link rel="stylesheet" href="skin/css/pukiwiki.css" />
  <script src="skin/js/jquery-3.2.0.min.js"></script>
  <script src="skin/js/lightbox.min.js"></script>
  <script src="skin/js/main.js" defer></script>
  <script src="skin/js/search2.js" defer></script>
  <script src="skin/js/system.js"></script>

  <?php echo $head_tag ?>
</head>

<body>
<?php echo $html_scripting_data ?>

  <header>
    <h1 class="title"><?php echo $page ?></h1>
    <a href="<?php echo $link['top'] ?>"><img id="logo" src="<?php echo IMAGE_DIR . $image['logo'] ?>" alt="[PukiWiki]" title="[PukiWiki]" /></a>
  </header>

  <div id="main">

    <?php if ($menu !== FALSE) { ?>

      <div id="read">

        <nav><?php echo convert_html(get_source('MenuBar')) ?></nav>
        <article><?php echo $body ?></article>

      </div>

      <hr>

      <?php if ($notes != '') { ?>
        <div id="note"><?php echo $notes ?></div>
      <?php } ?>

      <?php if ($attaches != '') { ?>
      <div id="attach">
        <?php echo $attaches ?>
      </div>
      <?php } ?>

      <?php if ($lastmodified != '') { ?>
        <div id="lastmodified">Last-modified: <?php echo $lastmodified ?></div>
      <?php } ?>

      <?php if ($related != '') { ?>
        <div id="related">Link: <?php echo $related ?></div>
      <?php } ?>

    <?php } else { ?>

      <div id="edit">
        <article><?php echo $body ?></article>
      </div>

    <?php } ?>

  </div>

  <footer>
    <!-- Toolbar -->
    <div id="toolbar">
    <?php

    // Set toolbar-specific images
    $_IMAGE['skin']['reload']   = 'reload.png';
    $_IMAGE['skin']['new']      = 'new.png';
    $_IMAGE['skin']['edit']     = 'edit.png';
    $_IMAGE['skin']['freeze']   = 'freeze.png';
    $_IMAGE['skin']['unfreeze'] = 'unfreeze.png';
    $_IMAGE['skin']['diff']     = 'diff.png';
    $_IMAGE['skin']['upload']   = 'file.png';
    $_IMAGE['skin']['copy']     = 'copy.png';
    $_IMAGE['skin']['rename']   = 'rename.png';
    $_IMAGE['skin']['top']      = 'top.png';
    $_IMAGE['skin']['list']     = 'list.png';
    $_IMAGE['skin']['search']   = 'search.png';
    $_IMAGE['skin']['recent']   = 'recentchanges.png';
    $_IMAGE['skin']['backup']   = 'backup.png';
    $_IMAGE['skin']['help']     = 'help.png';
    $_IMAGE['skin']['rss']      = 'rss.png';
    $_IMAGE['skin']['rss10']    = & $_IMAGE['skin']['rss'];
    $_IMAGE['skin']['rss20']    = 'rss20.png';
    $_IMAGE['skin']['rdf']      = 'rdf.png';

    function _toolbar($key, $x = 20, $y = 20){
      $lang  = & $GLOBALS['_LANG']['skin'];
      $link  = & $GLOBALS['_LINK'];
      $image = & $GLOBALS['_IMAGE']['skin'];
      if (! isset($lang[$key]) ) { echo 'LANG NOT FOUND';  return FALSE; }
      if (! isset($link[$key]) ) { echo 'LINK NOT FOUND';  return FALSE; }
      if (! isset($image[$key])) { echo 'IMAGE NOT FOUND'; return FALSE; }

      echo '<a href="' . $link[$key] . '">' .
        '<img src="' . IMAGE_DIR . $image[$key] . '" width="' . $x . '" height="' . $y . '" ' .
          'alt="' . $lang[$key] . '" title="' . $lang[$key] . '" />' .
        '</a>';
      return TRUE;
    }
    ?>
     <?php _toolbar('top') ?>

    <?php if ($is_page) { ?>
     &nbsp;
     <?php if ($rw) { ?>
      <?php _toolbar('edit') ?>
      <?php if ($is_read && $function_freeze) { ?>
        <?php if (! $is_freeze) { _toolbar('freeze'); } else { _toolbar('unfreeze'); } ?>
      <?php } ?>
     <?php } ?>
     <?php _toolbar('diff') ?>
    <?php if ($do_backup) { ?>
      <?php _toolbar('backup') ?>
    <?php } ?>
    <?php if ($rw) { ?>
      <?php if ((bool)ini_get('file_uploads')) { ?>
        <?php _toolbar('upload') ?>
      <?php } ?>
      <?php _toolbar('copy') ?>
      <?php _toolbar('rename') ?>
    <?php } ?>
     <?php _toolbar('reload') ?>
    <?php } ?>
     &nbsp;
    <?php if ($rw) { ?>
      <?php _toolbar('new') ?>
    <?php } ?>
     <?php _toolbar('list')   ?>
     <?php _toolbar('search') ?>
     <?php _toolbar('recent') ?>
     &nbsp; <?php _toolbar('help') ?>
    </div>

    <div id="signature">
      Site admin: <a href="<?php echo $modifierlink ?>"><?php echo $modifier ?></a><p />
      <?php echo S_COPYRIGHT ?>.
      Powered by PHP <?php echo PHP_VERSION ?>. HTML convert time: <?php echo elapsedtime() ?> sec.
    </div>

  </footer>

</body>
</html>
