<?php
/**
 * @file	class-bddb-douban-fetcher.php
 * @class	BDDB_DoubanFetcher
 * @brief	从豆瓣抓取用类
 * @date	2021-12-21
 * @author	大致
 * @version	0.4.5
 * @since	0.0.1
 * 
 */

require_once( BDDB_PLUGIN_DIR . '/class/class-bddb-settings.php');

function trim_fetched_item($value) {
    $value = trim($value);
    $value = str_replace(array("[", "]", "&nbsp;", ""),array("【", "】", "", "N/A"), $value);
    return $value;
}

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
 * 抓取类
 */
class BDDB_DoubanFetcher{
	//成员列表
	protected	$type;	//类型
	/**
	 * @brief	构造函数。
	 * @public
	 * @param	string	$in_type	可以为空
	 * @since 0.0.1
	 */
	public function __construct($in_type = ''){
		$this->type = $in_type;
	}//__construct

	/**
	 * @brief	构造函数。
	 * @public
	 * @param	string	$url	可以为空
	 * @return	array
	 * @since	0.0.1
	 * @version	0.3.3
	 */
	public function fetch($url = '') {
		$ret = array('result'=>'ERROR','reason'=>'invalid parameter.');
		if ('' === $url ) {
			return $ret;
		}elseif('test'==$url) {
			$ret['content']='aaaa';
			$fetch = array(
				'title' => '',
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
				'author' => '',
				'translator' => '',
				'artist' => '',
				'series_total' => '',
			);
			$aarrstr = 'charlie cho, faye wong';
			$actors = $this->translate_actors($aarrstr);
			$ret['result']=$fetch;
			$ret['result']['actor'] = $actors;
			return $ret;
		}else{
			$pos = mb_strrpos($url, "?");
			//去掉问号
			if ($pos > 0){
				$url = mb_strcut($url, 0, $pos);
			}
			if (strpos($url, "movie.douban.com")) {
				$this->type = "movie";
			} elseif (strpos($url, "book.douban.com")) {
				$this->type = "book";
			} elseif (strpos($url, "douban.com/game/")) {
				$this->type = "game";
			} elseif (strpos($url, "music.douban.com")) {
				$this->type = "album";
			} elseif (strpos($url, "imdb.com")){
				$this->type = "movie";
				//直接走imdb
				return $this->fetch_from_omdb($url);
			} elseif (strpos($url, "giantbomb.com")) {
				$this->type = "game";
				return $this->fetch_from_giantbomb($url);
			} else {
				if (strpos($url, "tt") !== false) {
					$this->type = "movie";
					//直接走imdb
					$url = "https://www.imdb.com/title/".$url;
					return $this->fetch_from_omdb($url);
				} elseif (is_numeric($url)) {
					if ("movie" === $this->type) {
						$url = "https://movie.douban.com/subject/".$url;
					} elseif ("book" === $this->type) {
						$url = "https://book.douban.com/subject/".$url;
					} elseif ("game" === $this->type) {
						$url = "https://www.douban.com/game/".$url;
					} elseif ("album" === $this->type) {
						$url = "https://music.douban.com/subject/".$url;
					}
				} else {
					return $ret;
				}
			}
		}
		return $this->fetch_from_douban_page($url);
	}
	
	/**
	 * @brief	从omdb获取。
	 * @private
	 * @param	string	$url	可以为空
	 * @return array
	 * @since 0.0.1
	 */
	private function fetch_from_omdb($url) {
		$ret = array('result'=>'ERROR','reason'=>'invalid parameter');
		preg_match('/tt[0-9][0-9]*/',$url, $ids);
		if (!is_array($ids)) {
			return $ret;
		}
		$id = $ids[0];
		$ret['content'] = $this->get_from_omdb($id);
		$ret['content']['imdbid'] = $id;
		$ret['content']['dou_id'] = '';
		$ret['content']['title'] = '';
		$ret['result'] = 'OK';
		return $ret;
	}

