<?php
/**
后台编辑用类
*/

/**
 * 编辑用模板类，不可直接创建对象
 */
class BDDB_Common_Template {
	//成员列表
	protected $common_items;		/*四种档案都包括的共通项目*/
	protected $settings;			/*保留*/
	protected $total_items;			/*每个档案的所有项目,初始为空,留待子类填充后再一起使用*/
	protected $box_title;			/*编辑盒子的标题,初始为空,待子类覆盖*/
	protected $self_post_type;			/*档案自身的种类*/
	protected $self_post_id;
	
	/**
	 * 构造函数。
	 * @access protected
	 * @param	array	$settings	reserved
	 * @since 0.0.1
	 */
	public function __construct($post_type, $post_id=0){
		if ('auto'===$post_type && 0 != $post_id) {
			$post = get_post($post_id);
			$this->self_post_type = $post->post_type;
			$this->self_post_id = $post->ID;
		} elseif (!in_array($post_type, array('movie', 'book', 'game', 'album'))) {
			return;
		} else {
			$this->self_post_type = $post_type;
			$this->self_post_id = 0;
		}
		$this->common_items = array(
			'bddb_display_name' => array(	'name' => 'bddb_display_name',
											'label' => '表示名',
											'type' => 'meta',
											'priority' => '10',
											'sort' => 'ASC',
											'display' => false,
											),
			'bddb_original_name' => array(	'name' => 'bddb_original_name',
											'label' => '原名',
											'type' => 'meta',
											'priority' => false,
											'sort' => 'DESC',
											'display' => false,
											),
			'bddb_personal_review' => array(	'name' => 'bddb_personal_review',
											'label' => '简评',
											'type' => 'meta',
											'priority' => false,
											'sort' => 'DESC',
											'display' => false,
											),
			'bddb_external_link' => array(	'name' => 'bddb_external_link',
											'label' => '外部链接',
											'type' => 'meta',
											'priority' => false,
											'sort' => 'DESC',
											'display' => false,
											),
			'bddb_poster_link'	=> array(	'name' => 'bddb_poster_link',
											'label' => '图片链接',
											'type' => 'meta',
											'priority' => false,
											'sort' => 'DESC',
											'display' => false,
											),
			'bddb_publish_time' => array(	'name' => 'bddb_publish_time',
											'label' => '出版时间',
											'type' => 'meta',
											'priority' => '03',
											'sort' => 'DESC',
											'display' => '21',
											),
			'bddb_view_time' => array(		'name' => 'bddb_view_time',
											'label' => '邂逅年月',
											'type' => 'meta',
											'priority' => '02',
											'sort' => 'ASC',
											'display' => false,
											),
			'bddb_personal_rating' => array( 'name' => 'bddb_personal_rating',
											'label' => '评分',
											'type' => 'meta',
											'priority' => '01',
											'ctype' => 'numeric',
											'sort' => 'DESC',
											'display' => false,
											),
			'country' => array(				'name' => 'country',
											'label' => '地区',
											'type' => 'tax',
											'priority' => false,
											'sort' => 'DESC',
											'display' => '11',
											),
		);
		if (is_callable(array($this, "add_{$this->self_post_type}_items"))){
			call_user_func(array($this, "add_{$this->self_post_type}_items")); 
		}
		
	}
	
	/**
	 * 创建编辑盒子。
	 * @access public
	 * @since 0.0.1
	 */
	public function get_order_str() {
		add_meta_box('bddbcommondiv', $this->box_title, array($this, 'show_meta_box'), $this->self_post_type, 'normal', 'core');
	}
	

