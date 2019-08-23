<?php
// PukiWiki - Yet another WikiWikiWeb clone
// $Id: image.inc.php,v 1.00 2009/11/24 01:35:34 teanan Exp $
// Copyright (C)
//   2009 Modified from ref.inc.php
//   2002-2006 PukiWiki Developers Team
//   2001-2002 Originally written by yu-ji
// License: GPL v2 or (at your option) any later version
//
// image plugin
// Include an attached image-file as an inline-image

// File icon image
if (! defined('FILE_ICON'))
	define('FILE_ICON',
	'<img src="' . IMAGE_DIR . 'file.png" width="20" height="20"' .
	' alt="file" style="border-width:0px" />');

/////////////////////////////////////////////////
// Default settings

// Horizontal alignment
define('PLUGIN_IMAGE_DEFAULT_ALIGN', 'none'); // 'left', 'center', 'right'

// Text wrapping
define('PLUGIN_IMAGE_WRAP_TABLE', FALSE); // TRUE, FALSE

// URL指定時に画像サイズを取得するか
define('PLUGIN_IMAGE_URL_GET_IMAGE_SIZE', FALSE); // FALSE, TRUE

// UPLOAD_DIR のデータ(画像ファイルのみ)に直接アクセスさせる
define('PLUGIN_IMAGE_DIRECT_ACCESS', FALSE); // FALSE or TRUE
// - これは従来のインラインイメージ処理を互換のために残すもので
//   あり、高速化のためのオプションではありません
// - UPLOAD_DIR をWebサーバー上に露出させており、かつ直接アクセス
//   できる(アクセス制限がない)状態である必要があります
// - Apache などでは UPLOAD_DIR/.htaccess を削除する必要があります
// - ブラウザによってはインラインイメージの表示や、「インライン
//   イメージだけを表示」させた時などに不具合が出る場合があります
$is_image_block = false;
// グローバル変数　要注意

/////////////////////////////////////////////////

// Image suffixes allowed
define('PLUGIN_IMAGE_IMAGE', '/\.(gif|png|jpe?g)$/i');

// Usage (a part of)
define('PLUGIN_IMAGE_USAGE', "([pagename/]attached-file-name[,parameters, ... ][,title])");

function plugin_image_inline()
{

	// インライン要素として配置 /////////////////////

	$params = plugin_image_body(func_get_args());

	if (isset($params['_error']) && $params['_error'] != '') {
		// Error
		return '&amp;image(): ' . $params['_error'] . ';';
	} else {
		return $params['_body'];
	}
}

function plugin_image_convert()
{

	// ブロック要素として配置 /////////////////////

	$GLOBALS['is_image_block'] = true;

	if (! func_num_args())
		return '<p>#image(): Usage:' . PLUGIN_IMAGE_USAGE . "</p>\n";

	$params = plugin_image_body(func_get_args());

	if (isset($params['_error']) && $params['_error'] != '') {
		return "<p>#image(): {$params['_error']}</p>\n";
	}

	//サイズの設定
	if ($params['_%']) {
		$size  = 'width:'.$params['_%'].'%';
	}else{
		$size  = 'width:100%';
	}

	//	回りこみとマージンの設定
	$params['_align'] = PLUGIN_IMAGE_DEFAULT_ALIGN;
	foreach (array('right', 'left', 'center') as $keytext) {
		if ($params[$keytext])  {
			$params['_align'] = $keytext;
			break;
		}
	}

	if($params['_align'] == 'right') {
		$style = $size.'; float:right; margin-left:2rem;';
	}else if($params['_align'] == 'left') {
		$style = $size.'; float:left; margin-right:2rem;';
	}else if($params['_align'] == 'center') {
		$style = $size.'; margin-right:auto; margin-left:auto;';
	}else {
		$style = $size.'; float:none;';
	}

	// divで包む
	return "<div class=\"img_margin\" style=\"$style\">{$params['_body']}</div>\n";
}

