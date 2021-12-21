<?php

/**
设定项管理类
*/
class BDDB_Settings{
	public $bddb_options;
	public function __construct(){
		$this->bddb_options = false;
	}
	public function default_options(){
		$ret = array(
			'default_folder'=>'wp-content/poster_gallery/',
			'm_omdb_key'=>'',
			'g_giantbomb_key'=>'',
			'primary_common_order'=>'bddb_personal_rating',
			'poster_width'=>400,
			'thumbnail_width'=>100,
			'b_max_serial_count'=>18,
		);
		return $ret;
	}
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
};