<?php
/**
 * @file	class-bddb-settings.php
 * @class	BDDB_Settings
 * @brief	设定项管理类
 * @date	2025-04-03
 * @author	大致
 * @version	1.0.5
 * @since	0.1.0
 * 
 */
class BDDB_Settings{
	public $bddb_options = null;			//成员
	private static $instance = null;
	public static function getInstance() {
		if (null === self::$instance) {
			self::$instance = new BDDB_Settings();
		}
		return self::$instance;
	}
	/**
	 * @brief	构造函数。
	 * @private
	 * @since	1.0.5
	 * @version	1.0.5
	 */
	private function __construct(){
	}

	/* 防止被克隆 */
	private function __clone(){}

	/**
	 * @brief	默认设置。
	 * @public
	 * @since	0.1.0
	 * @version	1.0.5
	 */
	public function default_options(){
		$ret = array(
			'default_folder'=>'wp-content/poster_gallery/',
			'm_omdb_key'=>'',
			'g_giantbomb_key'=>'',
			'primary_common_order'=>'bddb_personal_rating',
			'poster_width'=>400,
			'poster_height'=>592,
			'thumbnail_width'=>100,
			'thumbnail_height'=>148,
			'thumbnails_per_page'=>48,
			'poster_width_book'=>400,
			'poster_height_book'=>560,
			'thumbnail_width_book'=>100,
			'thumbnail_height_book'=>140,
			'b_max_serial_count'=>18,
			'poster_width_album'=>400,
			'poster_height_album'=>400,
			'thumbnail_width_album'=>128,
			'thumbnail_height_album'=>128,
			'poster_width_game'=>400,
			'poster_height_game'=>568,
			'thumbnail_width_game'=>100,
			'thumbnail_height_game'=>142,
			'tax_version'=>'20220101',
			'type_version'=>'20230210',
			'b_misc_map'=>'',
			'm_misc_map'=>'',
			'g_misc_map'=>'',
			'a_misc_map'=>'',
			'a_languages_def'=>'603-普通话;601-粤语;550-英语;796-日语;000-纯音乐;001-韩语',
			'b_countries_map'=>'日,日本;美,美国;',
			//TODO
			'general_order' => array(
				'bddb_display_name' => array( 'priority' => '09', 'orderby' => 'ASC'),
				'bddb_original_name' => array( 'priority' => false, 'orderby' => 'ASC'),
				'bddb_personal_review' => array( 'priority' => false, 'orderby' => 'ASC'),
				'bddb_publish_time' => array( 'priority' => '03', 'orderby' => 'ASC'),
				'bddb_view_time' => array( 'priority' => '02', 'orderby' => 'ASC'),
				'bddb_personal_rating' => array( 'priority' => '01', 'orderby' => 'DESC'),
			),
		);
		return $ret;
	}
	
	/**
	 * @brief	优化配置项。
	 * @public
	 * @since	0.1.0
	 * @version	1.0.5
	 */
	public function sanitize_options($input){
		//取得当前值。
		$current_options = $this->get_options();
		foreach( $current_options as $key => $val ) {
			if (!isset($input[$key])){
				$input[$key] = $val;
			}
		}

		$valid_img_strs = array(
			'poster_width_book',
			'poster_height_book',
			'thumbnail_width_book',
			'thumbnail_height_book',
			'poster_width_movie',
			'poster_height_movie',
			'thumbnail_width_movie',
			'thumbnail_height_movie',
			'poster_width_game',
			'poster_height_game',
			'thumbnail_width_game',
			'thumbnail_height_game',
			'poster_width_album',
			'poster_height_album',
			'thumbnail_width_album',
			'thumbnail_height_album',
		);

		foreach ($valid_img_strs as $valid_img_str) {
			if (isset($input[$valid_img_str])) {
				if (!is_numeric($input[$valid_img_str]) || $input[$valid_img_str] <= 0) {
					$input[$valid_img_str] = false;
				}
			}
		}

		if (isset($input['tax_version'])) {
			if (empty($input['tax_version'])) {
				$input['tax_version'] = BDDB_TAX_VER;
			}
		}
		if (isset($input['type_version'])) {
			if (empty($input['type_version'])) {
				$input['type_version'] = BDDB_META_VER;
			}
		}
		self::$instance->bddb_options = $input;

		return $input;
	}

