<?php
/**
 * @file	bddb-funcs.php
 * @brief	外部接口和内部工具
 * @date	2021-12-21
 * @author	大致
 * @version	0.4.1
 * @since	0.0.1
 * 
 */
add_action('admin_print_footer_scripts','bddb_quicktags');

/**
 * 乱七八糟的工具类
*/

class BDDB_Tools {
	//protected static
	
	/**
	 * 优化出品时间。改为年-月格式。如果只输入年则默认定位到该年1月
	 * @protected
	 * @param 	string 	$str	编辑框中的出品年份
	 * @return 	string	修改后的出品年份
	 * @see		update_meta()->sanitize_callback
	 * @since 0.4.1
	 */
	public static function sanitize_year_month($str) {
		if (empty($str)) {
			return $str;
		}
		if (!strpos($str,"-") && intval($str)>1904) {
			$str .= '-01';
		}
		if (strtotime(date("Y-m-d",strtotime($str))) == strtotime($str) ||
			strtotime(date("Y-m-d H:i:s",strtotime($str))) == strtotime($str)) {
			$str = date("Y-m", strtotime($str));
		}
		return $str;
	}
	/**
	 * @brief	根据tax的内容获取文字。
	 * @private
	 * @param	string	$tax			分类法slug
	 * @param	string	$imaged_slugs	取得的内容想象成slug
	 * @param	int		$limit			最大个数
	 * @return	string
	 * @since	0.0.1
	 * @version	0.4.1
	*/
	public static function tax_slugs_to_names($tax, $imaged_slugs, $limit = 10){
		$srcs = explode(',', $imaged_slugs);
		$old = array_map('trim', $srcs);
		$os = array_map('self::my_space_replace', $srcs);
		$got = array();
		$i = 0;
		foreach ($os as $slug) {
			$got_items = get_terms(array(	'taxonomy'=>$tax,
											'hide_empty'=>false,
											'slug'=>$slug));
			if (is_wp_error($got_items) || empty($got_items)) {
				$got[] = $old[$i];
			} else {
				$got[] = $got_items[0]->name;
			}
			$i++;
			if ($i == $limit){
				break;
			}
		}
		$ret = implode(", ", $got);
		return $ret;
	}
	/**
	 * @brief	字符串替换。
	 * @public
	 * @param	string	$pic_mass	页面html内容
	 * @return string
	 * @since 0.2.1
	*/
	public static function my_space_replace($in_str) {
		$in_str = str_replace(" ","-",trim($in_str));
		$in_str = strtolower($in_str);
		return $in_str;
	}
}

//从第n个位置开始查找count个start_str与stop_str间的内容
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

//查找str中start_str和stop_str间的内容
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

//供主题使用，最好在page里，不要使用the_post
function bddb_the_gallery($post_type) {
	if (!BDDB_Statics::is_valid_type($post_type)) {
        the_content();
        return;
    }
	if ('book' == $post_type) {
		BDDB_Book::getInstance()->the_gallery();
	} elseif('movie' == $post_type) {
		BDDB_Movie::getInstance()->the_gallery();
	} elseif('game' == $post_type) {
		BDDB_Game::getInstance()->the_gallery();
	} elseif('album' == $post_type) {
		BDDB_Album::getInstance()->the_gallery();
	}
}

//short_code
//fuck古腾堡
function bddb_quicktags(){
?>
	<script type="text/javascript" charset="utf-8">
	QTags.addButton( 'eg_bddbr', 'BDDbRd', '[bddbr id=\'', '\' /]', 'p' );
	</script>
<?php
}

//整合输出文件名
//TODO:使用静态类
function bddb_get_poster_names($post_type, $ID) {
	$ret = array();
	$name = sprintf("%s_%013d.jpg", $post_type, $ID);
	$dir_o = BDDB_Settings::get_default_folder();
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
	$poster_width = BDDB_Settings::get_poster_width($post_type);
	$poster_height = BDDB_Settings::get_poster_height($post_type);
	$thumb_width = BDDB_Settings::get_thumbnail_width($post_type);
	$thumb_height = BDDB_Settings::get_thumbnail_height($post_type);
	if ('album' == $post_type) {
		$poster_height = $poster_width;
		$thumb_height = $thumb_width;
	}
	$ret['nopic_thumb_url'] = sprintf( "%simg/nocover_%s_%s.png", $rel_plugin_url, $thumb_width, $thumb_height );
	$ret['nopic_poster_url'] = sprintf( "%simg/nocover_%s_%s.png", $rel_plugin_url, $poster_width, $poster_height );
	return (object)$ret;
}

function bddb_array_child_value_to_str($data, $key, $name_key="name", $unknown_str="") {
	$ret = '';
    if (array_key_exists($key, $data) && is_array($data[$key])) {
        $subs = $data[$key];
        if ( count($subs)>1 ) {
            if ( is_array($subs[0]) && array_key_exists($name_key, $subs[0])) {
                $items = wp_list_pluck($subs, $name_key);
                $ret .= implode(', ', $items);
            } else {
                $ret .= implode(', ', $subs);
            }
        } else if (!empty($subs)) {
            if (is_array($subs[0]) && array_key_exists($name_key, $subs[0])) {
                $ret .= $subs[0][$name_key];
            } else {
                $ret .= $subs[0];
            }
        } else {
            $ret .= $unknown_str;
        }
    } elseif (array_key_exists($key, $data)) {
        $ret .= $data[$key];
    } else {
        $ret .= $unknown_str;
    }
    return $ret;
}