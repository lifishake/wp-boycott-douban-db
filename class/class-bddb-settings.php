<?php
class BDDB_Settings{
	private $bddb_options;
	public function __construct(){
		$this->bddb_options = false;
	}
	public function default_options(){
		$ret = array(
			'default_folder'=>'wp-content/poster_gallery/',
			'm_omdb_key'=>'',
			'primary_common_order'=>'bddb_personal_rating',
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
	public function get_omdb_key(){
		$options = $this->get_options();
		return $options['m_omdb_key'];
	}
};