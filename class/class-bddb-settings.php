<?php

/**
* 设定项管理类
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
			'thumbnail_width'=>100,
			'thumbnails_per_page'=>48,
			'b_max_serial_count'=>18,
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
	public static function get_poster_width(){
		$options = self::get_options();
		return $options['poster_width'];
	}
	public static function get_poster_height(){
		$options = self::get_options();
		//TODO：100：148是电影海报的规格，书籍应该略宽。
		return floor($options['poster_width']*1.48);
	}
	public static function get_thumbnails_per_page(){
		$options = self::get_options();
		return $options['thumbnails_per_page'];
	}
	public static function get_thumbnail_width(){
		$options = self::get_options();
		return $options['thumbnail_width'];
	}
	public static function get_thumbnail_height(){
		$options = self::get_options();
		return floor($options['thumbnail_width']*1.48);
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
};