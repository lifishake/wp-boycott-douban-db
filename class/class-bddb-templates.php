<?php

/**
 * 内容显示用类
 */
class BDDB_Common_Template {
	//成员列表
	protected $common_items;		/*四种档案都包括的共通项目*/
	protected $settings;			/*保留*/
	protected $total_items;			/*每个档案的所有项目,初始为空,留待子类填充后再一起使用*/
	protected $self_post_type;			/*档案自身的种类*/
	protected $self_post_id;
	protected $default_item;
	
	/**
	 * 构造函数。
	 * @access protected
	 * @param	array	$settings	reserved
	 * @since 0.0.1
	 */
	public function __construct($post_type, $post_id=0){
		if ('auto'===$post_type && 0 != $post_id) {
			$po = get_post($post_id);
			$this->self_post_type = $po->post_type;
			$this->self_post_id = $po->ID;
		} elseif (!in_array($post_type, array('movie', 'book', 'game', 'album'))) {
			return;
		} else {
			$this->self_post_type = $post_type;
			$this->self_post_id = 0;
		}
		$this->default_item = array(
			'name' => '',
			'label' => '',
			'type' => 'meta',
			'priority' => false,
			'sort' => 'ASC',
			'ctype' => '',
			'summary' => false,
			'summary_callback' => false,
			'panel' => false,
			'panel_callback' => false,
		);
		$this->common_items = array(
			'bddb_display_name' => array(	'name' => 'bddb_display_name',
											'label' => '表示名',
											'priority' => '10',
											),
			'bddb_original_name' => array(	'name' => 'bddb_original_name',
											'label' => '原名',
											'summary' => '01',
											'summary_callback' =>array($this, 'display_original_name'),
											'panel' => '01',
											'panel_callback' => array($this, 'panel_original_name'),
											),
			'bddb_personal_review' => array(	'name' => 'bddb_personal_review',
											'label' => '简评',
											),
			'bddb_external_link' => array(	'name' => 'bddb_external_link',
											'label' => '外部链接',
											),
			'bddb_poster_link'	=> array(	'name' => 'bddb_poster_link',
											'label' => '图片链接',
											),
			'bddb_publish_time' => array(	'name' => 'bddb_publish_time',
											'label' => '出版时间',
											'priority' => '03',
											'sort' => 'DESC',
											'summary' => '21',
											),
			'bddb_view_time' => array(		'name' => 'bddb_view_time',
											'label' => '邂逅年月',
											'priority' => '02',
											'sort' => 'ASC',
											),
			'bddb_personal_rating' => array( 'name' => 'bddb_personal_rating',
											'label' => '评分',
											'priority' => '01',
											'ctype' => 'numeric',
											'sort' => 'DESC',
											),
			'country' => array(				'name' => 'country',
											'label' => '地区',
											'type' => 'tax',
											'summary' => '11',
											),
		);
		
		if (is_callable(array($this, "add_{$this->self_post_type}_items"))){
			call_user_func(array($this, "add_{$this->self_post_type}_items")); 
		}
		$this->total_items = array_map(array($this, 'merge_default_column'), $this->total_items);
	}
	
	/**
	 * 创建编辑盒子。
	 * @access public
	 * @since 0.0.1
	 */
	public function get_order_str() {
	}
	

