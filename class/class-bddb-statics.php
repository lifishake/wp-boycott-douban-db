<?php
/**
 * 固定数据类
 */
class BDDB_Statics {
	private $taxonomies = false;
	private $post_types = false;
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

		//国家/共通
        array('tax' => 'country',
              'obj' => array( 'movie','book','game','album' ),
              'label' => 'Country',
              'slug' => 'country',
              'complex_name' => 'countries',
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
		array(	'tax' => 'a_quantities',
				'obj' => array( 'album' ),
				'label' => 'Quantities',
				'slug' => 'a_quantities',
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
	public function check_taxonomies(){

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
				'meta_box_cb' => false,
				'show_ui' => true,
				'show_in_nav_menus' => false,
				'show_tagcloud' => false,
				'show_admin_column' => $chk_tax['show_admin_column'],
				'show_in_rest' => false,
			);
			register_taxonomy($chk_tax['tax'],$chk_tax['obj'],$arg);
    	};
	}
	public function check_types(){
		array_map(array($this, 'generte_type_taxonomies'), $this->taxonomies);
		foreach( $this->post_types as $bddb_type) {
			$labels = array(
				'singular_name'			=> ucfirst($bddb_type['slug']),
				'add_new_item'         => sprintf('Add new %s', ucfirst($bddb_type['slug'])),
				'all_items'				=> sprintf('All %s', $bddb_type['label']),
				'edit_item'				=> sprintf('Edit %s', ucfirst($bddb_type['slug'])),
				'search_items'				=> sprintf('Search %s', $bddb_type['label']),
			);
			$arg = array(
				'label'                 => $bddb_type['label'],
				'labels'                => $labels,
				'public'                => true,
				'publicly_queryable'    => false,
				'exclude_from_search'   => true,
				'show_in_rest'          => false,
				'register_meta_box_cb'  => array($this, 'add_metabox_by_type'),
				'menu_position'         => $bddb_type['menu_position'],
				'menu_icon'           	=> $bddb_type['icon'],
				'supports'              => array('title', 'editor'),
				'taxonomies'            => $bddb_type['taxonomies'],
				'has_archive'           => false,
				'show_ui'				=> true,
				'query_var'				=> $bddb_type['slug'],
				'rewrite'               => array('feeds'=>false,'pages'=>false,'with_front'=>false),
			);
			register_post_type( $bddb_type['slug'], $arg);
		}
	}
	public function admin_init() {
		add_filter( 'plugin_action_links', array($this, 'add_settings_link_to_plugin_page'),10,2);
		add_action( 'wp_user_dashboard_setup', array($this, 'add_dashboard_widget'));
		add_action( 'wp_dashboard_setup', array($this, 'add_dashboard_widget'));
		add_filter( "manage_edit-country_columns", array($this, 'redefine_country_header'));
		add_filter( 'manage_country_custom_column',array($this, 'print_country_count_by_type'),10,3 );
	}
	protected function generte_type_taxonomies($tax_item) {
		if (is_array($tax_item['obj'])) {
			foreach ($tax_item['obj'] as $type_name){
				if (isset($this->post_types[$type_name])) {
					$this->post_types[$type_name]['taxonomies'][] = $tax_item['slug'];
				}
			}
		}
	}
	
	//注册钩子用
	/* Plugin页面追加配置选项 */
	public function add_settings_link_to_plugin_page($action_links, $plugin_file){
		if($plugin_file == BDDB_PLUGIN_BASE_NAME){
			$bddb_settings_link = '<a href="options-general.php?page=wp-boycott-douban-db/bddb-options.php">Settings</a>';
			array_push($action_links, $bddb_settings_link);
		}
		return $action_links;
	}
	
	/*自定义post_type的meta_box的回调函数*/
	public function add_metabox_by_type($pt){
		$post_type = $pt->post_type;
		$te = new BDDB_Editor($post_type);
		$te->add_meta_box();
	}
	
	/* 创建后台统计小工具 */
	public function add_dashboard_widget(){
		wp_add_dashboard_widget( 'dashboard_bddb_recent', 'BDDb', array($this, 'dashboard_widget_div') );
	}
	
	/*  */
	public function redefine_country_header($columns) {
		unset($columns['posts']);
		$columns['real_count'] = "实数";
		$columns['posts'] = "Count";
		return $columns;
	}
	/**/
	public function print_country_count_by_type( $value, $column_name, $tax_id ){
		if ('real_count' !== $column_name) {
			echo $value;
		}
		global $post_type;
		$args = array(
				'post_type' => $post_type,
				'numberposts' => -1,
				'post_status' => 'publish',
				'fields' => 'ids',
				'tax_query'=> array(
					array(
						'taxonomy' => 'country',
						'terms' => $tax_id,
						),
				),
			);
		$ids = get_posts($args);
		echo count($ids);
	}
	/*后台总统计表*/
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
	private function tax_diff(){
		$op = new BDDB_Settings();
		$stored_tax_version = $op->get_tax_version();
		if (empty($stored_tax_version)) {
			return;
		}
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
	protected function tax_update_20220101($op){
		register_taxonomy('country',array('movie','book','game','album'));
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
					wp_set_post_terms($post_id, '', 'country');
				}
			}
    	};
		$op->update_tax_version('20220101');
	}
};