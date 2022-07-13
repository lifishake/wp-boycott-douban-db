<?php

/**
 * Plugin Name: Boycott Douban Database
 * Plugin URI:  http://pewae.com
 * GitHub Plugin URI: https://github.com/lifishake/wp-boycott-douban-db
 * Description: 抵制源于喜爱。既然无法改变它，那就自己创造一个。
 * Author:      lifishake
 * Author URI:  http://pewae.com
 * Version:     0.5.3
 * License:     GNU General Public License 3.0+ http://www.gnu.org/licenses/gpl.html
 */

/*宏定义*/
define('BDDB_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define('BDDB_PLUGIN_DIR', plugin_dir_path( __FILE__ ) ) ;
define ('BDDB_PLUGIN_BASE_NAME', plugin_basename(__FILE__));
define('BDDB_GALLERY_URL',home_url('/',is_ssl()?'https':'http').'wp-content/poster_gallery/');
//define('BDDB_GALLERY_DIR', ABSPATH.'wp-content/poster_gallery/');
define('BDDB_TAX_VER', '20220101');
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
require_once( BDDB_PLUGIN_DIR . '/class/class-bddb-statics.php');

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
	$dir_o = BDDB_Settings::get_default_folder();
	$gallery_dir= ABSPATH.$dir_o;
	$thumb_dir = $gallery_dir."thumbnails/";
	bddb_create_dir($gallery_dir);
	bddb_create_dir($thumb_dir);
	bddb_create_nopic(BDDB_Settings::get_poster_width(),BDDB_Settings::get_poster_width());
	bddb_create_nopic(BDDB_Settings::get_poster_width(),BDDB_Settings::get_poster_height());
	bddb_create_nopic(BDDB_Settings::get_thumbnail_width(),BDDB_Settings::get_thumbnail_width());
	bddb_create_nopic(BDDB_Settings::get_thumbnail_width(),BDDB_Settings::get_thumbnail_height());
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

/*变量初期化， 更早*/
add_action('plugins_loaded', 'bddb_init', 11);
function bddb_init()
{
    bddb_check_paths();
	add_action('admin_init','bddb_admin_init');
	add_action('init', 'bddb_init_actions', 11);
}



/* Plugin页面追加配置选项 */
function bddb_init_actions()
{   
	BDDB_Statics::check_taxonomies();
	BDDB_Statics::check_types();
	//js和css加载
    add_action( 'wp_enqueue_scripts', 'bddb_scripts' );
	//Quick Tag追加
	add_shortcode('bddbr', 'qt_show_record');
	//ajax 显示 page 回调
	add_action('wp_ajax_bddb_next_gallery_page', 'ajax_get_gallery_page');
	add_action('wp_ajax_nopriv_bddb_next_gallery_page', 'ajax_get_gallery_page');
}

function qt_show_record($atts, $content = null) {
	extract( $atts );
	$post_type = get_post_type($id);
	$ret = '';
	if ('book' == $post_type) {
		$ret = BDDB_Book::getInstance()->show_record($atts, $content);
	} elseif('movie' == $post_type) {
		$ret = BDDB_Movie::getInstance()->show_record($atts, $content);
	} elseif('game' == $post_type) {
		$ret = BDDB_Game::getInstance()->show_record($atts, $content);
	} elseif('album' == $post_type) {
		$ret = BDDB_Album::getInstance()->show_record($atts, $content);
	}
	return $ret;
}

/*取下一页的ajax回调函数*/
function ajax_get_gallery_page() {
	if (!isset($_POST['nonce']) || !isset($_POST['pid']) || !isset($_POST['type']) || !isset($_POST['nobj']) ) {
		wp_die();
	}
	if ('book' == $_POST['type']) {
		BDDB_Book::getInstance()->ajax_get_gallery_page();
	} elseif('movie' == $_POST['type']) {
		BDDB_Movie::getInstance()->ajax_get_gallery_page();
	} elseif('game' == $_POST['type']) {
		BDDB_Game::getInstance()->ajax_get_gallery_page();
	} elseif('album' == $_POST['type']) {
		BDDB_Album::getInstance()->ajax_get_gallery_page();
	}
}

/*取豆瓣信息的ajax回调函数*/
//TODO 放进类中
function bddb_ajax_douban_fetch() {
	$resp = array('title' => 'here is the title', 'content' => 'finished') ;
	if (!isset($_GET['nonce']) || !isset($_GET['id']) || !isset($_GET['ptype']) || !isset($_GET['doulink']) ) {
		wp_die();
	}
	if ( !wp_verify_nonce($_GET['nonce'],"douban-spider-".$_GET['id'])) {
		wp_die();
	}
	if (!BDDB_Statics::is_valid_type($_GET['ptype'])){
		wp_die();
	}
	$post_id = $_GET['id'];
	$link = $_GET['doulink'];
	$fecther = new BDDB_DoubanFetcher($_GET['ptype']);
	$got = $fecther->fetch($link);
	$resp['result'] = $got['content'];
	wp_send_json($resp) ;
	wp_die();

}

//后台初始化
function bddb_admin_init() {
	$t = new BDDB_Editor();
	add_action( 'admin_enqueue_scripts', 'bddb_admin_scripts' );
	add_action( 'wp_ajax_bddb_douban_fetch', 'bddb_ajax_douban_fetch' );
	BDDB_Statics::admin_init();
	$t->admin_init();
}

//js和css初始化
function bddb_scripts() {
	if (is_page(array('moviesgallery','booksgallery','gamesgallery','albumsgallery'))) {
		remove_action( 'wp_head','print_emoji_detection_script',7);
		remove_action( 'wp_print_styles', 'print_emoji_styles');
		wp_enqueue_script( 'bddb-fancy', BDDB_PLUGIN_URL . 'js/fancybox.umd.js', array(), '20211123', true );
		wp_enqueue_script( 'bddb-color-thief', BDDB_PLUGIN_URL . 'js/color-thief.js', array(), '20211123', true );
		wp_enqueue_script( 'bddb-fancy-func', BDDB_PLUGIN_URL . 'js/fancygallery.js', array(), '20220712', true );
		wp_localize_script( 'bddb-fancy-func', 'ajaxurl', admin_url('admin-ajax.php'));
		wp_enqueue_style( 'bddb-boxstyle', BDDB_PLUGIN_URL . 'css/fancybox.css' );
		if (is_page('albumsgallery')) {
			wp_enqueue_style( 'bddb-gallery-pagestyle', BDDB_PLUGIN_URL . 'css/bddb-fancy-square.css', array(), '20220526' );
		}else {
			wp_enqueue_style( 'bddb-gallery-pagestyle', BDDB_PLUGIN_URL . 'css/bddb-fancy-oblong.css', array(), '20220526' );
		}
		if (bddb_is_debug_mode()) {
			wp_enqueue_style( 'bddb-gallery-boxstyle', BDDB_PLUGIN_URL . 'css/bddb-fancy-gallery-debug.css', array(), '20220712' );
		} else {
			wp_enqueue_style( 'bddb-gallery-boxstyle', BDDB_PLUGIN_URL . 'css/bddb-fancy-gallery.css', array(), '20220712' );
		}
		
	}
	wp_enqueue_style( 'bddb-style-front', BDDB_PLUGIN_URL . 'css/bddb.css', array(), '20220526' );
}

/* 统一处理后台相关的脚本 */
function bddb_admin_scripts() {
    wp_enqueue_script('bddb-js-admin', BDDB_PLUGIN_URL . 'js/bddb-admin.js', array(), '20220615', true);
	wp_localize_script( 'bddb-js-admin', 'nomouse_names', array('nothing'));
    wp_enqueue_style( 'bddb-adminstyle', BDDB_PLUGIN_URL . 'css/bddb-admin.css', array(), '20220526' );
    wp_deregister_style( 'open-sans' );
    wp_register_style( 'open-sans', false );
}