	public function get_content() {
		$post = get_post();
		$content_str = '';
		$obj_name = bddb_get_poster_names($post->post_type, $post->ID);
		$content_str .= '<div class="poster"><img src="'.$obj_name->poster_url.'"/></div>';
		$content_str .= sprintf('<div class = "abstract">ID:%s</div>',$post->ID);
		foreach ($this->total_items as $key=>$item ) {
			$val_str = '';
			if ('meta' == $item['type']){
				$val_str = $this->get_disp_meta_str($item, $post->ID);
			} elseif('tax' == $item['type']){
				$val_str = $this->get_disp_tax_str($item, $post->ID);
			}
			if ('' != $val_str){
				$content_str .= '<div class = "abstract">'.$val_str.'</div>';
			}
		}
		return $content_str;
	}
	public function the_gallery() {
		//meta_quary 'key' compare  EXISTS compare
		$galleryargs = array(
			'post_type' => $this->self_post_type,
			'numberposts' => -1,
			'post_status' => 'publish',
		);
		echo "<div class='bddb-gallery-wall' id='bddb-gallery-{$this->self_post_type}'>";
		$order_args = $this->get_order_args();
		$galleryargs['meta_query'] = $order_args['meta_query'];
		$galleryargs['orderby'] = $order_args['orderby'];
		$all_posts = get_posts($galleryargs);
		foreach ($all_posts as $pt) {
			echo "<div class='bddb-poster-thumb' id='bddb-poster-{$pt->ID}'>";
			echo $this->get_poster_for_gallery($pt->ID);
			echo "</div>";
		}
		echo "</div>";
	}
	
	public function get_summary(){
		//print_r(debug_backtrace());
		if (0==$this->self_post_id || !$this->total_items){
			return "";
		}

		$post = get_post($this->self_post_id);
		$obj_name = bddb_get_poster_names($this->self_post_type, $this->self_post_id);
		$src_is_series = $this->get_meta_str('b_bl_series',$this->self_post_id);
		$src_title = $this->get_meta_str('bddb_display_name',$this->self_post_id);
		$src_link = $this->get_meta_str('bddb_external_link',$this->self_post_id);
		$src_score = intval($this->get_meta_str('bddb_personal_rating',$this->self_post_id));
		$src_dou_score = ($this->get_meta_str('bddb_score_douban',$this->self_post_id));
		$src_imdb_score = ($this->get_meta_str('m_score_imdb',$this->self_post_id));
		$src_score_social = '0';
		$src_score_social_float = 0.0;
		
		switch($this->self_post_type){
			case 'movie':{
				if ($src_dou_score){
					$src_score_social_float = floatval($src_dou_score);
				}elseif($src_imdb_score){
					$src_score_social_float = floatval($src_imdb_score);
				}
				break;
			};
			case 'book':{
				if ($src_dou_score){
					$src_score_social_float = floatval($src_dou_score);
				}
				break;
			}
			case 'game':
			case 'album':
			default:{
				if ($src_dou_score){
					$src_score_social_float = floatval($src_dou_score);
				}
				break;
			}
		}
		//3.标题
		$title_str=sprintf('<a href="%1$s" class="cute" target="_blank" rel="external nofollow">%2$s</a>', $src_link, $src_title);//3
		array_multisort( array_column($this->total_items,'summary'), array_column($this->total_items,'name'), $this->total_items);

		$src_score_social = strval(round($src_score_social_float));
		if (empty($src_is_series)){
			$template = '<div class="apip-item"><div class="mod"><div class="%1$s"><div class="apiplist-post">%2$s</div><div class="title">%3$s</div><div class="rating">%4$s</div><div class="abstract">%5$s</div></div></div></div>';
			//1.悬挂体风格
			$subject_class="v-overflowHidden doulist-subject";//1
			//2.缩略图
			$img_str=sprintf('<img src="%1$s" alt="%2$s"></img>', $obj_name->thumb_url, base64_encode($obj_name->short_name));//2
			
			//4.评分
			if ( $src_score<0 || $src_score>100 ){
				$my_score = '0';
			}else{
				$my_score = strval((int)($src_score/10));
				if ($src_score >= 0) $subject_class .= " my-score-".$my_score;
				
			}
			$rating_str=sprintf('<span class="allstardark">%1$s%2$s</span>',
								sprintf('<span class="dou-stars-%s"></span>', $src_score_social),
								sprintf('<span class="my-stars-%s"></span>', $my_score)
								);
			//5.详情
			$abstract_str = '';

			if (is_callable(array($this, "get_{$this->self_post_type}_abstract"))){
				$abstract_str = call_user_func(array($this, "get_{$this->self_post_type}_abstract"), $this->self_post_id);
			}
			return sprintf($template, $subject_class, $img_str, $title_str, $rating_str, $abstract_str);
		}else{
			$template = '<div class="apip-item"><div class="mod"><div class="v-overflowHidden doulist-subject"><div class="title">%1$s</div>%2$s</div></div></div>';
			if (is_callable(array($this, "get_{$this->self_post_type}_abstract_series"))){
				$abstract_str = call_user_func(array($this, "get_{$this->self_post_type}_abstract_series"), $this->self_post_id);
			}
			return sprintf($template, $title_str, $abstract_str);
		}
	}
	
