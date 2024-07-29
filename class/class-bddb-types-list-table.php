<?php

/**
 * @mention 开始准备使用继承的方法，但继承类不能在一开始创建类的时候就生成派生类，只能在进入edit页面时利用钩子替换掉全局变量$wp_list_table
 * 这样就造成两个问题:一是修改不了per_page，二是inline_edit更新后，ajax的回调仍然会调用默认类。
 */

class BDDB_Typed_List {
	protected static $supported_columns = array(
		'movie' => array(
			'm_region' => array(
				'key'	=>	'taxonomy-m_region',
				'label'	=>	'地区',
				'type'	=>	'tax',
				'style' =>	'',
			),
			'm_p_director' => array(
				'key'	=>	'taxonomy-m_p_director',
				'label'	=>	'导演',
				'type'	=>	'tax',
				'style' =>	'',
			),
			'm_p_actor' => array(
				'key'	=>	'taxonomy-m_p_actor',
				'label'	=>	'主演',
				'type'	=>	'tax',
				'style' =>	'',
			),
			'm_misc_brand' => array(
				'key'	=>	'taxonomy-m_misc_brand',
				'label'	=>	'特记',
				'type'	=>	'tax',
				'style' =>	'',
			),
			'bddb_view_time' => array(
				'key'	=>	'meta-bddb_view_time',
				'label'	=>	'观看年月',
				'type'	=>	'meta',
				'style' =>	'datetime',
				'qe_start' => '',
			),
			'm_length' => array(
				'key'	=>	'meta-m_length',
				'label'	=>	'片长',
				'type'	=>	'meta',
				'style' =>	'number',
				'qe_start' => '',
			),
			'bddb_personal_rating' => array(
				'key'	=>	'meta-bddb_personal_rating',
				'label'	=>	'评分',
				'type'	=>	'meta',
				'style' =>	'number',
				'qe_end' => '',
			),
		),

		'book'	=> array(
			'b_region' => array(
				'key'	=>	'taxonomy-b_region',
				'label'	=>	'地区',
				'type'	=>	'tax',
				'style' =>	'',
			),
			'b_p_writer' => array(
				'key'	=>	'taxonomy-b_p_writer',
				'label'	=>	'作者',
				'type'	=>	'tax',
				'style' =>	'',
			),
			'b_misc_brand' => array(
				'key'	=>	'taxonomy-b_misc_brand',
				'label'	=>	'特记',
				'type'	=>	'tax',
				'style' =>	'',
			),
			'bddb_view_time' => array(
				'key'	=>	'meta-bddb_view_time',
				'label'	=>	'阅读年月',
				'type'	=>	'meta',
				'style' =>	'datetime',
				'qe_start' => '',
			),
			'bddb_personal_rating' => array(
				'key'	=>	'meta-bddb_personal_rating',
				'label'	=>	'评分',
				'type'	=>	'meta',
				'style' =>	'number',
				'qe_end' => '',
			),
		),

		'game' => array(
			'g_genre' => array(
				'key'	=>	'taxonomy-g_genre',
				'label'	=>	'类型',
				'type'	=>	'tax',
				'style' =>	'',
			),
			'g_platform' => array(
				'key'	=>	'taxonomy-g_platform',
				'label'	=>	'机种',
				'type'	=>	'tax',
				'style' =>	'',
			),
			'bddb_view_time' => array(
				'key'	=>	'meta-bddb_view_time',
				'label'	=>	'接触年月',
				'type'	=>	'meta',
				'style' =>	'datetime',
				'qe_start' => '',
			),
			'bddb_personal_rating' => array(
				'key'	=>	'meta-bddb_personal_rating',
				'label'	=>	'评分',
				'type'	=>	'meta',
				'style' =>	'number',
			),
			'g_cost_time' => array(
				'key'	=>	'meta-g_cost_time',
				'label'	=>	'耗时',
				'type'	=>	'meta',
				'style' =>	'number',
				'qe_end' => '',
			),
		),

		'album' => array(
			'a_region' => array(
				'key'	=>	'meta-a_region',
				'label'	=>	'地区',
				'type'	=>	'tax',
				'style' =>	'',
			),
			'a_genre' => array(
				'key'	=>	'taxonomy-a_genre',
				'label'	=>	'类型',
				'type'	=>	'tax',
				'style' =>	'',
			),
			'a_p_musician' => array(
				'key'	=>	'meta-a_p_musician',
				'label'	=>	'主创',
				'type'	=>	'tax',
				'style' =>	'',
			),
			'bddb_view_time' => array(
				'key'	=>	'meta-bddb_view_time',
				'label'	=>	'欣赏年月',
				'type'	=>	'meta',
				'style' =>	'datetime',
				'qe_start' => '',
			),
			'bddb_personal_rating' => array(
				'key'	=>	'meta-bddb_personal_rating',
				'label'	=>	'评分',
				'type'	=>	'meta',
				'style' =>	'number',
				'qe_end' => '',
			),
		),
	);

