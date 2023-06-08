<?php
/**
 * @file	class-bddb-fetcher.php
 * @date	2023-06-08
 * @author	大致
 * @version	0.8.1
 * @since	0.5.5
 * 
 */

require_once( BDDB_PLUGIN_DIR . '/class/class-bddb-settings.php');

if (!function_exists('TrimArray')) {
	function TrimArray($Input){
		if (!is_array($Input)) {
			$tmp = trim(strtolower($Input));
			return $tmp;
		}
		return array_map('TrimArray', $Input);
	}
}

/**
 * @class	BDDB_Fetcher
 * @brief	抓取用类
 * @date	2022-07-22
 * @author	大致
 * @version	0.5.5
 * @since	0.5.5
 * 
 */
class BDDB_Fetcher{
	/**
	 * @brief	从豆瓣获取，根据url获得要获取的种类。
	 * @param	string	$url
	 * @return 	array
	 * @since 	0.0.1
	 * @version	0.5.5
	 */
	public static function fetch($url = '', $type = false) {
		$ret = array('result'=>'ERROR','reason'=>'invalid parameter.');
		if ('' === $url ) {
			return $ret;
		}else{
			$pos = mb_strrpos($url, "?");
			//去掉问号
			if ($pos > 0){
				$url = mb_strcut($url, 0, $pos);
			}
			if (strpos($url, "movie.douban.com")) {
				$type = "movie";
			} elseif (strpos($url, "book.douban.com")) {
				$type = "book";
			} elseif (strpos($url, "douban.com/game/")) {
				$type = "game";
			} elseif (strpos($url, "music.douban.com")) {
				$type = "album";
			} elseif (strpos($url, "imdb.com")){
				$type = "movie";
				$url = rtrim($url, "/");
				//直接走imdb
				return self::fetch_from_omdb($url);
			} elseif (strpos($url, "qidian.com")) {
				$type = "book";
				$url = rtrim($url, "/");
				//直接走imdb
				return self::fetch_from_qidian_page($url);
			} else {
				if (strpos($url, "tt") !== false) {
					$type = "movie";
					//直接走imdb
					$url = "https://www.imdb.com/title/".$url;
					return self::fetch_from_omdb($url);
				} elseif (is_numeric($url)) {
					if ("movie" === $type) {
						$url = "https://movie.douban.com/subject/".$url;
					} elseif ("book" === $type) {
						$url = "https://book.douban.com/subject/".$url;
					} elseif ("game" === $type) {
						$url = "https://www.douban.com/game/".$url;
					} elseif ("album" === $type) {
						$url = "https://music.douban.com/subject/".$url;
					}
				} else {
					return $ret;
				}
			}
		}
		return self::fetch_from_douban_page($url, $type);
	}
	
	/**
	 * @brief	从omdb获取。
	 * @param	string	$url	可以为空
	 * @return 	array
	 * @since 	0.0.1
	 */
	public static function fetch_from_omdb($url) {
		$ret = array('result'=>'ERROR','reason'=>'invalid parameter');
		preg_match('/tt[0-9][0-9]*/',$url, $ids);
		if (!is_array($ids)) {
			return $ret;
		}
		$id = $ids[0];
		$ret['content'] = self::get_from_omdb($id);
		$ret['content']['imdbid'] = $id;
		$ret['content']['dou_id'] = '';
		$ret['content']['title'] = '';
		$ret['result'] = 'OK';
		return $ret;
	}