	/**
	 * 为项目添加默认值。
	 * @access private
	 * @since 0.0.1
	 */
	public function merge_default_column($inItem) {
		if (!is_array($inItem)){
			return $this->default_item;
		}
		return array_merge($this->default_item, $inItem);
	}
	
	private function add_movie_items() {
		$this->common_items['bddb_display_name']['label'] = '电影名';
		$this->common_items['bddb_publish_time']['panel'] = '02';
		$this->common_items['bddb_publish_time']['label'] = '首映时间';
		$this->common_items['bddb_view_time']['label'] = '观看时间';
		$this->common_items['bddb_view_time']['panel'] = '03';
		$this->common_items['country']['label'] = '地区';
		$this->common_items['country']['panel'] = '05';
		$this->common_items['bddb_publish_time']['summary_callback'] = array($this, 'display_movie_publish_time');
		$add_items = array(
			'm_p_director' => array(	'name' => 'm_p_director',
											'label' => '导演',
											'type' => 'tax',
											'summary' => '02',
											'panel' => '11',
											),
			'm_p_actor' => array(	'name' => 'm_p_actor',
											'label' => '主演',
											'type' => 'tax',
											'summary' => '03',
											'panel' => '12',
											),
			'm_genre' => array(	'name' => 'm_genre',
											'label' => '类型',
											'type' => 'tax',
											'summary' => '04',
											'panel' => '04',
											),
			'm_publisher' => array(	'name' => 'm_publisher',
											'label' => '制作/发行方',
											),
			'm_p_screenwriter' => array(	'name' => 'm_p_screenwriter',
											'label' => '编剧',
											'type' => 'tax',
											'panel' => '13',
											),
			'm_p_musician' => array(	'name' => 'm_p_musician',
											'label' => '配乐',
											'type' => 'tax',
											'panel' => '14',
											),
			'm_misc_brand' => array(	'name' => 'm_misc_brand',
											'label' => 'TOBEDELETE',
											'type' => 'tax',
											'summary' => '61',
											'summary_callback' => array($this, 'display_movie_misc'),
											'panel' => '99',
											'panel_callback' => array($this, 'panel_movie_misc'),
											),
			'bddb_id_douban' => array(	'name' => 'bddb_id_douban',
											'label' => '豆瓣ID',
											),
			'bddb_score_douban' => array(	'name' => 'bddb_score_douban',
											'label' => '豆瓣评分',
											),
			'm_id_imdb' => array(	'name' => 'm_id_imdb',
											'label' => 'IMDB编号',
											),
			'm_score_imdb' => array(	'name' => 'm_score_imdb',
											'label' => 'IMDB评分',
											),
		);
		$this->total_items = array_merge($this->common_items, $add_items);
	}
	