	/**
	 * @brief	取得配置项。
	 * @public
	 * @param	none
	 * @return 	array
	 * @since	0.1.0
	 * @version	0.1.0
	 */
	public function get_options(){
		if (null === self::$instance->bddb_options) {
			self::$instance->bddb_options = get_option('bddb_settings');
			if (is_array(self::$instance->bddb_options)) {
				self::$instance->bddb_options = array_merge( self::$instance->default_options(), self::$instance->bddb_options);
			}else{
				self::$instance->bddb_options = self::$instance->default_options();
			}
		}
		return self::$instance->bddb_options;
	}

	/**
	 * @brief	取得omdb 的API KEY。
	 * @public
	 * @param	none
	 * @return 	string
	 * @since	0.1.0
	 * @version	0.1.0
	 * @see TODO
	 */
	public function get_omdb_key(){
		$options = $this->get_options();
		return $options['m_omdb_key'];
	}

	/**
	 * @brief	【共】取得系列最大数。
	 * @public
	 * @param	none
	 * @return 	int
	 * @since	0.1.0
	 * @version	0.1.0
	 * @see BDDB_Templates::set_working_mode()
	 */
	public function get_max_serial_count(){
		$options = $this->get_options();
		return $options['b_max_serial_count'];
	}

	/**
	 * @brief	【书】将国名简写转成全名。
	 * @public
	 * @param	string
	 * @return 	string
	 * @since	0.1.0
	 * @version	0.1.0
	 * @see TODO
	 */
	public function get_book_country_full_name($cap){
		$options = $this->get_options();
		$ret = $cap;
		if (!isset($options['b_countries_map'])) {
			return $ret;
		}
		$arrs = explode(';', $options['b_countries_map']);
		foreach ($arrs as $str ) {
			$pos = strpos($str, $cap);
			if (false === $pos) {
				continue;
			}
			if ($pos > strpos($str, ',')) {
				continue;
			}
			$ret = trim(str_replace(array($cap.",",";"),"", $str));
			break;
		}
		return $ret;
	}

	/**
	 * @brief	【游】取得giantbomb 的API KEY。
	 * @public
	 * @param	none
	 * @return 	string
	 * @since	0.1.0
	 * @version	0.1.0
	 * @see TODO
	 */
	public function get_giantbomb_key(){
		$options = $this->get_options();
		return $options['g_giantbomb_key'];
	}

	/**
	 * @brief	【碟】获取编辑用语言列表
	 * @public
	 * @param	none
	 * @return 	array
	 * @since	0.8.6
	 * @version	1.0.5
	 * @see		BDDB_Editor::set_additional_items_album()
	 */
	public function get_language_list(){
		$options = $this->get_options();
		$ret = array();
		$ret = explode(';', $options['a_languages_def']);
		return $ret;
	}

	/**
	 * @brief	【共】获取海报宽度。
	 * @param	string		$type		种类
	 * @return 	int
	 * @since	0.1.6
	 * @version	0.6.0
	 * @see		bddb_get_poster_names()
	 * @see		bddb_check_paths()
	 * @see		BDDB_Editor::download_pic()
	 * @see		bddb_scripts()
	 */
	public function get_poster_width($type){
		$options = $this->get_options();
		if (!BDDB_Statics::is_valid_type($type)) {
			return $options['poster_width'];
		}
		$key = 'poster_width_'.$type;
		return $this->get_sized_template($options, $key, $options['poster_width']);
	}

	/**
	 * @brief	【共】获取海报高度。
	 * @param	string		$type		种类
	 * @return 	int
	 * @since	0.1.6
	 * @version	0.6.0
	 * @see		bddb_get_poster_names()
	 * @see		bddb_check_paths()
	 * @see		BDDB_Editor::download_pic()
	 * @see		bddb_scripts()
	 */
	public function get_poster_height($type){
		$options = $this->get_options();
		if (!BDDB_Statics::is_valid_type($type)) {
			return $options['poster_height'];
		}
		$key = 'poster_height_'.$type;
		return $this->get_sized_template($options, $key, $options['poster_height']);
	}

	/**
	 * @brief	【共】获取每页显示的海报数。
	 * @param	array		$type		种类
	 * @return 	int
	 * @since	0.3.6
	 * @version	0.6.0
	 * @see		bddb_get_poster_names()
	 * @see		bddb_check_paths()
	 * @see		BDDB_Editor::download_pic()
	 */
	public function get_thumbnails_per_page(){
		$options = $this->get_options();
		return $options['thumbnails_per_page'];
	}

	/**
	 * @brief	【共】获取缩略图宽度。
	 * @param	string		$type		种类
	 * @return 	int
	 * @since	0.1.6
	 * @version	0.6.0
	 * @see		bddb_get_poster_names()
	 * @see		bddb_check_paths()
	 * @see		BDDB_Editor::download_pic()
	 */