	/**
	 * @brief	从起点中文获取。
	 * @param	string	$url
	 * @return 	array
	 * @since 	0.8.1
	 * @version 0.8.1
	 */
	public static function fetch_from_qidian_page($url) {
		$ret = array('result'=>'ERROR','reason'=>'invalid parameter.');
		$response = @wp_remote_get( 
			htmlspecialchars_decode($url), 
			array( 'timeout'  => 10000, 
					'limit_response_size ' => 20480,
			) 
		);
		if ( is_wp_error( $response ) || !is_array($response) ) {
			$ret['reason'] = "wp_remote_get() failed.";
			return $ret;
		}
		$body = wp_remote_retrieve_body($response);
		//$content['title']=;
		$content = array(
			'pic' => '',
			'average_score' => '',
			'genre' => '小说',
			'publisher' => '起点中文网',
			'pubdate' => '',
			'country' => '大陆',
			'original_name' => '',
			'author' => '',
			'aka' => '',
			'translator' => '',
			'editor' => '',
			'url' => $url,
		);
		$ret['result'] = 'OK';
		preg_match_all('/(<meta property=("og:title"|"og:image"|"og:novel:author").+?\/>)|(<li data-rid="1">.+?<\/li>)/', $body, $matches);
		if (is_array($matches) && is_array($matches[0]) && count($matches[0])>=4) {
			$title_str = $matches[0][0];
			$img_str = $matches[0][1];
			$author_str = $matches[0][2];
			$pubdate_str = $matches[0][3];
			$content['title'] = bddbt_get_in_qouta($title_str, 'content');
			$content['author'] = bddbt_get_in_qouta($author_str, 'content');
			$content['pic'] = "https:".preg_replace('/\/180$/','', trim(bddbt_get_in_qouta($img_str, 'content')));
			preg_match('/[0-9]{4}-[0-9]{2}/', $pubdate_str, $mt);
			if (is_array($mt)) {
				$content['pubdate'] = $mt[0];
			}
		}
		$ret['content'] = $content;
		return $ret;
	}

	/**
	 * @brief	抓取豆瓣页面。
	 * @param	string	$url	
	 * @param	string	$type
	 * @return array
	 * @since 	0.0.1
	 * @version 0.5.5
	 */
	public static function fetch_from_douban_page($url, $type) {
		$ret = array('result'=>'ERROR','reason'=>'invalid parameter.');
		$response = @wp_remote_get( 
			htmlspecialchars_decode($url), 
			array( 'timeout'  => 10000, ) 
		);
		if ( is_wp_error( $response ) || !is_array($response) ) {
			$ret['reason'] = "wp_remote_get() failed.";
			return $ret;
		}
		$body = wp_remote_retrieve_body($response);
		$start_pos = strpos($body, "<title>", 0);
		$end_pos = strpos($body, "</title>", $start_pos);
		$title_str = "";
		if ( $start_pos>0 && $end_pos > $start_pos) {
			$title_str = substr($body, $start_pos, ($end_pos - $start_pos)+strlen("</title>") );
		}
		$title = str_replace(array("(豆瓣)","<title>","</title>"), "", $title_str);
		$ret['result'] = 'OK';
		if ('movie' === $type) {
			$ret['content'] = self::parse_douban_movie_body($body);
		}
		elseif ('book' === $type) {
			$ret['content'] = self::parse_douban_book_body($body);
		}
		elseif ('game' === $type) {
			$ret['content'] = self::parse_douban_game_body($body);
			$title = trim($title);
			$end_pos = strpos($title, " ", 0);
			if ($end_pos > 0) {
				$original_name = trim(substr($title, $end_pos));
				$title = substr($title, 0, $end_pos);
				if (!empty($original_name)) {
					$ret['content']['original_name'] = $original_name;
				}
			}
		} 
		elseif ('album' === $type) {
			$ret['content'] = self::parse_douban_album_body($body);
		}
		else {
			return $ret;
		}
		$ret['content']['title'] = trim($title);
		$url = rtrim($url,"/");
		$ret['content']['dou_id'] = substr($url, strrpos($url, "/")+1);
		$ret['content']['url'] = $url;
		return $ret;
	}

