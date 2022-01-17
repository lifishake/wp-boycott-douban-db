<?php

/**
设定项管理类
*/
class BDDB_Settings{
	public $bddb_options;			//成员
	/*构造*/
	public function __construct(){
		$this->bddb_options = false;
	}
	/*默认值*/
	public function default_options(){
		$ret = array(
			'default_folder'=>'wp-content/poster_gallery/',
			'm_omdb_key'=>'',
			'g_giantbomb_key'=>'',
			'primary_common_order'=>'bddb_personal_rating',
			'poster_width'=>400,
			'thumbnail_width'=>100,
			'b_max_serial_count'=>18,
			'local_lazyload'=>0,
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
	public function sanitize_options($input){
		//取得当前值。
		$current_options = $this->get_options();
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
	public function get_options(){
		if (!$this->bddb_options) {
			$this->bddb_options = get_option('bddb_settings');
			if (is_array($this->bddb_options)) {
				$this->bddb_options = array_merge( $this->default_options(), $this->bddb_options);
			}else{
				$this->bddb_options = $this->default_options();
			}
		}
		return $this->bddb_options;
	}
	//movie
	public function get_omdb_key(){
		$options = $this->get_options();
		return $options['m_omdb_key'];
	}
	//book
	public function get_max_serial_count(){
		$options = $this->get_options();
		return $options['b_max_serial_count'];
	}
	//game
	public function get_giantbomb_key(){
		$options = $this->get_options();
		return $options['g_giantbomb_key'];
	}
	public function get_poster_width(){
		$options = $this->get_options();
		return $options['poster_width'];
	}
	public function get_poster_height(){
		$options = $this->get_options();
		//TODO：100：148是电影海报的规格，书籍应该略宽。
		return floor($options['poster_width']*1.48);
	}
	public function get_thumbnail_width(){
		$options = $this->get_options();
		return $options['thumbnail_width'];
	}
	public function get_thumbnail_height(){
		$options = $this->get_options();
		return floor($options['thumbnail_width']*1.48);
	}
	public function get_default_folder(){
		$options = $this->get_options();
		return $options['default_folder'];
	}
	public function get_tax_version(){
		$options = $this->get_options();
		return $options['tax_version'];
	}
	public function get_local_lazyload(){
		$options = $this->get_options();
		return $options['local_lazyload'];
	}
	public function update_tax_version($version){
		$options = $this->get_options();
		$options['tax_version'] = $version;
		update_option('bddb_settings', $options);
	}
};