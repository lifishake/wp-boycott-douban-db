<?php

/**
 * @file	class-bddb-templates.php
 * @class	BDDB_Common_Template
 * @brief	内容显示用类，包括gallery显示和嵌入文章显示
 * @date	2024-09-04
 * @author	大致
 * @version	0.9.2
 * @since	0.0.1
 * 
 */
//global $bddb_mq;
class BDDB_Common_Template {
	//成员列表
	protected $common_items;		/*四种档案都包括的共通项目*/
	protected $total_items;			/*每个档案的所有项目,初始为空,留待子类填充后再一起使用*/
	protected $self_post_type;		/*档案自身的种类*/
	protected $default_item;		/*选项默认值*/
	protected $num_per_page;		/*每页显示数*/
	protected $total_pages;			/*页数*/
	protected $query;
	/**
	 * @brief	构造函数。
	 * @public
	 * @param	string	$post_type	影书游碟之一，或者不设置
	 * @since	0.0.1
	 * @version	0.0.1
	 */
	public function __construct($post_type = false){
		$this->default_item = array(
			'name' => '',
			'label' => '',		//显示在冒号左侧的名字
			'type' => 'meta',
			'priority' => false,	//排序优先级
			'sort' => 'ASC',		//升降
			'ctype' => '',			//特殊排序规则
			'summary' => false,		//插入显示。
			'summary_callback' => false,	//插入显示的特殊处理回调
			'panel' => false,		//上墙
			'panel_callback' => false,	//上墙回调
			'portrait_ok' => false,	//竖屏具体信息时显示
		);
		$this->common_items = array(
			'bddb_display_name' => array(	'name' => 'bddb_display_name',
											'label' => '表示名',
											'priority' => '10',
											'portrait_ok' => true,
											),
			'bddb_original_name' => array(	'name' => 'bddb_original_name',
											'label' => '原名',
											'summary' => '01',
											'summary_callback' =>array($this, 'display_original_name'),
											'panel' => '01',
											'panel_callback' => array($this, 'panel_original_name'),
											),
			'bddb_aka' 			 => array(	'name' => 'bddb_aka',
											'label' => '别名',
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
											'portrait_ok' => true,
											),
			'bddb_view_time' => array(		'name' => 'bddb_view_time',
											'label' => '邂逅年月',
											'priority' => '02',
											'sort' => 'ASC',
											'portrait_ok' => true,
											),
			'bddb_personal_rating' => array( 'name' => 'bddb_personal_rating',
											'label' => '评分',
											'priority' => '01',
											'ctype' => 'numeric',
											'sort' => 'DESC',
											),
		);
		if (!BDDB_Statics::is_valid_type($post_type)) {
			return;
		}
		$this->set_working_mode($post_type);
	}
	
	
	/********    外部函数 开始    ********/
	/**
	 * @brief	通过所有参数创建自身
	 * @public
	 * @see		bddb_the_gallery()
	 * @since	0.4.0
	 * @version	0.4.0
	 */
	public function create_from_full_args( $args ) {
		if(!is_array($args) ||
			!isset($args['common_items']) ||
			!isset($args['total_items']) ||
			!isset($args['self_post_type']) ||
			!isset($args['default_item']) ||
			!isset($args['num_per_page'])
		) {
			return;
		}
		$this->num_per_page = $args['num_per_page'];
		$this->self_post_type = $args['self_post_type'];
		$this->default_item = array_merge(array(), $args['default_item']);
		$this->total_items = array_merge(array(), $args['total_items']);
		$this->default_item = array_merge(array(), $args['common_items']);
	}
	
	/**
	 * @brief	显示照片墙，被主题调用。
	 * @public
	 * @see		bddb_the_gallery()
	 * @since	0.0.1
	 * @version	0.4.0
	 */
	public function the_gallery() {
		echo "<div class='bddb-gallery-wall' id='bddb-gallery-{$this->self_post_type}'>";
		echo '<div class="ring-loading">Loading</div>';
		$this->get_gallery_page(1);
		echo '</div>';
	}
	
	/**
	 * @brief	显示照片墙，ajax回调函数。
	 * @public
	 * @see		bddb_the_gallery()
	 * @since	0.3.7
	 * @version	0.3.9
	 */
	public function ajax_get_gallery_page() {
		if (!isset($_POST['nonce']) || !isset($_POST['pid']) || !isset($_POST['type']) || !isset($_POST['nobj']) ) {
			wp_die();
		}
		$page_id = $_POST['pid'];
		$type = $_POST['type'];
		if (!wp_verify_nonce($_POST['nonce'], 'bddb-gallery-wall-'.$type .$page_id)) {
			wp_die();
		}
		//$this->set_working_mode($type);
		echo "<div>";
		$this->get_gallery_page($page_id, $_POST['nobj']);
		echo "</div>";
		wp_die();
	}
	