	/**
	 * @brief	解析豆瓣页面内容。
	 * @param	string	$body	页面html内容
	 * @param	string	$type
	 * @return 	array
	 * @since 	0.0.1
	 * @version 0.6.8
	 */
	public static function parse_douban_movie_body($body) {
		$fetch = array(
			'pic' => '',
			'average_score' => '',
			'director' => '',
			'actor' => '',
			'genre' => '',
			'pubdate' => '',
			'country' => '',
			'original_name' => '',
			'imdbid' => '',
			'screenwriter' => '',
			'akas' => '',
		);
		preg_match_all('/(<div id="mainpic"[\s\S]+?<\/div>)|(<div id="info"[\s\S]+?<\/div>)|(<strong .+? property="v:average">.+?(<\/strong>|>))/',$body, $matches);
		if (is_array($matches) && is_array($matches[0]) && count($matches[0])>=3) {
			$mainpic_div_str = $matches[0][0];
			$info_div_str = $matches[0][1];
			$score_str = $matches[0][2];

			//图
			preg_match('/(?<=href=").*?(?=")/',$mainpic_div_str,$match_imgs);
			if (is_array($match_imgs)) {
				$fetch['pic'] = trim($match_imgs[0]);
			}

			//分
			preg_match('/(?<= property="v:average"\>).*?(?=\<)/',$score_str, $match_score);
			if (is_array($match_score)) {
				$fetch['average_score'] = trim($match_score[0]);
			}

			unset($matches);
			$got_arr = self::douban_info_to_array($info_div_str);
			$aka_arr = array();
			foreach ($got_arr as $label => $obj) {
				if ("导演" == $label) {
					$fetch['director'] = $obj['content'];
				}
				else if ("编剧" == $label) {
					$fetch['screenwriter'] = $obj['content'];
				}
				else if ("主演" == $label) {
					$fetch['actor'] = $obj['content'];
				}
				else if ("类型" == $label) {
					$fetch['genre'] = $obj['content'];
				}
				else if ("上映日期" == $label) {
					$fetch['pubdate'] = self::trim_year_month($obj['content']);					
				}
				else if ("制片国家/地区" == $label) {
					$fetch['country'] = self::trim_contry_title($obj['content']);
				}
				else if ("又名" == $label) {
					$fetch['original_name'] = $obj['content'];
					$aka_arr = $obj['arr'];
					//$fetch['akas'] = $obj['content'];
				}
				else if ("IMDb" == $label) {
					$fetch['imdbid'] = $obj['content'];
				}
				else if ("片长" == $label) {
					//时间有可能有多个，只取第一个
					$value = $obj['arr'][0];
					$value = str_replace(array("（","）"),array("(",")"),$value);
					$value = self::remove_words_in_sig($value, "(", ")");
					$fetch['m_length'] = trim(str_replace(array('分钟','分','minutes','minute','min'),'', $value));
				}			
			}//for

			if (isset($fetch['imdbid']) && '' != $fetch['imdbid']) {
				$fetch = self::get_from_omdb($fetch['imdbid'], $fetch);
			}
			if (strpos($fetch['pic'], 'type=R')>0) {
				preg_match('/http.*?(.jpg|.png|.webp)/', $mainpic_div_str, $match_imgs);
				$ref = $match_imgs[0];
				$fetch['pic'] = self::get_detail_douban_pic($fetch['pic'], $ref);
			}
			if (strpos(mb_convert_encoding(trim($fetch['country']),'utf-8'), mb_convert_encoding("大陆",'utf-8'))===0 ||
				strpos(mb_convert_encoding(trim($fetch['country']),'utf-8'), mb_convert_encoding("香港",'utf-8'))===0 || 
				strpos(mb_convert_encoding(trim($fetch['country']),'utf-8'), mb_convert_encoding("台湾",'utf-8'))===0) {
				$fetch['original_name'] = '';
				if (count($aka_arr)>0) {
					foreach($aka_arr as $aka_str) {
						//中文地区不需要英文别名
						if (!preg_match('/^[A-Za-z0-9\'_ ]/', $aka_str)) {
							if (!empty($fetch['akas'])) {
								$fetch['akas'].= " ,";
							}
							$fetch['akas'] .= $aka_str;
						}
					}
				}				
			}
		}
		return $fetch;
	}//parse_douban_movie_body

