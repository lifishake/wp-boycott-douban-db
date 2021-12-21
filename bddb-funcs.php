<?php

add_action('admin_print_footer_scripts','bddb_quicktags');

function bddbt_get_msg($str, $start_str, $stop_str, $count, $n) { 
	$start=$n; //从第n个位置开始查找
	$data=array(); 
	for($i=0;$i<$count;$i++) {
		$start=strpos($str,$start_str,$start);
		$stop=strpos($str,$stop_str,$start);
		$start=strlen($start_str)+$start;
		$data[$i]= substr($str,$start,$stop-$start);
		$start=$stop;
	}
	return $data;
}

function bddbt_get_inlabel($str, $start_str, $stop_str){
	$arr = bddbt_get_msg($str, $start_str, $stop_str ,1 ,0);
	if (1 != count($arr)){
		return false;
	}
	return $arr[0];
}

function bddbt_substr_n_pos($str,$find,$n){
    $pos_val=0;
    for ($i=1;$i<=$n;$i++){
        $pos = strpos($str,$find);
		if(false===$pos){
			break;
		}
        $str = substr($str,$pos+1);
        $pos_val=$pos+$pos_val+1;
    }
	if ($pos_val > 0) {
		return substr($str,0,$pos_val - 1);
	}
    return $str;
}

function bddb_the_content() {
    $post = get_post();
    if (!in_array($post->post_type, array('movie','book','game','album'))) {
        the_content();
        return;
    }
	$post_ID = $post->ID;
	$tl = new BDDB_Common_Template($post->post_type);
	echo $tl->get_content();
}

function bddb_the_gallery($post_type) {
	if (!in_array($post_type, array('movie','book','game','album'))) {
        the_content();
        return;
    }
	$tl = new BDDB_Common_Template($post_type);
	$tl->the_gallery();
}

function bddb_quicktags(){
?>
	<script type="text/javascript" charset="utf-8">
	QTags.addButton( 'eg_bddbr', 'BDDbRd', '[bddbr id=\'', '\' /]', 'p' );
	</script>
<?php
}

function bddb_real_transfer($atts, $content = null){
	extract( $atts );
	$tl = new BDDB_Common_Template('auto', $id);
	$summary=$tl->get_summary();
	return $summary;
}

function bddb_get_poster_names($post_type, $ID) {
	$ret = array();
	$name = sprintf("%s_%013d.jpg", $post_type, $ID);
	$s = new BDDB_Settings();
	$dir_o = $s->get_default_folder();
	$gallery_dir = ABSPATH.$dir_o;
	$gallery_url = home_url('/',is_ssl()?'https':'http').$dir_o;
	$rel_url = str_replace(home_url(), '', $gallery_url);
	$rel_plugin_url = str_replace(home_url(), '', BDDB_PLUGIN_URL);
	if (bddb_is_debug_mode()){
		$rel_url = str_replace('http://localhost', '', $gallery_url);
		$rel_plugin_url = str_replace('http://localhost', '', BDDB_PLUGIN_URL);
	}
	$ret['short_name'] = $name;
	$ret['gallery_dir'] = $gallery_dir;
	$ret['thumb_dir'] = $gallery_dir.'thumbnails/';
	$ret['poster_name'] = $gallery_dir .$name;
	$ret['thumb_name'] = $gallery_dir.'thumbnails/'.$name;
	$ret['thumb_series_front'] = $gallery_dir.'thumbnails/'.sprintf("%s_%013d_", $post_type, $ID);
	$ret['poster_url'] = $rel_url .$name;
	$ret['thumb_url'] = $rel_url.'thumbnails/'.$name;
	$ret['thumb_url_front'] = $rel_url.'thumbnails/';
	$poster_width = $s->get_poster_width();
	$poster_height = $s->get_poster_height();
	$thumb_width = $s->get_thumbnail_width();
	$thumb_height = $s->get_thumbnail_height();
	if ('album' == $post_type) {
		$poster_height = $poster_width;
		$thumb_height = $thumb_width;
	}
	$ret['nopic_thumb_url'] = sprintf( "%simg/nocover_%s_%s.png", $rel_plugin_url, $thumb_width, $thumb_height );
	$ret['nopic_poster_url'] = sprintf( "%simg/nocover_%s_%s.png", $rel_plugin_url, $poster_width, $poster_height );
	return (object)$ret;
}