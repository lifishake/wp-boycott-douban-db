<?php

/**
 * Plugin Name: Boycott Douban Database
 * Plugin URI:  http://pewae.com
 * GitHub Plugin URI: https://github.com/lifishake/bddb
 * Description: 抵制源于喜爱。既然无法改变它，那就自己创造一个。
 * Author:      lifishake
 * Author URI:  http://pewae.com
 * Version:     0.0.9
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
require_once( BDDB_PLUGIN_DIR . '/class/class-bddb-metaboxes.php');
require_once( BDDB_PLUGIN_DIR . '/class/class-bddb-douban-fecther.php');
require_once( BDDB_PLUGIN_DIR . '/class/class-bddb-image.php');
require_once( BDDB_PLUGIN_DIR . '/class/class-bddb-templates.php');

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
              'show_admin_column' => true,
              ),
            //种类/书
        array('tax' => 'b_genre',
              'obj' => array( 'book' ),
              'label' => 'Genre',
              'slug' => 'b_genre',
              'complex_name' => 'genres',
              'show_admin_column' => true,
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
            //if (!taxonomy_exists($chk_tax['tax'])) {
                $labels = array( 'name'             => $chk_tax['label'],
                                 'singular_name'    => $chk_tax['slug'],
                                 'search_items'     => sprintf('Search %s',$chk_tax['complex_name']),
                                 'popular_items'    => sprintf('Popular %s',$chk_tax['complex_name']),
                                 'all_items'        => sprintf('All %s',$chk_tax['complex_name']),
                                 'edit_item'        => sprintf('Edit %s',$chk_tax['label']),
                                 'update_item'      => sprintf('Update %s',$chk_tax['label']),
                                 'add_new_item'     => sprintf('Add New %s',$chk_tax['label']),
                                 'new_item_name'    => sprintf('%s Name',$chk_tax['label']),
                                 'add_or_remove_items'   => sprintf('Add or Remove %s',$chk_tax['label']),
                                 'choose_from_most_used'=> sprintf('Choose from most used %s',strtolower($chk_tax['complex_name'])),'separate_items_with_commas' => sprintf('Separate %s with commas',strtolower($chk_tax['complex_name'])),
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
            //}
    };
}

/**/
function bddb_add_common_meta_box($post) {
	$post_type = $post->post_type;
	$settings = array('base_url' => BDDB_GALLERY_URL, 'base_dir' => BDDB_GALLERY_DIR, 'plugin_url' => BDDB_PLUGIN_URL);
	if ('movie' === $post_type) {
		$template = new BDDB_T_Movie($settings);
	} elseif ('book'=== $post_type) {
		$template = new BDDB_T_Book($settings);
	} elseif ('game'=== $post_type) {
		$template = new BDDB_T_Game($settings);
	} elseif ('album' === $post_type) {
		$template = new BDDB_T_Album($settings);
	} else {
		return;
	}
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
            'add_new_item'         => sprintf('Add new %s', $bddb_type['slug']),
			'all_items'				=> sprintf('All %s', $bddb_type['label']),
			'edit_item'				=> sprintf('Edit %s', $bddb_type['label']),
        );
        $arg = array(
            'label'                 => $bddb_type['label'],
            'labels'                => $labels,
            'public'                => true,
            'publicly_queryable'    => true,
            'exclude_from_search'   => true,
            'show_in_rest'          => false,
            'register_meta_box_cb'  => 'bddb_add_common_meta_box',
            'menu_position'         => $bddb_type['menu_position'],
            'menu_icon'           => $bddb_type['icon'],
            'supports'              => array('title', 'editor'),
            'taxonomies'            => $bddb_type['taxonomies'],
            'has_archive'           => true,
            'rewrite'               => array('feeds'=>false,'pages'=>false,'with_front'=>false),
        );
        register_post_type( $bddb_type['slug'], $arg);
    }
}

function bddb_create_dir($dir) {
	if (file_exists ($dir)) {
        if (! is_writeable ( $dir )) {
            @chmod ( $dir, '511' );
        }
    } else {
        @mkdir ( $dir, '511', true );
    }
}