	public function get_thumbnail_width($type){
		$options = $this->get_options();
		if (!BDDB_Statics::is_valid_type($type)) {
			return $options['thumbnail_width'];
		}
		$key = 'thumbnail_width_'.$type;
		return $this->get_sized_template($options, $key, $options['thumbnail_width']);
	}

	/**
	 * @brief	【共】获取缩略图高度。
	 * @param	string		$type		种类
	 * @return 	int
	 * @since	0.1.6
	 * @version	0.6.0
	 * @see		bddb_get_poster_names()
	 * @see		bddb_check_paths()
	 * @see		BDDB_Editor::download_pic()
	 */

	public function get_thumbnail_height($type){
		$options = $this->get_options();
		if (!BDDB_Statics::is_valid_type($type)) {
			return $options['thumbnail_height'];
		}
		$key = 'thumbnail_height_'.$type;
		return $this->get_sized_template($options, $key, $options['thumbnail_height']);
	}

	/**
	 * @brief	【共】获取缩略图默认保存路径。
	 * @param	none
	 * @return 	string
	 * @since	0.1.6
	 * @version	0.6.0
	 * @see		bddb_get_poster_names()
	 * @see		bddb_get_check_paths()
	 * @see		bddb_maintain_render()
	 */
	public function get_default_folder(){
		$options = self::$instance->get_options();
		return $options['default_folder'];
	}

	/**
	 * @brief	【共】追加的taxonomy定义版本号。
	 * @param	none
	 * @return 	string
	 * @since	0.1.6
	 * @version	0.3.0
	 * @see		check_db()
	 * @see		tax_diff()
	 */
	public function get_tax_version(){
		$options = $this->get_options();
		return $options['tax_version'];
	}

	/**
	 * @brief	【共】更新taxonomy定义版本号。
	 * @param	string
	 * @return 	none
	 * @since	0.1.6
	 * @version	0.3.0
	 * @see		check_db()
	 */
	public function update_tax_version($version){
		$options = $this->get_options();
		$options['tax_version'] = $version;
		update_option('bddb_settings', $options);
	}

	/**
	 * @brief	【共】取得种类定义版本号。
	 * @param	none
	 * @return 	string
	 * @since	0.1.6
	 * @version	0.3.0
	 * @see		check_db()
	 */
	public function get_type_version(){
		$options = $this->get_options();
		return $options['type_version'];
	}

	/**
	 * @brief	【共】更新type定义版本号。
	 * @param	string
	 * @return 	none
	 * @since	0.1.6
	 * @version	0.3.0
	 * @see		check_db()
	 */
	public function update_type_version($version){
		$options = $this->get_options();
		$options['type_version'] = $version;
		update_option('bddb_settings', $options);
	}

	/**
	 * @brief	图片规格相关的几个函数的模板。
	 * @param	array		$options			配置信息
	 * @param	string		$key				要取得的项目
	 * @param	int			$default			默认值
	 * @return 	int
	 * @since	0.6.0
	 * @see		get_poster_width()
	 * @see		get_poster_height()
	 * @see		get_thumbnail_width()
	 * @see		get_thumbnail_height()
	 */
	public function get_sized_template($options, $key, $default) {
		if (!isset($options[$key])) {
			return $default;
		}
		if (false === $options[$key]) {
			return $default;
		}
		if (!is_numeric($options[$key]) || $options[$key] <= 0) {
			return $default;
		}
		return $options[$key];
	}

	/**
	 * @brief	判断是否是支持图片的misc项。
	 * @param	string		$slug				misc的key名
	 * @param	string		$type				要判断的种类
	 * @return 	bool
	 * @since	0.6.5
	 * @see		movie_misc_special()
	 * @see		book_misc_special()
	 */
	public function is_pictured_misc($slug, $type) {
		if (!BDDB_Statics::is_valid_type($type)) {
			return false;
		}
		$key = substr($type,0,1).'_misc_map';
		$options = $this->get_options();
		if (!array_key_exists($key, $options)) {
			return false;
		}
		$valid_slugs = explode(';' , $options[$key]);
		$valid_slugs = array_map('trim', $valid_slugs);
		return in_array(trim($slug), $valid_slugs);
	}
	
};//class


/**
 * @brief	单例模式不能直接设为回调函数，封装一次
 * @param	array		$input				要更新的配置项
 * @return 	bool
 * @since	1.0.5
 * @see		bddb_sanitize_options()
 */
function bddb_sanitize_options($input) {
	return BDDB_Settings::getInstance()->sanitize_options($input);
}