	/**
	 * @brief	解析豆瓣页面内容。
	 * @param	string	$body	页面html内容
	 * @return 	array
	 * @since 	0.4.1
	 * @version 0.7.4
	 * @date	2023-02-08
	 */
	public static function parse_douban_game_body($body) {
		$fetch = array(
			'pic' => '',
			'average_score' => '',
			'original_name' => '',
			'akas' => '',
			'pubdate' => '',
			'publisher' => '',
			'genre' => '',
			'platform' => '',
		);
		$matches = array();
		preg_match_all('/(<div class="item-subject-info"[\s\S]+?<\/div>)|(<dl class="game-attr">[\s\S]+?<\/dl>)|(<div .+? typeof="v:Rating"[\s\S]+?<\/div>)/',$body, $matches);
		if (is_array($matches) && is_array($matches[0]) && count($matches[0])>=3) {
			$mainpic_div_str = $matches[0][0];
			$info_div_str = $matches[0][1];
			$score_str = $matches[0][2];

			//图
			preg_match('/(?<=href=").*?(?=")/',$mainpic_div_str,$match_imgs);
			if (is_array($match_imgs)) {
				$fetch['pic'] = trim($match_imgs[0]);
			}

			//分
			preg_match('/(?<= property="v:average"\>).*?(?=\<)/',$score_str, $match_score);
			if (is_array($match_score)) {
				$fetch['average_score'] = trim($match_score[0]);
			}
			unset($matches);
			preg_match_all( '/(<dt>[\s\S]+?<\/dt>)|(<dd>[\s\S]+?<\/dd>)/', $info_div_str, $matches);
			$label = "";
			for ($i=0;$i<count($matches[0]);++$i) {
				$temp = trim(strip_tags($matches[0][$i]));
				if (0 == $i%2) {
					$label = $temp;
					continue;
				} else {
					switch ($label) {
						case "类型:":
							$arr_temp = explode("/", $temp);
							$arr_temp = array_map("trim", $arr_temp);
							//$fetch['genre'] = implode(", ", $arr_temp);
							$fetch['genre'] = "";
							break;
						case "平台:":
							$fetch['platform'] = str_replace("/", ",", $temp);
							break;
						case "别名:":
							$temp = str_replace("/", ",", $temp);
							$arr_temp = explode(',' , $temp);
							$arr_temp = array_map("trim", $arr_temp);
							$fetch['akas'] = implode(" / ", $arr_temp);
							break;
						case "开发商:":
							$fetch['publisher'] = str_replace("/", ",", $temp);
							break;
						case "发行日期:":
							$fetch['pubdate'] = str_replace("/", ",", $temp);
							break;
						default:
							break;
					}
					$label = "";
				}
			}
		}
		return $fetch;
	}//parse_douban_game_body


	/**
	 * @brief	解析豆瓣页面内容。
	 * @param	string	$body	页面html内容
	 * @return 	array
	 * @since 	0.5.5
	 */
	public static function parse_douban_book_body($body) {
		$fetch = array(
			'pic' => '',
			'average_score' => '',
			'genre' => '',
			'publisher' => '',
			'pubdate' => '',
			'country' => '',
			'original_name' => '',
			'author' => '',
			'aka' => '',
			'translator' => '',
			'editor' => '',
		);
		preg_match_all('/(<div id="mainpic"[\s\S]+?<\/div>)|(<div id="info"[\s\S]+?<\/div>)|(<strong .+? property="v:average">.+?(<\/strong>|>))/',$body, $matches);
		if (is_array($matches) && is_array($matches[0]) && count($matches[0])>=3) {
			$mainpic_div_str = $matches[0][0];
			$info_div_str = $matches[0][1];
			$score_str = $matches[0][2];

			//图
			preg_match('/(?<=href=").*?(?=")/',$mainpic_div_str,$match_imgs);
			if (is_array($match_imgs)) {
				$fetch['pic'] = trim($match_imgs[0]);
			}

			//分
			preg_match('/(?<= property="v:average"\>).*?(?=\<)/',$score_str, $match_score);
			if (is_array($match_score)) {
				$fetch['average_score'] = trim($match_score[0]);
			}

			unset($matches);
			$got_arr = self::douban_info_to_array($info_div_str);
			foreach ($got_arr as $label => $obj) {
				if ("出版社" == $label) {
					$fetch['publisher'] = $obj['content'];
				}
				elseif ("又名" == $label) {
					$fetch['akas'] = $obj['content'];
				}
				elseif("出版年" == $label) {
					$fetch['pubdate'] = $obj['content'];
				}
				elseif("原作名" == $label) {
					$fetch['original_name'] = $obj['content'];
				}
				elseif("作者" == $label) {
					$value = str_replace(array("【","】"), array("[","]") , $obj['content']);
					$pos = strpos($value,']');	
					if ($pos>0) {
						$capital = substr($value, 0, $pos);
						$tail = substr($value, $pos+1);
						$capital = trim(str_replace(array("[","]"),"",$capital));
						$country = BDDB_Settings::get_book_country_full_name($capital);
						$fetch['country'] = $country;
						$value = trim($tail);

					}
					$fetch['author'] = $value;
				}
				elseif("译者" == $label) {
					$fetch['translator'] = $obj['content'];
				}
			}//for
		}
		else{
			$data = bddbt_get_inlabel($body,"</h1>","<h2>");
			if (!empty($data)){
				$data = trim($data);
				$publisher_str = bddbt_get_inlabel($data, '<div class="ll publishers">','</div>');
				if (!empty($publisher_str)){
					$fetch['publisher'] = trim($publisher_str);
				}
				$count_str = bddbt_get_inlabel($data, '<div class="clear-both">','</div>');
				if (!empty($count_str)){
					$count_str = strip_tags($count_str);
					$count_str = str_replace('&nbsp;','',$count_str);
					$pos = strpos($count_str,':');
					if ($pos > 0){
						$count_str = trim(substr($count_str,$pos+1));
						$fetch['series_total'] = $count_str;
					}
				}
			}
		}//series
		return $fetch;
	}//parse_douban_book_body