	private function add_book_items() {
		$this->common_items['bddb_display_name']['label'] = '书名';
		$this->common_items['bddb_publish_time']['label'] = '出版时间';
		$this->common_items['bddb_publish_time']['summary_callback'] = array($this, 'display_book_publish_time');
		$this->common_items['bddb_publish_time']['panel'] = '02';
		$this->common_items['bddb_publish_time']['panel_callback'] = array($this, 'panel_book_publish_time');
		$this->common_items['bddb_view_time']['label'] = '阅读时间';
		$this->common_items['bddb_view_time']['panel'] = '03';
		$this->common_items['country']['summary'] = false;
		$this->common_items['country']['panel'] = '05';
		$add_items = array(
			'b_p_writer' => array(	'name' => 'b_p_writer',
											'label' => '作者',
											'type' => 'tax',
											'summary' => '02',
											'panel' => '11',
											),
			'b_p_translator' => array(	'name' => 'b_p_translator',
											'label' => '译者',
											'type' => 'tax',
											'summary' => '03',
											'panel' => '12',
											),
			'b_p_editor' => array(	'name' => 'b_p_editor',
											'label' => '编者',
											'type' => 'tax',
											'panel' => '13',
											),
			'b_publisher' => array(	'name' => 'b_publisher',
											'label' => '出版社',
											'type' => 'tax',
											'summary' => '04',
											'panel' => '20',
											),
			'b_genre' => array(	'name' => 'b_genre',
											'label' => '类别',
											'type' => 'tax',
											'panel' => '04',
											),
			'b_series_total' => array(	'name' => 'b_series_total',
											'label' => '全套册数',
											'summary' => '31',
											'summary_callback' => array($this, 'display_book_series_total'),
											'panel' => 02,
											'panel_callback' => array($this, 'panel_book_series_total'),
											),
			'bddb_id_douban' => array(	'name' => 'bddb_id_douban',
											'label' => '豆瓣ID',
											),
			'bddb_score_douban' => array(	'name' => 'bddb_score_douban',
											'label' => '豆瓣评分',
											),
			'b_misc_brand' => array(	'name' => 'b_misc_brand',
											'label' => 'TOBEDELETE',
											'type' => 'tax',
											'summary' => '61',
											'summary_callback' => array($this, 'display_book_misc'),
											'panel' => '99',
											'panel_callback' => array($this, 'panel_book_misc'),
											),
			'b_bl_series' => array(	'name' => 'b_bl_series',
											'label' => '是否丛书',
											),
			'b_pub_time_end' => array(	'name' => 'b_pub_time_end',
											'label' => '最终出版时间',
											),
			'b_series_covers' => array(	'name' => 'b_series_covers',
											'label' => '系列封面',
											),
		);
		$this->total_items = array_merge($this->common_items, $add_items);
	}
	private function add_game_items() {
		$this->common_items['bddb_display_name']['label'] = '游戏名';
		$this->common_items['bddb_publish_time']['label'] = '首发年月';
		$this->common_items['bddb_view_time']['label'] = '接触年月';
		$add_items = array(
			'g_genre'		=>		array(	'name' => 'g_genre',
											'label' => '类别',
											'type' => 'tax',
											'summary' => '06',
											),
			'g_platform'	=>		array(	'name' => 'g_platform',
											'label' => '机种',
											'type' => 'tax',
											'summary' => '04',
											),
			'g_publisher'	=>		array(	'name' => 'g_publisher',
											'label' => '制作方',
											'type' => 'tax',
											'summary' => '05',
											),
			'g_p_producer'	=>		array(	'name' => 'g_p_producer',
											'label' => '制作人',
											'type' => 'tax',
											),
			'g_p_musician'	=>		array(	'name' => 'g_p_musician',
											'label' => '作曲家',
											'type' => 'tax',
											),
			'g_cost_time'	=>		array(	'name' => 'g_cost_time',
											'label' => '耗时',
											'priority' => '09',
											'sort' => 'DESC',
											'ctype' => 'numeric',
											),
			'g_score_ign'	=>		array(	'name' => 'g_score_ign',
											'label' => 'IGN评分',
											),
		);
		$this->total_items = array_merge($this->common_items, $add_items);
	}
	private function add_album_items() {
		$this->common_items['bddb_display_name']['label'] = '专辑名';
		$this->common_items['bddb_publish_time']['label'] = '发行年月';
		$this->common_items['bddb_view_time']['label'] = '欣赏年月';
		$add_items = array(
			'a_genre'		=>		array(	'name' => 'a_genre',
											'label' => '风格',
											'type' => 'tax',
											'summary' => '08',
											),
			'a_p_musician'	=>		array(	'name' => 'a_p_musician',
											'label' => '音乐家',
											'sort' => 'ASC',
											'type' => 'tax',
											'summary' => '02',
											),
			'a_p_producer'	=>		array(	'name' => 'a_p_producer',
											'label' => '制作人',
											'type' => 'tax',
											),
			'a_quantities'	=>		array(	'name' => 'a_quantities',
											'label' => '专辑规格',
											'type' => 'tax',
											),
			'a_publisher'	=>		array(	'name' => 'a_publisher',
											'label' => '厂牌',
											'type' => 'tax',
											),
			'a_bl_multicreator'	=>	array(	'name' => 'a_bl_multicreator',
											'label' => '多人创作',
											),
			'bddb_id_douban'		=>	array(	'name' => 'bddb_id_douban',
											'label' => '豆瓣ID',
											),
			'bddb_score_douban'	=>	array(	'name' => 'bddb_score_douban',
											'label' => '豆瓣评分',
											),
		);
		$this->total_items = array_merge($this->common_items, $add_items);
	}
	