/*插件激活*/
function bddb_plugin_activation() {
	$dirs = array(BDDB_GALLERY_DIR, BDDB_GALLERY_DIR."thumbnails/");
	foreach ($dirs as $dir) {
		bddb_create_dir($dir);
	}
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
add_filter('plugin_action_links','bddb_settings_link',10,2);

/*变量初期化*/
add_action('plugins_loaded', 'bddb_init', 11);
function bddb_init()
{
    global $wpdb;

}

add_action('init', 'bddb_init_actions', 11);
/* Plugin页面追加配置选项 */
function bddb_init_actions()
{   
    bddb_check_taxonomy();
    bddb_check_post_type();
	if (is_admin()) {
		add_action('admin_init','bddb_admin_init');
	}
	//js和css加载
    add_action( 'wp_enqueue_scripts', 'bddb_scripts' );
	//Quick Tag追加
	add_shortcode('bddbitem', 'bddb_shortcode_transfer');
    add_shortcode('bddbr', 'bddb_real_transfer');
	//manage_$post_type_posts_custom_column 
}


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

function bddb_get_pic() {
	if (!isset($_POST['nonce']) || !isset($_POST['id']) || !isset($_POST['ptype']) || !isset($_POST['piclink']) ) {
		die();
	}
	if ( !wp_verify_nonce($_POST['nonce'],"bddb-get-pic-".$_POST['id'])) { 
		die();
	}
	$poster_name = sprintf("%s_%013d.jpg", $_POST['ptype'], $_POST['id']);
	$poster_full_name = BDDB_GALLERY_DIR.$poster_name;
	$thumbnail_full_name = BDDB_GALLERY_DIR.'thumbnails/'.$poster_name;
	if (file_exists($poster_full_name)) {
		unlink($poster_full_name);
	}
	if (file_exists($thumbnail_full_name)) {
		unlink($thumbnail_full_name);
	}
	$response = @wp_remote_get( 
            htmlspecialchars_decode($_POST['piclink']), 
            array( 
                'timeout'  => 3000, 
                'stream'   => true, 
                'filename' => $poster_full_name 
            ) 
        );
	if ( is_wp_error( $response ) )
	{
		return false;
	}
	$full_width = 400;
	$full_height = 592;
	if ('album' == $_POST['ptype']) {
		$full_height = 400;
	}
	$image = new Bddb_SimpleImage();
	$image->load($poster_full_name);
	$image->resize($full_width, $full_height);
	$image->save($poster_full_name);
	$image->resize($full_width/4, $full_height/4);
	$image->save($thumbnail_full_name);
}

function bddb_get_scovers(){
    if (!isset($_POST['nonce']) || !isset($_POST['id']) || !isset($_POST['ptype']) || !isset($_POST['slinks']) ) {
		die();
	}
	if ( !wp_verify_nonce($_POST['nonce'],"bddb-get-scovers-".$_POST['id'])) { 
		die();
	}
    $tl = new BDDB_Common_Template($_POST['ptype']);
    $po = get_post($_POST['id']);
    $obj_names = $tl->get_poster_names($po);
	print_r($obj_names);
	$slinks = $_POST['slinks'];
	print_r($slinks);
	$parts = explode(";", $slinks);
	print_r($parts);
    $serial_count = min(count($parts),18,$_POST['stotal']);
	for($i=0;$i<18;++$i) {
		//$dest = sprintf($obj_names['scover_name_template'],$i);
		$dest = BDDB_GALLERY_DIR.'thumbnails/'. sprintf("%s_%013d_%02d.jpg", $po->post_type, $po->ID, $i);
		if (file_exists($dest))
			unlink($dest);
	}
	for($i=0;$i<$serial_count;++$i) {
		//$dest = sprintf($obj_names['scover_name_template'],$i);
		$dest = BDDB_GALLERY_DIR.'thumbnails/'. sprintf("%s_%013d_%02d.jpg", $po->post_type, $po->ID, $i);
		$src = $parts[$i];
		$response = @wp_remote_get( 
            htmlspecialchars_decode($src), 
            array( 
                'timeout'  => 3000, 
                'stream'   => true, 
                'filename' => $dest 
            ) 
        );
		if ( is_wp_error( $response ) )
		{
			continue;
		}
		$image = new Bddb_SimpleImage();
		$image->load($dest);
		$image->resize(100, 148);
		$image->save($dest);
	}
}

function bddb_admin_init() {
	$settings = array('base_url' => BDDB_GALLERY_URL, 'base_dir' => BDDB_GALLERY_DIR, 'plugin_url' => BDDB_PLUGIN_URL);
	$tm = new BDDB_T_Movie($settings);
	$tb = new BDDB_T_Book($settings);
	$tg = new BDDB_T_Game($settings);
	$ta = new BDDB_T_Album($settings);
	add_action ( 'save_post_movie', array($tm, 'update_all_items'), 10, 2);
	add_action ( 'save_post_book', array($tb, 'update_all_items'), 10, 2);
	add_action ( 'save_post_game', array($tg, 'update_all_items'), 10, 2);
	add_action ( 'save_post_album', array($ta, 'update_all_items'), 10, 2);
	add_filter ( 'wp_insert_post_data', array($tm, 'generate_content'), 10, 2);
	add_filter ( 'wp_insert_post_data', array($tb, 'generate_content'), 10, 2);
	add_filter ( 'wp_insert_post_data', array($tg, 'generate_content'), 10, 2);
	add_filter ( 'wp_insert_post_data', array($ta, 'generate_content'), 10, 2);
	add_action( 'admin_enqueue_scripts', 'bddb_admin_scripts' );
	add_action( 'wp_ajax_bddb_douban_fetch', 'bddb_douban_fetch' );
	add_action( 'wp_ajax_bddb_get_pic', 'bddb_get_pic' );
    add_action( 'wp_ajax_bddb_get_scovers', 'bddb_get_scovers' );
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
    wp_deregister_style( 'open-sans' );
    wp_register_style( 'open-sans', false );
}

