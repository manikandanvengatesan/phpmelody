<?php
// +------------------------------------------------------------------------+
// | PHP Melody ( www.phpsugar.com )
// +------------------------------------------------------------------------+
// | PHP Melody IS NOT FREE SOFTWARE
// | If you have downloaded this software from a website other
// | than www.phpsugar.com or if you have otherwise received
// | this software from someone who is not a representative of
// | this site you are involved in an illegal activity.
// | ---
// | In such case, please contact us at: support@phpsugar.com.
// +------------------------------------------------------------------------+
// | Developed by: PHPSUGAR (www.phpsugar.com) / support@phpsugar.com
// | Copyright: (c) 2004-2013 PHPSUGAR. All rights reserved.
// +------------------------------------------------------------------------+


if(!defined('PHPMELODY'))
	die('Restricted Access!');

function get_info($url)
{
	$video_data = array();
	$error = 0;
	$target_url = $url;
	
	if(function_exists('curl_init'))
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $target_url);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.7.5) Gecko/20041107 Firefox/1.0');
		$video_data = curl_exec($ch);
		$errormsg = curl_error($ch);
		curl_close($ch);
		
		if($errormsg != '')
		{
			echo '<div class="alert alert-error">'.$errormsg.'</div>';
			return false;
		}
	}
	else if(ini_get('allow_url_fopen') == 1)
	{
		$video_data = @file($target_url);
		if($video_data === false)
			$error = 1;
	}
	if( ! is_array($video_data))
	{
		$video_data = explode("\n", $video_data);
	}
	
	return $video_data;
}

function get_flv($video_data)
{
	return;
}

function get_thumb_link($video_data)
{
	return;
}


function video_details($video_data, $url, &$info)
{
	$title = $thumb = $duration = false;
	$direct = $tags = $description = false;
	
	$arr_length = count($video_data);
	
	for ($i = 0; $i < $arr_length; $i++)
	{
		if ( ! $title)
		{
			if (strpos($video_data[$i], 'og:title"'))
			{
				if (preg_match('/content="(.*?)"/', $video_data[$i], $matches) != 0)
				{
					$info['video_title'] = $matches[1];
					$title = true;
				}
			}
		}
		if ( ! $thumb)
		{
			if (strpos($video_data[$i], 'og:image"'))
			{
				if (preg_match('/content="(.*?)"/', $video_data[$i], $matches) != 0)
				{
					$info['yt_thumb'] = $matches[1];
					$thumb = true;
				}
			}
		}
		if ( ! $direct)
		{
			if (strpos($video_data[$i], 'og:url"'))
			{
				if (preg_match('/content="(.*?)"/', $video_data[$i], $matches) != 0)
				{
					$info['direct'] = $matches[1];
					$direct = true;
				}
			}
		}
		if ( ! $duration)
		{
			if (strpos($video_data[$i], 'og:duration'))
			{
				if (preg_match('/content="(.*?)"/', $video_data[$i], $matches) != 0)
				{
					$info['yt_length'] = $matches[1];
					$duration = true;
				}
			}
		}
		if ( ! $tags)
		{
			if (strpos($video_data[$i], 'og:tag'))
			{
				if (preg_match('/content="(.*?)"/', $video_data[$i], $matches) != 0)
				{
					$info['tags'] = $matches[1];
					$tags = true;
				}
			}
		}
		if ( ! $description)
		{
			if (strpos($video_data[$i], 'og:description'))
			{
				if (preg_match('/content="(.*?)"/', $video_data[$i], $matches) != 0)
				{
					$info['description'] = $matches[1];
					$description = true;
				}
			}
		}
		
		if ($title && $thumb && $duration && $direct && $tags && $description)
		{
			break;
		}
	}
	
	if ($info['direct'] == '')
	{
		$info['direct'] = $url;
	}
	
	if (preg_match('/video_([0-9]+)/', $info['direct'], $matches) != 0)
	{	
		$info['yt_id'] = $matches[1];
	}
	else
	{
		$url = rtrim($url, '/');
		$pieces = explode('/', $url);
		$info['yt_id'] = $pieces[count($pieces)-1];
		if ( ! ctype_digit($info['yt_id']))
		{
			$info['yt_id'] = $pieces[count($pieces)-2];
		}
	}
	
	$info['url_flv'] = 'http://www.izlesene.com/embedplayer.swf?video='. $info['yt_id'];
}