	/**
	 * admin初始化时调用。
	 * @see		bddb_admin_init
	 * @since 	0.5.4
	 * @version 0.5.4
	 */
	public static function admin_init() {
		//处理列标题
		add_filter( 'manage_posts_columns', 'BDDB_Typed_List::fix_list_headers', 10, 2);
		
		//显示meta类型的值的内容
		add_action( 'manage_posts_custom_column', 'BDDB_Typed_List::show_custom_meta_value', 10, 2 );

		//追加自定义的排序列
		add_filter( 'manage_edit-movie_sortable_columns', 'BDDB_Typed_List::add_movie_sortable_columns');
		add_filter( 'manage_edit-book_sortable_columns', 'BDDB_Typed_List::add_book_sortable_columns');
		add_filter( 'manage_edit-game_sortable_columns', 'BDDB_Typed_List::add_game_sortable_columns');
		add_filter( 'manage_edit-album_sortable_columns', 'BDDB_Typed_List::add_album_sortable_columns');

		//修改自己追加的meta列的排序方法（主要针对数字100和个位数）
		add_action( 'pre_get_posts', 'BDDB_Typed_List::resort_meta_column_query');

		//增加分类法的下拉列表
		add_action( 'restrict_manage_posts', 'BDDB_Typed_List::add_taxonomy_filter_ddl', 10, 2 );

		//不显示按月分类下拉列表
		add_filter('disable_months_dropdown', 'BDDB_Typed_List::disable_month_filter_ddl', 10, 2);

		//修改每页最大显示条数
		add_filter( 'edit_posts_per_page', 'BDDB_Typed_List::modify_list_per_page', 10, 2 );

		//添加可以快速编辑的meta项
		add_action('quick_edit_custom_box', 'BDDB_Typed_List::add_quickedit_items', 10, 2);

	}

	/**
	 * 后台类别列表修改增加标题。
	 * @param string 	$columns	修改前的标题
	 * @param string 	$post_type	list的种类
	 * @return string
	 * @see		filter::manage_posts_columns
	 * @since 	0.1.0
	 * @version 0.8.2
	 */
	public static function fix_list_headers($columns, $post_type) {
		$my_columns  = self::get_columns($post_type) ;
		if (!$my_columns || empty($my_columns)) {
			return $columns;
		}
		//先删掉日期再加回来，让日期保持在最后一列。
		unset($columns['date']);

		foreach ($my_columns as $key=>$col) {
			if ($col['type'] == 'meta') {
				$columns[$col['key']] = $col['label'];
			}

			//改自定义taxonomy的名字。如果taxonomy的label设成了中文，这一步应该可以不做，暂时保留。
			if (isset($columns[$col['key']])) {
				$columns[$col['key']] = $col['label'];
			}
			
		}

		//加回日期
		$columns['date'] = '发布时间';
		return $columns;
	}