function plugin_image_body($args)
{
	global $script, $vars;
	global $WikiName, $BracketName; // compat

	// 戻り値
	$params = array(
		'left'   => FALSE, // 左寄せ
		'center' => FALSE, // 中央寄せ
		'right'  => FALSE, // 右寄せ
	//	'around' => FALSE, // 回り込み
		'noicon' => FALSE, // アイコンを表示しない
		'nolink' => FALSE, // 元ファイルへのリンクを張らない
		'noimg'  => FALSE, // 画像を展開しない
		'zoom'   => FALSE, // 縦横比を保持する
		'_align' => none,
		'_%'     => 0,     // 拡大率
		'_args'  => array(),
		'_done'  => FALSE,
		'_error' => ''
	);

	// 添付ファイルのあるページ: defaultは現在のページ名
	$page = isset($vars['page']) ? $vars['page'] : '';

	// 添付ファイルのファイル名
	$name = '';

	// 添付ファイルまでのパスおよび(実際の)ファイル名
	$file = '';

	// 第一引数: "[ページ名および/]添付ファイル名"、あるいは"URL"を取得
	$name = array_shift($args);
	$is_url = is_url($name);

	if(! $is_url) {
		// 添付ファイル
		if (! is_dir(UPLOAD_DIR)) {
			$params['_error'] = 'No UPLOAD_DIR';
			return $params;
		}

		$matches = array();
		// ファイル名にページ名(ページ参照パス)が合成されているか
		//   (Page_name/maybe-separated-with/slashes/ATTACHED_FILENAME)
		if (preg_match('#^(.+)/([^/]+)$#', $name, $matches)) {
			if ($matches[1] == '.' || $matches[1] == '..') {
				$matches[1] .= '/'; // Restore relative paths
			}
			$name = $matches[2];
			$page = get_fullname(strip_bracket($matches[1]), $page); // strip is a compat
			$file = UPLOAD_DIR . encode($page) . '_' . encode($name);
			$is_file = is_file($file);

		// 第二引数以降が存在し、それはrefのオプション名称などと一致しない
		} else if (isset($args[0]) && $args[0] != '' && ! isset($params[$args[0]])) {
			$e_name = encode($name);

			// Try the second argument, as a page-name or a path-name
			$_arg = get_fullname(strip_bracket($args[0]), $page); // strip is a compat
			$file = UPLOAD_DIR .  encode($_arg) . '_' . $e_name;
			$is_file_second = is_file($file);

			// If the second argument is WikiName, or double-bracket-inserted pagename (compat)
			$is_bracket_bracket = preg_match("/^($WikiName|\[\[$BracketName\]\])$/", $args[0]);

			if ($is_file_second && $is_bracket_bracket) {
				// Believe the second argument (compat)
				array_shift($args);
				$page = $_arg;
				$is_file = TRUE;
			} else {
				// Try default page, with default params
				$is_file_default = is_file(UPLOAD_DIR . encode($page) . '_' . $e_name);

				// Promote new design
				if ($is_file_default && $is_file_second) {
					// Because of race condition NOW
					$params['_error'] = htmlspecialchars('The same file name "' .
						$name . '" at both page: "' .  $page . '" and "' .  $_arg .
						'". Try ref(pagename/filename) to specify one of them');
				} else {
					// Because of possibility of race condition, in the future
					$params['_error'] = 'The style ref(filename,pagename) is ambiguous ' .
						'and become obsolete. ' .
						'Please try ref(pagename/filename)';
				}
				return $params;
			}
		} else {
			// Simple single argument
			$file = UPLOAD_DIR . encode($page) . '_' . encode($name);
			$is_file = is_file($file);
		}
		if (! $is_file) {
			$params['_error'] = htmlspecialchars('File not found: "' .
				$name . '" at page "' . $page . '"');
			return $params;
		}
	}

	// 残りの引数の処理
	if (! empty($args))
		foreach ($args as $arg)
			image_check_arg($arg, $params);

/*
 $nameをもとに以下の変数を設定
 $url,$url2 : URL
 $title :タイトル
 $is_image : 画像のときTRUE
 $style : 画像ファイルのとき、サイズ(%)
 $info : 画像ファイル以外のファイルの情報
         添付ファイルのとき : ファイルの最終更新日とサイズ
         URLのとき : URLそのもの
*/
	$title = $url = $url2 = $style = $info = '';
	$width = $height = 0;
	$matches = array();

	if ($is_url) {	// URL
		if (PKWK_DISABLE_INLINE_IMAGE_FROM_URI) {
			//$params['_error'] = 'PKWK_DISABLE_INLINE_IMAGE_FROM_URI prohibits this';
			//return $params;
			$url = htmlspecialchars($name);
			$params['_body'] = '<a href="' . $url . '">' . $url . '</a>';
			return $params;
		}

		$url = $url2 = htmlspecialchars($name);
		$title = htmlspecialchars(preg_match('/([^\/]+)$/', $name, $matches) ? $matches[1] : $url);

		$is_image = (! $params['noimg'] && preg_match(PLUGIN_IMAGE_IMAGE, $name));

	} else { // 添付ファイル

		$title = htmlspecialchars($name);

		$is_image = (! $params['noimg'] && preg_match(PLUGIN_IMAGE_IMAGE, $name));

		// Count downloads with attach plugin
		$url = $script . '?plugin=attach' . '&amp;refer=' . rawurlencode($page) .
			'&amp;openfile=' . rawurlencode($name); // Show its filename at the last

		if ($is_image) {
			// Swap $url
			$url2 = $url;

			// URI for in-line image output
			if (! PLUGIN_IMAGE_DIRECT_ACCESS) {
				// With image plugin (faster than attach)
				$url = $script . '?plugin=image' . '&amp;page=' . rawurlencode($page) .
					'&amp;src=' . rawurlencode($name); // Show its filename at the last
			} else {
				// Try direct-access, if possible
				$url = $file;
			}

		} else {
			$info = get_date('Y/m/d H:i:s', filemtime($file) - LOCALZONE) .
				' ' . sprintf('%01.1f', round(filesize($file)/1024, 1)) . 'KB';
		}
	}

	// 拡張パラメータをチェック
	if (! empty($params['_args'])) {
		$_title = array();
		foreach ($params['_args'] as $arg) {
			if (preg_match('/^([0-9.]+)%$/', $arg, $matches) && $matches[1] > 0) {
				$params['_%'] = $matches[1];
			} else {
				$_title[] = $arg;
			}
		}

		if (! empty($_title)) {
			$title = htmlspecialchars(join(',', $_title));
			if ($is_image) $title = make_line_rules($title);
		}
	}


	if ($is_image) { // 画像
		if( $GLOBALS['is_image_block'] ){ // ブロックの場合は、ここではサイズ指定しない

			$params['_body'] = "<img src=\"$url\" alt=\"$title\" title=\"$title\" $info />";

		}else{ // インラインの場合は、ここでサイズ指定

			if ($params['_%']) {
				$style  = 'width:'.$params['_%'].'%';
			}
			$params['_body'] = "<img src=\"$url\" alt=\"$title\" title=\"$title\" $info style=\"$style\" />";

		}
		if (! $params['nolink'] && $url2)
			$params['_body'] = "<a href=\"$url2\"  data-lightbox=\"roadtrip\" title=\"$title\">{$params['_body']}</a>";
	} else {
		$icon = $params['noicon'] ? '' : FILE_ICON;
		$params['_body'] = "<a href=\"$url\" title=\"$info\">$icon$title</a>";
	}

	return $params;
}