	private function get_tax_str($tax_name, $id) {
		$val_str = '';
		$str_array = wp_get_post_terms($id, $tax_name, array('fields'=>'names'));
		if (is_wp_error($str_array))
			return '';
		if (count($str_array)>1) {
			$val_str = implode(', ', $str_array);
		} elseif(count($str_array) == 1) {
			$val_str =trim($str_array[0]);
		}
		return $val_str;
	}

	private function get_meta_str($meta_name, $id) {
		$val_str = get_post_meta($id, $meta_name, true);
		return $val_str;
	}

	private function get_order_args() {
		$ret = array();
		array_multisort( array_column($this->total_items,'priority'), array_column($this->total_items,'name'), $this->total_items);
		foreach ($this->total_items as $key=>$item ) {
			if (!$item['priority']) {
				continue;
			}
			if ('meta' == $item['type']) {
				$current_meta = array();
				$current_meta['key'] = $key;
				$current_meta['compare'] = 'EXIST';
				if (isset($item['ctype'])) {
					$current_meta['type'] = $item['ctype'];
				}
				$ret['meta_query'][$key] = $current_meta;
				$ret['orderby'][$key] = $item['sort'];
			}
		}
		return $ret;
	}
	public function display_movie_publish_time($id, $item) {
		$val = $this->get_meta_str($item['name'], $id);
		if (!$val) return false;
		return sprintf('<span class="abs-list">%s：%s</span>', $item['label'], substr($val,0,4));
	}
	public function movie_misc_special($id, $item) {
		$feature='';
		$str_array = wp_get_post_terms($id, $item['name'], array('fields'=>'id=>slug'));
		if (is_wp_error($str_array)) return '';
		foreach($str_array as $key => $slug) {
			$img = BDDB_PLUGIN_URL.'img/'.$slug.'.png';
			switch($slug) {
				case 'sanji':
					$img = BDDB_PLUGIN_URL.'img/restricted.png';
					/*go through*/
				case 'cat':
				case 'dou250':
				case '404':
				case 'restricted':
				case 'imdb250':
					$feature.=sprintf('<img class="m-misc-brand" src="%s" alt="%s"/>', $img, $slug);
				break;
				default:
				break;
			}
		}
		return $feature;
	}
	private function original_name_special($id, $item) {
		$val = $this->get_meta_str($item['name'], $id);
		$name_str = get_post_meta($id, 'bddb_display_name', true);
		if (empty($val)||empty($name_str)) {
			return false;
		}
		if (trim($val) == trim($name_str)){
			return false;
		}
		return $val;
	}
	private function book_series_total_special($id, $item) {
		$val = $this->get_meta_str($item['name'], $id);
		$is_series = $this->get_meta_str('b_bl_series', $id);
		if (empty($is_series)||empty($val)){
			return false;
		}
		return $val;
	}
	private function book_publish_time_special($id, $item) {
		$val = $this->get_meta_str($item['name'], $id);
		$is_series = $this->get_meta_str('b_bl_series',$id);
		if (empty($val)) return false;
		if(!empty($is_series)){
			$val2 = $this->get_meta_str('b_pub_time_end', $id);
			if (empty($val2)){
				$val = substr($val, 0, 4);	
			}elseif($val2==$val) {
				$val = substr($val, 0, 4);	
			}else {
				$val = $val .' / '.$val2;
			}
		}else{
			$val = substr($val, 0, 4);
		}
		return $val;
	}
	public function book_misc_special($id, $item) {
		$feature='';
		$str_array = wp_get_post_terms($id, $item['name'], array('fields'=>'id=>slug'));
		if (is_wp_error($str_array)) return '';
		foreach($str_array as $key => $slug) {
			$img = BDDB_PLUGIN_URL.'img/'.$slug.'.png';
			switch($slug) {
				case 'cat':
				case '404':
				case 'doved':
					$feature.=sprintf('<img class="m-misc-brand" src="%s" alt="%s"/>', $img, $slug);
				break;
				default:
				break;
			}
		}
		return $feature;
	}
	protected function display_movie_misc($id, $item) {
		$val = $this->movie_misc_special($id, $item);
		if (empty($val)) {
			return false;
		}
		return sprintf('<span class="abs-list">%s</span>', $val);
	}