	/**
	 * @brief	从giantbomb获取。
	 * @private
	 * @param	string	$url	
	 * @return array
	 * @since 0.4.4
	 */
	private function fetch_from_giantbomb($url) {
		$ret = array('result'=>'ERROR','reason'=>'invalid parameter');
		preg_match('/[0-9]+\-[0-9]+/',$url, $ids);
		if (!is_array($ids)) {
			return $ret;
		}
		$id = $ids[0];
		$ret['content'] = $this->get_from_giantbomb($id);
		$ret['content']['giid'] = $id;
		//$ret['content']['dou_id'] = '';
		$ret['content']['title'] = '';
		$ret['result'] = 'OK';
		return $ret;
	}
	
	/**
	 * @brief	抓取豆瓣页面。
	 * @private
	 * @param	string	$url	可以为空
	 * @return array
	 * @since 0.0.1
	 */
	private function fetch_from_douban_page($url) {
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
		if ('game' === $this->type) {
			$ret['content'] = $this->parse_douban_game_body($body);
			$title = trim($title);
			$end_pos = strpos($title, " ", 0);
			if ($end_pos > 0) {
				$original_name = trim(substr($title, $end_pos));
				$title = substr($title, 0, $end_pos);
				if (!empty($original_name)) {
					$ret['content']['original_name'] = $original_name;
				}
			}
		} else {
			$ret['content'] = $this->parse_douban_body($body);
		}
		$ret['content']['title'] = trim($title);
		$url = rtrim($url,"/");
		$ret['content']['dou_id'] = substr($url, strrpos($url, "/")+1);
		return $ret;
	}