	/**
	 * 后台类别列表增加显示内容。
	 * @public
	 * @param string 	$column_name	列标题
	 * @param int 		$id				post_ID
	 * @return string
	 * @see		action::manage_posts_custom_column
	 * @since 	0.2.0
	 * @version 0.8.2
	 */	
	public static function show_custom_meta_value($column_name, $id) {
		if ( 0 === strpos( $column_name, 'meta-' ) ) {
			$meta = substr( $column_name, 5 );
		} else {
			return '';
		}
		/*
		if ('pic' == $meta) {
			$image = new Bddb_SimpleImage();
			$names = bddb_get_poster_names('book', $id);
	   		$poster_full_name = $names->poster_name;
			if (!file_exists($poster_full_name)) {
				$out = '冇';
				return $out;
			}
			$image_info = getimagesize($poster_full_name);
			$full_width = BDDB_Settings::get_poster_width('book');
			$full_height = BDDB_Settings::get_poster_height('book');
			if (!$image_info ||
				!is_array($image_info) ||
				$image_info[0] != $full_width ||
				$image_info[1] != $full_height) {
					$out = '要！';
			}
			else {
				$out = '-';
			}
			

		}
		else
		*/
		{
			$out = get_post_meta($id, $meta, true);
			if (empty($out)) {
				if (0 != $out) {
					$out = '&#8212;';
				}
			}
		}
		
		echo $out;
	}

	/**
	 * 后台电影列表增加排序属性。
	 * @param array 	$sortable_columns	已经存在的排序列
	 * @return array
	 * @see		filter::manage_{$this->screen->id}_sortable_columns
	 * @since 	0.1.0
	 * @version 0.5.4
	 */
	public static function add_movie_sortable_columns($sortable_columns) {
		return self::add_sortable_columns('movie', $sortable_columns);
	}
	/**
	 * 后台书籍列表增加排序属性。
	 * @param array 	$sortable_columns	已经存在的排序列
	 * @return array
	 * @see		filter::manage_{$this->screen->id}_sortable_columns
	 * @since 	0.1.0
	 * @version 0.5.4
	 */
	public static function add_book_sortable_columns($sortable_columns) {
		return self::add_sortable_columns('book', $sortable_columns);
	}
		/**
	 * 后台游戏列表增加排序属性。
	 * @param array 	$sortable_columns	已经存在的排序列
	 * @return array
	 * @see		filter::manage_{$this->screen->id}_sortable_columns
	 * @since 	0.1.0
	 * @version 0.5.4
	 */
	public static function add_game_sortable_columns($sortable_columns) {
		return self::add_sortable_columns('game', $sortable_columns);
	}
	/**
	 * 后台专辑列表增加排序属性。
	 * @param array 	$sortable_columns	已经存在的排序列
	 * @return array
	 * @see		filter::manage_{$this->screen->id}_sortable_columns
	 * @since 	0.1.0
	 * @version 0.5.4
	 */
	public static function add_album_sortable_columns($sortable_columns) {
		return self::add_sortable_columns('album', $sortable_columns);
	}

	/**
	 * 后台类别列表增加排序属性。
	 * @param string 	$post_type			种类
	 * @param array 	$sortable_columns	已经存在的排序列
	 * @return array
	 * @since 	0.1.0
	 * @version 0.5.4
	 */
	public static function add_sortable_columns($post_type, $sortable_columns) {
		$my_columns  = self::get_columns($post_type) ;
		if (!$my_columns || empty($my_columns)) {
			return $sortable_columns;
		}
		foreach ($my_columns as $key=>$col) {
			if ($col['type'] == 'meta') {
				$sortable_columns[$col['key']] = array($key, true);
			}
		}
		return $sortable_columns;
	}

	/**
	 * 更改meta类型列的排序方法。
	 * @param object 	$query		WP_Query
	 * @see		action::pre_get_posts
	 * @since 	0.1.0
	 * @version 0.5.4
	 */
	public static function resort_meta_column_query($query){
		$orderby = $query->get( 'orderby' );
		//这个值是register_type的时候注册的，默认没改过，也就是type本身。
		$post_type = $query->get( 'post_type' );
		$my_columns  = self::get_columns($post_type) ;
		if (!$my_columns || empty($my_columns)) {
			return;
		}

		if (isset($my_columns[$orderby])) {
			$item = $my_columns[$orderby];
			if ('meta' == $item['type']) {
				$key = substr($item['key'],5);
				$meta_query = array(
					//不存在或者按照名排序，任何一个条件都不能省略。
					'relation' => 'OR',
					array(
						'key' => $key,
						'compare' => 'NOT EXISTS',
					),
					array(
						'key' => $key,
					),
				);
				//如果是数字类型，转化成数字排序，这就是无法整合进tax排序函数的原因。
				if ('number' == $item['style']) {
					$meta_query[1]['type'] = 'NUMERIC';
					$meta_query[0]['type'] = 'NUMERIC';
				}
				$query->set( 'meta_query', $meta_query );
				$query->set( 'orderby', 'meta_value' );
			}
		}
	}

