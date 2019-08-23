<?php

function plugin_youtube_convert() {
	if (func_num_args() < 1) return FALSE;
	$args = func_get_args();
	$name = trim($args[0]);
	if( $args[1] != "") $width = $args[1]; else $width = "560";
	if( $args[2] != "") $height = $args[2]; else $height = "315";

	// DEFAULT
	$autoplay = "autoplay=0";
	$loop = 'loop=0';
	$lr = '1';
	$ud = '1';

	for($i=0; $i<7; $i++){

		if( $args[$i] == "auto") $autoplay = "autoplay=1";
		if( $args[$i] == "loop") $loop = "loop=1";
		if( $args[$i] == "LR") $lr = '-1';
		if( $args[$i] == "UD") $ud = '-1';
	}
	$style='style="-webkit-transform: scale('.$lr.','.$ud.'); transform: scale('.$lr.','.$ud.');"';

    return <<<HTML

	<iframe width="$width" height="$height" src="https://www.youtube.com/embed/$name?$autoplay&autohide=1&$loop&theme=dark&color=white" $style frameborder="0" allowfullscreen></iframe>

HTML;

}

function plugin_youtube_inline() {
	$args = func_get_args();
	return call_user_func_array('plugin_youtube_convert', $args);
}

?>
