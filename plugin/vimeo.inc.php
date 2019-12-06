<?php

function plugin_vimeo_convert() {
  if (func_num_args() < 1) return FALSE;
  $args = func_get_args();
  $name = trim($args[0]);

  // Width
  if( substr($args[1],-1,1) == "%") {
    $wslen = strlen($args[1])-1; // 幅 XXX% の XXX の桁数を取得
    $wsvalue = substr($args[1],0,$wslen); // XXX の部分のみを取得
    if( is_numeric($wsvalue) && 20<=$wsvalue && $wsvalue<=100 ) $width = $args[1]; else $width = "100%";
  } else {
    $width = "100%";
  }

  // Aspect
  if( is_numeric($args[2]) && 0.1<$args[2] && $args[2]<=3.0 ) $aspect = $args[2]; else $aspect = 0.5625;
  $str_aspect = strval( $aspect * 100.0 )."%";

  // Option
  $autoplay = "autoplay=0";
  $loop = 'loop=0';

  for($i=0; $i<5; $i++){
    if( $args[$i] == "autoplay") $autoplay = 'autoplay=1';
    if( $args[$i] == "loop") $loop = 'loop=1';
  }

  $iframeStyle='style="position: absolute; top: 0; left: 0; width: 100%; height: 100%;"';

  // Genetate ID
  $num = 8; // 文字数
  $ar1 = range('a', 'z'); // アルファベット小文字を配列に
  $ar2 = range('A', 'Z'); // アルファベット大文字を配列に
  $ar_all = array_merge($ar1, $ar2); // すべて結合
  shuffle($ar_all); // ランダム順にシャッフル
  $id = substr(implode($ar_all), 0, $num); // 先頭の8文字
  $idSelector = "#"."$id";

  return <<<HTML
  <style type="text/css">
      $idSelector {
        position:relative;
        width:$width;
      }
      $idSelector:before {
        content:"";
        display: block;
        padding-top: $str_aspect;
      }
  </style>
  <div id="$id">
    <iframe src="https://player.vimeo.com/video/$name?$autoplay&$loop" $iframeStyle frameborder="0" allow="autoplay; fullscreen" allowfullscreen></iframe>
  </div>
HTML;

}

function plugin_vimeo_inline() {
  $args = func_get_args();
  return call_user_func_array('plugin_vimeo_convert', $args);
}

?>
