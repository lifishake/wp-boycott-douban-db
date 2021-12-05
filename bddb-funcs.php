<?php

add_action('admin_print_footer_scripts','bddb_quicktags');
add_action( 'transition_post_status', 'bddb_content_filter', 10, 3 );

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
	if (!class_exists('BDDB_Common_Template')) {
		echo "<p>tffesting...</p>";
		return;
	}
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
	QTags.addButton( 'eg_bddb', 'BDDbItem', '[bddbitem id=\'', '\' got=\'no\' summary=\'\' /]', 'p' );
	QTags.addButton( 'eg_bddbr', 'BDDbRd', '[bddbr id=\'', '\' /]', 'p' );
	</script>
<?php
}

function bddb_content_filter($new_status, $old_status, $post) {
	if ( 'post' !== $post->post_type && 'page' !== $post->post_type) {
        return;
    }
	if (!class_exists('BDDB_Common_Template')){
		return;
	}
	$my_content = $post->post_content;
    $fix_to = "";
    if ( "draft" == $new_status || "publish" == $new_status || "private" == $new_status) {
        preg_match_all('/\[bddbitem.+[^\]]/', $post->post_content, $matches);
        if ( !is_array($matches) || empty($matches) ) {
            return;
        }
		foreach ($matches[0] as $hit) {
			unset($id);
			unset($got);
			unset($summary);
			//id
            preg_match('/(?<=id=\').*?(?=\')/', $hit, $keys);
            if(!is_array($keys) || count($keys) == 0 || trim($keys[0])== "") {
                continue;
            }
            $id = trim($keys[0]);
			
			preg_match('/(?<=got=\').*?(?=\')/', $hit, $keys);
            if(!is_array($keys) || count($keys) == 0 || trim($keys[0])== "") {
                continue;
            }
			$got = trim($keys[0]);
			if('no'!=$got) {
				continue;
			}
			//print_r(debug_backtrace());
			$tl = new BDDB_Common_Template('auto', $id);
			$summary=$tl->get_summary();
			$fix_to = sprintf("[bddbitem id='%s' got='yes' summary='%s']", $id, $summary);
			$hit = trim($hit);
			$my_content = str_replace($hit, $fix_to, $my_content);
		}//end foreach
		if ($fix_to !== "") {
            //防止无限循环
            remove_action( 'transition_post_status', 'bddb_content_filter', 10 );
			if (function_exists('apip_fix_shortcodes')){
				remove_filter( 'the_content', 'apip_fix_shortcodes' );
			}
            wp_update_post(array("ID"=>$post->ID, "post_content"=> $my_content));
            add_action( 'transition_post_status', 'bddb_content_filter', 10, 3 );
			if (function_exists('apip_fix_shortcodes')){
				add_filter( 'the_content', 'apip_fix_shortcodes');
			}
        }
	}
}

function bddb_shortcode_transfer($atts, $content = null){
	extract( $atts );
	if ("yes"!=$got) {
		return '';
	}
	return $summary;
}

function bddb_real_transfer($atts, $content = null){
	extract( $atts );
	$tl = new BDDB_Common_Template('auto', $id);
	$summary=$tl->get_summary();
	return $summary;
}