	/**
	 * @brief	解析豆瓣页面内容。
	 * @param	string	$body	页面html内容
	 * @return 	array
	 * @since 	0.5.5
	 */
	public static function parse_douban_album_body($body) {
		$fetch = array(
			'pic' => '',
			'average_score' => '',
			'genre' => '',
			'publisher' => '',
			'pubdate' => '',
			'country' => '',
			'original_name' => '',
			'artist' => '',
			'aka' => '',
			'quantity' => '',
		);
		preg_match_all('/(<div id="mainpic"[\s\S]+?<\/div>)|(<div id="info"[\s\S]+?<\/div>)|(<strong .+? property="v:average">.+?(<\/strong>|>))/',$body, $matches);
		if (is_array($matches) && is_array($matches[0]) && count($matches[0])>=3) {
			$mainpic_div_str = $matches[0][0];
			$info_div_str = $matches[0][1];
			$score_str = $matches[0][2];

			//图
			preg_match('/(?<=href=").*?(?=")/',$mainpic_div_str,$match_imgs);
			if (is_array($match_imgs)) {
				$fetch['pic'] = trim($match_imgs[0]);
			}

			//分
			preg_match('/(?<= property="v:average"\>).*?(?=\<)/',$score_str, $match_score);
			if (is_array($match_score)) {
				$fetch['average_score'] = trim($match_score[0]);
			}

			unset($matches);
			preg_match_all( '/<span class=\"pl\">[\s\S]+?(<br[\s\S]\/>|<br\/>)/', $info_div_str, $matches);
			for ($i=0;$i<count($matches[0]);++$i) {
				$temp = trim(strip_tags($matches[0][$i]));
				$temp = preg_replace("/(\s|\&nbsp\;|　|\xc2\xa0)/", " ", strip_tags($temp));
				$temp = str_replace("：",":",$temp);
				$pos = strpos($temp,':');	
				if ($pos <= 0){
					continue;				
				}
				$label = trim(substr($temp, 0, $pos));
				$value = trim(substr($temp,$pos+1));
				$value = str_replace("/", ",", $value);
				if (strpos($value, ',') > 0) {
					$temp_array = explode(',' , $value);
					$value = self::items_implode($temp_array);
				}
				if ("出版者" == $label) {
					$fetch['publisher'] = $value;
				}
				elseif ("又名" == $label) {
					$fetch['akas'] = $value;
				}
				elseif("发行时间" == $label) {
					$fetch['pubdate'] = $value;
				}
				elseif("流派" == $label) {
					$fetch['genre'] = $value;
				}
				elseif("表演者" == $label) {
					$fetch['artist'] = $value;
				}
				elseif("专辑类型" == $label) {
					$fetch['quantity'] = $value;
				}
			}//for
		}
		return $fetch;
	}//parse_douban_album_body

	/**
	 * @brief	格式化豆瓣列表区内容。
	 * @param	string	$info	页面html内容
	 * @return 	array
	 * @since 	0.5.5
	 * @version 0.5.7
	*/	
	public static function douban_info_to_array($info) {
		$total = str_replace(array('<br>','<br />','<br/>'),'^_^',$info);
		$total = strip_tags($total);			
		$total_arr = explode('^_^', $total);
		$got_arr = array();
		$temp_array = array();
		foreach($total_arr as $line) {
			$temp = preg_replace("/(\s|\&nbsp\;|　|\xc2\xa0)/", " ", $line);//去掉空格和换行等字符
			//查找冒号，根据冒号划分前半和后半部分。如果没找到半角冒号，就替换第一个全角冒号再找一次。
			$pos = strpos($temp,':');
			if (false === $pos) {
				$count = 1;
				$temp = str_replace("：",":", $temp, $count);
			}
			$pos = strpos($temp,':');	
			if (false === $pos){
				continue;				
			}
			$label = trim(substr($temp, 0, $pos));
			$value = trim(substr($temp, $pos+1));
			$value = str_replace("/", ",", $value);
			if (strpos($value, ',') > 0) {
				$temp_array = explode(',' , $value);
				$temp_array = array_map('trim', $temp_array);
				$value = self::items_implode($temp_array);
			}
			else {
				$temp_array=array();
				$temp_array[] = $value;
			}
			$got_arr[$label] = array( 'content' => $value, 
									  'arr' => $temp_array );
		}
		return $got_arr;
	}