// オプションを解析する
function image_check_arg($val, & $params)
{
	if ($val == '') {
		$params['_done'] = TRUE;
		return;
	}

	if (! $params['_done']) {
		foreach (array_keys($params) as $key) {
			if (strpos($key, strtolower($val)) === 0) {
				$params[$key] = TRUE;
				return;
			}
		}
		$params['_done'] = TRUE;
	}

	$params['_args'][] = $val;
}

// Output an image (fast, non-logging <==> attach plugin)
function plugin_image_action()
{
	global $vars;

	$usage = 'Usage: plugin=ref&amp;page=page_name&amp;src=attached_image_name';

	if (! isset($vars['page']) || ! isset($vars['src']))
		return array('msg'=>'Invalid argument', 'body'=>$usage);

	$page     = $vars['page'];
	$filename = $vars['src'] ;

	$ref = UPLOAD_DIR . encode($page) . '_' . encode(preg_replace('#^.*/#', '', $filename));
	if(! file_exists($ref))
		return array('msg'=>'Attach file not found', 'body'=>$usage);

	$got = @getimagesize($ref);
	if (! isset($got[2])) $got[2] = FALSE;
	switch ($got[2]) {
	case 1: $type = 'image/gif' ; break;
	case 2: $type = 'image/jpeg'; break;
	case 3: $type = 'image/png' ; break;
	case 4: $type = 'application/x-shockwave-flash'; break;
	default:
		return array('msg'=>'Seems not an image', 'body'=>$usage);
	}

	// Care for Japanese-character-included file name
	if (LANG == 'ja') {
		switch(UA_NAME . '/' . UA_PROFILE){
		case 'Opera/default':
			// Care for using _auto-encode-detecting_ function
			$filename = mb_convert_encoding($filename, 'UTF-8', 'auto');
			break;
		case 'MSIE/default':
			$filename = mb_convert_encoding($filename, 'SJIS', 'auto');
			break;
		}
	}
	$file = htmlspecialchars($filename);
	$size = filesize($ref);

	// Output
	pkwk_common_headers();
	header('Content-Disposition: inline; filename="' . $filename . '"');
	header('Content-Length: ' . $size);
	header('Content-Type: '   . $type);
	@readfile($ref);
	exit;
}
?>
