<?php
// PukiWiki - Yet another WikiWikiWeb clone.
// $Id: sonots $

// 見出しアンカーの書式
define('PLUGIN_CONTENTS2_1_ANCHOR_PREFIX', '#content_');
// 見出しアンカーの開始番号
define('PLUGIN_CONTENTS2_1_ANCHOR_ORIGIN', 0);
define('PLUGIN_CONTENTS2_1_PAGE_ANCHOR_ORIGIN', 1);
// #contents2_1 が書いてある次の行以降の見出しのみをリストする(デフォルト TRUE)
define('PLUGIN_CONTENTS2_1_FROMHERE', true);
// リストのレベルを調整する(デフォルト TRUE)
define('PLUGIN_CONTENTS2_1_COMPACT', true);
// リスト表示形式(デフォルト 'hierarchy')
define('PLUGIN_CONTENTS2_1_DISPLAY', 'hierarhcy');
// #include プラグインで取り込んでいるページの見出しも扱う(デフォルト TRUE)
define('PLUGIN_CONTENTS2_1_INCLUDE', true);
// display=inline 時に前、間、後ろにつける文字
define('PLUGIN_CONTENTS2_1_DISPLAY_INLINE_BEFORE', '[ ');
define('PLUGIN_CONTENTS2_1_DISPLAY_INLINE_MIDDLE', ' | ');
define('PLUGIN_CONTENTS2_1_DISPLAY_INLINE_AFTER', ' ]');
// fixed anchor の利用（デフォルト TRUE)
define('PLUGIN_CONTENTS2_1_FIXEDANCHOR', true);
// CSSクラス設定
define('PLUGIN_CONTENTS2_1_CSS_CLASS', 'contents2_1');

function plugin_contents2_1_init()
{
    $messages['_contents2_1_msg_err'] = '<div>\'%s\' does not exist.</div>';
    set_plugin_messages($messages);
}

function plugin_contents2_1_inline()
{
    $args = func_get_args();
    array_pop($args);
    return plugin_contents2_1($args, 'inline');
}

function plugin_contents2_1_convert()
{
    return plugin_contents2_1(func_get_args(), 'convert');
}

function plugin_contents2_1($args, $calledby = 'convert')
{
    global $vars;
    global $script;
    global $_contents2_1_msg_err;
    // true or false のオプション
    $params = array('fromhere' => PLUGIN_CONTENTS2_1_FROMHERE,
        'compact' => PLUGIN_CONTENTS2_1_COMPACT,
        'include' => PLUGIN_CONTENTS2_1_INCLUDE,
        'fixed_anchor' => PLUGIN_CONTENTS2_1_FIXEDANCHOR,
        );
    // その他の引数を持つオプション
    $argparams = array('page' => '',
        'depth' => '',
        'number' => '',
        'except' => '',
        'display' => PLUGIN_CONTENTS2_1_DISPLAY,
        );
    // その他の引数を持ち、値が HTML に出力されるオプション( 要 htmlspecialchars )
    $arghtmlparams = array('inline_before' => PLUGIN_CONTENT22_1_DISPLAY_INLINE_BEFORE,
        'inline_delimiter' => PLUGIN_CONTENTS2_1_DISPLAY_INLINE_DELIMITER,
        'inline_after' => PLUGIN_CONTENTS2_1_DISPLAY_INLINE_AFTER,
        );
    array_walk($args, 'plugin_contents2_1_check_params', $params);
    array_walk($args, 'plugin_contents2_1_check_argparams', $argparams);
    array_walk($args, 'plugin_contents2_1_check_arghtmlparams', $arghtmlparams);
    $params = array_merge($params, $argparams, $arghtmlparams);
    // inline プラグイン時は強制 display=inline。
    if ($calledby == 'inline') {
        $params['display'] = 'inline';
    }
    // ページ名処理
    if ($params['page'] == '') {
        $page = $vars['page'];
    } else {
        $page = $params['page'];
    }
    if (! is_page($page) || ! check_readable($page, false, false)) {
        return sprintf($_contents2_1_msg_err, htmlspecialchars($page));
    }
    // page オプションを利用し、現在表示ページと違うページの見出しリンクを作る場合アンカーだけでは足りない。
    if ($page != $vars['page']) {
        $r_page = rawurlencode($page);
        $href = $script . '?cmd=read&amp;page=' . $r_page;
        $params['href'] = $href;
        // 表示ページと違うページが指定されていれば強制 FALSE
        $params['fromhere'] = false;
    } else {
        $params['href'] = '';
    }
    // depth オプション解析
    if ($params['depth'] != '') {
        list($params['lowdepth'], $params['highdepth']) = plugin_contents2_1_depth_option_analysis($params['depth']);
    }
    // number オプション解析
    if ($params['number'] != '') {
        if (! preg_match('/^\d+$/', $params['number'])) {
            $params['number'] = '';
        }
    }

    $params['result'] = $params['saved'] = array();
    $params['page_anchor_counter'] = PLUGIN_CONTENTS2_1_PAGE_ANCHOR_ORIGIN;
    $params['number_counter'] = 0;
    $params['fromhere_detected'] = false;
    plugin_contents2_1_get_headings($page, $params);

    if ($params['display'] == 'inline') {
        if ($calledby == 'inline') {
            $tag = 'span';
        } else {
            $tag = 'div';
        }
        return "<$tag class=\"" . PLUGIN_CONTENTS2_1_CSS_CLASS . "\">"
         . join("", $params['result']) . join("", $params['saved']) . "</$tag>";
    } else {
        return join("\n", $params['result']) . join("\n", $params['saved']);
    }
}

