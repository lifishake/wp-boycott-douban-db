<?php
/**
 * 固定数据类
 */
class BDDB_Statics {
	//成员列表
	private $taxonomies = false;	//新增的分类法
	private $post_types = false;	//新增的类型

	/**
	 * 构造函数
	 * @public
	 * @since 0.1.1
	 */
	public function __construct(){
		$this->taxonomies = array(
			//地区/电影
		array('tax' => 'm_region',
			'obj' => array( 'movie' ),
			'label' => 'Region',
			'slug' => 'm_region',
			'complex_name' => 'regions',
			'show_admin_column' => true,
			),
			//地区/书
		array('tax' => 'b_region',
			'obj' => array( 'book' ),
			'label' => 'Region',
			'slug' => 'b_region',
			'complex_name' => 'regions',
			'show_admin_column' => true,
			),
			//地区/游戏
		array('tax' => 'g_region',
			'obj' => array( 'game' ),
			'label' => 'Region',
			'slug' => 'g_region',
			'complex_name' => 'regions',
			'show_admin_column' => true,
			),
			//地区/专辑
		array('tax' => 'a_region',
			'obj' => array( 'album' ),
			'label' => 'Region',
			'slug' => 'a_region',
			'complex_name' => 'regions',
			'show_admin_column' => true,
			),
			//种类/电影
		array('tax' => 'm_genre',
			  'obj' => array( 'movie' ),
			  'label' => 'Genre',
			  'slug' => 'm_genre',
			  'complex_name' => 'genres',
			  'show_admin_column' => false,
			  ),
			//种类/书
		array('tax' => 'b_genre',
			  'obj' => array( 'book' ),
			  'label' => 'Genre',
			  'slug' => 'b_genre',
			  'complex_name' => 'genres',
			  'show_admin_column' => false,
			  ),
			//种类/游戏
		array('tax' => 'g_genre',
			  'obj' => array( 'game' ),
			  'label' => 'Genre',
			  'slug' => 'g_genre',
			  'complex_name' => 'genres',
			  'show_admin_column' => true,
			  ),
			//种类/专辑
		array('tax' => 'a_genre',
			  'obj' => array( 'album' ),
			  'label' => 'Genre',
			  'slug' => 'a_genre',
			  'complex_name' => 'genres',
			  'show_admin_column' => true,
			  ),
			//出品方/电影
		array('tax' => 'm_publisher',
			  'obj' => array( 'movie' ),
			  'label' => 'Publisher',
			  'slug' => 'm_publisher',
			  'complex_name' => 'publishers',
			  'show_admin_column' => false,
			  ),
			//出版社/书
		array('tax' => 'b_publisher',
			  'obj' => array( 'book' ),
			  'label' => 'Publisher',
			  'slug' => 'b_publisher',
			  'complex_name' => 'publishers',
			  'show_admin_column' => false,
			  ),
			//发行方/游戏
		array('tax' => 'g_publisher',
			  'obj' => array( 'game' ),
			  'label' => 'Publisher',
			  'slug' => 'g_publisher',
			  'complex_name' => 'publishers',
			  'show_admin_column' => false,
			  ),
			//唱片公司/专辑
		array('tax' => 'a_publisher',
			  'obj' => array( 'album' ),
			  'label' => 'Publisher',
			  'slug' => 'a_publisher',
			  'complex_name' => 'publishers',
			  'show_admin_column' => false,
			  ),
			//导演/电影
		array('tax' => 'm_p_director',
			  'obj' => array( 'movie' ),
			  'label' => 'Director',
			  'slug' => 'm_p_director',
			  'complex_name' => 'directors',
			  'show_admin_column' => true,
			  ),
			//主演/电影
		array('tax' => 'm_p_actor',
			  'obj' => array( 'movie' ),
			  'label' => 'Actor',
			  'slug' => 'm_p_actor',
			  'complex_name' => 'actors',
			  'show_admin_column' => true,
			  ),
			//编剧/电影
		array('tax' => 'm_p_screenwriter',
			  'obj' => array( 'movie' ),
			  'label' => 'Screen Writer',
			  'slug' => 'm_p_screenwriter',
			  'complex_name' => 'screen Writers',
			  'show_admin_column' => false,
			  ),
			//配乐/电影
		array('tax' => 'm_p_musician',
			  'obj' => array( 'movie' ),
			  'label' => 'Musician',
			  'slug' => 'm_p_musician',
			  'complex_name' => 'musicians',
			  'show_admin_column' => false,
			  ),
			//其余特性/电影
			//豆瓣250,IMDB250,露点,3级或R级
		array('tax' => 'm_misc_brand',
			  'obj' => array( 'movie' ),
			  'label' => 'Brand',
			  'slug' => 'm_misc_brand',
			  'complex_name' => 'brands',
			  'show_admin_column' => true,
			  ),
			//作者/书
		array('tax' => 'b_p_writer',
			  'obj' => array( 'book' ),
			  'label' => 'Writer',
			  'slug' => 'b_p_writer',
			  'complex_name' => 'writers',
			  'show_admin_column' => true,
			  ),
			//编者/书
		array('tax' => 'b_p_editor',
			  'obj' => array( 'book' ),
			  'label' => 'Editor',
			  'slug' => 'b_p_editor',
			  'complex_name' => 'editors',
			  'show_admin_column' => false,
			  ),
			//译者/书
		array('tax' => 'b_p_translator',
			  'obj' => array( 'book' ),
			  'label' => 'Translator',
			  'slug' => 'b_p_translator',
			  'complex_name' => 'translators',
			  'show_admin_column' => false,
			  ),
		array('tax' => 'b_misc_brand',
			  'obj' => array( 'book' ),
			  'label' => 'Brand',
			  'slug' => 'b_misc_brand',
			  'complex_name' => 'brands',
			  'show_admin_column' => true,
			  ),
			//机种/游戏
		array(	'tax' => 'g_platform',
				'obj' => array( 'game' ),
				'label' => 'Platform',
				'slug' => 'g_platform',
				'complex_name' => 'platforms',
				'show_admin_column' => true,
				),
			//制作人/游戏
		array(	'tax' => 'g_p_producer',
				'obj' => array( 'game' ),
				'label' => 'Producer',
				'slug' => 'g_p_producer',
				'complex_name' => 'producers',
				'show_admin_column' => false,
				),
			//音乐人/游戏
		array(	'tax' => 'g_p_musician',
				'obj' => array( 'game' ),
				'label' => 'Musician',
				'slug' => 'g_p_musician',
				'complex_name' => 'musicians',
				'show_admin_column' => false,
				),
			//音乐人/专辑
		array(	'tax' => 'a_p_musician',
				'obj' => array( 'album' ),
				'label' => 'Musician',
				'slug' => 'a_p_musician',
				'complex_name' => 'musicians',
				'show_admin_column' => true,
				),
			//制作人/专辑
		array(	'tax' => 'a_p_producer',
				'obj' => array( 'album' ),
				'label' => 'Producer',
				'slug' => 'a_p_producer',
				'complex_name' => 'producers',
				'show_admin_column' => false,
				),
			//专辑长度/专辑
		array(	'tax' => 'a_quantity',
				'obj' => array( 'album' ),
				'label' => 'Quantities',
				'slug' => 'a_quantity',
				'complex_name' => 'quantities',
				'show_admin_column' => false,
				),
		);
		$this->post_types = array(
			'movie' => array(
				'label' => 'Movies',
				'slug' => 'movie',
				'icon' => 'dashicons-video-alt',
				'menu_position' => 6,
				),
			'book' => array(
				'label' => 'Books',
				'slug' => 'book',
				'icon' => 'dashicons-book-alt',
				'menu_position' => 7,
				),
			'game' => array(
				'label' => 'Games',
				'slug' => 'game',
				'icon' => 'dashicons-laptop',
				'menu_position' => 8,
				),
			'album' => array(
				'label' => 'Albums',
				'slug' => 'album',
				'icon' => 'dashicons-album',
				'menu_position' => 9,
				),
		);
	}
	/********    外部函数 开始    ********/
	/******  直接调用的外部函数 开始  ******/
	/**
	 * 检查已经存在的分类法，注册分类法。
	 * @public
	 * @since 0.1.1
	 * @ref		bddb_init_actions()
	 */
	public function check_taxonomies(){
		//检查已经存在的分类法，处理既存数据。
		$this->tax_diff();
		foreach ($this->taxonomies as $chk_tax) {
			$labels = array( 
				'name'             => $chk_tax['label'],
				 'singular_name'    => $chk_tax['slug'],
				 'search_items'     => sprintf('Search %s',$chk_tax['complex_name']),
				 'popular_items'    => sprintf('Popular %s',$chk_tax['complex_name']),
				 'all_items'        => sprintf('All %s',$chk_tax['complex_name']),
				 'edit_item'        => sprintf('Edit %s',$chk_tax['label']),
				 'update_item'      => sprintf('Update %s',$chk_tax['label']),
				 'add_new_item'     => sprintf('Add New %s',$chk_tax['label']),
				 'new_item_name'    => sprintf('%s Name',$chk_tax['label']),
				 'add_or_remove_items'   => sprintf('Add or Remove %s',$chk_tax['label']),
				 'menu_name'        => ucfirst($chk_tax['complex_name']),
			);
			$arg = array (
				'label' => $chk_tax['label'],
				'labels' => $labels,
				'public' => false,
				'meta_box_cb' => false,			//metabox不直接上编辑页面，统一汇总后再上
				'show_ui' => true,
				'show_in_nav_menus' => false,
				'show_tagcloud' => false,		//不支持标签云
				'show_admin_column' => $chk_tax['show_admin_column'],
				'show_in_rest' => false,		//不支持REST
			);
			register_taxonomy($chk_tax['tax'],$chk_tax['obj'],$arg);
		};
	}