	/**
	 * @brief	解析豆瓣页面内容。
	 * @param	string	$pic_mass	批量图片地址
	 * @param	string	$default	默认图片地址
	 * @return 	array
	 * @since 	0.0.1
	*/	
	public static function get_detail_douban_pic($pic_mass, $default){
		sleep(11);
		//防止被豆瓣当成恶意IP
		$response = @wp_remote_get( 
			htmlspecialchars_decode($pic_mass), 
			array( 'timeout'  => 10000, ) 
		);
		if ( is_wp_error( $response ) || !is_array($response) ) {
			return $default;
		}
		$official_name = self::get_short_name($default);
		$array_result_imgs = array();

		$body = wp_remote_retrieve_body($response);
		preg_match_all('/<div class="cover"[\s\S]+?<\/div>/', $body, $matches);
		if (is_array($matches)&& is_array($matches[0])) {
			foreach ($matches[0] as $m_str) {
				preg_match('/(?<=src=").*?(?=")/', $m_str, $match_imgs);
				if (is_array($match_imgs)) {
					if (strpos($match_imgs[0], $official_name)!==false) {
						return trim($match_imgs[0]);
					}
					else
					{
						$array_result_imgs[] = trim($match_imgs[0]);
					}
				}
			}
			return $array_result_imgs[0];
		}
		return $default;
	}

	/**
	 * @brief	修改日期格式。
	 * @param	string	$y	日期字符串
	 * @return 	string
	 * @since 	0.0.1
	*/
	public static function trim_year_month($y) {
		//去掉（中国香港）等括号内容
		$pos = strpos($y, "(");
		if (false !== $pos) {
			$y = substr($y, 0, $pos);
		}
		$pos = strpos($y, "（");
		if (false !== $pos) {
			$y = substr($y, 0, $pos);
		}
		//只保留年月
		$pos = strpos($y, "-");
		if (false === $pos) {
			//只有年
			return $y.'-01';
		}
		$parts = explode("-", $y);
		return $parts[0]."-".$parts[1];
	}
	
	/**
	 * @brief	修改地区格式。
	 * @private
	 * @param	string	$c	地区字符串
	 * @return string
	 * @since 0.0.1
	*/
	private function trim_contry_title($c){
		return str_replace(array("中国","/"),array("",","),$c);
	}

	/**
	 * @brief	从omdb获取。
	 * @param	string		$id			omdb id
	 * @param   bool|array	$input		要合并的内容
	 * @return 	array
	 * @since 	0.0.1
	*/
	public static function get_from_omdb($id, $input=false){
		$default = array(
			'pic' => '',
			'average_score' => '',
			'director' => '',
			'actor' => '',
			'screenwriter' => '',
			'genre' => '',
			'pubdate' => '',
			'original_name' => '',
			'imdbid' => '',
			'country' => '',
			'm_length' => '',
		);
		if (!$input) {
			$output = $default;
		}else{
			$output = wp_parse_args($input, $default);
		}
		if (''==$id || strpos($id, "tt")!=0) {
			return $output;
		}
		$api_key = BDDB_Settings::get_omdb_key();
		if(empty($api_key)) {
			return $output;
		}
		$url = "https://www.omdbapi.com/?i=".$id."&apikey=".$api_key;
		$response = @wp_remote_get($url);
		if (is_wp_error($response))
		{
			return $output;
		}
		$content = json_decode(wp_remote_retrieve_body($response),true);
		$output['original_name'] = $content['Title'];
		$output['imdb_score'] = $content['imdbRating'];
		if ('' == $output['pic']) $output['pic'] = $content['Poster'];
		if ('' == $output['director']) $output['director'] = self::translate_directors($content['Director']);
		if ('' == $output['actor']) $output['actor'] = self::translate_actors($content['Actors']);
		if ('' == $output['screenwriter']) $output['screenwriter'] = self::trim_year_month($content['Writer']);
		if ('' == $output['genre']) $output['genre'] = self::translate_m_genres($content['Genre']);
		if ('' == $output['country']) $output['country'] = self::translate_m_region($content['Country']);
		if ('' == $output['pubdate']) $output['pubdate'] = self::trim_year_month($content['Year']);
		if ('' == $output['m_length']) {
			$output['m_length'] = trim(str_replace('min','', $content['Runtime']));
		}
		if (strpos($output['country'],'中国') === 0||
			strpos($output['country'],'香港') === 0||
			strpos($output['country'],'台湾') === 0||
			strpos($output['country'],'china') === 0||
			strpos($output['country'],'hong kong')=== 0||
			strpos($output['country'],'taiwan') === 0
		){
			$output['original_name'] = '';
		}
		$output['url'] = "https://www.imdb.com/title/".$id;
		return $output;
	}
	
