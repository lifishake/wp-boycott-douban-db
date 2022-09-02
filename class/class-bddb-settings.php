<?php
/**
 * @file	class-bddb-settings.php
 * @class	BDDB_Settings
 * @brief	设定项管理类
 * @date	2021-12-02
 * @author	大致
 * @version	0.6.4
 * @since	0.1.0
 * 
 */
class BDDB_Settings{
	public static $bddb_options = false;			//成员
	/*默认值*/
	public static function default_options(){
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
			'poster_height_book'=>560,
			'thumbnail_width_book'=>false,
			'thumbnail_height_book'=>false,
			'b_max_serial_count'=>18,
			'poster_height_album'=>400,
			'thumbnail_width_album'=>false,
			'thumbnail_height_album'=>false,
			'poster_height_game'=>568,
			'thumbnail_width_game'=>false,
			'thumbnail_height_game'=>false,
			'b_misc_map'=>'',
			'm_misc_map'=>'',
			'g_misc_map'=>'',
			'a_misc_map'=>'',
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
	/*优化（保存前）*/
	public static function sanitize_options($input){
		//取得当前值。
		$current_options = self::get_options();
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
		return $input;
	}
	/*取得*/
	public static function get_options(){
		if (!self::$bddb_options) {
			self::$bddb_options = get_option('bddb_settings');
			if (is_array(self::$bddb_options)) {
				self::$bddb_options = array_merge( self::default_options(), self::$bddb_options);
			}else{
				self::$bddb_options = self::default_options();
			}
		}
		return self::$bddb_options;
	}
	//movie
	public static function get_omdb_key(){
		$options = self::get_options();
		return $options['m_omdb_key'];
	}
	//book
	public static function get_max_serial_count(){
		$options = self::get_options();
		return $options['b_max_serial_count'];
	}
	public static function get_book_country_full_name($cap){
		$options = self::get_options();
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
	//game
	public static function get_giantbomb_key(){
		$options = self::get_options();
		return $options['g_giantbomb_key'];
	}

	/**
	 * @brief	获取海报宽度。
	 * @param	array		$type		种类
	 * @return 	int
	 * @since	0.1.6
	 * @version	0.6.0
	 * @see		bddb_get_poster_names()
	 * @see		bddb_check_paths()
	 * @see		BDDB_Editor::download_pic()
	 */
	public static function get_poster_width($type){
		$options = self::get_options();
		if (!BDDB_Statics::is_valid_type($type)) {
			return $options['poster_width'];
		}
		$key = 'poster_width_'.$type;
		return self::get_sized_template($options, $key, $options['poster_width']);
	}

	/**
	 * @brief	获取海报高度。
	 * @param	array		$type		种类
	 * @return 	int
	 * @since	0.1.6
	 * @version	0.6.0
	 * @see		bddb_get_poster_names()
	 * @see		bddb_check_paths()
	 * @see		BDDB_Editor::download_pic()
	 */
	public static function get_poster_height($type){
		$options = self::get_options();
		if (!BDDB_Statics::is_valid_type($type)) {
			return $options['poster_height'];
		}
		$key = 'poster_height_'.$type;
		return self::get_sized_template($options, $key, $options['poster_height']);
	}

	/**
	 * @brief	获取每页显示的海报数。
	 * @param	array		$type		种类
	 * @return 	int
	 * @since	0.3.6
	 * @version	0.6.0
	 * @see		bddb_get_poster_names()
	 * @see		bddb_check_paths()
	 * @see		BDDB_Editor::download_pic()
	 */
	public static function get_thumbnails_per_page(){
		$options = self::get_options();
		return $options['thumbnails_per_page'];
	}

	/**
	 * @brief	获取缩略图宽度。
	 * @param	array		$type		种类
	 * @return 	int
	 * @since	0.1.6
	 * @version	0.6.0
	 * @see		bddb_get_poster_names()
	 * @see		bddb_check_paths()
	 * @see		BDDB_Editor::download_pic()
	 */

	public static function get_thumbnail_width($type){
		$options = self::get_options();
		if (!BDDB_Statics::is_valid_type($type)) {
			return $options['thumbnail_width'];
		}
		$key = 'thumbnail_width_'.$type;
		return self::get_sized_template($options, $key, $options['thumbnail_width']);
	}

	/**
	 * @brief	获取缩略图高度。
	 * @param	array		$type		种类
	 * @return 	int
	 * @since	0.1.6
	 * @version	0.6.0
	 * @see		bddb_get_poster_names()
	 * @see		bddb_check_paths()
	 * @see		BDDB_Editor::download_pic()
	 */

	public static function get_thumbnail_height($type){
		$options = self::get_options();
		if (!BDDB_Statics::is_valid_type($type)) {
			return $options['thumbnail_height'];
		}
		$key = 'thumbnail_height_'.$type;
		return self::get_sized_template($options, $key, $options['thumbnail_height']);
	}
	public static function get_default_folder(){
		$options = self::get_options();
		return $options['default_folder'];
	}
	public static function get_tax_version(){
		$options = self::get_options();
		return $options['tax_version'];
	}
	public static function update_tax_version($version){
		$options = self::get_options();
		$options['tax_version'] = $version;
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
	public static function get_sized_template($options, $key, $default) {
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
	public static function is_pictured_misc($slug, $type) {
		if (!BDDB_Statics::is_valid_type($type)) {
			return false;
		}
		$key = substr($type,0,1).'_misc_map';
		$options = self::get_options();
		if (!array_key_exists($key, $options)) {
			return false;
		}
		$valid_slugs = explode(';' , $options[$key]);
		$valid_slugs = array_map('trim', $valid_slugs);
		return in_array(trim($slug), $valid_slugs);
	}
};//class