function plugin_contents2_1_get_headings($page, &$params)
{
    static $_contents2_1_anchor = 0;
    // すでにこのページの見出しを表示したかどうかのフラグ
    $is_done = (isset($params["page_$page"]) && $params["page_$page"] > 0);
    if (! $is_done) $params["page_$page"] = ++$_contents2_1_anchor;
    // include ページの場合
    if ($params['page_anchor_counter'] > 1) {
        // 表示済み
        if ($is_done) {
            $params['page_anchor_counter']--;
            return;
        }
        // 標準 #include プラグインにはアンカーはつかないのでこのアンカーリンクは機能しない。
        // 自作プラグイン/include2.inc.php はこれに対応しています。
        $id = '#' . plugin_contents2_1_pageanchor($page);
        // include ページ名のレベルは０
        $level = 0;

        $link_string = htmlspecialchars($page);
        $title = $link_string . ' ' . get_pg_passage($page, false);

        plugin_contents2_1_push2result($page, $params, $level, $page, $id, $title, $link_string);
    }

    $anchor_counter = PLUGIN_CONTENTS2_1_ANCHOR_ORIGIN;
    $matches = array();

    foreach (get_source($page) as $line) {
        // include ページにある #contents2_1 は呼び出しの #contents2_1 とは明らかに違うやつなので
        // include ページに対しては fromhere 検索すらしない（無論見出しも）。
        // ただ $params['page_anchor_counter'] のために #include を辿って #include の数は数えないといけない。
        // fixed_anchor=false だとしても fixed_anchor がまだ作られていないページである可能性があるのでやはり数えないといけない。
        if ($params['fromhere'] && ! $params['fromhere_detected'] && $params['page_anchor_counter'] > 1) {
            if ($params['include'] &&
                preg_match('/^#include.*\((.+)\)/', $line, $matches) &&
                    is_page($matches[1])) {
                $params['page_anchor_counter']++;
                plugin_contents2_1_get_headings($matches[1], $params);
            }
            continue;
        }
        // fromhere 判定。まだ見つかっていない場合は何もしない
        if ($params['fromhere'] && ! $params['fromhere_detected'] &&
            $params['fromhere_detected'] = preg_match('/^#contents2\_1/', $line, $matches)) {
            // do nothing
        }
        // 見出し検出
        elseif (preg_match('/^(\*{1,3})/', $line, $matches)) {
            // アンカー文字列をつくる。$anchor_counter++ が重要。
            $id = PLUGIN_CONTENTS2_1_ANCHOR_PREFIX . $params['page_anchor_counter'] . '_' . $anchor_counter++;
            // fromhere がまだ見つかっていなければ $anchor_counter++ だけして continue。
            if ($params['fromhere'] && ! $params['fromhere_detected']) continue;
            // 見出しレベルは１以降
            $level = strlen($matches[1]);
            // $line は 'remove footnotes and HTML tags' される。見出し行の固定アンカーが返される。
            $fixed_id = make_heading($line);
            if ($params['fixed_anchor'] && $fixed_id !== '') {
                $id = '#' . $fixed_id;
            }
            // 自動アンカーがつく設定の場合の [#438239] の前に勝手に挿入される空白が make_heading ではまだ残るようなので。
            $title = $link_string = trim($line);

            plugin_contents2_1_push2result($line, $params, $level, $page, $id, $title, $link_string);
            // number 判定。制限を越えていれば抜けて終了。
            if ($params['number'] != '' && $params['number_counter'] >= $params['number']) {
                break;
            }
        }
        // include 検出
        elseif ($params['include'] &&
            preg_match('/^#include.*\((.+)\)/', $line, $matches) &&
                is_page($matches[1])) {
            $params['page_anchor_counter']++;
            plugin_contents2_1_get_headings($matches[1], $params);
        }
    }
}
// オプション判定を行い、問題なければリンクを作成し格納していく。
function plugin_contents2_1_push2result($line, &$params, $level, $page, $link_id, $link_title, $link_string)
{
    // number 判定。include ページ名の表示も１つと数える。
    if ($params['number'] != '' && $params['number_counter'] >= $params['number']) {
        // do nothing
    }
    // except 判定
    elseif ($params['except'] != '' && ereg($params['except'], $line)) {
        // do nothing
    }
    // depth 判定。
    elseif ($params['lowdepth'] != '' && $level < $params['lowdepth']) {
        // do nothing
    } elseif ($params['highdepth'] != '' && $level > $params['highdepth']) {
        // do nothing
    } else {
        // display  オプション
        if ($params['display'] == 'inline') {
            $litag = '';
        } else {
            $litag = '<li>';
        }
        // include オプション時は include ページ名も表示しなければいけないので、レベル0から+1ずらす。他も
        if ($params['include']) {
            $level++;
        }
        // リスト作成
        plugin_contents2_1_list_push($params, $level);

        array_push($params['result'], $litag);
        $ret .= '<a id="list_' . $params["page_$page"] . '" href="' . $params['href'] . $link_id . '" title="' . $link_title . '">' . $link_string . '</a>';
        array_push($params['result'], $ret);

        $params['number_counter']++;
    }
}
// <ul> と </li></ul> を適宜挿入する。
function plugin_contents2_1_list_push(&$params, $level)
{
    global $_ul_left_margin, $_ul_margin, $_list_pad_str;

    $result = &$params['result']; // バッファ。これを出力することになる。
    $saved = &$params['saved']; // 閉じなければいけない文だけ </ul> をたくわえておく。
    if ($params['display'] == 'inline') {
        $ulopen = PLUGIN_CONTENTS2_1_DISPLAY_INLINE_BEFORE;
        $ulclose = PLUGIN_CONTENTS2_1_DISPLAY_INLINE_AFTER;
        $liclose = PLUGIN_CONTENTS2_1_DISPLAY_INLINE_MIDDLE;
    } else {
        $ulopen = '<ul class="' . PLUGIN_CONTENTS2_1_CSS_CLASS . '"%s>';
        $ulclose = "</li>\n</ul>";
        $liclose = '</li>';
    }

    if ($params['display'] == 'flat' || $params['display'] == 'inline') {
        // 初期化がここにあるのはうれしくないが、まとめておきたかった。
        if (count($saved) < 1) {
            if ($params['display'] == 'flat') {
                $left = $_ul_margin;
            } else if ($params['display'] == 'inline') {
                $left = 0;
            }
            $level = 1;
            $str = sprintf($_list_pad_str, $level, $left, $left);
            array_push($result, sprintf($ulopen, $str));
            array_unshift($saved, $ulclose);
        } else {
            array_push($result, $liclose);
        }
    } else {
        while (count($saved) > $level || (! empty($saved) && $saved[0] != $ulclose))
        array_push($result, array_shift($saved));

        $margin = $level - count($saved);
        // count($saved)を増やす
        while (count($saved) < ($level - 1)) array_unshift($saved, '');

        if (count($saved) < $level) {
            array_unshift($saved, $ulclose);

            $left = ($level == $margin) ? $_ul_left_margin : 0;

            if ($params['compact']) {
                $left += $_ul_margin; // マージンを固定
                $level -= ($margin - 1); // レベルを修正
            } else {
                $left += $margin * $_ul_margin;
            }

            $str = sprintf($_list_pad_str, $level, $left, $left);
            array_push($result, sprintf($ulopen, $str));
        } else {
            array_push($result, $liclose);
        }
    }
}
// オプション \d?[+-]?\d? を解析。
function plugin_contents2_1_depth_option_analysis($arg)
{
    $low = 0;
    $high = 0;
    if (!preg_match('/^\d*\-?\d*$/', $arg) or $arg == '') {
        return array('', '');
    }

    if (substr_count($arg, "-")) { // \d-\d の場合
            list($low, $high) = split("-", $arg, 2);
    } elseif (substr_count($arg, "+")) { // \d+\d の場合
            list($low, $high) = split("+", $arg, 2);
        $high += $low;
    } else { // \d だけの場合
            $low = $high = $arg;
    }
    return array($low, $high);
}
// true or false の値を持つオプションを解析する
function plugin_contents2_1_check_params($value, $key, &$params)
{
    // $value に depth=2-3 や hierarchy のような値が入る。実質 $key は意味なし。
    // trim はあえてしていない。
    if ($value == '') return;

    list($key, $val) = split("=", $value);
    if (isset($params[$key])) {
        if ($val == '' || $val == "true") {
            $params[$key] = true;
        } elseif ($val == "false") {
            $params[$key] = false;
        }
    }
}
// その他の値を持つオプションを解析する
function plugin_contents2_1_check_argparams($value, $key, &$params)
{
    if ($value == '') return;

    list($key, $val) = split("=", $value);
    if (isset($params[$key])) {
        $params[$key] = $val;
    }
}
// その他の引数を持ち、値が HTML に出力されるオプションを解析する (要 htmlspecialchars)
function plugin_contents2_1_check_arghtmlparams($value, $key, &$params)
{
    if ($value == '') return;

    list($key, $val) = split("=", $value);
    if (isset($params[$key])) {
        $params[$key] = htmlspecialchars($val);
    }
}

// ページアンカー作成。
function plugin_contents2_1_pageanchor($page)
{
    // ページ名後ろ100文字をキーに md5 ハッシュを作り、さらに 7 文字に削る。
    // アンカーの先頭は [A-Za-z] でなければならないので 'A' をつける。
    $start = (($len = strlen($page) - 100) > 0) ? $len : 0;
    $pageanchor = 'A' . substr(md5(substr($page, $start)), 0, 7);
    return $pageanchor;
}

?>