	/**
	 * @brief	填充照片墙的某一页，被主题调用。
	 * @param	int		$page_id	要获取的页序号
	 * @param	int		$in_str		上下文字符串
	 * @public
	 * @see		bddb_the_gallery()
	 * @see		bddb_next_gallery_page()
	 * @since	0.3.6
	 * @version	0.3.9
	 */
	public function get_gallery_page($page_id, $in_str = "") {
		//meta_quary 'key' compare  EXISTS compare
		if (1==$page_id) {
			$galleryargs = array(
					'post_type' => $this->self_post_type,
					'posts_per_page' => $this->num_per_page,
					'post_status' => 'publish',
					'order' => 'DESC',
					'include' => array(),
					'exclude' => array(),
					'meta_key' => '',
					'meta_value' =>'',
					'suppress_filters' => true,	//不确定有用
					'ignore_sticky_posts' => true,
					'fields' => 'ids',			//只返回id
					'paged' => $page_id,
				);
			$order_args = $this->get_order_args();
			$galleryargs = array_merge($galleryargs, $order_args);
			$query = new WP_Query($galleryargs);
			$this->total_pages = $query->max_num_pages;
			$this->query = $query;
		}
		else {
			$query_vars = json_decode(stripslashes($in_str), true);
			$query_vars['paged'] = $page_id;
			$query = new WP_Query($query_vars);
		}
		
		$all_posts = $query->get_posts();
		$nonce = wp_create_nonce('bddb-gallery-wall-'.$this->self_post_type .($page_id+1));//用下一页的pid
		$jec = "";
		$jec = json_encode($query->query);

		foreach ($all_posts as $pt) {
			if ($pt == end($all_posts)) {
				if ($page_id == $query->max_num_pages) {
					echo "<div class='bddb-poster-thumb' id='bddb-poster-{$pt}' type='{$this->self_post_type}' pid='0' nonce='' nobj=''>";
				} else {
					echo "<div class='bddb-poster-thumb' id='bddb-poster-{$pt}' type='{$this->self_post_type}' pid='{$page_id}' nonce='{$nonce}' nobj='{$jec}'>";
				}
			} else {
				echo "<div class='bddb-poster-thumb' id='bddb-poster-{$pt}'>";
			}
			echo $this->get_poster_for_gallery($pt);
			echo "</div>";
		}

	}
	
	
	/**
	 * @brief	转换插入文章中的shortcode，被回调调用。
	 * @param	array	$atts	短代码属性，该函数中只包括一个$id。
	 * @public
	 * @since	0.1.4
	 * @version	0.8.0
	 * @see		add_shortcode()
	 */
	public function show_record($atts, $content = null) {
		extract( $atts );
		$obj_name = bddb_get_poster_names($this->self_post_type, $id);
		$src_is_series = $this->get_meta_str('b_bl_series',$id);
		$src_title = $this->get_meta_str('bddb_display_name',$id);
		$src_link = $this->get_meta_str('bddb_external_link',$id);
		$src_score = intval($this->get_meta_str('bddb_personal_rating',$id));
		$src_score_social = $this->get_summary_social_stars($id);

		//3.标题
		$title_str=sprintf('<a href="%1$s" class="cute" target="_blank" rel="external nofollow">%2$s</a>', $src_link, $src_title);//3
		array_multisort( array_column($this->total_items,'summary'), array_column($this->total_items,'name'), $this->total_items);

		if (empty($src_is_series)){
			$template = '<div class="bddb-item"><div class="mod"><div class="%1$s"><div class="apiplist-post">%2$s</div><div class="title">%3$s</div><div class="rating">%4$s</div><div class="abstract">%5$s</div></div></div></div>';
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
				$abstract_str = call_user_func(array($this, "get_{$this->self_post_type}_abstract"), $id);
			}
			return sprintf($template, $subject_class, $img_str, $title_str, $rating_str, $abstract_str);
		}else{ //系列
			$template = '<div class="bddb-item"><div class="mod"><div class="v-overflowHidden doulist-subject"><div class="title">%1$s</div>%2$s</div></div></div>';
			if (is_callable(array($this, "get_{$this->self_post_type}_abstract_series"))){
				$abstract_str = call_user_func(array($this, "get_{$this->self_post_type}_abstract_series"), $id);
			}
			return sprintf($template, $title_str, $abstract_str);
		}
	}
	/********    外部函数 结束    ********/

	/********    私有函数 开始    ********/
	/**
	 * @brief	读取term的值。
	 * @private
	 * @param	string	$tax_name	taxonomy名。
	 * @param	int		$id			postID。
	 * @return	string	要读取的taxonomy的值
	 * @since	0.0.1
	 * @version	0.0.1
	 */
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

	/**
	 * @brief	读取1条term的值。
	 * @param	string	$tax_name	taxonomy名。
	 * @param	int		$id			postID。
	 * @return	string	要读取的taxonomy的值
	 * @since	0.5.5
	 * @version	0.5.5
	 */
	private function get_first_tax_str($tax_name, $id) {
		$val_str = '';
		$str_array = wp_get_post_terms($id, $tax_name, array('fields'=>'names'));
		if (is_wp_error($str_array))
			return '';
		if (count($str_array)>=1) {
			$val_str =trim($str_array[0]);
		}
		return $val_str;
	}

	/**
	 * @brief	读取1条term的值。
	 * @param	string	$tax_name	taxonomy名。
	 * @param	int		$id			postID。
	 * @param	string	$va			超出限量显示的字符串。
	 * @param	int		$limit		超出n个字显示字符串。
	 * @return	string	要读取的taxonomy的值
	 * @since	0.9.1
	 * @version	0.9.1
	 */
	private function get_tax_str_va($tax_name, $id, $va, $limit) {
		$str_array = wp_get_post_terms($id, $tax_name, array('fields'=>'names'));
		if (count($str_array) > $limit) {
			return $va;
		}
		elseif(count($str_array) == 1) {
			$val_str =trim($str_array[0]);
		}
		else{
			$val_str = implode(', ', $str_array);
		}
		return $val_str;
	}
	/**
	 * @brief	读取meta的值。
	 * @private
	 * @param	string	$meta_name	meta名。
	 * @param	int		$id			postID。
	 * @return	string	要读取的meta的值
	 * @since	0.0.1
	 * @version	0.0.1
	 */
	private function get_meta_str($meta_name, $id) {
		$val_str = get_post_meta($id, $meta_name, true);
		return $val_str;
	}

	/**
	 * @brief	获取条目的class。竖屏项目增加.loly类
	 * @private
	 * @param	array	$item	    条目信息。
	 * @param	string	$base_class	基本类。
	 * @return  string	组合后的类
	 * @since	0.7.3
	 * @see		panel_common_loop()
	 * @see		panel_callback()
	 */
	private function get_item_class($item, $base_class="bddb-disp-item") {
		if (isset($item['portrait_ok']) && $item['portrait_ok']){
			return $base_class;
		}
		return $base_class.' loly';
	}

	/**
	 * @brief	设置模板的类型。
	 * @private
	 * @param	string	$post_type	要显示的bddb种类。
	 * @since	0.1.4
	 * @version	1.0.5
	 * @see		__construct()
	 * @see		show_record()
	 */
	private function set_working_mode($post_type) {
		if (!BDDB_Statics::is_valid_type($post_type)) {
			return;
		}
		$this->self_post_type = $post_type;
		if (is_callable(array($this, "add_{$this->self_post_type}_items"))){
			call_user_func(array($this, "add_{$this->self_post_type}_items")); 
		}
		$this->total_items = array_map(array($this, 'merge_default_column'), $this->total_items);
		$this->num_per_page = BDDB_Settings::getInstance()->get_thumbnails_per_page();
		$this->total_pages = 0;
		$this->query = false;
	}

	/**
	 * @brief	追加和修改movie类型的显示和排序。
	 * @private
	 * @since	0.0.1
	 * @version	0.4.0
	 * @see		set_working_mode()->add_{$this->self_post_type}_items
	 */
	private function add_movie_items() {
		$this->common_items['bddb_display_name']['label'] = '电影名';
		$this->common_items['bddb_original_name']['portrait_ok'] = true;
		$this->common_items['bddb_publish_time']['panel'] = '02';		//上墙显示。
		$this->common_items['bddb_publish_time']['label'] = '首映时间';
		$this->common_items['bddb_publish_time']['panel_callback'] = array($this, 'panel_time_only_year');
		$this->common_items['bddb_view_time']['label'] = '观看时间';
		$this->common_items['bddb_view_time']['panel'] = '03';			//上墙显示。
		$this->common_items['bddb_view_time']['panel_callback'] = array($this, 'panel_time_only_year');
		$this->common_items['bddb_publish_time']['summary_callback'] = array($this, 'display_movie_publish_time');
		$add_items = array(
			'm_region' => array(			'name' => 'm_region',
											'label' => '地区',
											'type' => 'tax',
											'summary' => '11',
											'panel' => '05',
											),
			'm_p_director' => array(	'name' => 'm_p_director',
											'label' => '导演',
											'type' => 'tax',
											'summary' => '02',			//插入显示。
											'panel' => '11',			//上墙显示。
											'portrait_ok' => true,
											),
			'm_p_actor' => array(	'name' => 'm_p_actor',
											'label' => '主演',
											'type' => 'tax',
											'summary' => '03',			//插入显示。
											'panel' => '12',			//上墙显示。
											'panel_callback' => array($this, 'panel_movie_people'),
											'portrait_ok' => true,
											),
			'm_genre' => array(	'name' => 'm_genre',
											'label' => '类型',
											'type' => 'tax',
											'summary' => '04',			//插入显示。
											'panel' => '04',			//上墙显示。
											'portrait_ok' => true,
											),
			'm_publisher' => array(	'name' => 'm_publisher',
											'label' => '制作/发行方',
											),
			'm_p_screenwriter' => array(	'name' => 'm_p_screenwriter',
											'label' => '编剧',
											'type' => 'tax',
											),
			'm_p_musician' => array(	'name' => 'm_p_musician',
											'label' => '配乐',
											'type' => 'tax',
											),
			'm_misc_brand' => array(	'name' => 'm_misc_brand',
											'label' => 'TOBEDELETE',
											'type' => 'tax',
											'summary' => '61',			//插入显示。
											'summary_callback' => array($this, 'display_movie_misc'),
											'panel' => '99',			//上墙显示。
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
			'm_length' => array(	'name' => 'm_length',
											'label' => '片长',
											),							
		);
		$this->total_items = array_merge($this->common_items, $add_items);
	}

	/**
	 * @brief	追加和修改book类型的显示和排序。
	 * @private
	 * @since	0.0.1
	 * @version	0.6.6
	 * @see		set_working_mode()->add_{$this->self_post_type}_items
	 */
	private function add_book_items() {
		$this->common_items['bddb_display_name']['label'] = '书名';
		$this->common_items['bddb_publish_time']['label'] = '出版时间';
		$this->common_items['bddb_publish_time']['summary_callback'] = array($this, 'display_book_publish_time');
		$this->common_items['bddb_publish_time']['panel'] = '02';		//上墙显示
		$this->common_items['bddb_publish_time']['panel_callback'] = array($this, 'panel_book_publish_time');
		$this->common_items['bddb_view_time']['label'] = '阅读时间';
		$this->common_items['bddb_view_time']['panel'] = '03';			//上墙显示
		$this->common_items['bddb_view_time']['sort'] = 'DESC';			//同分时改为降序。
		$this->common_items['bddb_view_time']['panel_callback'] = array($this, 'panel_time_only_year');
		$add_items = array(
			'b_region' => array(			'name' => 'b_region',
											'label' => '地区',
											'type' => 'tax',
											'summary' => false,
											'panel' => false,
											),
			'b_p_writer' => array(	'name' => 'b_p_writer',
											'label' => '作者',
											'type' => 'tax',
											'summary' => '02',
											'panel' => '01',			//上墙显示
											'portrait_ok' => true,
											),
			'b_p_translator' => array(	'name' => 'b_p_translator',
											'label' => '译者',
											'type' => 'tax',
											'summary' => '03',
											'panel' => '12',			//上墙显示
											'portrait_ok' => true,
											),
			'b_p_editor' => array(	'name' => 'b_p_editor',
											'label' => '编者',
											'type' => 'tax',
											'panel' => '13',			//上墙显示
											'portrait_ok' => true,
											),
			'b_publisher' => array(	'name' => 'b_publisher',
											'label' => '出版社',
											'type' => 'tax',
											'summary' => '05',
											'panel' => '20',			//上墙显示
											'portrait_ok' => true,
											),
			'b_genre' => array(	'name' => 'b_genre',
											'label' => '分类',
											'type' => 'tax',
											'panel' => '04',			//上墙显示
											'portrait_ok' => true,
											),
			'b_series_total' => array(	'name' => 'b_series_total',
											'label' => '全套册数',
											'summary' => '31',
											'summary_callback' => array($this, 'display_book_series_total'),
											'panel' => '05',
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
											'summary' => '99',
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

	/**
	 * @brief	追加和修改game类型的显示和排序。
	 * @private
	 * @since	0.0.1
	 * @version	0.0.1
	 * @see		set_working_mode()->add_{$this->self_post_type}_items
	 */
	private function add_game_items() {
		$this->common_items['bddb_display_name']['label'] = '游戏名';
		$this->common_items['bddb_original_name']['portrait_ok'] = false;
		$this->common_items['bddb_publish_time']['label'] = '发行年月';
		$this->common_items['bddb_publish_time']['panel'] = '11';
		$this->common_items['bddb_publish_time']['priority'] = '09';
		$this->common_items['bddb_view_time']['label'] = '接触年月';
		$this->common_items['bddb_view_time']['panel'] = '12';
		$this->common_items['bddb_view_time']['priority'] = '03';
		$this->common_items['bddb_aka']['panel'] = '02';
		$this->common_items['bddb_aka']['summary'] = '02';
		$this->common_items['bddb_aka']['portrait_ok'] = true;
		$add_items = array(
			'g_language' => array(			'name' => 'g_language',
											'label' => '语言版本',
											'type' => 'tax',
											'summary' => false,
											'panel' => false,
											),
			'g_genre'		=>		array(	'name' => 'g_genre',
											'label' => '类别',
											'type' => 'tax',
											'summary' => '06',
											'panel' => '04',
											),
			'g_platform'	=>		array(	'name' => 'g_platform',
											'label' => '机种',
											'type' => 'tax',
											'summary' => '04',
											'panel' => '03',
											'portrait_ok' => true,
											),
			'g_publisher'	=>		array(	'name' => 'g_publisher',
											'label' => '厂商',
											'type' => 'tax',
											'summary' => '05',
											'panel' => '05',
											'portrait_ok' => true,
											),
			'g_cost_time'	=>		array(	'name' => 'g_cost_time',
											'label' => '耗时',
											'priority' => '02',
											'sort' => 'DESC',
											'ctype' => 'numeric',
											'summary' => '26',
											'panel' => '13',
											),
			'g_misc_brand' => array(	'name' => 'g_misc_brand',
											'label' => 'TOBEDELETE',
											'type' => 'tax',
											'summary' => '99',
											'summary_callback' => array($this, 'display_game_misc'),
											'panel' => '99',
											'panel_callback' => array($this, 'panel_game_misc'),
											),

		);
		$this->total_items = array_merge($this->common_items, $add_items);
	}

	/**
	 * @brief	追加和修改album类型的显示和排序。
	 * @private
	 * @since	0.0.1
	 * @version	0.9.5
	 * @date	2024-09-03
	 * @see		set_working_mode()->add_{$this->self_post_type}_items
	 */
	private function add_album_items() {
		$this->common_items['bddb_display_name']['label'] = '专辑名';
		$this->common_items['bddb_publish_time']['label'] = '发行年月';
		$this->common_items['bddb_publish_time']['panel'] = '11';
		$this->common_items['bddb_publish_time']['priority'] = '07';
		$this->common_items['bddb_view_time']['label'] = '欣赏年月';
		$this->common_items['bddb_view_time']['priority'] = '02';
		$add_items = array(
			'a_region' => array(			'name' => 'a_region',
											'label' => '地区',
											'type' => 'tax',
											'summary' => '11',
											'panel'	=> '04',
											'portrait_ok' => true,
											'panel_callback' => array($this, 'panel_album_region'),
											),
			'a_language' => array(			'name' => 'a_language',
											'label' => '语言',
											'priority' => '03',
											'sort' => 'DESC',
											'summary' => false,
											'panel' => false,
											),								
			'a_genre'		=>		array(	'name' => 'a_genre',
											'label' => '风格',
											'type' => 'tax',
											'summary' => '08',
											'panel'	=> '02',
											'portrait_ok' => true,
											),
			'a_p_musician'	=>		array(	'name' => 'a_p_musician',
											'label' => '音乐家',
											'type' => 'tax',
											'summary' => '02',
											'summary_callback' => array($this, 'summary_album_musician'),
											'panel'	=> '01',
											'panel_callback' => array($this, 'panel_album_musician'),
											'portrait_ok' => true,
											),
			'a_p_asstants'	=>		array(	'name' => 'a_p_asstants',
											'label' => '协助音乐家',
											'type' => 'tax',
											'summary' => false,
											'panel'	=> false,
											),								
			'a_quantity'	=>		array(	'name' => 'a_quantity',
											'label' => '专辑规格',
											'type' => 'tax',
											'panel'	=> '10',
											'summary' => false,
											'panel' => true,
											'panel_callback' => array($this, 'panel_album_quantity'),
											'portrait_ok' => true,
											),
			'a_publisher'	=>		array(	'name' => 'a_publisher',
											'label' => '厂牌',
											'type' => 'tax',
											'summary' => false,
											'panel' => '05',
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

	/**
	 * @brief	为项目添加默认值。被set_working_mode中的array_map函数回调。
	 * @return array
	 * @protected
	 * @param	array	inItem	显示用的单个项目
	 * @since	0.0.1
	 * @version	0.0.1
	 * @see		set_working_mode()->array_map()
	 */
	protected function merge_default_column($inItem) {
		if (!is_array($inItem)){
			return $this->default_item;
		}
		return array_merge($this->default_item, $inItem);
	}

	/**
	 * 生成相册检索用的的排序参数。
	 * @return string
	 * @private
	 * @since	0.0.1
	 * @version	0.0.1
	 * @see		the_gallery()
	 */
	private function get_order_args() {
		$ret = array();
		//TODO:画面点击后AJAX变化
		array_multisort( array_column($this->total_items,'priority'), array_column($this->total_items,'name'), $this->total_items);
		foreach ($this->total_items as $key=>$item ) {
			if (!$item['priority']) {
				continue;
			}
			if ('meta' == $item['type']) {
				$current_meta = array();
				$current_meta['key'] = $key;
				$current_meta['compare'] = 'EXIST';
				if (isset($item['ctype'])&& 'list' != $item['ctype']) {
					$current_meta['type'] = $item['ctype'];
				}
				$ret['meta_query'][$key] = $current_meta;
				$ret['orderby'][$key] = $item['sort'];
			}
		}
		return $ret;
	}
	
	/**
	 * @brief	生成相册画面的图片以及上墙显示的信息。
	 * @param	int		$id			post_ID
	 * @return string
	 * @private
	 * @since	0.0.1
	 * @version	0.7.8
	 * @date 2023-03-13
	 * @see		the_gallery()
	 */
	private function get_poster_for_gallery($id) {
		$obj_name = bddb_get_poster_names($this->self_post_type, $id);

		$detail_str = '';
		array_multisort( array_column($this->total_items,'panel'), array_column($this->total_items,'name'), $this->total_items);
		//get_xxx_info
		$info_str = "";
		if (is_callable(array($this,"get_{$this->self_post_type}_panel_info"))) {
			$info_str .= call_user_func(array($this,"get_{$this->self_post_type}_panel_info"), $id);
		}
		if(file_exists($obj_name->poster_name)) {
			$poster_url = $obj_name->poster_url;
		}else{
			$poster_url = $obj_name->nopic_poster_url;
		}
		$thumb_url = $poster_url;
		$tooltip = $this->get_poster_tooltip($id);
		$addi_class = call_user_func(array($this, "get_{$this->self_post_type}_poster_class"), $id);
		//20230313 使用最后更新时间作为缩略图刷新标识
		//U是php中相对1970-01-01的秒数，足够了。
		$ts = "?ts=".strval(get_post_modified_time( "U", false, $id ));

		/*
		20230214暂时注掉，不用缩略图看看效果
		if(file_exists($obj_name->thumb_name)) {
			$thumb_url = $obj_name->thumb_url;
		}else{
			$thumb_url = $obj_name->nopic_thumb_url;
		}
		*/

		
		//$ts = "?ts=".strval(time() + mt_rand(0,9999));		
		//$ts = "";//暂时去掉让浏览器一直刷新海报功能 20220523
		//恢复让海报一直刷新的功能 20220607
		
		$ret = "<a href='{$poster_url}' data-fancybox='gallery' data-info='{$info_str}' ><img data-src='{$thumb_url}{$ts}' src='data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7' lazy='true' alt='{$id}' /><span class='tooltiptext'>{$tooltip}</span>{$addi_class}</a>";
		//$ret = "<a href='{$poster_url}' data-fancybox='gallery' data-info='{$info_str}' ><img src='{$thumb_url}{$ts}' lazy='false' alt='{$id}' /><span class='tooltiptext'>{$tooltip}</span></a>";

		return $ret;
	
	}

	/**
	 * @brief	生成上墙显示的Tooltip。
	 * @param	int		$id			post_ID
	 * @return string
	 * @private
	 * @since	0.4.0
	 * @version	0.9.1
	 * @date	2024-09-03
	 * @see		get_gallery_page()
	 */
	private function get_poster_tooltip($id) {
		//比较简单，不做定制。
		$contents = array();
		$results = array();
		$contents['name'] = "bddb_display_name";
		if ($this->self_post_type == 'book') {
			$contents['writer'] = "b_p_writer";
		}
		if ($this->self_post_type == 'movie') {
			$contents['regin'] = "m_region";
		}
		if ($this->self_post_type == 'game') {
			$contents['genre'] = "g_genre";
			$contents['platform'] = "g_platform";
		}
		if ($this->self_post_type == 'album') {
			$contents['genre'] = "a_genre";
			$contents['musician'] = "a_p_musician";
		}
		$contents['publish_time'] = "bddb_publish_time";
		foreach ($contents as $key=>$val) {
			if ('name' == $key) {
				$var = $this->get_meta_str($val, $id);
				if (!empty($var)) {
					$results[] = $var;
				}
				
			} else if ('publish_time' == $key) {
				$var = $this->get_meta_str($val, $id);
				if (!empty($var)) {
					$results[] = substr($var,0,4);
				}
			} else if ('musician' == $key) {
				$is_multi = $this->get_meta_str('a_bl_multicreator', $id);
				if ("1" === $is_multi) {
					$var = "群星";
				}
				else {
					$var = $this->get_tax_str_va($val, $id, "群星", 5);
				}
				if (!empty($var)) {
					$results[] = $var;
				}
			} else {
				$var = $this->get_first_tax_str($val, $id);
				if (!empty($var)) {
					$results[] = $var;
				}
			}
		}
		return implode(", ", $results);
	}
	/**
	 * @brief	生成上墙显示的电影信息。
	 * @param	int		$id			post_ID
	 * @return string
	 * @private
	 * @since	0.0.1
	 * @version	0.0.1
	 * @see		the_gallery()->get_poster_for_gallery()->get_{$this->self_post_type}_panel_info
	 */
	private function get_movie_panel_info($id) {
		//title
		$detail_str = $this->get_panel_title($id);
		//stars
		$star_str = $this->get_panel_rating_stars($id);
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

	/**
	 * @brief	生成上墙显示的书籍信息。
	 * @param	int		$id			post_ID
	 * @return string
	 * @private
	 * @since	0.0.1
	 * @version	0.0.1
	 * @see		the_gallery()->get_poster_for_gallery()->get_{$this->self_post_type}_panel_info
	 */
	private function get_book_panel_info($id) {
		//title
		$detail_str = $this->get_panel_title($id);
		//stars
		$star_str = $this->get_panel_rating_stars($id);
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

	/**
	 * @brief	生成上墙显示的游戏信息。
	 * @param	int		$id			post_ID
	 * @return string
	 * @private
	 * @since	0.0.1
	 * @version	0.0.1
	 * @see		the_gallery()->get_poster_for_gallery()->get_{$this->self_post_type}_panel_info
	 */
	private function get_game_panel_info($id) {
		//title
		$detail_str = $this->get_panel_title($id);
		//stars
		$star_str = $this->get_panel_rating_stars($id);
		$the_score = $this->get_meta_str('bddb_score_douban', $id);
		if (''==$the_score)$the_score='--';
		$dou_score_str = '<span class="bddb-disp-sp-dou-score">'.$the_score.'</span>';
		$detail_str .= sprintf('<p class="bddb-disp-item bddb-inline">%s%s</p>', $star_str, $dou_score_str);
		$detail_str .= $this->panel_common_loop($id);
		$detail_str .= '<div class="bddb-disp-review" id="bddb-gallery-review">'.$this->get_meta_str('bddb_personal_review', $id).'</div>';
		return $detail_str;
	}

	/**
	 * @brief	生成上墙显示的专辑信息。
	 * @param	int		$id			post_ID
	 * @return string
	 * @private
	 * @since	0.0.1
	 * @version	0.5.5
	 * @see		the_gallery()->get_poster_for_gallery()->get_{$this->self_post_type}_panel_info
	 */
	private function get_album_panel_info($id) {
		//title
		$detail_str = $this->get_panel_title($id);
		//stars
		$star_str = $this->get_panel_rating_stars($id);
		$the_score = $this->get_meta_str('bddb_score_douban', $id);
		if (''==$the_score)$the_score='--';
		$dou_score_str = '<span class="bddb-disp-sp-dou-score">'.$the_score.'</span>';
		$detail_str .= sprintf('<p class="bddb-disp-item bddb-inline">%s%s</p>', $star_str, $dou_score_str);
		$detail_str .= $this->panel_common_loop($id);
		$detail_str .= '<div class="bddb-disp-review" id="bddb-gallery-review">'.$this->get_meta_str('bddb_personal_review', $id).'</div>';
		return $detail_str;
	}
	
	/**
	 * @brief	生成上墙显示的电影海报附加风格。
	 * @param	int		$id			post_ID
	 * @return string
	 * @private
	 * @since	0.9.7
	 * @see		the_gallery()->get_poster_for_gallery()->get_{$this->self_post_type}_poster_class
	 */
	private function get_movie_poster_class($id) {
		return "";
	}

	/**
	 * @brief	生成上墙显示的书籍海报附加风格。
	 * @param	int		$id			post_ID
	 * @return string
	 * @private
	 * @since	0.9.7
	 * @see		the_gallery()->get_poster_for_gallery()->get_{$this->self_post_type}_poster_class
	 */
	private function get_book_poster_class($id) {
		return "";
	}

	/**
	 * @brief	生成上墙显示的游戏海报附加风格。
	 * @param	int		$id			post_ID
	 * @return string
	 * @private
	 * @since	0.9.7
	 * @see		the_gallery()->get_poster_for_gallery()->get_{$this->self_post_type}_poster_class
	 */
	private function get_game_poster_class($id) {
		return "";
	}

	/**
	 * @brief	生成上墙显示的专辑海报附加风格。
	 * @param	int		$id			post_ID
	 * @return string
	 * @private
	 * @since	0.9.7
	 * @see		the_gallery()->get_poster_for_gallery()->get_{$this->self_post_type}_poster_class
	 */
	private function get_album_poster_class($id) {
		$ret = '';
		$the_class = '';
		$str_a_quantity = $this->get_tax_str('a_quantity', $id);
		if (stristr($str_a_quantity, "单曲")) {
			$the_class .= " &#xf0cc ";
		}
		if (stristr($str_a_quantity, "live")) {
			$the_class .= " &#xf211 ";
		}
		if (stristr($str_a_quantity, "精选")) {
			$the_class .= " &#xf1b8 ";
		}
		if (stristr($str_a_quantity, "EP")) {
			$the_class .= " &#xf0c3 ";
		}
		if ("1" == $this->get_meta_str('a_bl_multicreator', $id)) {
			$the_class .= " &#xf0c0 ";
		}
		if ($the_class)	{
			$the_class = trim($the_class);
			$ret = "<span class = 'quantity'>{$the_class}</span>";
		}
		return $ret;
	}

	/**
	 * @brief	生成上墙显示的标题。
	 * @param	int		$id			post_ID
	 * @return string
	 * @private
	 * @since	0.0.1
	 * @version	0.0.1
	 * @see		get_xxx_info()
	 */
	private function get_panel_title($id) {
		$name_dsp = $this->get_meta_str('bddb_display_name', $id);
		$link_str = $this->get_meta_str('bddb_external_link', $id);
		return sprintf('<a class="bddb-disp-name" target="_blank" href="%1$s">%2$s</a>', $link_str, $name_dsp);
	}

	/**
	 * @brief	生成上墙显示的个人评星。
	 * @param	int		$id			post_ID
	 * @return string
	 * @private
	 * @since	0.0.1
	 * @version	0.0.1
	 * @see		get_xxx_info()
	 */
	private function get_panel_rating_stars($id) {
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
	
	/**
	 * @brief	生成上墙显示的其它内容。
	 * @param	int		$id			post_ID
	 * @return string	多行
	 * @private
	 * @since	0.0.1
	 * @version	0.7.3
	 * @see		get_xxx_panel_info()
	 */
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
				$row_str = sprintf('<p class="%s"><span class="bddb-disp-label">%s:</span>%s</p>', $this->get_item_class($item), $item['label'], $val_str);
			}
			$panel_str .= $row_str;
		}
		return $panel_str;
	}
	/**
	 * @brief	生成电影摘要显示的其它内容。
	 * @param	int		$id			post_ID
	 * @return string	摘要字符串，多行
	 * @private
	 * @since	0.0.1
	 * @version	0.0.1
	 * @see		show_record()->get_{$this->self_post_type}_abstract
	 */
	private function get_movie_abstract($id) {
		return $this->abstract_common_loop($id);
	}
	/**
	 * @brief	生成书籍摘要显示的其它内容。
	 * @param	int		$id			post_ID
	 * @return string	摘要字符串，多行
	 * @private
	 * @since	0.0.1
	 * @version	0.0.1
	 * @see		show_record()->get_{$this->self_post_type}_abstract
	 */
	private function get_book_abstract($id) {
		return $this->abstract_common_loop($id);
	}
	/**
	 * @brief	生成游戏摘要显示的其它内容。
	 * @param	int		$id			post_ID
	 * @return string	摘要字符串，多行
	 * @private
	 * @since	0.4.3
	 * @version	0.4.3
	 * @see		show_record()->get_{$this->self_post_type}_abstract
	 */
	private function get_game_abstract($id) {
		return $this->abstract_common_loop($id);
	}

	/**
	 * @brief	生成专辑摘要显示的其它内容。
	 * @param	int		$id			post_ID1	 
	 * @return string	摘要字符串，多行
	 * @since	0.5.5
	 * @version	0.8.0
	 * @see		show_record()->get_{$this->self_post_type}_abstract
	 */
	private function get_album_abstract($id) {
		return $this->abstract_common_loop($id);
	}
	/**
	 * @brief	生成系列书籍摘要显示的其它内容。
	 * @param	int		$id			post_ID
	 * @return string	摘要字符串，多行
	 * @private
	 * @since	0.0.1
	 * @version	0.0.1
	 * @see		show_record()->get_{$this->self_post_type}_abstract_series
	 */
	private function get_book_abstract_series($id) {
		$template = '<div class="abstract-left">%s</div>%s';
		$abs_str = $this->abstract_common_loop($id);
		$images = '';
		$count = BDDB_Settings::getInstance()->get_max_serial_count();
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
	
	/**
	 * @brief	生成摘要显示的其它内容。
	 * @param	int		$id			post_ID
	 * @return string	摘要字符串，多行
	 * @private
	 * @since	0.0.1
	 * @version	1.0.0
	 * @date	2025-01-02
	 * @see		show_record()->get_{$this->self_post_type}_abstract
	 */
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
				if (is_feed()) {
					$tmp_str = sprintf('<span class="abs-list" style="display:block;"><span class="bddb-summary-label" style="font-weight:700;">%s：</span>%s</span>', $item['label'], str_replace(",", " / ", $val_str));
				} else {
					$tmp_str = sprintf('<span class="abs-list"><span class="bddb-summary-label">%s：</span>%s</span>', $item['label'], str_replace(",", " / ", $val_str));
				}
				
			}
			$abs_str .= $tmp_str;
		}
		return $abs_str;
	}

	/**
	 * @brief	生成摘要显示外部评星的字符串。
	 * @param	int		$id			post_ID
	 * @return string
	 * @private
	 * @since	0.0.1
	 * @version	0.0.1
	 * @see		show_record()->get_{$this->self_post_type}_abstract
	 */
	private function get_summary_social_stars($id) {
		$src_score_social = '0';
		$src_score_social_float = 0.0;
		switch($this->self_post_type){
			case 'movie':{
				$src_dou_score = ($this->get_meta_str('bddb_score_douban',$id));
				$src_imdb_score = ($this->get_meta_str('m_score_imdb',$id));
				if (!empty($src_dou_score)){
					$src_score_social_float = floatval($src_dou_score);
				}elseif(!empty($src_imdb_score)){
					$src_score_social_float = floatval($src_imdb_score);
				}
				break;
			};
			case 'book':{
				$src_dou_score = ($this->get_meta_str('bddb_score_douban',$id));
				if (!empty($src_dou_score)){
					$src_score_social_float = floatval($src_dou_score);
				}
				break;
			}
			case 'game':{
				$src_dou_score = ($this->get_meta_str('bddb_score_douban',$id));
				if (!empty($src_dou_score)){
					$src_score_social_float = floatval($src_dou_score);
				}
				break;
			}
			case 'album':
			default:{
				$src_dou_score = ($this->get_meta_str('bddb_score_douban',$id));
				if (!empty($src_dou_score)){
					$src_score_social_float = floatval($src_dou_score);
				}
				break;
			}
		}
		$src_score_social = strval(round($src_score_social_float));
		return $src_score_social;
	}
	
	
	/****   显示处理用内部回调函数 开始   ****/
	/**
	 * @brief	显示影片特殊属性图标。
	 * @param	int		$id			post_ID
	 * @param	array	$item		条目
	 * @return string	只有图标部分，不包括前后的html标签
	 * @private
	 * @since	0.0.1
	 * @version	0.6.5
	 * @see		summary_callback()->display_movie_misc
	 * @see		panel_callback()->panel_movie_misc
	 */
	private function movie_misc_special($id, $item) {
		$feature='';
		$str_array = wp_get_post_terms($id, $item['name'], array('fields'=>'id=>slug'));
		if (is_wp_error($str_array)) return '';
		foreach($str_array as $key => $slug) {
			if (!BDDB_Settings::getInstance()->is_pictured_misc($slug,'movie')) {
				continue;
			}
			$img = BDDB_PLUGIN_URL.'img/'.$slug.'.png';
			$feature.=sprintf('<img class="m-misc-brand" src="%s" alt="%s"/>', $img, $slug);
		}
		return $feature;
	}

	/**
	 * @brief	显示条目原名。
	 * @param	int		$id			post_ID
	 * @param	array		$item		条目
	 * @return string	只有文字部分，不包括前后的html标签
	 * @private
	 * @since	0.0.1
	 * @version	0.0.1
	 * @see		summary_callback()->display_original_name
	 * @see		panel_callback()->panel_original_name
	 */
	private function original_name_special($id, $item) {
		//原名与显示名一致时不显示
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
	
	/**
	 * @brief	显示书籍册数。
	 * @param	int		$id			post_ID
	 * @param	array	$item		条目
	 * @return string	只有文字，不包括前后的html标签
	 * @private
	 * @since	0.0.1
	 * @version	0.0.1
	 * @see		summary_callback()->display_book_series_total
	 * @see		panel_callback()->panel_book_series_total
	 */
	private function book_series_total_special($id, $item) {
		//非系列时不显示
		$val = $this->get_meta_str($item['name'], $id);
		$is_series = $this->get_meta_str('b_bl_series', $id);
		if (empty($is_series)||empty($val)){
			return false;
		}
		return $val;
	}

	/**
	 * @brief	显示书籍出版时间。
	 * @param	int		$id			post_ID
	 * @param	array	$item		条目
	 * @return string	只有文字，不包括前后的html标签
	 * @private
	 * @since	0.0.1
	 * @version	0.0.1
	 * @see		summary_callback()->display_book_publish_time
	 * @see		panel_callback()->panel_book_publish_time
	 */
	private function book_publish_time_special($id, $item) {
		$val = $this->get_meta_str($item['name'], $id);
		$is_series = $this->get_meta_str('b_bl_series',$id);
		//非系列时显示一个出版时间，系列时显示XX-XX
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
	
	/**
	 * @brief	显示书籍特殊属性图标。
	 * @param	int		$id			post_ID
	 * @param	array	$item		条目
	 * @return string	只有图标部分，不包括前后的html标签
	 * @private
	 * @since	0.0.1
	 * @version	0.6.5
	 * @see		summary_callback()->display_book_misc
	 * @see		panel_callback()->panel_book_misc
	 */
	public function book_misc_special($id, $item) {
		$feature='';
		$str_array = wp_get_post_terms($id, $item['name'], array('fields'=>'id=>slug'));
		if (is_wp_error($str_array)) return '';
		foreach($str_array as $key => $slug) {
			if (!BDDB_Settings::getInstance()->is_pictured_misc($slug,'book')) {
				continue;
			}
			$img = BDDB_PLUGIN_URL.'img/'.$slug.'.png';
			$feature.=sprintf('<img class="b-misc-brand" src="%s" alt="%s"/>', $img, $slug);
		}
		return $feature;
	}
	
	/**
	 * @brief	显示电影出版时间。
	 * @param	int		$id			post_ID
	 * @param	array	$item		条目
	 * @return string | bool
	 * @protected
	 * @since	0.0.1
	 * @version	1.0.0
	 * @date	2025-01-02
	 * @see		summary_callback()
	 */
	protected function display_movie_publish_time($id, $item) {
		//只显示年
		$val = $this->get_meta_str($item['name'], $id);
		if (!$val) return false;
		if (is_feed()) {
			$ret = sprintf('<span class="abs-list" style="display:block;"><span class="bddb-summary-label" style="font-weight:700;">%s：</span>%s</span>', $item['label'], substr($val,0,4));
		} else {
			$ret = sprintf('<span class="abs-list"><span class="bddb-summary-label">%s：</span>%s</span>', $item['label'], substr($val,0,4));
		}
		return $ret;
	}
	
	/**
	 * @brief	显示电影特殊图标。
	 * @param	int		$id			post_ID
	 * @param	array	$item		条目
	 * @return string | bool
	 * @protected
	 * @since	0.0.1
	 * @version	1.0.0
	 * @date	2025-01-02
	 * @see		summary_callback()
	 */
	protected function display_movie_misc($id, $item) {
		$val = $this->movie_misc_special($id, $item);
		if (empty($val)) {
			return false;
		}
		if (is_feed() ) {
			$ret = sprintf('<span class="abs-list style="display:block;">%s</span>', $val);
		} else {
			$ret = sprintf('<span class="abs-list">%s</span>', $val);
		}
		return $ret;
	}

	/**
	 * @brief	显示电影原名。
	 * @param	int		$id			post_ID
	 * @param	array	$item		条目
	 * @return string | bool
	 * @protected
	 * @since	0.0.1
	 * @version	1.0.0
	 * @date	2025-01-02
	 * @see		summary_callback()
	 */
	protected function display_original_name($id, $item) {
		$val = $this->original_name_special($id, $item);
		if (empty($val)) {
			return false;
		}
		if (is_feed()) {
			$ret = sprintf('<span class="abs-list" style="display:block;"><span class="bddb-summary-label" style="font-weight:700;">%s：</span>%s</span>', $item['label'], $val);
		} else {
			$ret = sprintf('<span class="abs-list"><span class="bddb-summary-label">%s：</span>%s</span>', $item['label'], $val);
		}
		return $ret;
	}

	/**
	 * @brief	显示书籍特殊图标。
	 * @param	int		$id			post_ID
	 * @param	array	$item		条目
	 * @return string | bool
	 * @protected
	 * @since	0.0.1
	 * @version	1.0.0
	 * @date	2025-01-02
	 * @see		summary_callback()
	 */
	protected function display_book_misc($id, $item) {
		$val = $this->book_misc_special($id, $item);
		if (empty($val)) {
			return false;
		}
		if (is_feed()) {
			$ret = sprintf('<span class="abs-list" style="display:block;">%s</span>', $val);
		} else {
			$ret = sprintf('<span class="abs-list">%s</span>', $val);
		}
		return $ret;
	}

	/**
	 * 显示书籍出版时间。
	 * @param	int		$id			post_ID
	 * @param	array	$item		条目
	 * @return string | bool
	 * @protected
	 * @since	0.0.1
	 * @version	1.0.0
	 * @date	2025-01-02
	 * @see		summary_callback()
	 */
	protected function display_book_publish_time($id, $item) {
		$val = $this->book_publish_time_special($id, $item);
		if (empty($val)) {
			return false;
		}
		if (is_feed()) {
			$ret = sprintf('<span class="abs-list" style="display:block;"><span class="bddb-summary-label" style="font-weight:700;">%s：</span>%s</span>', $item['label'], $val);
		} else{
			$ret = sprintf('<span class="abs-list"><span class="bddb-summary-label">%s：</span>%s</span>', $item['label'], $val);
		}
		return $ret;
	}

	/**
	 * @brief	显示书籍系列册数。
	 * @param	int		$id			post_ID
	 * @param	array	$item		条目
	 * @return string | bool
	 * @protected
	 * @since	0.0.1
	 * @version	1.0.0
	 * @date	2025-01-02
	 * @see		summary_callback()
	 */
	protected function display_book_series_total($id, $item) {
		$val = $this->book_series_total_special($id, $item);
		if (empty($val)) {
			return false;
		}
		if (is_feed()) {
			$ret = sprintf('<span class="abs-list" style="display:block;"><span class="bddb-summary-label" style="font-weight:700;">%s：</span>%s</span>', $item['label'], $val);
		} else {
			$ret = sprintf('<span class="abs-list"><span class="bddb-summary-label">%s：</span>%s</span>', $item['label'], $val);
		}
		return $ret;
	}

	/**
	 * @brief	上墙电影人名过长。
	 * @param	int		$id			post_ID
	 * @param	array	$item		条目
	 * @return	string
	 * @protected
	 * @since	0.3.2
	 * @version	0.7.3
	 * @remarks	暂时只用于演员
	 * @see		panel_callback()
	 */
	protected function panel_movie_people($id, $item) {
		$val_str = '';
		$str_array = wp_get_post_terms($id, $item['name'], array('fields'=>'names'));
		if (is_wp_error($str_array))
			return '';
		foreach ($str_array as $str_name) {
			if (!empty($val_str)) {
				$val_str .= ", ";
			}
			$p1 = mb_strrpos($str_name, ".");
			$p2 = mb_strrpos($str_name, " ");
			$p3 = mb_strrpos($str_name, "·");
			$p = $p1>0?$p1:($p2>0?$p2:($p3>0?$p3:0));
			if ( mb_strlen($str_name) >= 7 &&
				$p > 0) {
					$str_name = mb_substr($str_name, $p+1);
				}
			$val_str .= $str_name;
		}
		if (empty($val_str)) {
			return '';
		}
		return sprintf('<p class="%s"><span class="bddb-disp-label">%s:</span>%s</p>', $this->get_item_class($item), $item['label'], $val_str);
	}

	/**
	 * @brief	上墙时时间只显示年份。
	 * @param	int		$id			post_ID
	 * @param	array	$item		条目
	 * @return	string
	 * @protected
	 * @since	0.4.0
	 * @version	0.7.3
	 * @see		panel_callback()
	 */
	protected function panel_time_only_year($id, $item) {
		return sprintf('<p class="%s"><span class="bddb-disp-label">%s:</span>%s</p>', $this->get_item_class($item), $item['label'], date("Y", strtotime($this->get_meta_str($item['name'], $id))));
	}


	/**
	 * @brief	上墙电影特殊图标。
	 * @param	int		$id			post_ID
	 * @param	array	$item		条目
	 * @return string
	 * @protected
	 * @since	0.0.1
	 * @version	0.0.1
	 * @see		panel_callback()
	 */
	protected function panel_movie_misc($id, $item) {
		$val = $this->movie_misc_special($id, $item);
		if (empty($val)) {
			return false;
		}
		return sprintf('<p class="bddb-disp-item align-left">%s</p>', $val);
	}

	/**
	 * @brief	上墙书籍出版时间。
	 * @param	int		$id			post_ID
	 * @param	array	$item		条目
	 * @return string
	 * @protected
	 * @since	0.0.1
	 * @version	0.7.3
	 * @see		panel_callback()
	 */
	protected function panel_book_publish_time($id, $item) {
		$val = $this->book_publish_time_special($id, $item);
		if (empty($val)) {
			return false;
		}
		return sprintf('<p class="%s"><span class="bddb-disp-label">%s:</span>%s</p>', $this->get_item_class($item), $item['label'], $val);
	}

	/**
	 * @brief	上墙书籍册数。
	 * @param	int		$id			post_ID
	 * @param	array	$item		条目
	 * @return string
	 * @protected
	 * @since	0.0.1
	 * @version	0.7.3
	 * @see		panel_callback()
	 */
	protected function panel_book_series_total($id, $item) {
		$val = $this->book_series_total_special($id, $item);
		if (empty($val)) {
			return false;
		}
		return sprintf('<p class="%s"><span class="bddb-disp-label">%s:</span>%s</p>', $this->get_item_class($item), $item['label'], $val);
	}

	/**
	 * @brief	上墙原名。
	 * @param	int		$id			post_ID
	 * @param	array	$item		条目
	 * @return string
	 * @protected
	 * @since	0.0.1
	 * @version	0.7.3
	 * @see		panel_callback()
	 */
	protected function panel_original_name($id, $item) {
		$val = $this->original_name_special($id, $item);
		if (empty($val)) {
			return false;
		}
		return sprintf('<p class="%s"><span class="bddb-disp-label">%s:</span>%s</p>', $this->get_item_class($item), $item['label'], $val);
	}
	
	/**
	 * @brief	上墙书籍特殊图标。
	 * @param	int		$id			post_ID
	 * @param	array	$item		条目
	 * @return string
	 * @protected
	 * @since	0.0.1
	 * @version	0.0.1
	 * @see		panel_callback()
	 */
	protected function panel_book_misc($id, $item) {
		$val = $this->book_misc_special($id, $item);
		if (empty($val)) {
			return false;
		}
		return sprintf('<p class="bddb-disp-item align-left">%s</p>', $val);
	}

	/**
	 * @brief	显示游戏特殊图标。
	 * @param	int		$id			post_ID
	 * @param	array	$item		条目
	 * @return string | bool
	 * @protected
	 * @since	0.6.7
	 * @version	1.0.0
	 * @date	2025-01-02
	 * @see		summary_callback()
	 */
	protected function display_game_misc($id, $item) {
		$val = $this->game_misc_special($id, $item);
		if (empty($val)) {
			return false;
		}
		if (is_feed()) {
			$ret = sprintf('<span class="abs-list" style="display:block;">%s</span>', $val);
		} else {
			$ret = sprintf('<span class="abs-list">%s</span>', $val);
		}
		return $ret;
	}

		/**
	 * @brief	显示游戏特殊属性图标。
	 * @param	int		$id			post_ID
	 * @param	array	$item		条目
	 * @return string	只有图标部分，不包括前后的html标签
	 * @private
	 * @since	0.6.7
	 * @see		summary_callback()->display_game_misc
	 * @see		panel_callback()->panel_game_misc
	 */
	public function game_misc_special($id, $item) {
		$feature='';
		$str_array = wp_get_post_terms($id, $item['name'], array('fields'=>'id=>slug'));
		if (is_wp_error($str_array)) return '';
		foreach($str_array as $key => $slug) {
			if (!BDDB_Settings::getInstance()->is_pictured_misc($slug,'game')) {
				continue;
			}
			$img = BDDB_PLUGIN_URL.'img/'.$slug.'.png';
			$feature.=sprintf('<img class="g-misc-brand" src="%s" alt="%s"/>', $img, $slug);
		}
		return $feature;
	}

	/**
	 * @brief	上墙游戏特殊图标。
	 * @param	int		$id			post_ID
	 * @param	array	$item		条目
	 * @return string
	 * @protected
	 * @since	0.6.7
	 * @see		panel_callback()
	 */
	protected function panel_game_misc($id, $item) {
		$val = $this->game_misc_special($id, $item);
		if (empty($val)) {
			return false;
		}
		return sprintf('<p class="bddb-disp-item align-left">%s</p>', $val);
	}

	/**
	 * @brief	上墙专辑地区和语种。
	 * @param	int		$id			post_ID
	 * @param	array	$item		条目
	 * @return string | bool
	 * @protected
	 * @since	0.8.6
	 * @version 0.8.9
	 * @see		panel_callback()
	 */
	protected function panel_album_region($id, $item) {
		$args = array(
			'orderby' => 'term_id',
			'order' => 'DESC',
		);
		$arr_regions = wp_get_post_terms($id, $item['name'], $args);
		$str_regions = "";
		if (empty($arr_regions)){
			return false;
		}
		$arr_str_region = array();
		foreach ($arr_regions as $region) {
			$arr_str_region[] = sprintf('<img src="https://flagcdn.com/16x12/%1$s.png"  alt="%2$s" />', $region->slug, $region->name);
		}
		if (empty($arr_str_region)){
			return false;
		}
		$str_regions = implode(' , ', $arr_str_region);
		$str_regions = str_replace(" , ", " ",$str_regions);

		$val_l = $this->get_meta_str('a_language', $id);
		if (!empty($val_l)) {
			$val_l = preg_replace('/[0-9]{3}-/i', "", $val_l);
			$str_regions .=  " / " . $val_l;
		}
		return sprintf('<p class="%s"><span class="bddb-disp-label">%s:</span>%s</p>', $this->get_item_class($item), "区域", $str_regions);
	}

	/**
	 * @brief	显示专辑音乐家。
	 * @param	int		$id			post_ID
	 * @param	array	$item		条目
	 * @return string | bool
	 * @protected
	 * @since	0.9.1
	 * @version 1.0.0
	 * @date	2025-01-02
	 * @see		summary_callback()
	 */
	protected function summary_album_musician($id, $item) {
		$val = $this->get_tax_str($item['name'], $id);
		if (!$val) return false;
		if (is_feed()) {
			$ret = sprintf('<span class="abs-list" style="display:block;"><span class="bddb-summary-label" style="font-weight:700;">%s：</span>%s</span>', $item['label'], $val);
		} else {
			$ret = sprintf('<span class="abs-list"><span class="bddb-summary-label">%s：</span>%s</span>', $item['label'], $val);
		}
		
		$val = $this->get_tax_str('a_p_asstants', $id);
		if (!$val) return $ret;
		if (is_feed()) {
			$ret .= sprintf('<span class="abs-list"  style="display:block;"><span class="bddb-summary-label" style="font-weight:700;">%s：</span>%s</span>', '协作', $val);
		} else {
			$ret .= sprintf('<span class="abs-list"><span class="bddb-summary-label">%s：</span>%s</span>', '协作', $val);
		}
		return $ret;
	}

		/**
	 * @brief	上墙专辑音乐家。
	 * @param	int		$id			post_ID
	 * @param	array	$item		条目
	 * @return string | bool
	 * @protected
	 * @since	0.9.1
	 * @version 0.9.2
	 * @see		summary_callback()
	 */
	protected function panel_album_musician($id, $item) {
		$val = $this->get_tax_str($item['name'], $id);
		if (!$val) return false;
		$ret = sprintf('<p class="bddb-disp-item"><span class="bddb-disp-label">%s:</span>%s</p>', $item['label'], $val);
		$val = $this->get_tax_str('a_p_asstants', $id);
		if (!$val) return $ret;
		$ret .= sprintf('<p class="bddb-disp-item"><span class="bddb-disp-label">%s:</span>%s</p>', '协作', $val);
		return $ret;
	}
	/**
	 * @brief	上墙专辑规格，只显示特殊属性。
	 * @param	int		$id			post_ID
	 * @param	array	$item		条目
	 * @return string | bool
	 * @protected
	 * @since	0.9.4
	 * @see		summary_callback()
	 */
	protected function panel_album_quantity($id, $item) {
		$str_array = wp_get_post_terms($id, $item['name'], array('fields'=>'names'));
		if (is_wp_error($str_array) || !is_array($str_array)) {
			return false;
		}
		$arr_val = array_intersect($str_array, array("单曲", "Live", "精选", "EP", "2CD", "3CD", "4CD"));
		if (!is_array($arr_val))
		{
			return false;
		}
		if (count($arr_val)>1) {
			$val_str = implode(', ', $arr_val);
		} elseif(count($arr_val) == 1) {
			$val_str =trim($arr_val[0]);
		}
		else {
			return false;
		}
		$ret = sprintf('<p class="bddb-disp-item"><span class="bddb-disp-label">%s:</span>%s</p>', $item['label'], $val_str);
		return $ret;
	}

	/****   显示处理用内部回调函数 结束   ****/
	/********    私有函数 结束    ********/
};
//movie/brand

class BDDB_Book {
	public static $class_self = false;
	public static function getInstance() {
		if (!self::$class_self) {
			self::$class_self = new BDDB_Common_Template('book');
		}
		return self::$class_self;
	}
}

class BDDB_Movie {
	public static $class_self = false;
	public static function getInstance() {
		if (!self::$class_self) {
			self::$class_self = new BDDB_Common_Template('movie');
		}
		return self::$class_self;
	}
}

class BDDB_Game {
	public static $class_self = false;
	public static function getInstance() {
		if (!self::$class_self) {
			self::$class_self = new BDDB_Common_Template('game');
		}
		return self::$class_self;
	}
}

class BDDB_Album {
	public static $class_self = false;
	public static function getInstance() {
		if (!self::$class_self) {
			self::$class_self = new BDDB_Common_Template('album');
		}
		return self::$class_self;
	}
}