	/**
	 * 检查已经存在的种类。
	 * @public
	 * @ref		bddb_init_actions()
	 * @since 0.1.1
	 */
	public function check_types(){
		//确定每个种类支持的分类法，存入$this->$bddb_type['taxonomies']
		array_map(array($this, 'generte_type_taxonomies'), $this->taxonomies);
		$e = new BDDB_Editor();
		foreach( $this->post_types as $bddb_type) {
			$labels = array(
				'singular_name'			=> ucfirst($bddb_type['slug']),
				'add_new_item'			=> sprintf('Add new %s', ucfirst($bddb_type['slug'])),
				'all_items'				=> sprintf('All %s', $bddb_type['label']),
				'edit_item'				=> sprintf('Edit %s', ucfirst($bddb_type['slug'])),
				'search_items'			=> sprintf('Search %s', $bddb_type['label']),
			);
			$arg = array(
				'label'                 => $bddb_type['label'],
				'labels'                => $labels,
				'show_in_rest'          => false,	//不支持REST
				'register_meta_box_cb'  => array($e, 'add_meta_box'),
				'menu_position'         => $bddb_type['menu_position'],
				'menu_icon'           	=> $bddb_type['icon'],
				'supports'              => array('title', 'editor'/*, 'thumbnail'*/),
				'taxonomies'            => $bddb_type['taxonomies'],
				'has_archive'           => false,	//不生成前台归档页面
				'show_ui'				=> true,	//后台侧边栏显示
				'show_in_nav_menus'		=> true,	//后台鼠标悬停后显示子菜单
				'show_in_admin_bar'		=> false,
				'rewrite'               => array('feeds'=>false,'pages'=>false,'with_front'=>false),
			);
			register_post_type( $bddb_type['slug'], $arg);
		}
	}