	/**
	 * @brief	解析豆瓣页面内容。
	 * @private
	 * @param	string	$body	页面html内容
	 * @return array
	 * @since 0.0.1
	 */
	private function parse_douban_body($body) {
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
			'author' => '',
			'translator' => '',
			'artist' => '',
			'series_total' => '',
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

			if ("movie"=== $this->type) {
				//电影：导演，演员，类型，上映时间，imdb链接
				$info_grep_keys = array(
					array('pattern'=>'/(?<="v:directedBy"\>).*?(?=\<)/', 'item'=>'director'), 
					array('pattern'=>'/(?<="v:starring"\>).*?(?=\<)/', 'item'=>'actor', 'sanitize_callback'=>array($this, 'translate_actors')),
					array('pattern'=>'/(?<="v:genre"\>).*?(?=\<)/', 'item'=>'genre'),
					array('pattern'=>'/(?<=\<span property="v:initialReleaseDate" content=").*?(?=\")/', 'item'=>'pubdate', 'sanitize_callback'=>array($this, 'trim_year_month')),
					array('pattern'=>'/(?<=\<span class=[\',\"]pl[\',\"]\>制片国家\/地区:\<\/span\>).*?(?=\<br\/\>)/', 'item'=>'country', 'sanitize_callback'=>array($this, 'trim_contry_title')),
					//array('pattern'=>'/(?<=\<span class=[\',\"]pl[\',\"]\>编剧\<\/span\>:).*?(?=\<br\/\>)/', 'item'=>'screenwriter'),
					array('pattern'=>'/(?<=\<span class=[\',\"]pl[\',\"]\>又名:\<\/span\>).*?(?=\<br\/\>)/', 'item'=>'original_name'),
					array('pattern'=>'/(?<=\<span class=[\',\"]pl[\',\"]\>IMDb:\<\/span\>).*?(?=\<br\>)/', 'item'=>'imdbid'),
				);
				preg_match_all( '/(?<=\<span class=[\',\"]pl[\',\"]\>编剧\<\/span\>:).*?(?=\<br\/\>)/', $info_div_str, $matches);
				if (is_array($matches) && is_array($matches[0]) && count($matches[0])>=1) {
					$screenwriter_str = $matches[0][0];
					unset($matches);
					preg_match_all( '/<a(.*?)>(.*?)<\/a>/', $screenwriter_str, $matches);
					if (is_array($matches) && count($matches)==3 && is_array($matches[2]) && count($matches[2])>=1) {
						$screenwriter_str = $this->items_implode($matches[2]);
					}
					$fetch['screenwriter'] = $screenwriter_str;
				}
			} elseif ("book"===$this->type) {
				$fetch['original_name'] = '';
				$info_grep_keys = array(
					array('pattern'=>'/(?<=\<span class="pl"\>出版社:\<\/span\>).*?(?=\<br\/\>)/', 'item'=>'publisher'),
					array('pattern'=>'/(?<=\<span class="pl"\>出版年:\<\/span\>).*?(?=\<br\/\>)/', 'item'=>'pubdate'),
					array('pattern'=>'/(?<=\<span class="pl"\>原作名:\<\/span\>).*?(?=\<br\/\>)/', 'item'=>'original_name'),
				);
				/*<span>[\s\S]+?<span class="pl"> 作者</span>\:[\s\S]+?<\/span> */
				/*<span>[\s\S]+?<span class="pl"> 译者</span>\:[\s\S]+?<\/span> */
				$ak = 1;
				$pos_start = strpos($info_div_str, '<span class="pl"> 作者</span>:');
				if ($pos_start <=0) {
					$pos_start = strpos($info_div_str, '<span class="pl">作者:</span>');
					$ak = 2;
				}
				if (1==$ak) {
					$pos_end = strpos($info_div_str, '</span><br/>', $pos_start);
				} else {
					$pos_end = strpos($info_div_str, '<br>', $pos_start);
				}
				
				if ( $pos_start>0 && $pos_end > $pos_start) {
					if (1 == $ak) {
						$author_str = substr($info_div_str, $pos_start, ($pos_end - $pos_start)+strlen('</span><br/>') );
					} else {
						$author_str = substr($info_div_str, $pos_start, ($pos_end - $pos_start)+strlen('<br>') );
					}
					
					unset($matches);
					preg_match_all( '/(?<=>)[\s\S].*?(?=<\/a>)/', $author_str, $matches);
					if (is_array($matches) && count($matches)>0 && is_array($matches[0]) ) {
						$author_untrim = $this->items_implode($matches[0]);
						preg_match_all('/^【(.*)】[\s](.*)$/', $author_untrim, $m1);
						$fetch['author'] = preg_replace('/【.】 /',"",$this->items_implode($matches[0]));
					}
				}

				$fetch['translator'] = '';
				$ak = 1;
				$pos_start = strpos($info_div_str, '<span class="pl"> 译者</span>:');
				if ($pos_start <=0) {
					$ak = 2;
					$pos_start = strpos($info_div_str, '<span class="pl">译者:</span>');
				}
				if (1==$ak) {
					$pos_end = strpos($info_div_str, '</span><br/>', $pos_start);
				} else {
					$pos_end = strpos($info_div_str, '<br>', $pos_start);
				}
				if ( $pos_start>0 && $pos_end > $pos_start) {
					if (1==$ak) {
						$author_str = substr($info_div_str, $pos_start, ($pos_end - $pos_start)+strlen('</span><br/>') );
					} else{
						$author_str = substr($info_div_str, $pos_start, ($pos_end - $pos_start)+strlen('<br>') );
					}
					unset($matches);
					preg_match_all( '/(?<=>)[\s\S].*?(?=<\/a>)/', $author_str, $matches);
					if (is_array($matches) && count($matches)>0 && is_array($matches[0]) ) {
						$fetch['translator'] = $this->items_implode($matches[0]);
					}
				}
			} elseif ("album"=== $this->type) {
				$info_grep_keys = array(
					array('pattern'=>'/(?<=\<span class="pl"\>出版者:<\/span>).[\s\S]*?(?=\<br[\s\S]\/>)/', 'item'=>'publisher'),
					array('pattern'=>'/(?<=\<span class="pl"\>发行时间:<\/span>).[\s\S]*?(?=\<br[\s\S]\/>)/', 'item'=>'release_date'),
					array('pattern'=>'/(?<=\<span class="pl"\>流派:<\/span>).[\s\S]*?(?=\<br[\s\S]\/>)/', 'item'=>'genre'),
				);
				$fetch['artist'] = $this->fetch_douban_people_str('表演者:','</span>',$info_div_str);
			}

			foreach ($info_grep_keys as $grep) {
				unset($matches);
				preg_match_all( $grep['pattern'], $info_div_str, $matches);
				if (is_array($matches) && is_array($matches[0]) && count($matches[0])>=1) {
					$fetch[$grep['item']] = $this->items_implode($matches[0]);
				}
				if (isset($grep['sanitize_callback']) && is_callable($grep['sanitize_callback'])){
					$fetch[$grep['item']] = call_user_func($grep['sanitize_callback'], $fetch[$grep['item']]);
				}
			}
		}//preg_matches
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
		//$fetch['pubdate'] = $this->trim_year_month($fetch['pubdate']);
		if (isset($fetch['imdbid']) && '' != $fetch['imdbid']) {
			$fetch = $this->get_from_omdb($fetch['imdbid'], $fetch);
		}
		if (strpos($fetch['pic'], 'type=R')>0) {
			$fetch['pic'] = $this->get_detail_douban_pic($fetch['pic']);
		}
		if (strpos(mb_convert_encoding(trim($fetch['country']),'utf-8'), mb_convert_encoding("大陆",'utf-8'))===0 ||
			strpos(mb_convert_encoding(trim($fetch['country']),'utf-8'), mb_convert_encoding("香港",'utf-8'))===0 || 
			strpos(mb_convert_encoding(trim($fetch['country']),'utf-8'), mb_convert_encoding("台湾",'utf-8'))===0) {
			$fetch['original_name'] = '';
		}
		return $fetch;
	}//parse_douban_body

	/**
	 * @brief	解析豆瓣页面内容。
	 * @private
	 * @param	string	$body	页面html内容
	 * @return array
	 * @since 0.4.1
	 */
	private function parse_douban_game_body($body) {
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
							$fetch['akas'] = str_replace("/", ",", $temp);
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
	 * @private
	 * @param	string	$pic_mass	页面html内容
	 * @return array
	 * @since 0.0.1
	*/	
	private function get_detail_douban_pic($pic_mass){
		sleep(12);
		//防止被豆瓣当成恶意IP
		$response = @wp_remote_get( 
			htmlspecialchars_decode($pic_mass), 
			array( 'timeout'  => 10000, ) 
		);
		if ( is_wp_error( $response ) || !is_array($response) ) {
			return $pic_mass;
		}
		$body = wp_remote_retrieve_body($response);
		preg_match('/<div class="cover"[\s\S]+?<\/div>/', $body, $matches);
		if (is_array($matches)) {
			preg_match('/(?<=src=").*?(?=")/',$matches[0],$match_imgs);
			if (is_array($match_imgs)) {
				return trim($match_imgs[0]);
			}
		}
		return $pic_mass;
	}

	/**
	 * @brief	修改日期格式。
	 * @private
	 * @param	string	$pic_mass	页面html内容
	 * @return string
	 * @since 0.0.1
	*/
	private function trim_year_month($y) {
		if (strpos($y, "-")<=3) {
			return $y;
		}
		$parts = explode("-", $y);
		return $parts[0]."-".$parts[1];
	}
	
	/**
	 * @brief	修改地区格式。
	 * @private
	 * @param	string	$pic_mass	页面html内容
	 * @return string
	 * @since 0.0.1
	*/
	private function trim_contry_title($c){
		return str_replace(array("中国","/"),array("",","),$c);
	}

	/**
	 * @brief	从omdb获取。
	 * @private
	 * @param	string		$pic_mass	页面html内容
	 * @param   bool|array	$input		要合并的内容
	 * @return string
	 * @since 0.0.1
	*/
	private function get_from_omdb($id, $input=false){
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
		if ('' == $output['director']) $output['director'] = $this->translate_directors($content['Director']);
		if ('' == $output['actor']) $output['actor'] = $this->translate_actors($content['Actors']);
		if ('' == $output['genre']) $output['genre'] = $this->translate_m_genres($content['Genre']);
		if ('' == $output['country']) $output['country'] = $this->translate_contries($content['Country']);
		if ('' == $output['pubdate']) $output['pubdate'] = $this->trim_year_month($content['Year']);
		if (strpos($output['country'],'中国') === 0||
			strpos($output['country'],'香港') === 0||
			strpos($output['country'],'台湾') === 0||
			strpos($output['country'],'china') === 0||
			strpos($output['country'],'hong kong')=== 0||
			strpos($output['country'],'taiwan') === 0
		){
			$output['original_name'] = '';
		}
		return $output;
	}
	
	/**
	 * @brief	从giantbomb获取。
	 * @public
	 * @param	string	$pic_mass	页面html内容
	 * @return string
	 * @since 0.4.1
	*/
	public function get_from_giantbomb($id, $input=0){
		$default = array(
			'pic' => '',
			'producer' => '',
			'publisher' => '',
			'platform' => '',
			'genre' => '',
			'pubdate' => '',
			'original_name' => '',
			'language' => '',
			'akas' => '',
		);
		if (!$input) {
			$output = $default;
		}else{
			$output = wp_parse_args($input, $default);
		}
		if (empty($id)) {
			return $output;
		}
		$api_key = BDDB_Settings::get_giantbomb_key();
		if(empty($api_key)) {
			return $output;
		}
		
		$chk_lang = false;
		$chk_plat = false;
		
		//3 GB
		switch ( $input['platform'] ) {
			case 'FC':
				$chk_plat = 21;
				break;
			case 'GB':
				$chk_plat = 3;
				break;
			case 'GBC':
				$chk_plat = 57;
				break;
			case 'GBA':
				$chk_plat = 4;
				break;
			case 'DS':
				$chk_plat = 52;
				break;
			case '3DS':
				$chk_plat = 117;
				break;
			case 'GG':
				$chk_plat = 5;
				break;
			case 'MD':
				$chk_plat = 6;
				break;
			case 'SFC':
				$chk_plat = 9;
				break;
			case 'PS':
				$chk_plat = 22;
				break;
			case 'SS':
				$chk_plat = 42;
				break;
			case 'PC':
				$chk_plat = 94;
				break;
			case 'ARC':
				$chk_plat = 84;
				break;
			case 'NS':
				$chk_plat = 157;
				break;
			default:
			break;
		}
		
		//1 US
		//2 UK
		//6 JPN
		switch ( $input['language'] ) {
			case '美版':
				$chk_lang = 1;
				break;
			case '欧版':
				$chk_lang = 2;
				break;
			case '日版':
				$chk_lang = 6;
				break;
			default:
			break;
		}
		
		$url = "https://www.giantbomb.com/api/game/".$id."/?api_key=".$api_key."&format=json&field_list=genres,image,platforms,original_release_date,name,publishers,aliases,developers,releases";
		$curl = curl_init($url);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_USERAGENT, 'API Test UA');
		curl_setopt($curl, CURLOPT_TIMEOUT, 180);
		curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 60);
		curl_setopt ($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
		$response = curl_exec($curl);
		curl_close($curl);

		if (is_wp_error($response))
		{
			return $output;
		}

		$content = json_decode($response,true);
		$results = $content['results'];
		$arr_temp = array();
		if (key_exists('image', $results) && key_exists('original_url', $results['image'])) {
			if ('' == $output['pic']) $output['pic'] = $results['image']['original_url'];
		}
		
		if ('' == $output['publisher']) {
			$output['publisher'] = bddb_array_child_value_to_str($results,'developers').','.bddb_array_child_value_to_str($results,'publishers');
		}
		if (key_exists('name', $results) ) {
			if ('' == $output['original_name']) $output['original_name'] = $results['name'];
		}
		if (key_exists('genres', $results) ) {
			//if ('' == $output['genre']) $output['genre'] = bddb_array_child_value_to_str($results,'genres');
		}
		if (key_exists('original_release_date', $results) ) {
			if ('' == $output['pubdate']) $output['pubdate'] = $this->trim_year_month($results['original_release_date']);
		}
		if (key_exists('aliases', $results) ) {
			//if ('' == $output['akas']) $output['akas'] = $results['aliases'];
		}
		if (key_exists('releases', $results) ) {
			$rb = false;
			foreach ($results['releases'] as $rel_base) {
				$url = sprintf('%1$s?api_key=%2$s&format=json&field_list=image,platform,region,release_date,site_detail_url', $rel_base['api_detail_url'], $api_key);
				$rn = $this->get_from_giantbomb_release($url);
				if (key_exists('region', $rn) && 
					is_array($rn['region']) &&
					key_exists('id', $rn['region']) &&
					$rn['region']['id'] == $chk_lang && 
					key_exists('platform', $rn) && 
					is_array($rn['platform']) &&
					key_exists('id', $rn['platform']) && 
					$rn['platform']['id'] == $chk_plat) {
					$rb = $rn;
					break;
				}elseif(false===$rb) {
					$rb = $rn;
				}
			}
			if (false !== $rb) {
				if (!empty($rb['release_date'])) $output['pubdate'] = $rb['release_date'];
				if (!empty($rb['image'])) $output['pic'] = $rb['image']['original_url'];
				//$output['image']
			}
		}
		return $output;
	}
	
	protected function get_from_giantbomb_release($release_url) {
		$results = array();
		$curl = curl_init($release_url);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_USERAGENT, 'API Test UA');
		curl_setopt($curl, CURLOPT_TIMEOUT, 180);
		curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 60);
		curl_setopt ($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
		$response = curl_exec($curl);
		curl_close($curl);
		$content = json_decode($response,true);
		$results = $content['results'];
		return $results;
	}
	
	/**
	 * @brief	字符串替换。
	 * @private
	 * @param	string	$pic_mass	页面html内容
	 * @return string
	 * @since 0.2.1
	*/
	protected function my_space_replace($in_str) {
		$in_str = str_replace(" ","-",trim($in_str));
		return $in_str;
	}

	/**
	 * @brief	根据tax的内容获取文字。
	 * @private
	 * @param	string	$tax			分类法slug
	 * @param	string	$imaged_slugs	取得的内容想象成slug
	 * @return	string
	 * @since	0.0.1
	 * @version	0.3.3
	*/
	private function tax_slugs_to_names($tax, $imaged_slugs){
		$ret = strtolower($imaged_slugs);
		$srcs = TrimArray(explode(',', $imaged_slugs));
		$old = $srcs;
		$os = array_map(array($this, 'my_space_replace'), $srcs);
		//需要先手动设置好slug
		if ('region' == $tax) {
			switch ($this->type) {
				case 'movie':
				default:
				$tax = 'm_region';
				break;
				case 'book':
				$tax = 'b_region';
				break;
				case 'album':
				$tax = 'a_region';
				break;
			}
		}
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
	private function translate_directors($in_str){
		return $this->tax_slugs_to_names('m_p_director', $in_str);
	}
	private function translate_actors($in_str){
		return $this->tax_slugs_to_names('m_p_actor', $in_str);
	}
	/**
	 * @brief	转换地区。
	 * @private
	 * @param	string	$in_str	转换前内容（英）
	 * @return string
	 * @since 0.0.1
	*/
	private function translate_contries($in_str){
		return $this->tax_slugs_to_names('region', $in_str);
	}

	/**
	 * @brief	转换类型。
	 * @private
	 * @param	string	$in_str	转换前内容（英）
	 * @return string
	 * @since 0.0.1
	*/
	private function translate_m_genres($in_str){
		return $this->tax_slugs_to_names('m_genre', $in_str);
	}

	/**
	 * @brief	内容转字符串。
	 * @private
	 * @param	array	$items	要排列的内容
	 * @return string
	 * @since 0.0.1
	*/
	private function items_implode($items) {
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
		$items = array_map('trim_fetched_item', $items);
		if (1 == $count) {
			return $items[0];
		} else {
			return implode(",",$items);
		}
	}//items_implode

	/**
	 * @brief	获取豆瓣人名信息。
	 * @private
	 * @param	string	$from_str	开始的字符串(不包含)
	 * @param	string	$to_str	截止的字符串(不包含)
	 * @param	string	$base_str	数据来源
	 * @return string
	 * @since 0.0.1
	*/
	private function fetch_douban_people_str ($from_str, $to_str, $base_str) {
		$pos_start = strpos($base_str, $from_str);
		$pos_end = strpos($base_str, $to_str, $pos_start);
		if ( $pos_start>0 && $pos_end > $pos_start) {
			$people_str = substr($base_str, $pos_start, ($pos_end - $pos_start) + strlen($to_str) );
			preg_match_all( '/(?<=>).*?(?=<\/a>)/', $people_str, $matches);
			if (is_array($matches) && count($matches)>0 && is_array($matches[0]) ) {
				return $this->items_implode($matches[0]);
			}
		}
		return "";
	}//fetch_douban_people_str
}//class

