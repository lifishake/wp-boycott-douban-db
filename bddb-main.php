<?php

/**
 * Plugin Name: Boycott Douban Database
 * Plugin URI:  http://pewae.com
 * GitHub Plugin URI: https://github.com/lifishake/bddb
 * Description: 抵制源于喜爱。既然无法改变它，那就自己创造一个。
 * Author:      lifishake
 * Author URI:  http://pewae.com
 * Version:     0.1.4
 * License:     GNU General Public License 3.0+ http://www.gnu.org/licenses/gpl.html
 */

/*宏定义*/
define('BDDB_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define('BDDB_PLUGIN_DIR', plugin_dir_path( __FILE__ ) ) ;
define('BDDB_GALLERY_URL',home_url('/',is_ssl()?'https':'http').'wp-content/poster_gallery/');
define('BDDB_GALLERY_DIR', ABSPATH.'wp-content/poster_gallery/');
define('BDDB_TAX_VER', '20211103');
define('BDDB_META_VER', '20211103');

register_activation_hook( __FILE__, 'bddb_plugin_activation' );
register_deactivation_hook( __FILE__,'bddb_plugin_deactivation' );
register_uninstall_hook(__FILE__, 'bddb_plugin_uninstall');

require_once( BDDB_PLUGIN_DIR . '/bddb-funcs.php');
require_once( BDDB_PLUGIN_DIR . '/class/class-bddb-editor.php');
require_once( BDDB_PLUGIN_DIR . '/class/class-bddb-douban-fecther.php');
require_once( BDDB_PLUGIN_DIR . '/class/class-bddb-image.php');
require_once( BDDB_PLUGIN_DIR . '/class/class-bddb-templates.php');
require_once( BDDB_PLUGIN_DIR . '/class/class-bddb-settings.php');

function bddb_is_debug_mode()
{
    if (isset( $_SERVER['PHPRC'] ) && strpos($_SERVER['PHPRC'], "xampp" ) > 0)
    {
        return 1;
    }
    return 0;
}

/* 打log用 */
function bddb_log()
{
    //echo '<pre>'.$any.'</pre>';
	print_r(debug_backtrace());
}

/* 检查分类法 */
function bddb_check_taxonomy()
{
    $check_taxonomies = array(
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
    foreach ($check_taxonomies as $chk_tax) {
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

/*自定义post_type的meta_box的回调函数*/
function bddb_add_common_meta_box($post) {
	$post_type = $post->post_type;
	$template = new BDDB_Editor($post_type);
	$template->add_meta_box();
}

/*检查自定义类型*/
function bddb_check_post_type() {
    $bddb_post_types = array(
        array(
        'label' => 'Movies',
        'slug' => 'movie',
        'taxonomies' => array('country', 'm_genre'),
        'icon' => 'dashicons-video-alt',
        'menu_position' => 6,
        ),
        array(
        'label' => 'Books',
        'slug' => 'book',
        'taxonomies' => array('country', 'b_genre'),
        'icon' => 'dashicons-book-alt',
        'menu_position' => 7,
        ),
        array(
        'label' => 'Games',
        'slug' => 'game',
        'taxonomies' => array('country', 'g_genre'),
        'icon' => 'dashicons-laptop',
        'menu_position' => 8,
        ),
        array(
        'label' => 'Albums',
        'slug' => 'album',
        'taxonomies' => array('country', 'a_genre'),
        'icon' => 'dashicons-album',
        'menu_position' => 9,
        ),
    );
    foreach( $bddb_post_types as $bddb_type) {
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
            'register_meta_box_cb'  => 'bddb_add_common_meta_box',
            'menu_position'         => $bddb_type['menu_position'],
            'menu_icon'           => $bddb_type['icon'],
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

/*创建目录*/
function bddb_create_dir($dir) {
	if (file_exists ($dir)) {
        if (! is_writeable ( $dir )) {
            @chmod ( $dir, '511' );
        }
    } else {
        @mkdir ( $dir, '511', true );
    }
}

/*创建必须文件*/
function bddb_create_nopic($width, $height) {
	if ($width == $height){
		$src = sprintf("%s/img/nocover_square.png", BDDB_PLUGIN_DIR);
	}else{
		$src = sprintf("%s/img/nocover_oblone.png", BDDB_PLUGIN_DIR);
	}
	$dest = sprintf("%s/img/nocover_%s_%s.png", BDDB_PLUGIN_DIR, $width, $height);
	if (file_exists ($dest)) {
		return;
	}
	print_r($dest);
	$image = new Bddb_SimpleImage();
	$image->load($src);
	$image->resize($width,$height);
	$image->save($dest);
}

/*插件激活*/
function bddb_plugin_activation() {
	
}

/*检查路径，检查默认文件*/
function bddb_check_paths(){
	$s = new BDDB_Settings();
	$dir_o = $s->get_default_folder();
	$gallery_dir= ABSPATH.$dir_o;
	$thumb_dir = $gallery_dir."thumbnails/";
	bddb_create_dir($gallery_dir);
	bddb_create_dir($thumb_dir);
	bddb_create_nopic($s->get_poster_width(),$s->get_poster_width());
	bddb_create_nopic($s->get_poster_width(),$s->get_poster_height());
	bddb_create_nopic($s->get_thumbnail_width(),$s->get_thumbnail_width());
	bddb_create_nopic($s->get_thumbnail_width(),$s->get_thumbnail_height());
}

/*插件反激活*/
function bddb_plugin_deactivation()
{

}

/*插件卸载*/
function bddb_plugin_uninstall()
{
}

/*配置画面*/
if (is_admin())
{
    require_once( BDDB_PLUGIN_DIR . '/bddb-options.php');
}

/* Plugin页面追加配置选项 */
function bddb_settings_link($action_links, $plugin_file){
    if($plugin_file == plugin_basename(__FILE__)){
        $bddb_settings_link = '<a href="options-general.php?page=wp-boycott-douban-db/bddb-options.php">Settings</a>';
        array_push($action_links, $bddb_settings_link);
    }
    return $action_links;
}

/*变量初期化， 更早*/
add_action('plugins_loaded', 'bddb_init', 11);
function bddb_init()
{
    bddb_check_paths();
	add_action('admin_init','bddb_admin_init');

}

add_action('init', 'bddb_init_actions', 11);

/* Plugin页面追加配置选项 */
function bddb_init_actions()
{   
	bddb_check_taxonomy();
    bddb_check_post_type();
	//js和css加载
    add_action( 'wp_enqueue_scripts', 'bddb_scripts' );
	//Quick Tag追加
    add_shortcode('bddbr', 'bddb_real_transfer');
}

/*取豆瓣信息的ajax回调函数*/
function bddb_douban_fetch() {
	$resp = array('title' => 'here is the title', 'content' => 'finished') ;
	if (!isset($_GET['nonce']) || !isset($_GET['id']) || !isset($_GET['ptype']) || !isset($_GET['doulink']) ) {
		die();
	}
	if ( !wp_verify_nonce($_GET['nonce'],"douban-spider-".$_GET['id'])) {
		die();
	}
	if (!in_array(($_GET['ptype']), array('movie', 'book', 'album'))){
		die();
	}
	$post_id = $_GET['id'];
	$link = $_GET['doulink'];
	$fecther = new BDDB_DoubanFetcher($_GET['ptype']);
	$got = $fecther->fetch($link);
	$resp['result'] = $got['content'];
	wp_send_json($resp) ;

}

/*取系列多缩略图的ajax回调函数*/
function bddb_redefine_country_header($columns) {
	unset($columns['posts']);
	$columns['real_count'] = "实数";
	$columns['posts'] = "Count";
	return $columns;
}

function bddb_country_content( $value, $column_name, $tax_id ){
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

function bddb_admin_init() {
	$t = new BDDB_Editor();
	add_action ( 'save_post', array($t, 'update_all_items'), 10, 2);
	add_filter ( 'wp_insert_post_data', array($t, 'generate_content'), 10, 2);
	add_action( 'admin_enqueue_scripts', 'bddb_admin_scripts' );
	add_action( 'wp_ajax_bddb_douban_fetch', 'bddb_douban_fetch' );
	add_action( 'wp_ajax_bddb_get_pic', array($t, 'download_pic') );
    add_action( 'wp_ajax_bddb_get_scovers', array($t, 'download_serial_pics'));
	add_filter( 'manage_posts_columns', array($t,'get_admin_edit_headers'), 10, 2);
	add_action( 'manage_movie_posts_custom_column', array($t, 'manage_movie_admin_columns'), 10, 2);
    add_action( 'manage_book_posts_custom_column', array($t, 'manage_book_admin_columns'), 10, 2);
	add_action( 'manage_game_posts_custom_column', array($t, 'manage_game_admin_columns'), 10, 2);
	add_action( 'manage_album_posts_custom_column', array($t, 'manage_album_admin_columns'), 10, 2);
	add_filter( "manage_edit-country_columns", 'bddb_redefine_country_header');
	add_filter( 'manage_country_custom_column','bddb_country_content',10,3 );
    add_filter( 'manage_edit-movie_sortable_columns', array($t, 'add_movie_sortable_columns'));
    add_filter( 'manage_edit-book_sortable_columns', array($t, 'add_book_sortable_columns'));
    add_filter( 'manage_edit-game_sortable_columns', array($t, 'add_game_sortable_columns'));
    add_filter( 'manage_edit-album_sortable_columns', array($t, 'add_album_sortable_columns'));
	add_action( 'wp_user_dashboard_setup','bddb_dashboard_widget');
	add_action( 'wp_dashboard_setup','bddb_dashboard_widget');
	add_action( 'pre_get_posts', array($t, 'sort_custom_column_query') );
	add_filter('plugin_action_links','bddb_settings_link',10,2);
}


function bddb_dashboard_widget() {
	$t = new BDDB_Editor();
	wp_add_dashboard_widget( 'dashboard_bddb_recent', 'BDDb', array($t, 'dashboard_widget_div') );
}

function bddb_scripts() {
	if (is_page(array('moviesgallery','booksgallery','gamesgallery','albumsgallery'))) {
		wp_enqueue_script( 'bddb-fancy', BDDB_PLUGIN_URL . 'js/fancybox.umd.js', array(), '20211123', true );
		wp_enqueue_script( 'bddb-color-thief', BDDB_PLUGIN_URL . 'js/color-thief.js', array(), '20211123', true );
		wp_enqueue_script( 'bddb-fancy-func', BDDB_PLUGIN_URL . 'js/fancygallery.js', array(), '20211123', true );
		wp_enqueue_style( 'bddb-boxstyle', BDDB_PLUGIN_URL . 'css/fancybox.css' );
		if (is_page('albumsgallery')) {
			wp_enqueue_style( 'bddb-gallery-pagestyle', BDDB_PLUGIN_URL . 'css/bddb-fancy-square.css' );
		}else {
			wp_enqueue_style( 'bddb-gallery-pagestyle', BDDB_PLUGIN_URL . 'css/bddb-fancy-oblong.css' );
		}
		wp_enqueue_style( 'bddb-gallery-boxstyle', BDDB_PLUGIN_URL . 'css/bddb-fancy-gallery.css' );
	}
	wp_enqueue_style( 'bddb-style-front', BDDB_PLUGIN_URL . 'css/bddb.css' );
}

/* 统一处理后台相关的脚本 */
function bddb_admin_scripts() {
    wp_enqueue_script('bddb-js-admin', BDDB_PLUGIN_URL . 'js/bddb-admin.js', array(), '20211110', true);
    wp_enqueue_style( 'bddb-adminstyle', BDDB_PLUGIN_URL . 'css/bddb-admin.css' );
    wp_deregister_style( 'open-sans' );
    wp_register_style( 'open-sans', false );
}