	protected function display_original_name($id, $item) {
		$val = $this->original_name_special($id, $item);
		if (empty($val)) {
			return false;
		}
		return sprintf('<span class="abs-list">%s：%s</span>', $item['label'], $val);
	}

	protected function display_book_misc($id, $item) {
		$val = $this->book_misc_special($id, $item);
		if (empty($val)) {
			return false;
		}
		return sprintf('<span class="abs-list">%s</span>', $val);
	}

	protected function display_book_publish_time($id, $item) {
		$val = $this->book_publish_time_special($id, $item);
		if (empty($val)) {
			return false;
		}
		return sprintf('<span class="abs-list">%s：%s</span>', $item['label'], $val);
	}
	protected function display_book_series_total($id, $item) {
		$val = $this->book_series_total_special($id, $item);
		if (empty($val)) {
			return false;
		}
		return sprintf('<span class="abs-list">%s：%s</span>', $item['label'], $val);
	}

	protected function panel_movie_misc($id, $item) {
		$val = $this->movie_misc_special($id, $item);
		if (empty($val)) {
			return false;
		}
		return sprintf('<p class="bddb-disp-item align-left">%s</p>', $val);
	}

	protected function panel_book_publish_time($id, $item) {
		$val = $this->book_publish_time_special($id, $item);
		if (empty($val)) {
			return false;
		}
		return sprintf('<p class="bddb-disp-item"><span class="bddb-disp-label">%s:</span>%s</p>', $item['label'], $val);
	}

	protected function panel_book_series_total($id, $item) {
		$val = $this->book_series_total_special($id, $item);
		if (empty($val)) {
			return false;
		}
		return sprintf('<p class="bddb-disp-item"><span class="bddb-disp-label">%s:</span>%s</p>', $item['label'], $val);
	}

	protected function panel_original_name($id, $item) {
		$val = $this->original_name_special($id, $item);
		if (empty($val)) {
			return false;
		}
		return sprintf('<p class="bddb-disp-item"><span class="bddb-disp-label">%s:</span>%s</p>', $item['label'], $val);
	}

