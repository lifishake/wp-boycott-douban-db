<?php
require_once( BDDB_PLUGIN_DIR . '/class/class-bddb-settings.php');
//http://localhost/chaos/wp-content/gallery/Sample/MrCharacter.jpg
function trim_fetched_item($value) {
    $value = trim($value);
    $value = str_replace(array("[", "]", "&nbsp;", ""),array("【", "】", "", "N/A"), $value);
    return $value;
}

if (!function_exists('TrimArray')) {
	function TrimArray($Input){
		if (!is_array($Input)) {
			$tmp = trim(strtolower($Input));
			return str_replace(' ', '-', $tmp);
		}
		return array_map('TrimArray', $Input);
	}
}

class BDDB_DoubanFetcher{
	protected	$type;
	public function __construct($in_type = ''){
		$this->type = $in_type;
	}//__construct
			
	public function fetch($url = '') {
		$ret = array('result'=>'ERROR','reason'=>'invalid parameter.');
		if ('' === $url || '' === $dou_id) {
			return $ret;
		}elseif ('' !== $url) {
			if (strpos($url, "movie.douban.com")) {
				$this->type = "movie";
			} elseif (strpos($url, "book.douban.com")) {
				$this->type = "book";
			} elseif (strpos($url, "music.douban.com")) {
				$this->type = "album";
			} elseif (strpos($url, "imdb.com")){
				$this->type = "movie";
				return $this->fetch_from_omdb($url);
			} else {
				return $ret;
			}
		}
		return $this->fetch_from_douban_page($url);
	}
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
		$ret['content'] = $this->parse_douban_body($body);
		$ret['content']['title'] = trim($title);
		$url = rtrim($url,"/");
		$ret['content']['dou_id'] = substr($url, strrpos($url, "/")+1);
		return $ret;
	}
		
		
	private function parse_douban_body($body) {
		$fetch = array();
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
					array('pattern'=>'/(?<="v:starring"\>).*?(?=\<)/', 'item'=>'actor'),
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
				$fetch['country']="";
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
			//$fetch['pubdate'] = $this->trim_year_month($fetch['pubdate']);
			if (isset($fetch['imdbid']) && '' != $fetch['imdbid']) {
				$fetch = $this->get_from_omdb($fetch['imdbid'], $fetch);
			}
			if (strpos($fetch['pic'], 'type=R')>0) {
				$fetch['pic'] = $this->get_detail_douban_pic($fetch['pic']);
			}
			return $fetch;
		}//parse_douban_body
		
		private function get_detail_douban_pic($pic_mass){
			sleep(15);
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

		private function trim_year_month($y) {
			if (strpos($y, "-")<=3) {
				return $y;
			}
			$parts = explode("-", $y);
			return $parts[0]."-".$parts[1];
		}
		
		private function trim_contry_title($c){
			return str_replace(array("中国","/"),array("",","),$c);
		}

		private function get_from_omdb($id, $input=0){
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
			$settings = new BDDB_Settings();
			$api_key = $settings->get_omdb_key();
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
			if ('' == $output['actor']) $output['actor'] = $this->translate_directors($content['Actors']);
			if ('' == $output['genre']) $output['genre'] = $this->translate_m_genres($content['Genre']);
			if ('' == $output['country']) $output['country'] = $this->translate_contries($content['Country']);
			if ('' == $output['pubdate']) $output['pubdate'] = $this->trim_year_month($content['Year']);
			return $output;
		}
		private function tax_slugs_to_names($tax, $slugs){
			$ret = strtolower($slugs);
			$srcs = TrimArray(explode(',', $slugs));

			$got_terms = get_terms(array(	'taxonomy'=>$tax,
											'hide_empty'=>false,
									 		'slug'=>$srcs));
			foreach ($got_terms as $term) {
				$ret = str_replace($term->slug, $term->name, $ret);
			}
			return $ret;
		}
		private function translate_directors($in_str){
			return $in_str;
		}
		private function translate_contries($in_str){
			return $this->tax_slugs_to_names('country', $in_str);
		}
		private function translate_m_genres($in_str){
			return $this->tax_slugs_to_names('m_genre', $in_str);
		}
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