	/**
	 * 显示taxonomy的下拉列表。
	 * @param string 	$post_type
	 * @param array		$switch		未使用
	 * @see		action::pre_get_posts
	 * @since 	0.1.0
	 * @version 0.5.4
	 */
	public static function add_taxonomy_filter_ddl($post_type, $which){
		$my_columns  = self::get_columns($post_type) ;
		if (!$my_columns || empty($my_columns)) {
			return;
		}
		foreach ($my_columns as $key=>$item){
			if ($item['type'] != 'tax') {
				continue;
			}
			$selection = isset($_GET[$key])?$_GET[$key]:'';
			echo '<label class="screen-reader-text" for="'.$key.'">Filter by '.$key.'</label>';
			$dropdown_arg = array(
				'show_option_none' => get_taxonomy($key)->labels->all_items,
				'option_none_value' => '',
				'orderby' => 'count',
				'order' => 'DESC',
				'name' => $key,
				'value_field' => 'slug',
				'taxonomy' => $key,
				'selected' => $selection,
			);
			wp_dropdown_categories($dropdown_arg);
		}
	}

	/**
	 * 不显示按月过滤的下拉列表。
	 * @param 	bool		$disabled		是否阻止显示，默认是false，也就是不阻止，也就是显示。
	 * @param 	string 		$post_type		类型
	 * @return	bool
	 * @see		action::pre_get_posts
	 * @since 	0.1.0
	 * @version 0.5.4
	 */
	public static function disable_month_filter_ddl($disabled, $post_type){
		if (BDDB_Statics::is_valid_type($post_type)) {
			$disabled = true;
		}
		return $disabled;
	}

	/**
	 * 修改后台每页显示条数。
	 * @param	int		$per_page		每页显示条数
	 * @param	string	$post_type		类型
	 * @return  int
	 * @see		filter::edit_posts_per_page
	 * @since 	0.2.1
	 * @version 0.5.4
	 */
	public static function modify_list_per_page($per_page, $post_type) {
		if (BDDB_Statics::is_valid_type($post_type)) {
			//TODO
			$per_page = 50;
		}
		return $per_page;
	}

	/**
	 * 快速编辑页面显示追加的meta框。
	 * @param	int		$column_name	列名（包括自己追加的前缀）		
	 * @param	string	$post_type		类型
	 * @return  int
	 * @see		filter::quick_edit_custom_box
	 * @since 	0.2.1
	 * @version 0.5.4
	 */
	public static function add_quickedit_items($column_name, $post_type) {
		$my_columns  = self::get_columns($post_type) ;
		if (!$my_columns || empty($my_columns)) {
			return;
		}
		if ( 0 === strpos( $column_name, 'meta-' ) ) {
			$meta = substr( $column_name, 5 );
		} else {
			return;
		}
		if (isset($my_columns[$meta])) {
			$item = $my_columns[$meta];
		}
		else {
			return;
		}
		if (isset($item['qe_start'])) {
			wp_nonce_field( 'bddb_q_edit_nonce', 'bddb_nonce' );
			echo '<fieldset class="inline-edit-col-center"><div class="inline-edit-col"><div class="inline-edit-group wp-clearfix">';
		}
		//TODO表示框里的内容
		//$val_str = get_post_meta($post->ID, $arg['name'], true);
		echo '<label class="alignleft">
					<span class="title">'.$item['label'].'</span>
					<span class="input-text-wrap"><input type="text" name="'.$column_name.'" value=""></span>
				</label>';
		if (isset($item['qe_end'])) {
			echo '</div></div></fieldset>';
		}
	}

	/**
	 * 取得类型所对应的附加列。
	 * @param string 	$post_type	list的种类
	 * @return	bool|array
	 * @since 	0.5.4
	 * @version 0.5.4
	 */
	public static function get_columns($post_type) {
		if (!BDDB_Statics::is_valid_type($post_type)) {
			return false;
		}
		return self::$supported_columns[$post_type];
	}
}