	public function get_content() {
		array_multisort( array_column($this->total_items,'display'), array_column($this->total_items,'name'), $this->total_items);
		$post = get_post();
		$obj_name = $this->get_poster_names($post);
		$content_str = '<div class="bddb-item"><div class="mod"><div class="bddblist-subject">';
		$content_str .= '<div class="poster"><img src="'.$obj_name->poster_url.'"/></div>';
		foreach ($this->total_items as $key=>$item ) {
			$val_str = '';
			if (isset($item['display_callback']) && is_callable($item['display_callback'])) {
				$val_str = call_user_func($item['display_callback'], $post->ID);
			} else {
				if ('meta' == $item['type']){
					$val_str = $this->get_disp_meta_str($item, $post->ID);
				} elseif('tax' == $item['type']){
					$val_str = $this->get_disp_tax_str($item, $post->ID);
				}
			}
			if ('' != $val_str){
				$content_str .= '<div class = "abstract">'.$val_str.'</div>';
			}
		}
		$content_str .= '</div></div></div>';// class="bddb-item"
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
			echo $this->get_poster_for_gallery($pt);
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
		$obj_name = $this->get_poster_names($post);
		$src_is_series = $this->get_meta_str('b_bl_series',$this->self_post_id);
		$src_title = $this->get_meta_str('bddb_display_name',$this->self_post_id);
		$src_link = $this->get_meta_str('bddb_external_link',$this->self_post_id);
		$src_score = intval($this->get_meta_str('bddb_personal_rating',$this->self_post_id));
		$src_dou_score = ($this->get_meta_str('bddb_score_douban',$this->self_post_id));
		$src_imdb_score = ($this->get_meta_str('m_score_imdb',$this->self_post_id));
		$src_score_social = '0';
		$src_score_social_float = 0.0;
		
		switch($post->post_type){
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
			array_multisort( array_column($this->total_items,'display'), array_column($this->total_items,'name'), $this->total_items);

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
	
		private function add_movie_items() {
		$this->total_items = $this->common_items;
		$this->common_items['bddb_display_name']['label'] = '电影名';
		$this->common_items['bddb_publish_time']['label'] = '首映年月';
		$this->common_items['bddb_view_time']['label'] = '观看年月';
		$this->common_items['bddb_publish_time']['display_callback'] = array($this, 'display_movie_publish_time');
		$add_items = array(
			'm_p_director' => array(	'name' => 'm_p_director',
											'label' => '导演',
											'type' => 'tax',
											'priority' => false,
											'sort' => 'ASC',
											'display' => '02',
											),
			'm_p_actor' => array(	'name' => 'm_p_actor',
											'label' => '主演',
											'type' => 'tax',
											'priority' => false,
											'sort' => 'ASC',
											'display' => '03',
											),
			'm_genre' => array(	'name' => 'm_genre',
											'label' => '类型',
											'type' => 'tax',
											'priority' => false,
											'sort' => 'ASC',
											'display' => '04',
											),
			'm_publisher' => array(	'name' => 'm_publisher',
											'label' => '制作/发行方',
											'type' => 'meta',
											'priority' => false,
											'sort' => 'ASC',
											'display' => false,
											),
			'm_p_screenwriter' => array(	'name' => 'm_p_screenwriter',
											'label' => '编剧',
											'type' => 'tax',
											'priority' => false,
											'sort' => 'ASC',
											'display' => false,
											),
			'm_p_musician' => array(	'name' => 'm_p_musician',
											'label' => '配乐',
											'type' => 'tax',
											'priority' => false,
											'sort' => 'ASC',
											'display' => false,
											),
			'm_misc_brand' => array(	'name' => 'm_misc_brand',
											'label' => 'TOBEDELETE',
											'type' => 'tax',
											'priority' => false,
											'sort' => 'ASC',
											'display' => '61',
											'display_callback' => array($this, 'display_movie_misc_brand'),
											),
			'bddb_id_douban' => array(	'name' => 'bddb_id_douban',
											'label' => '豆瓣ID',
											'type' => 'meta',
											'priority' => false,
											'sort' => 'DESC',
											'display' => false,
											),
			'bddb_score_douban' => array(	'name' => 'bddb_score_douban',
											'label' => '豆瓣评分',
											'type' => 'meta',
											'priority' => false,
											'sort' => 'DESC',
											'display' => false,
											),
			'm_id_imdb' => array(	'name' => 'm_id_imdb',
											'label' => 'IMDB编号',
											'type' => 'meta',
											'priority' => false,
											'sort' => 'DESC',
											'display' => false,
											),
			'm_score_imdb' => array(	'name' => 'm_score_imdb',
											'label' => 'IMDB评分',
											'type' => 'meta',
											'priority' => false,
											'sort' => 'DESC',
											'display' => false,
											),
		);
		$this->total_items = array_merge($this->common_items, $add_items);
	}
	
	private function add_book_items() {
		$this->total_items = $this->common_items;
		$this->common_items['bddb_display_name']['label'] = '书名';
		$this->common_items['bddb_publish_time']['label'] = '出版年月';
		$this->common_items['bddb_publish_time']['display_callback'] = array($this, 'display_book_publish_time');
		$this->common_items['bddb_view_time']['label'] = '阅读年月';
		$this->common_items['country']['display'] = false;
		$add_items = array(
			'b_p_writer' => array(	'name' => 'b_p_writer',
											'label' => '作者',
											'type' => 'tax',
											'priority' => false,
											'sort' => 'ASC',
											'display' => '02',
											),
			'b_p_translator' => array(	'name' => 'b_p_translator',
											'label' => '译者',
											'type' => 'tax',
											'priority' => false,
											'sort' => 'ASC',
											'display' => '03',
											),
			'b_p_editor' => array(	'name' => 'b_p_editor',
											'label' => '编者',
											'type' => 'tax',
											'priority' => false,
											'sort' => 'ASC',
											'display' => false,
											),
			'b_genre' => array(	'name' => 'b_genre',
											'label' => '类别',
											'type' => 'tax',
											'priority' => false,
											'sort' => 'ASC',
											'display' => false,
											),
			'b_publisher' => array(	'name' => 'b_publisher',
											'label' => '出版社',
											'type' => 'tax',
											'priority' => false,
											'sort' => 'DESC',
											'display' => '05',
											),
			'b_series_total' => array(	'name' => 'b_series_total',
											'label' => '全套册数',
											'type' => 'meta',
											'priority' => false,
											'sort' => 'DESC',
											'display' => '31',
											'display_callback' =>array($this, 'display_book_series_total'),
											),
			'bddb_id_douban' => array(	'name' => 'bddb_id_douban',
											'label' => '豆瓣ID',
											'type' => 'meta',
											'priority' => false,
											'sort' => 'DESC',
											'display' => false,
											),
			'bddb_score_douban' => array(	'name' => 'bddb_score_douban',
											'label' => '豆瓣评分',
											'type' => 'meta',
											'priority' => false,
											'sort' => 'DESC',
											'display' => false,
											),
			'b_bl_series' => array(	'name' => 'b_bl_series',
											'label' => '是否丛书',
											'type' => 'meta',
											'priority' => false,
											'sort' => 'DESC',
											'display' => false,
											),
			'b_pub_time_end' => array(	'name' => 'b_pub_time_end',
											'label' => '最终出版时间',
											'type' => 'meta',
											'priority' => false,
											'sort' => 'DESC',
											'display' => false,
											),
			'b_series_covers' => array(	'name' => 'b_series_covers',
											'label' => '系列封面',
											'type' => 'meta',
											'priority' => false,
											'sort' => 'DESC',
											'display' => false,
											),

		);
		$this->total_items = array_merge($this->common_items, $add_items);
	}
	private function add_game_items() {
	}
	private function add_album_items() {
	}
	public function get_poster_names($post) {
		$ret = array();
		$name = sprintf("%s_%013d.jpg", $post->post_type, $post->ID);
		//$b = bloginfo
		$rel_url = str_replace(home_url(), '', BDDB_GALLERY_URL);
		if (bddb_is_debug_mode()){
			$rel_url = str_replace('http://localhost', '', BDDB_GALLERY_URL);
		}
		$ret['short_name'] = $name;
		$ret['poster_name'] = BDDB_GALLERY_DIR .$name;
		$ret['thumb_name'] = BDDB_GALLERY_DIR.'thumbnails/'.$name;
		$ret['poster_url'] = $rel_url .$name;
		$ret['thumb_url'] = $rel_url.'thumbnails/'.$name;
		//$ret['scover_name_template'] = BDDB_GALLERY_DIR.'thumbnails/'.$post->post_type . sprintf('_%013d',$post->ID).'_%02d.jpg';
		//$ret['scover_url_template'] = BDDB_GALLERY_URL.'thumbnails/'.$post->post_type . sprintf('_%013d',$post->ID).'_%02d.jpg';
		$ret['scover_name_template'] = str_replace('.jpg','',$ret['poster_name']) . "_\%02d.jpg";
		return (object)$ret;
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
	private function get_disp_tax_str($item, $id) {
		$val_str = $this->get_tax_str($item['name'], $id);
		if ('' !== $val_str) {
			return "<p calss='bddb-item-content'><span calss='bddb-item-name'>{$item['label']}:</span>{$val_str}</p>";
		}else {
			return '';
		}
	}
	private function get_meta_str($meta_name, $id) {
		$val_str = get_post_meta($id, $meta_name, true);
		return $val_str;
	}
	private function get_disp_meta_str($item, $id) {
		$val_str = $this->get_meta_str($item['name'], $id);
		if ('' !== $val_str) {
			return "<p calss='bddb-item-content'><span calss='bddb-item-name'>{$item['label']}:</span>{$val_str}</p>";
		}else {
			return '';
		}
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
	public function display_movie_publish_time($id) {
		$val = $this->get_meta_str('bddb_publish_time', $id);
		if (!$val) return false;
		return sprintf('<span class="abs-list">%s：%s</span>', '时间', substr($val,0,4));
	}
	public function display_movie_misc_brand($id) {
		$feature='';
		$str_array = wp_get_post_terms($id, 'm_misc_brand', array('fields'=>'id=>slug'));
		if (is_wp_error($str_array)) return '';
		foreach($str_array as $key => $slug) {
			$img = BDDB_PLUGIN_URL.'img/'.$slug.'.png';
			switch($slug) {
				case 'cat':
					$feature.=sprintf('<img class="m-misc-brand" src="%s" alt="%s"/>', $img, $slug);
				break;
				case 'dou250':
					$feature.=sprintf('<img class="m-misc-brand" src="%s" alt="%s"/>', $img, $slug);
				break;
				case '404':
					$feature.=sprintf('<img class="m-misc-brand" src="%s" alt="%s"/>', $img, $slug);
				break;
				case 'sanji':
					$img = BDDB_PLUGIN_URL.'img/restricted.png';
				case 'restricted':
					$feature.=sprintf('<img class="m-misc-brand" src="%s" alt="%s"/>', $img, $slug);
				break;
				case 'imdb250':
					$feature.=sprintf('<img class="m-misc-brand" src="%s" alt="%s"/>', $img, $slug);
				break;
				default:
				break;
			}
		}
		return $feature;
	}
	public function display_book_publish_time($id) {
		$val = $this->get_meta_str('bddb_publish_time', $id);
		$is_series = $this->get_meta_str('b_bl_series',$this->self_post_id);
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
		return sprintf('<span class="abs-list">%s：%s</span>', '出版时间', $val);
	}
	public function display_book_series_total($id) {
		$val = $this->get_meta_str('b_series_total', $id);
		$is_series = $this->get_meta_str('b_bl_series',$this->self_post_id);
		if (empty($is_series)||empty($val)){
			return false;
		}
		return sprintf('<span class="abs-list">%s：%s</span>', '全套册数', $val);
	}

	private function get_poster_for_gallery($post, $class='bddb-poster-thumb') {
		$obj_name = $this->get_poster_names($post);
		$link_str = $this->get_meta_str('bddb_external_link', $post->ID);
		$name_dsp = $this->get_meta_str('bddb_display_name', $post->ID);
		$detail_str = '';
		if (is_callable(array($this,"get_{$post->post_type}_info"))) {
			$info_str .= call_user_func(array($this,"get_{$post->post_type}_info"), $post->ID);
		}
		return "<a href='{$obj_name->poster_url}' target='{$name_dsp}' rel='{$link_str}' data-fancybox='gallery' data-caption='{$name_dsp}' data-info='{$info_str}' ><img src='{$obj_name->thumb_url}' alt='{$obj_name->short_name}' /></a>";
	
	}
	private function get_display_name_link_str($post, $class='bddb-name-link') {
		$name_str = get_post_meta($post->ID, 'bddb_display_name', true);
		$link_str = get_post_meta($post->ID, 'bddb_external_link', true);
		if ('' == $link_str) {
			$link_str = '#';
		}
		return "<a target='_blank' href='{$link_str}' class='{$class}' >{$name_str}</a>";
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

	private function get_movie_info($id) {
		$star_str = $this->get_rating_stars($id);
		$the_score = $this->get_meta_str('bddb_score_douban', $id);
		if (''==$the_score)$the_score='--';
		$dou_score_str = '<span class="bddb-disp-sp-dou-score">'.$the_score.'</span>';
		$the_score = $this->get_meta_str('m_score_imdb', $id);
		if (''==$the_score)$the_score='--';
		$imdb_score_str = '<span class="bddb-disp-sp-imdb-score">'.$the_score.'</span>';
		$detail_str .= sprintf('<p class="bddb-disp-item bddb-inline">%s%s%s</p>', $star_str, $dou_score_str, $imdb_score_str);
		$publish_str = '<span class="bddb-disp-label">上映时间:</span>'.$this->get_meta_str('bddb_publish_time', $id);
		$detail_str .= sprintf('<p class="bddb-disp-item">%s</p>', $publish_str);
		$view_time_str = '<span class="bddb-disp-label">观影时间:</span>'.$this->get_meta_str('bddb_view_time', $id);
		$detail_str .= sprintf('<p class="bddb-disp-item">%s</p>', $view_time_str);
		$genre_str = '<span class="bddb-disp-label">类型:</span>'.$this->get_tax_str('m_genre', $id);
		$detail_str .= sprintf('<p class="bddb-disp-item">%s</p>', $genre_str);
		$country_str = '<span class="bddb-disp-label">地区:</span>'.$this->get_tax_str('country', $id);
		$detail_str .= sprintf('<p class="bddb-disp-item">%s</p>', $country_str);
		$staff_str = '';
		$tmp_str = $this->get_tax_str('m_p_director', $id);
		if ('' !== $tmp_str) {
			$staff_str = '<span class="bddb-disp-label">导演:</span>'.$tmp_str;
			$detail_str .= sprintf('<p class="bddb-disp-item">%s</p>', $staff_str);
		}
		$tmp_str = $this->get_tax_str('m_p_actor', $id);
		if ('' !== $tmp_str) {
			$staff_str = '<span class="bddb-disp-label">主演:</span>'.$tmp_str;
			$detail_str .= sprintf('<p class="bddb-disp-item">%s</p>', $staff_str);
		}
		$tmp_str = $this->get_tax_str('m_p_screenwriter', $id);
		if ('' !== $tmp_str) {
			$staff_str = '<span class="bddb-disp-label">编剧:</span>'.$tmp_str;
			$detail_str .= sprintf('<p class="bddb-disp-item">%s</p>', $staff_str);
		}
		$tmp_str = $this->get_tax_str('m_p_musician', $id);
		if ('' !== $tmp_str) {
			$staff_str = '<span class="bddb-disp-label">配乐:</span>'.$tmp_str;
			$detail_str .= sprintf('<p class="bddb-disp-item">%s</p>', $staff_str);
		}
		$tmp_str = $this->display_movie_misc_brand($id);
		if ('' !== $tmp_str) {
			$detail_str .= sprintf('<p class="bddb-disp-item align-left">%s</p>', $tmp_str);
		}
		$detail_str .= '<div class="bddb-disp-review" id="bddb-gallery-review">'.$this->get_meta_str('bddb_personal_review', $id).'</div>';
		return $detail_str;
	}
	private function get_book_info($id) {
		$star_str = $this->get_rating_stars($id);
		$is_series = $this->get_meta_str('b_bl_series', $id);
		$dou_score_str = '';
		$detail_str = '';
		if ('1'==$is_series){
			$detail_str .= sprintf('<p class="bddb-disp-item bddb-inline">%s</p>', $star_str);//只有自主评分
			$publish_str = '<span class="bddb-disp-label">出版时间:</span>'.$this->get_meta_str('bddb_publish_time', $id).' / '.$this->get_meta_str('b_pub_time_end', $id);
			$detail_str .= sprintf('<p class="bddb-disp-item">%s</p>', $publish_str);
			$serial_total_str = '<span class="bddb-disp-label">全套册数:</span>'.$this->get_meta_str('b_series_total', $id);
			$detail_str .= sprintf('<p class="bddb-disp-item">%s</p>', $serial_total_str);
			$view_time_str = '<span class="bddb-disp-label">阅读时间:</span>'.$this->get_meta_str('bddb_view_time', $id);
			$detail_str .= sprintf('<p class="bddb-disp-item">%s</p>', $view_time_str);
			$genre_str = '<span class="bddb-disp-label">类别:</span>'.$this->get_tax_str('b_genre', $id);
			$detail_str .= sprintf('<p class="bddb-disp-item">%s</p>', $genre_str);
			$country_str = '<span class="bddb-disp-label">创作地:</span>'.$this->get_tax_str('country', $id);
			$detail_str .= sprintf('<p class="bddb-disp-item">%s</p>', $country_str);
		}else{	//非丛书
			$the_score = $this->get_meta_str('bddb_score_douban', $id);
			if (''==$the_score)$the_score='--';
			$dou_score_str = '<span class="bddb-disp-sp-dou-score">'.$the_score.'</span>';
			$detail_str .= sprintf('<p class="bddb-disp-item bddb-inline">%s%s</p>', $star_str, $dou_score_str);
			$publish_str = '<span class="bddb-disp-label">出版时间:</span>'.$this->get_meta_str('bddb_publish_time', $id);
			$detail_str .= sprintf('<p class="bddb-disp-item">%s</p>', $publish_str);
			$view_time_str = '<span class="bddb-disp-label">阅读时间:</span>'.$this->get_meta_str('bddb_view_time', $id);
			$detail_str .= sprintf('<p class="bddb-disp-item">%s</p>', $view_time_str);
			$genre_str = '<span class="bddb-disp-label">类别:</span>'.$this->get_tax_str('b_genre', $id);
			$detail_str .= sprintf('<p class="bddb-disp-item">%s</p>', $genre_str);
			$country_str = '<span class="bddb-disp-label">创作地:</span>'.$this->get_tax_str('country', $id);
			$detail_str .= sprintf('<p class="bddb-disp-item">%s</p>', $country_str);
		}
		$tmp_str = $this->get_tax_str('b_p_writer', $id);
		if ('' !== $tmp_str) {
			$staff_str = '<span class="bddb-disp-label">作者:</span>'.$tmp_str;
			$detail_str .= sprintf('<p class="bddb-disp-item">%s</p>', $staff_str);
		}
		$tmp_str = $this->get_tax_str('b_p_translator', $id);
		if ('' !== $tmp_str) {
			$staff_str = '<span class="bddb-disp-label">译者:</span>'.$tmp_str;
			$detail_str .= sprintf('<p class="bddb-disp-item">%s</p>', $staff_str);
		}
		$tmp_str = $this->get_tax_str('b_p_editor', $id);
		if ('' !== $tmp_str) {
			$staff_str = '<span class="bddb-disp-label">编者:</span>'.$tmp_str;
			$detail_str .= sprintf('<p class="bddb-disp-item">%s</p>', $staff_str);
		}
		$tmp_str = $this->get_tax_str('b_publisher', $id);
		if ('' !== $tmp_str) {
			$staff_str = '<span class="bddb-disp-label">出版社:</span>'.$tmp_str;
			$detail_str .= sprintf('<p class="bddb-disp-item">%s</p>', $staff_str);
		}

		$detail_str .= '<div class="bddb-disp-review" id="bddb-gallery-review">'.$this->get_meta_str('bddb_personal_review', $id).'</div>';
		return $detail_str;
	}
	private function get_game_info($id) {
		return '';
	}
	private function get_album_info($id) {
		return '';
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
		for($i = 0; $i<18; ++$i){
			$short_name = sprintf('book_%013d_%02d.jpg', $id, $i);
			$thumbnail_full_name = BDDB_GALLERY_DIR.'thumbnails/'.$short_name;
			if (!file_exists($thumbnail_full_name)){
				continue;
			}
			$url = BDDB_GALLERY_URL.'thumbnails/'.$short_name;
			$images.=sprintf('<div class="apiplist-post"><img src="%1$s" alt="%2$s" ></img></div>', $url, base64_encode($short_name));
		}
		return sprintf($template, $abs_str, $images);
	}
	private function abstract_common_loop($id){
		$abs_str = '';
		foreach ($this->total_items as $key=>$item ) {
			if (!$item['display']) continue;
			$tmp_str='';
			if (isset($item['display_callback']) && is_callable($item['display_callback'])) {
				$val_str = call_user_func($item['display_callback'], $id);
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