	/**
	 * @brief	字符串替换。
	 * @param	string	$pic_mass	页面html内容
	 * @return 	string
	 * @since 	0.2.1
	*/
	public static function my_space_replace($in_str) {
		$in_str = str_replace(" ","-",trim($in_str));
		return $in_str;
	}

	/**
	 * @brief	根据tax的内容获取文字。
	 * @param	string	$tax			分类法slug
	 * @param	string	$imaged_slugs	取得的内容想象成slug
	 * @return	string
	 * @since	0.0.1
	 * @version	0.3.3
	*/
	public static function tax_slugs_to_names($tax, $imaged_slugs){
		$ret = strtolower($imaged_slugs);
		$srcs = TrimArray(explode(',', $imaged_slugs));
		$old = $srcs;
		$os = array_map('self::my_space_replace', $srcs);
		$got = array();
		$i = 0;
		$limit = 10;
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
	 * @brief	翻译导演名字。
	 * @param	string	$in_str			原始导演名
	 * @return	string
	 * @since	0.0.1
	*/
	public static function translate_directors($in_str){
		return self::tax_slugs_to_names('m_p_director', $in_str);
	}

	/**
	 * @brief	翻译演员名字。
	 * @param	string	$in_str			原始导演名
	 * @return	string
	 * @since	0.0.1
	*/
	public static function translate_actors($in_str){
		return self::tax_slugs_to_names('m_p_actor', $in_str);
	}

	/**
	 * @brief	转换地区。
	 * @param	string	$in_str	转换前内容（英）
	 * @return string
	 * @since 0.0.1
	*/
	public static function translate_m_region($in_str){
		return self::tax_slugs_to_names('m_region', $in_str);
	}

	/**
	 * @brief	转换类型。
	 * @param	string	$in_str	转换前内容（英）
	 * @return string
	 * @since 0.0.1
	*/
	public static function translate_m_genres($in_str){
		return self::tax_slugs_to_names('m_genre', $in_str);
	}

	/**
	 * @brief	内容转字符串。
	 * @param	array	$items	要排列的内容
	 * @return string
	 * @since 0.0.1
	*/
	public static function items_implode($items) {
		if (!is_array($items)) {
			return $items;
		}
		$count = count($items);
		if (0 == $count) {
			return "";
		}
		if ($count > 8) {
			$items = array_slice($items, 0, 16);
		}
		$items = array_map('trim', $items);
		if (1 == $count) {
			return $items[0];
		} else {
			return implode(",",$items);
		}
	}//items_implode

	/**
	 * @brief	删除两个符号之间的内容，包括符号本身。
	 * @param	string	$str	字符串
	 * @param	string	$b		开始字符
	 * @param	string	$e		结束字符
	 * @return 	string
	 * @since 	0.5.6
	*/
	public static function remove_words_in_sig($str, $b, $e) {
		$posa = mb_strpos($str, $b);
		$pose = mb_strpos($str, $e);
		if ($posa !== false && $pose !== false && $pose > $posa) {
			$stra = mb_substr($str, 0, $posa);
			$strb = mb_substr($str, $pose + 1);
			$str = $stra + $strb;
		}
		return $str;
	}

	/**
	 * @brief	取文件名，不包括扩展名。
	 * @param	string	$str	字符串
	 * @return 	string
	 * @since 	0.5.6
	*/
	public static function get_short_name($url) {
		$posa = strrpos($url, '/');
		if (false === $posa) {
			return false;
		}
		$pose = strrpos($url, '.');
		if (false === $pose) {
			return false;
		}
		if ($pose <= $posa) {
			return false;
		}
		$name = substr($url, $posa+1, $pose - $posa -1);
		return $name;
	}

}//class