function download_thumb($thumbnail_link, $upload_path, $video_uniq_id) {
	
	$last_ch = substr($upload_path, strlen($upload_path)-1, strlen($upload_path));
	if($last_ch != "/")
		$upload_path .= "/"; 

	$ext = ".jpg";
	
	$thumb_name = $video_uniq_id . "-1" . $ext;
	
	if(is_file( $upload_path . $thumb_name )) {
		return FALSE;
	}
	
	$error = 0;

	if ( function_exists('curl_init') ) 
	{

		$ch = curl_init();
		$timeout = 0;
		curl_setopt ($ch, CURLOPT_URL, $thumbnail_link);
		curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
		
		// Getting binary data
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
		
		$image = curl_exec($ch);
		curl_close($ch);
		
		//	create & save image;
		$img_res = @imagecreatefromstring($image);
		if($img_res === false)
			return FALSE;
		
		$img_width = imagesx($img_res);
		$img_height = imagesy($img_res);
		
		$resource = @imagecreatetruecolor($img_width, $img_height);
		
		if( function_exists('imageantialias'))
		{
			@imageantialias($resource, true); 
		}
		
		@imagecopyresampled($resource, $img_res, 0, 0, 0, 0, $img_width, $img_height, $img_width, $img_height);
		@imagedestroy($img_res);
	
		switch($ext)
		{
			case ".gif":
				//GIF
				@imagegif($resource, $upload_path . $thumb_name);
			break;
			case ".jpg":
				//JPG
				@imagejpeg($resource, $upload_path . $thumb_name);
			break;  
			case ".png":
				//PNG
				@imagepng($resource, $upload_path . $thumb_name);
			break;
		}
	}
	else if( ini_get('allow_url_fopen') == 1 )
	{
		// try copying it... if it fails, go to backup method.
		if(!copy($thumbnail_link, $upload_path . $thumb_name ))
		{
			//	create a new image
			list($img_width, $img_height, $img_type, $img_attr) = @getimagesize($thumbnail_link);

			$image = '';

			switch($img_type)
			{
				case 1:
					//GIF
					$image = imagecreatefromgif($thumbnail_link);
					$ext = ".gif";
				break;
				case 2:
					//JPG
					$image = imagecreatefromjpeg($thumbnail_link);
					$ext = ".jpg";
				break;  
				case 3:
					//PNG
					$image = imagecreatefrompng($thumbnail_link);
					$ext = ".png";
				break;
			}
			
			$resource = @imagecreatetruecolor($img_width, $img_height);
			if( function_exists('imageantialias'))
			{
				@imageantialias($resource, true); 
			}
			
			@imagecopyresampled($resource, $image, 0, 0, 0, 0, $img_width, $img_height, $img_width, $img_height);
			@imagedestroy($image);
		}
		
		$thumb_name = $video_uniq_id . "-1" . $ext;
		
		$img_type = 2;
		switch($img_type)
		{
			default:
			case 1:
				//GIF
				@imagegif($resource, $upload_path . $thumb_name);
			break;
			case 2:
				//JPG
				@imagejpeg($resource, $upload_path . $thumb_name);
			break;  
			case 3:
				//PNG
				@imagepng($resource, $upload_path . $thumb_name);
			break;
		}
		
		if($resource === '')
			$error = 1;
	} 

	return $upload_path . $thumb_name;
}


function do_main(&$video_details, $url, $show_warnings = true)
{
	$video_data = @get_info($url);
	if($video_data != false)
	{
		video_details($video_data, $url, $video_details);
	}
	else
	{
		$video_details = array();
	}
}
?>