	/**
	 * 后台初始化。
	 * @public
	 * @since 0.1.1
	 * @ref		bddb_admin_init()
	 */
	public function admin_init() {
		add_filter( 'plugin_action_links', array($this, 'add_settings_link_to_plugin_page'),10,2);
		add_action( 'wp_user_dashboard_setup', array($this, 'add_dashboard_widget'));
		add_action( 'wp_dashboard_setup', array($this, 'add_dashboard_widget'));
	}
	/******  直接调用的外部函数 结束  ******/

	/******  钩子调用的外部函数 开始  ******/
	/**
	 * Plugin页面追加配置选项。
	 * @public
	 * @param	array	$action_links	可执行标签，回调固定
	 * @param	string	$plugin_file	文件名，回调固定
	 * @ref		admin_init()
	 * @ref		filter：：plugin_action_links
	 * @return	array	$action_links
	 * @since 0.1.1
	 */
	public function add_settings_link_to_plugin_page($action_links, $plugin_file){
		if($plugin_file == BDDB_PLUGIN_BASE_NAME){
			$bddb_settings_link = '<a href="options-general.php?page=wp-boycott-douban-db/bddb-options.php">Settings</a>';
			array_push($action_links, $bddb_settings_link);
		}
		return $action_links;
	}

	/**
	 * 创建后台统计小工具。
	 * @public
	 * @ref		admin_init()
	 * @ref		action::wp_user_dashboard_setup
	 * @ref		action::add_dashboard_widget
	 * @mention	感觉两个钩子可以只留一个，待测
	 * @since 0.1.2
	 */
	public function add_dashboard_widget(){
		//下面这个函数的调用时机不能太早，不能在admin_init里直接调。
		wp_add_dashboard_widget( 'dashboard_bddb_recent', 'BDDb', array($this, 'dashboard_widget_div') );
	}
	