	protected function panel_book_misc($id, $item) {
		$val = $this->book_misc_special($id, $item);
		if (empty($val)) {
			return false;
		}
		return sprintf('<p class="bddb-disp-item align-left">%s</p>', $val);
	}

	private function get_poster_for_gallery($id) {
		$obj_name =bddb_get_poster_names($this->self_post_type, $id);

		$detail_str = '';
		array_multisort( array_column($this->total_items,'panel'), array_column($this->total_items,'name'), $this->total_items);
		if (is_callable(array($this,"get_{$this->self_post_type}_info"))) {
			$info_str .= call_user_func(array($this,"get_{$this->self_post_type}_info"), $id);
		}
		$alt = base64_encode($obj_name->short_name);
		if(file_exists($obj_name->poster_name)) {
			$poster_url = $obj_name->poster_url;
		}else{
			$poster_url = $obj_name->nopic_poster_url;
		}
		if(file_exists($obj_name->thumb_name)) {
			$thumb_url = $obj_name->thumb_url;
		}else{
			$thumb_url = $obj_name->nopic_thumb_url;
		}
		$is_lazy = wp_script_is("apip-js-lazyload");
		if (!$is_lazy) {
			$ret = "<a href='{$poster_url}' data-fancybox='gallery' data-info='{$info_str}' ><img src='{$thumb_url}' alt='{$alt}' /></a>";
		}else{
			$ret = "<a href='{$poster_url}' data-fancybox='gallery' data-info='{$info_str}' ><img data-src='{$thumb_url}' src='data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7' data-unveil='true' alt='{$alt}' /></a>";
		}
		return $ret;
	
	}

	private function get_rating_stars($id) {
		$stars='';
		$emp_stars='';
		$i=1;
		for(;$i<=intval($this->get_meta_str('bddb_personal_rating', $id)/10);++$i){
			$stars.='&#xf005;';
		}
		for(;$i<=10;++$i){
			$emp_stars.='&#xf006;';
		}
		if (''!==$stars){
			$stars = '<span class="bddb-score-stars">'.$stars.'</span>';
		}
		if (''!==$emp_stars){
			$emp_stars = '<span class="bddb-score-no-stars">'.$emp_stars.'</span>';
		}
		$star_str = '<span class="bddb-gallery-scores">'.$stars.$emp_stars.'</span>';
		return $star_str;
	}
	private function get_panel_title($id) {
		$name_dsp = $this->get_meta_str('bddb_display_name', $id);
		$link_str = $this->get_meta_str('bddb_external_link', $id);
		return sprintf('<a class="bddb-disp-name" target="_blank" href="%1$s">%2$s</a>', $link_str, $name_dsp);
	}
	private function get_movie_info($id) {
		//title
		$detail_str = $this->get_panel_title($id);
		//stars
		$star_str = $this->get_rating_stars($id);
		$the_score = $this->get_meta_str('bddb_score_douban', $id);
		if (''==$the_score)$the_score='--';
		$dou_score_str = '<span class="bddb-disp-sp-dou-score">'.$the_score.'</span>';
		$the_score = $this->get_meta_str('m_score_imdb', $id);
		if (''==$the_score)$the_score='--';
		$imdb_score_str = '<span class="bddb-disp-sp-imdb-score">'.$the_score.'</span>';
		$detail_str .= sprintf('<p class="bddb-disp-item bddb-inline">%s%s%s</p>', $star_str, $dou_score_str, $imdb_score_str);
		
		
		$detail_str .= $this->panel_common_loop($id);

		$detail_str .= '<div class="bddb-disp-review" id="bddb-gallery-review">'.$this->get_meta_str('bddb_personal_review', $id).'</div>';
		return $detail_str;
	}

