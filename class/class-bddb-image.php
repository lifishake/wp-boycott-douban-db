<?php

/**
 * @class	Bddb_SimpleImage
 * @brief	封装图像处理
 * @date	2022-12-27
 * @author	网络
 * @version	0.7.1
 * @since	0.1.4
 * 
 */
class Bddb_SimpleImage {

   var $image;
   var $image_type;

   function load($filename) {
      if(strtolower(substr($filename, 0, 4))=='http'){
         //url  
         $cxContext = stream_context_create();
         $proxy = new WP_HTTP_Proxy();
         if ($proxy->is_enabled()) {
            $proxy_str = $proxy->host().":".$proxy->port();
            $stream_default_opts = array(
               'http'=>array(
                 'proxy'=>$proxy_str,
                 'request_fulluri' => true,
               ),
               'ssl' => array(
                  'verify_peer' => false,
                  'verify_peer_name' => false,
                  'allow_self_signed' => true
               ),
             );
             $cxContext = stream_context_create($stream_default_opts);
         }
         file_put_contents("./temp", file_get_contents($filename,false, $cxContext));
         $filename = "./temp";
      }
      $image_info = getimagesize($filename);
      
      $this->image_type = $image_info[2];
      if( $this->image_type == IMAGETYPE_JPEG ) {

         $this->image = imagecreatefromjpeg($filename);
      } elseif( $this->image_type == IMAGETYPE_GIF ) {

         $this->image = imagecreatefromgif($filename);
      } elseif( $this->image_type == IMAGETYPE_PNG ) {

         $this->image = imagecreatefrompng($filename);
      } elseif( $this->image_type == IMAGETYPE_WEBP ) {
         
         $this->image = imagecreatefromwebp($filename);
      }
      if ($filename==="./temp") {
         unlink("./temp");
      }
   }
   function save($filename, $image_type=IMAGETYPE_JPEG, $compression=75, $permissions=null) {

      if( $image_type == IMAGETYPE_JPEG ) {
         imagejpeg($this->image,$filename,$compression);
      } elseif( $image_type == IMAGETYPE_GIF ) {

         imagegif($this->image,$filename);
      } elseif( $image_type == IMAGETYPE_PNG ) {

         imagepng($this->image,$filename);
      }
      if( $permissions != null) {

         chmod($filename,$permissions);
      }
   }
   function output($image_type=IMAGETYPE_JPEG) {

      if( $image_type == IMAGETYPE_JPEG ) {
         imagejpeg($this->image);
      } elseif( $image_type == IMAGETYPE_GIF ) {

         imagegif($this->image);
      } elseif( $image_type == IMAGETYPE_PNG ) {

         imagepng($this->image);
      }
   }
   function getWidth() {

      return imagesx($this->image);
   }
   function getHeight() {

      return imagesy($this->image);
   }
   function resizeToHeight($height) {

      $ratio = $height / $this->getHeight();
      $width = $this->getWidth() * $ratio;
      $this->resize($width,$height);
   }

   function resizeToWidth($width) {
      $ratio = $width / $this->getWidth();
      $height = $this->getheight() * $ratio;
      $this->resize($width,$height);
   }

   function scale($scale) {
      $width = $this->getWidth() * $scale/100;
      $height = $this->getheight() * $scale/100;
      $this->resize($width,$height);
   }

   function resize($width,$height) {
      $new_image = imagecreatetruecolor($width, $height);
      imagecopyresampled($new_image, $this->image, 0, 0, 0, 0, $width, $height, $this->getWidth(), $this->getHeight());
      $this->image = $new_image;
   }

   /**
	 * 图像旋转。
	 * @param int $angle	旋转角度(逆时针为正)
	 * @since 	0.7.0
	 * @version	0.7.0
	 */
   function rotate($angle) {
      $new_image = imagerotate($this->image, $angle, 0);
      $this->image = $new_image;
   }

   /**
	 * 制作封面。
	 * @param int $new_width	封面宽度
    * @param int $new_height	封面高度
    * @param int $border_width	边框宽度
	 * @since 	0.7.1
	 * @version	0.7.1
	 */
   function addcover($new_width, $new_height, $border_width=0) {
      $bg0 = imagecreatetruecolor($new_width, $new_height);
      $white = imagecolorallocate($bg0, 255, 255, 255);

      //第一次，用白色填充全部
      imagefilledrectangle($bg0, 0, 0, $new_width, $new_height, $white);

      //外套。将图片拉伸或缩小到高度，然后从中间截取。
      $original_img = $this->image;    //backup源
      $this->resizeToHeight($new_height);
      $mid_x = $this->getWidth()/2;
      $start_x = $mid_x - $new_width/2;

      imagecopymerge( $bg0, $this->image, 0, 0, $start_x, 0, $new_width, $new_height, 50);

      //白框，无框全覆盖
      $th_x = 7;
      $th_width = $new_width - $th_x * 2;
      $ratio = imagesy($original_img) / imagesx($original_img);
      $th_height = intval($th_width*$ratio);
      $th_y = intval(($new_height-$th_height) / 2);
      imagefilledrectangle($bg0, $th_x, $th_y, $th_x +$th_width, $th_y +$th_height, $white);


      $th_width -= 2*$border_width;
      $th_height -= 2*$border_width;
      //$th_y = 120;
      $th_y += $border_width;
      $th_x += $border_width;
      imagecopyresized($bg0, $original_img, $th_x, $th_y, 0, 0, $th_width, $th_height, imagesx($original_img), imagesy($original_img));
      imagedestroy($original_img);
      $this->image = $bg0;
   }

}
