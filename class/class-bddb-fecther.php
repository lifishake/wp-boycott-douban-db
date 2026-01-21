<?php
/**
 * @file	class-bddb-fetcher.php
 * @date	2025-12-26
 * @author	大致
 * @version	1.1.8
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
			} elseif (strpos($url, "themoviedb.org/movie/") || strpos($url, "tmdb.org/movie/")) {
				$type = "movie";
				$url = rtrim($url, "/");
				return self::fetch_from_tmdb($url);
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
	 * @brief	从tmdb获取。
	 * @param	string	$url
	 * @return 	array
	 * @since 	1.2.6
	 * @date	2026-01-19
	 */
	public static function fetch_from_tmdb($url) {
		$ret = array('result'=>'ERROR','reason'=>'invalid parameter');
		$search = basename($url);
		preg_match('/^[0-9][0-9]*/',$search, $ids);
		$auth_key = BDDB_Settings::getInstance()->get_tmdb_key();
		$api_link = 'https://api.tmdb.org/3/movie/'.(string)$ids[0].'?append_to_response=credits%2Crelease_dates%2Cimages%2Calternative_titles&language=zh-CN';
	    $api_link = htmlspecialchars_decode($api_link);
		$response = @wp_remote_get( 
			   $api_link, 
			   array( 
				   'timeout'  => 30000, 
				   'headers'   => array(
					'Content-Type'  => 'application/json',
					'Authorization' => 'Bearer '.$auth_key,
				),
			   ) 
		   );
		if ( is_wp_error( $response ) || !is_array($response) ) {
			$ret['reason'] = "wp_remote_get() failed.";
			return $ret;
		}
		$ret['result'] = 'OK';
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
			'dou_id' => '',
			'title' => '',
			'url' => $url,
			'akas' => '',
		);
		$output = $default;
		$content = json_decode(wp_remote_retrieve_body($response), true);

		//国家地区
		$region_names = array();
		$chief_3166 = '';
		foreach($content['production_countries'] as $region_info) {
			if (empty($chief_3166)) {
				$chief_3166 = $region_info['iso_3166_1'];
			}
			$region_names[] = self::get_3166_region_name($region_info);
		}
		$output['country'] = self::items_implode($region_names);

		//电影名
		$output['title'] = $content['title'];

		//原名
		$output['original_name'] = $content['original_title'];
		//中文地区不设原名
		$chinese_regions = array('CN', 'HK', 'TW', 'MY', 'SG');
		if (in_array($chief_3166, $chinese_regions)) {
			$output['original_name'] = '';
		}

		//海报
		$output['pic'] = 'https://image.tmdb.org/t/p/original'.$content['poster_path'];
		if ('CN' != $chief_3166) {
			$poster = self::get_loaction_poster($ids[0], $chief_3166);
			if (!empty($poster)) {
				$output['pic'] = 'https://image.tmdb.org/t/p/original'.$poster;
			}
		}

		//上映时间
		$output['pubdate'] = self::get_latest_tmdb_release_date($content['release_dates']['results'], $chief_3166);

		//评分当豆瓣评分
		$output['average_score'] = sprintf("%.1f", $content['vote_average']);


		//导演、演员、编剧、配乐
		$all_actors = array();
		$all_directors = array();
		$all_writers = array();
		$all_musicians = array();
		foreach($content['credits']['cast'] as $cast) {
			if ("Acting" == $cast['known_for_department']) {
				$all_actors[] = $cast['name'];
			}
		}

		foreach($content['credits']['crew'] as $crew) {
			if ("Director" == $crew['job']) {
				$all_directors[] = $crew['name'];
			}
			if ("Music" ==  $crew['job'] ||
			"Original Music Composer" ==  $crew['job'] ||
			"Songs" ==  $crew['job']) {
				$all_musicians[] = $crew['name'];
			}
			if ("Writer" ==  $crew['job'] ||
			"Novel" ==  $crew['job'] ||
			"Screenplay" ==  $crew['job']) {
				$all_writers[] = $crew['name'];
			}
		}
		$actors_str = self::items_implode($all_actors);
		$output['actor'] = self::translate_actors($actors_str);
		$directors_str = self::items_implode($all_directors);
		$output['director'] = self::translate_directors($directors_str);
		$output['screenwriter'] = self::items_implode($all_writers);
		$output['musician'] = self::items_implode($all_musicians);

		//类型
		$output['genre'] = self::items_implode_by_key($content['genres'], 'name');

		//出品公司
		$output['company']  = self::items_implode_by_key($content['production_companies'], 'name');

		//imdb编号
		$output['imdbid'] = $content['imdb_id'];

		//片长
		$output['m_length'] = $content['runtime'];

		//分级
		//$output['misc'] = self::get_latest_tmdb_release_date($content['release_dates']['results'], $chief_3166);

		$ret['content'] = $output;
		return $ret;
	}

	/**
	 * @brief	从起点中文获取。
	 * @param	string	$url
	 * @return 	array
	 * @since 	0.8.1
	 * @version 0.8.2
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
		if (is_array($matches) && is_array($matches[0]) && count($matches[0])>=3) {
			$title_str = $matches[0][0];
			$img_str = $matches[0][1];
			$author_str = $matches[0][2];
			$content['title'] = bddbt_get_in_qouta($title_str, 'content');
			$content['author'] = bddbt_get_in_qouta($author_str, 'content');
			$content['pic'] = "https:".preg_replace('/\/180$/','', trim(bddbt_get_in_qouta($img_str, 'content')));
			if (count($matches[0])==4) {
				$pubdate_str = $matches[0][3];
				preg_match('/[0-9]{4}-[0-9]{2}/', $pubdate_str, $mt);
				if (is_array($mt)) {
					$content['pubdate'] = $mt[0];
				}
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
	 * @version 1.0.9
	 * @date	2025-10-21
	 */
	public static function fetch_from_douban_page($url, $type) {
		$ua = BDDB_Settings::getInstance()->get_user_agent();
		$arg = array();
		$arg['timeout'] = 10000;
		$arg['user-agent'] = $ua;
		if (strpos($url, "douban")> 0) {
			$cookie = get_transient('douban_thief');
			$arg['cookies'] = $cookie? $cookie:array();
		}
		$ret = array('result'=>'ERROR','reason'=>'invalid parameter.');
		$response = @wp_remote_get( 
			htmlspecialchars_decode($url), 
			$arg
		);
		if ( is_wp_error( $response ) || !is_array($response) ) {
			$ret['reason'] = "wp_remote_get() failed.";
			return $ret;
		}
		if (strpos($url, "douban")> 0) {
			BDDB_Settings::getInstance()->save_douban_cookie($response);
		}

		$body = wp_remote_retrieve_body($response);
		$start_pos = strpos($body, "<title>", 0);
		$end_pos = strpos($body, "</title>", $start_pos);
		$title_str = "";
		if ( $start_pos>0 && $end_pos > $start_pos) {
			$title_str = substr($body, $start_pos, ($end_pos - $start_pos)+strlen("</title>") );
		}
		$title = str_replace(array("(豆瓣)","<title>","</title>"), "", $title_str);
		$title = htmlspecialchars_decode($title, ENT_QUOTES);
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
	 * @version 1.1.3
	 * @date	2025-11-17
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
				$obj['content'] = htmlspecialchars_decode($obj['content'], ENT_QUOTES);
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
				//$fetch['pic'] = self::get_detail_douban_pic($fetch['pic'], $ref);
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
	 * @version 0.9.9
	 * @date	2024-11-06
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
				$temp = htmlspecialchars_decode($temp, ENT_QUOTES);
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
	 * @version	1.0.5
	 * @date	2024-04-03
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
				$obj['content'] = htmlspecialchars_decode($obj['content'], ENT_QUOTES);
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
						$country = BDDB_Settings::getInstance()->get_book_country_full_name($capital);
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
				$count_str = htmlspecialchars_decode($count_str);
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
	 * @version	1.0.3
	 * @date	2025-03-24
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
				$value= htmlspecialchars_decode($value, ENT_QUOTES);
				if ("出版者" == $label) {
					if ("唱片"=== mb_substr($value, -2)) {
						$value = str_replace("唱片", "", $value);
					}
					$value = str_replace(array("滾石"), array("滚石"), $value);
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
	 * @return 	string
	 * @since 	0.0.1
	 * @version 1.0.8
	 * @date 2025-10-20
	*/	
	public static function get_detail_douban_pic($pic_mass, $default){
		sleep(11);
		//防止被豆瓣当成恶意IP
		$ua = BDDB_Settings::getInstance()->get_user_agent();
		$arg = array();
		$arg['timeout'] = 10000;
		$arg['user-agent'] = $ua;
		if (strpos($pic_mass, "douban")> 0) {
			$cookie = get_transient('douban_thief');
			$arg['cookies'] = $cookie? $cookie:array();
		}
		$ret = array('result'=>'ERROR','reason'=>'invalid parameter.');
		$response = @wp_remote_get( 
			htmlspecialchars_decode($pic_mass), 
			$arg 
		);
		if ( is_wp_error( $response ) || !is_array($response) ) {
			return $default;
		}
		if (strpos($pic_mass, "douban")> 0) {
			BDDB_Settings::getInstance()->save_douban_cookie($response);
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
	public static function trim_contry_title($c){
		return str_replace(array("中国","/"),array("",","),$c);
	}

	/**
	 * @brief	从omdb获取。
	 * @param	string		$id			omdb id
	 * @param   bool|array	$input		要合并的内容
	 * @return 	array
	 * @since 	0.0.1
	 * @version	1.0.5
	 * @date	2025-04-03
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
		$api_key = BDDB_Settings::getInstance()->get_omdb_key();
		if(empty($api_key)) {
			return $output;
		}
		$url = "http://www.omdbapi.com/?i=".$id."&apikey=".$api_key;
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
	 * @brief	内容转字符串。
	 * @param	array	$items	要排列的array
	 * @param	string	$key	要排列的key
	 * @return string
	 * @since 1.2.6
	 * @date 2026-01-20
	*/
	public static function items_implode_by_key($items, $key) {
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
		$new_items = array_column($items, $key);

		$new_items = array_map('trim', $new_items);
		if (1 == $count) {
			return $new_items[0];
		} else {
			return implode(",",$new_items);
		}
	}//items_implode

	/**
	 * @brief	删除两个符号之间的内容，包括符号本身。
	 * @param	string	$str	字符串
	 * @param	string	$b		开始字符
	 * @param	string	$e		结束字符
	 * @return 	string
	 * @since 	0.5.6
	 * @version 1.1.8
	*/
	public static function remove_words_in_sig($str, $b, $e) {
		$posa = mb_strpos($str, $b);
		$pose = mb_strpos($str, $e);
		if ($posa !== false && $pose !== false && $pose > $posa) {
			$stra = mb_substr($str, 0, $posa);
			$strb = mb_substr($str, $pose + 1);
			$str = $stra;
			if (!empty($strb)) {
				$str .= $strb;
			}
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

	/**
	 * @brief	把imdb国家地区情报中的iso-3166-1的2字符国家码转成中文国家名。
	 * @param	array	$region_info	地区情报 {"iso_3166_1": "IT", "name": "Italy"}
	 * @return 	string
	 * @since 	1.2.6
	 * @date	2026-01-20
	*/
	public static function get_3166_region_name($region_info) {
		$region_names_all = array(
			'AF' => '阿富汗',
			'AL' => '阿尔巴尼亚',
			'DZ' => '阿尔及利亚',
			'AD' => '安道尔',
			'AO' => '安哥拉',
			'AR' => '阿根廷',
			'AM' => '亚美尼亚',
			'AU' => '澳大利亚',
			'AT' => '奥地利',
			'AZ' => '阿塞拜疆',
			'BS' => '巴哈马',
			'BH' => '巴林',
			'BD' => '孟加拉',
			'BB' => '巴巴多斯',
			'BY' => '白俄罗斯',
			'BE' => '比利时',
			'BJ' => '贝宁',
			'BT' => '不丹',
			'BO' => '玻利维亚',
			'BA' => '波黑',
			'BW' => '博茨瓦纳',
			'BR' => '巴西',
			'BN' => '文莱',
			'BG' => '保加利亚',
			'BF' => '布基纳法索',
			'BI' => '布隆迪',
			'CV' => '佛得角',
			'KH' => '柬埔寨',
			'CM' => '喀麦隆',
			'CA' => '加拿大',
			'CF' => '中非',
			'TD' => '乍得',
			'CL' => '智利',
			'CN' => '大陆',
			'CO' => '哥伦比亚',
			'CD' => '刚果（金）',
			'CG' => '刚果（布）',
			'CR' => '哥斯达黎加',
			'CI' => '科特迪瓦',
			'HR' => '克罗地亚',
			'CU' => '古巴',
			'CY' => '塞浦路斯',
			'CZ' => '捷克',
			'DK' => '丹麦',
			'DJ' => '吉布提',
			'DM' => '多米尼克',
			'DO' => '多米尼加',
			'EC' => '厄瓜多尔',
			'EG' => '埃及',
			'SV' => '萨尔瓦多',
			'GQ' => '赤道几内亚',
			'ER' => '厄立特里亚',
			'EE' => '爱沙尼亚',
			'SZ' => '斯威士兰',
			'ET' => '埃塞俄比亚',
			'FJ' => '斐济',
			'FI' => '芬兰',
			'FR' => '法国',
			'GA' => '加蓬',
			'GM' => '冈比亚',
			'GE' => '格鲁吉亚',
			'DE' => '德国',
			'GH' => '加纳',
			'GR' => '希腊',
			'GU' => '关岛',
			'GT' => '危地马拉',
			'GN' => '几内亚',
			'GW' => '几内亚比绍',
			'GY' => '圭亚那',
			'HT' => '海地',
			'VA' => '梵蒂冈',
			'HN' => '洪都拉斯',
			'HK' => '香港',
			'HU' => '匈牙利',
			'IS' => '冰岛',
			'IN' => '印度',
			'ID' => '印尼',
			'IR' => '伊朗',
			'IQ' => '伊拉克',
			'IE' => '爱尔兰',
			'IL' => '以色列',
			'IT' => '意大利',
			'JM' => '牙买加',
			'JP' => '日本',
			'JO' => '约旦',
			'KZ' => '哈萨克斯坦',
			'KE' => '肯尼亚',
			'KI' => '基里巴斯',
			'KP' => '朝鲜',
			'KR' => '韩国',
			'KW' => '科威特',
			'KG' => '吉尔吉斯斯坦',
			'LA' => '老挝',
			'LV' => '拉脱维亚',
			'LB' => '黎巴嫩',
			'LS' => '莱索托',
			'LR' => '利比里亚',
			'LY' => '利比亚',
			'LI' => '列支敦士登',
			'LT' => '立陶宛',
			'LU' => '卢森堡',
			'MO' => '澳门',
			'MK' => '北马其顿',
			'MG' => '马达加斯加',
			'MW' => '马拉维',
			'MY' => '马来西亚',
			'MV' => '马尔代夫',
			'ML' => '马里',
			'MT' => '马耳他',
			'MH' => '马绍尔群岛',
			'MR' => '毛里塔尼亚',
			'MU' => '毛里求斯',
			'MX' => '墨西哥',
			'MD' => '摩尔多瓦',
			'MC' => '摩纳哥',
			'MN' => '蒙古',
			'ME' => '黑山',
			'MA' => '摩洛哥',
			'MZ' => '莫桑比克',
			'MM' => '缅甸',
			'NA' => '纳米比亚',
			'NR' => '瑙鲁',
			'NP' => '尼泊尔',
			'NL' => '荷兰',
			'NZ' => '新西兰',
			'NI' => '尼加拉瓜',
			'NE' => '尼日尔',
			'NG' => '尼日利亚',
			'NO' => '挪威',
			'OM' => '阿曼',
			'PK' => '巴基斯坦',
			'PS' => '巴勒斯坦',
			'PA' => '巴拿马',
			'PG' => '巴布亚新几内亚',
			'PY' => '巴拉圭',
			'PE' => '秘鲁',
			'PH' => '菲律宾',
			'PL' => '波兰',
			'PT' => '葡萄牙',
			'PR' => '波多黎各',
			'QA' => '卡塔尔',
			'RO' => '罗马尼亚',
			'RU' => '俄罗斯',
			'RW' => '卢旺达',
			'SM' => '圣马力诺',
			'SA' => '沙特',
			'SN' => '塞内加尔',
			'RS' => '塞尔维亚',
			'SL' => '塞拉利昂',
			'SG' => '新加坡',
			'SK' => '斯洛伐克',
			'SI' => '斯洛文尼亚',
			'SO' => '索马里',
			'ZA' => '南非',
			'SS' => '南苏丹',
			'ES' => '西班牙',
			'LK' => '斯里兰卡',
			'SD' => '苏丹',
			'SR' => '苏里南',
			'SE' => '瑞典',
			'CH' => '瑞士',
			'SY' => '叙利亚',
			'TW' => '台湾',
			'TJ' => '塔吉克斯坦',
			'TZ' => '坦桑尼亚',
			'TH' => '泰国',
			'TL' => '东帝汶',
			'TG' => '多哥',
			'TO' => '汤加',
			'TT' => '特立尼达和多巴哥',
			'TN' => '突尼斯',
			'TR' => '土耳其',
			'TM' => '土库曼斯特',
			'UG' => '乌干达',
			'UA' => '乌克兰',
			'AE' => '阿联酋',
			'GB' => '英国',
			'US' => '美国',
			'UY' => '乌拉圭',
			'UZ' => '乌兹别克斯坦',
			'VU' => '瓦努阿图',
			'VE' => '委内瑞拉',
			'VN' => '越南',
			'YE' => '也门',
			'ZM' => '赞比亚',
			'ZW' => '津巴布韦',
		);
		if (array_key_exists($region_info['iso_3166_1'], $region_names_all)) {
			return $region_names_all[$region_info['iso_3166_1']];
		} else {
			return $region_info['name'];
		}
	}

	public static function get_loaction_poster($id, $location = '') {
		$auth_key = BDDB_Settings::getInstance()->get_tmdb_key();
		$api_link = 'https://api.tmdb.org/3/movie/'.(string)$id.'/images';
	    $api_link = htmlspecialchars_decode($api_link);
		$response = @wp_remote_get( 
			   $api_link, 
			   array( 
				   'timeout'  => 30000, 
				   'headers'   => array(
					'Content-Type'  => 'application/json',
					'Authorization' => 'Bearer '.$auth_key,
				),
			   ) 
		   );
		if ( is_wp_error( $response ) || !is_array($response) ) {
			return '';
		}
		$content = json_decode(wp_remote_retrieve_body($response), true);
		$posters = $content['posters'];
		if (!is_array($posters)|| 0 == count($posters)) {
			return '';
		}
		$regions = array_column($posters, 'iso_3166_1');
		$index_key = array_search($location, $regions);
		if ($index_key !== false) {
			return $posters[$index_key]['file_path'];
		} else {
			//西语国家，第二选择西班牙
			$spanish_regions = array('ES', 'AR', 'MX', 'CL', 'CO', 'PE', 'VE', 'EC', 'GT', 'CU',
			'BO', 'DO', 'HN', 'PY', 'SV', 'NI', 'CR', 'PA', 'UY', 'GQ');
			if (in_array($location, $spanish_regions)) {
				$index_key = array_search('ES', $regions);
				if ($index_key !== false) {
					return $posters[$index_key]['file_path'];
				}
			}
			//葡语国家，第二选择葡萄牙
			$portuguese_regions = array('PT', 'BR', 'AO', 'CV', 'MZ', 'GW', 'TL');
			if (in_array($location, $portuguese_regions)) {
				$index_key = array_search('PT', $regions);
				if ($index_key !== false) {
					return $posters[$index_key]['file_path'];
				}
			}
			//第三选择，美国
			$index_key = array_search('US', $regions);
			if ($index_key !== false) {
				return $posters[$index_key]['file_path'];
			}
			//最后选择，第一个
			return $posters[0]['file_path'];
		}
	}

	/**
	 * @brief	按照第一出品地区--语言宗主地--美国--顺序，取出imdb情报中的原始海报。
	 * @param	array	$poster_infos	所有海报
	 * @param	string	$key_3166	第一出品地区
	 * @return 	string
	 * @since 	1.2.6
	 * @date	2026-01-20
	*/
	public static function get_3166_regiond_poster($poster_infos, $key_3166) {
		if (!is_array($poster_infos)|| 0 == count($poster_infos)) {
			return '';
		}
		$regions = array_column($poster_infos, 'iso_3166_1');
		if (!is_array($regions) || 0 == count($regions)) {
			return '';
		}
		$index_key = array_search($key_3166, $regions);
		if ($index_key !== false) {
			return $poster_infos[$index_key]['file_path'];
		} else {
			//西语国家，第二选择西班牙
			$spanish_regions = array('ES', 'AR', 'MX', 'CL', 'CO', 'PE', 'VE', 'EC', 'GT', 'CU',
			'BO', 'DO', 'HN', 'PY', 'SV', 'NI', 'CR', 'PA', 'UY', 'GQ');
			if (in_array($key_3166, $spanish_regions)) {
				$index_key = array_search('ES', $regions);
				if ($index_key !== false) {
					return $poster_infos[$index_key]['file_path'];
				}
			}
			//葡语国家，第二选择葡萄牙
			$portuguese_regions = array('PT', 'BR', 'AO', 'CV', 'MZ', 'GW', 'TL');
			if (in_array($key_3166, $portuguese_regions)) {
				$index_key = array_search('PT', $regions);
				if ($index_key !== false) {
					return $poster_infos[$index_key]['file_path'];
				}
			}
			//第三选择，美国
			$index_key = array_search('US', $regions);
			if ($index_key !== false) {
				return $poster_infos[$index_key]['file_path'];
			}
			//最后选择，第一个
			return $poster_infos[0]['file_path'];
		}
	}

	/**
	 * @brief	如果地区存在，找到该地区最早放映时间，否则找到所有时间里最早的放映时间。
	 * @param	array	$all_release_info	所有上映时间
	 * @param	string	$key_3166	第一出品地区
	 * @return 	string
	 * @since 	1.2.6
	 * @date	2026-01-20
	*/
	public static function get_latest_tmdb_release_date($all_release_info, $key_3166) {
		if (!is_array($all_release_info)|| 0 == count($all_release_info)) {
			return '';
		}
		$regions = array_column($all_release_info, 'iso_3166_1');
		if (!is_array($regions) || 0 == count($regions)) {
			return '';
		}
		$release_dates = array();
		$key_index = array_search($key_3166, $regions, true);
		if (false !== $key_index) {
			$releases = $all_release_info[$key_index]['release_dates'];
			$release_dates = array_column($releases, 'release_date');
		}
		else {
			foreach ($all_release_info as $region_release) {
				foreach ($region_release['release_dates'] as $release_date_info) {
					if (array_key_exists('release_date', $release_date_info) && !empty($release_date_info['release_date'])) {
						$release_dates[] = $release_date_info['release_date'];
					}
				}
			}
		}
		$release_date = min($release_dates);
		return (substr($release_date, 0, 7));
	}

}//class

