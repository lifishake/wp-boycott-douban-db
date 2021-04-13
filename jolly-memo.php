<?php
/**
 * Plugin Name: Jolly Memo
 * Plugin URI:  http://pewae.com
 * GitHub Plugin URI: https://github.com/lifishake/jolly-memo
 * Description: Wordpress原生环境记录自己的观影阅读游戏史
 * Author:      lifishake
 * Author URI:  http://pewae.com
 * Version:     0.0.1
 * License:     GNU General Public License 3.0+ http://www.gnu.org/licenses/gpl.html
 * Text Domain: jolly-memo
 */

//防止php被直接访问 
if(preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) { die('You are not allowed to call this page directly.'); }
 
if (!class_exists('JolMoLoad')) {
    //控件加载，初始化环境
    class JolMoLoad {
        var $options = '';
        var $version = '0.0.1';
        var $jolmoAdminPanel;
        function __construct() {
            $this->load_options();
            $this->define_constant();
            $this->define_tables();
            $this->load_dependencies();
            $this->plugin_name = plugin_basename(__FILE__);
            
            register_activation_hook( $this->plugin_name, array(&$this, 'activate') );
            register_deactivation_hook( $this->plugin_name, array(&$this, 'deactivate') );
            
            //删除时删库，此时已经没有this了只能静态调用
            register_uninstall_hook( $this->plugin_name, array('JolMoLoad', 'uninstall') );
            
            add_action( 'plugins_loaded', array(&$this, 'start_plugin') );
            
            add_action( 'wp_enqueue_scripts', array( &$this, 'register_scripts_frontend' ), 3 );
        }
        function load_options() {
            $this->options = get_option('jmemo_options');
        }
        function define_constant() {
            define('JOLMO_VERSION', $this->version);
            if ( !defined('WINABSPATH') ) {
                define('WINABSPATH', str_replace("\\", "/", ABSPATH) );
            }
            define('JOLMO_FOLDER', plugin_basename( dirname(__FILE__)) );
            define('JOLMO_ABSPATH', str_replace("\\","/", WP_PLUGIN_DIR . '/' . plugin_basename( dirname(__FILE__) ) . '/' ));
            define('JOLMO_URLPATH', plugins_url() . '/' . plugin_basename( dirname(__FILE__) ) . '/' );
            define('JOLMO_DIRNAME', dirname(__FILE__));
        }
        //在wpdb中加入自定义的表。
        function define_tables() {
            global $wpdb;
            $wpdb->jolmo                    = $wpdb->prefix . 'jolmo';
            $wpdb->jolmo_meta               = $wpdb->prefix . 'jolmo_meta';
            $wpdb->jolmo_person             = $wpdb->prefix . 'jolmo_person';
            $wpdb->jolmo_define             = $wpdb->prefix . 'jolmo_define';
            $wpdb->jolmo_relation           = $wpdb->prefix . 'jolmo_relation';
        }
        
        //需要的库。
        function load_dependencies() {
            if (is_admin()) {
                require_once (JOLMO_DIRNAME . '/admin/admin.php');
                $this->jolmoAdminPanel = new jolmoAdminPanel();
            }
        }
        
        //前台用脚本
        function register_scripts_frontend() {
            
        }

        //插件激活
        function activate() {
            include_once (JOLMO_DIRNAME . '/admin/jolmo_install.php');
            jolmo_install();
            flush_rewrite_rules();
        }
        
        //插件反激活
        function deactivate() {
        }
        //插件删除
        function uninstall() {
            include_once (dirname (__FILE__) . '/admin/jolmo_install.php');
            jolmo_uninstall();
        }
        
        //加载插件后的动作,保留
        function start_plugin() {
        }
    }
    global $jolmo;
    $jolmo = new JolMoLoad();
}