	/**
	 * dashboard页面创建统计小工具。
	 * @public
	 * @ref		add_dashboard_widget()
	 * @mention	可以做成option项，各个项目的配比也可以做成option项
	 * @since 0.1.2
	 */
	public function dashboard_widget_div() {
		echo '<div id="bddb-recent-widget">';
		$bddb_types = array_keys($this->post_types);
		$quary_args = array(
				'post_type' => $bddb_types,
				'numberposts' => 20,
				'post_status' => 'publish',
				'orderby' => 'modified',
				'order' => 'DESC',
		);
		//一年前
		$last_year_t = strtotime( '-1 year', current_time( 'timestamp' ) );
		$bddb_posts = get_posts($quary_args);
		if (is_wp_error($bddb_posts) || count($bddb_posts) == 0) {
			echo 'Noting found.';
		} else {
			$this->display_dashboard_block($bddb_posts, 'bddb');
			$exclude_ids = array_map(create_function('$o', 'return $o->ID;'), $bddb_posts);
			$quary_args['post__not_in'] = $exclude_ids;
			$quary_args['numberposts'] = 5;

			foreach ($bddb_types as $my_type) {
				$quary_args['post_type'] = $my_type;
				$bddb_posts = get_posts($quary_args);
				if (is_wp_error($bddb_posts) || count($bddb_posts) == 0) {
					continue;
				}
				$this->display_dashboard_block($bddb_posts, $my_type);
			}
		}
		
		echo '</div>';
	}
	/******  钩子调用的外部函数 结束  ******/
	/********    外部函数 结束    ********/

	/********    私有函数 开始    ********/

	/**
	 * array_map回调，获取type支持的taxonomies。
	 * @protected
	 * @param	array	$tax_item	单个taxonomy
	 * @ref		check_types()
	 * @since 0.1.1
	 */
	protected function generte_type_taxonomies($tax_item) {
		if (is_array($tax_item['obj'])) {
			foreach ($tax_item['obj'] as $type_name){
				if (isset($this->post_types[$type_name])) {
					$this->post_types[$type_name]['taxonomies'][] = $tax_item['slug'];
				}
			}
		}
	}

	/**
	 * 显示不同种类的小工具区域。
	 * @private
	 * @param	array	$pts	post objects
	 * @param	string	$post_type,'bddb'时不按分类，取最新更新；其余取最新发布。
	 * @ref		dashboard_widget_div()
	 * @since 0.1.2
	 */
	private function display_dashboard_block($pts, $post_type) {
		$last_year_t = strtotime( '-1 year', current_time( 'timestamp' ) );
		if ('bddb' == $post_type){
			$block_id = 'bddb-recent-dashboard';
			$h3 = ' Recent Records ';
		} else {
			$block_id = $post_type . '-recent-dashboard';
			$h3 = " Recent {$post_type}s ";
		}
		echo '<div id="' . $post_type . '-recent-dashboard" class="activity-block">';
		echo "<h3>{$h3}</h3><ul>";
		foreach ($pts as $p) {
			$draft_or_post_title = _draft_or_post_title($p->ID);
			if ('bddb' == $post_type) {
				$m_time_t = strtotime($p->post_modified);
			}else{
				$m_time_t = strtotime($p->post_date);
			}
			if ($m_time_t > $last_year_t) {
				$relative = date('m-d H:i', $m_time_t);
			}else{
				$relative = date('Y-m-d', $m_time_t);
			}
			printf(
				'<li><span>%1$s</span> <a href="%2$s" aria-label="%3$s">%4$s</a></li>',
				$relative,
				get_edit_post_link($p->ID),
				esc_attr( sprintf( __( 'Edit &#8220;%s&#8221;' ), $draft_or_post_title ) ),
				$draft_or_post_title
			);
		}
		echo '</ul></div>';
	}