	private function get_book_info($id) {
		//title
		$detail_str = $this->get_panel_title($id);
		//stars
		$star_str = $this->get_rating_stars($id);
		$is_series = $this->get_meta_str('b_bl_series', $id);
		if (empty($is_series)) {
			$the_score = $this->get_meta_str('bddb_score_douban', $id);
			if (''==$the_score)$the_score='--';
			$dou_score_str = '<span class="bddb-disp-sp-dou-score">'.$the_score.'</span>';
			$detail_str .= sprintf('<p class="bddb-disp-item bddb-inline">%s%s</p>', $star_str, $dou_score_str);
		} else {
			$detail_str .= sprintf('<p class="bddb-disp-item bddb-inline">%s</p>', $star_str);//只有自主评分
		}
		$detail_str .= $this->panel_common_loop($id);
		$detail_str .= '<div class="bddb-disp-review" id="bddb-gallery-review">'.$this->get_meta_str('bddb_personal_review', $id).'</div>';
		return $detail_str;
	}
	private function get_game_info($id) {
		return '';
	}
	private function get_album_info($id) {
		return '';
	}
	private function panel_common_loop($id) {
		$panel_str = '';
		foreach ($this->total_items as $key=>$item ) {
			if (!$item['panel']) continue;
			$row_str='';
			if (isset($item['panel_callback']) && is_callable($item['panel_callback'])) {
				$val_str = call_user_func($item['panel_callback'], $id, $item);
				if (empty($val_str)){
					continue;
				}
				$row_str = $val_str;
			} else {
				$val_str = false;
				if ('meta' == $item['type']){
					$val_str = $this->get_meta_str($item['name'], $id);
				} elseif('tax' == $item['type']){
					$val_str = $this->get_tax_str($item['name'], $id);
				}
				if (empty($val_str)){
					continue;
				}
				$row_str = sprintf('<p class="bddb-disp-item"><span class="bddb-disp-label">%s:</span>%s</p>', $item['label'], $val_str);
			}
			$panel_str .= $row_str;
		}
		return $panel_str;
	}
	private function get_movie_abstract($id) {
		return $this->abstract_common_loop($id);
	}
	private function get_book_abstract($id) {
		return $this->abstract_common_loop($id);
	}
	private function get_game_abstract($id) {
		return '';
	}
	private function get_album_abstract($id) {
		return '';
	}
	private function get_book_abstract_series($id) {
		$template = '<div class="abstract-left">%s</div>%s';
		$abs_str = $this->abstract_common_loop($id);
		$images = '';
		$s = new BDDB_Settings();
		$count = $s->get_max_serial_count();
		$obj_names = bddb_get_poster_names($this->self_post_type, $id);
		for($i = 0; $i<$count; ++$i){
			$short_name = sprintf('%s_%013d_%02d.jpg', $this->self_post_type, $id, $i);
			$thumbnail_full_name = $obj_names->thumb_dir.$short_name;
			if (!file_exists($thumbnail_full_name)){
				continue;
			}
			$url = $obj_names->thumb_url_front.$short_name;
			$images.=sprintf('<div class="apiplist-post"><img src="%1$s" alt="%2$s" ></img></div>', $url, base64_encode($short_name));
		}
		return sprintf($template, $abs_str, $images);
	}
	private function abstract_common_loop($id){
		$abs_str = '';
		foreach ($this->total_items as $key=>$item ) {
			if (!$item['summary']) continue;
			$tmp_str='';
			if (isset($item['summary_callback']) && is_callable($item['summary_callback'])) {
				$val_str = call_user_func($item['summary_callback'], $id, $item);
				if (!$val_str) continue;
				$tmp_str = $val_str;
			} else {
				$val_str = false;
				if ('meta' == $item['type']){
					$val_str = $this->get_meta_str($item['name'], $id);
				} elseif('tax' == $item['type']){
					$val_str = $this->get_tax_str($item['name'], $id);
				}
				if (empty($val_str)){
					continue;
				}
				$tmp_str = sprintf('<span class="abs-list">%s：%s</span>', $item['label'], str_replace(",", " / ", $val_str));
			}
			$abs_str .= $tmp_str;
		}
		return $abs_str;
	}
};
//movie/brand