	/**
	 * 比较分类法版本号，版本号不同时调用对应的版本升级函数。
	 * @private
	 * @since 0.1.5
	 */
	private function tax_diff(){
		$op = new BDDB_Settings();
		$stored_tax_version = $op->get_tax_version();
		if (empty($stored_tax_version)) {
			return;
		}
		//可升级版本，没有太好的办法保存，暂时写死。
		$available_vals[] = '20220101';
		$old_val = intval($stored_tax_version);
		foreach ($available_vals as $new_val_str){
			if ($old_val < intval($new_val_str)) {
				if (is_callable(array($this, "tax_update_{$new_val_str}"))){
					call_user_func(array($this, "tax_update_{$new_val_str}"), $op);
				}
			}
		}
	}

	/**
	 * taxo升级函数20220101。
	 * @protected
	 * @param	object		$op setting类，用于操作option信息。
	 * @since 0.1.7
	 */
	protected function tax_update_20220101($op){
		//函数在check_taxonomy前被调用，所以要先注册一下准备删除的'country'。
		register_taxonomy('country',array('movie','book','game','album'));
		//函数在check_taxonomy前被调用，先注册一次
		$taxonomies = array(
			array('tax' => 'm_region',
				'obj' => array( 'movie' ),
				'label' => 'Region',
				'slug' => 'm_region',
				),
			array('tax' => 'b_region',
				'obj' => array( 'book' ),
				'label' => 'Region',
				'slug' => 'b_region',
				),
			array('tax' => 'g_region',
				'obj' => array( 'game' ),
				'label' => 'Region',
				'slug' => 'g_region',
				),
			array('tax' => 'a_region',
				'obj' => array( 'album' ),
				'label' => 'Region',
				'slug' => 'a_region',
				),
		);
		foreach ($taxonomies as $chk_tax) {
			$labels = array( 
				 'name'             => 'Region',
				 'singular_name'    => 'region',
				 'search_items'     => 'Search Regions',
				 'popular_items'    => 'Popular Regions',
				 'all_items'        => 'All Regions',
				 'edit_item'        => 'Edit Region',
				 'update_item'      => 'Update Region',
				 'add_new_item'     => 'Add New Region',
				 'new_item_name'    => 'Region Name',
				 'add_or_remove_items'   => 'Add or Remove Regions',
				 'menu_name'        => 'Regions',
			);
			$arg = array (
				'label' => $chk_tax['label'],
				'labels' => $labels,
				'public' => false,
				'meta_box_cb' => false,
				'show_ui' => true,
				'show_in_nav_menus' => false,
				'show_tagcloud' => false,
				'show_admin_column' => true,
				'show_in_rest' => false,
			);
			register_taxonomy($chk_tax['tax'],$chk_tax['obj'],$arg);
			$args = array(
				'post_type' => $chk_tax['obj'][0],
				'numberposts' => -1,
				'post_status' => 'any',
				'fields' => 'ids',
			);
			$ids = get_posts($args);
			if (is_array($ids)&&count($ids)>0) {
				foreach ($ids as $post_id) {
					$val_str = '';
					$str_array = wp_get_post_terms($post_id, 'country', array('fields'=>'names'));
					if (is_wp_error($str_array))
						continue;
					if (count($str_array)>1) {
						$val_str = implode(', ', $str_array);
					} elseif(count($str_array) == 1) {
						$val_str =trim($str_array[0]);
					}
					wp_set_post_terms($post_id, $val_str, $chk_tax['tax']);
					//这个函数莫名其妙被调用两次，如果把country清空，那么第二次再被调用的时候新拷贝的region也会被清空，就白做了。
					//下次写升级函数继续研究。
					//wp_set_post_terms($post_id, '', 'country');
				}
			}
		};
		$op->update_tax_version('20220101');
		//这里好像应该调用unregister_taxonomy。
	}
	/********    私有函数 结束    ********/
};