<?php

/*
	Question2Answer 1.3-beta-2 (c) 2010, Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-util-image.php
	Version: 1.3-beta-2
	Date: 2010-11-11 10:26:02 GMT
	Description: Some useful image-related stuff


	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.
	
	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	More about this license: http://www.question2answer.org/license.php
*/

	if (!defined('QA_VERSION')) { // don't allow this page to be requested directly from browser
		header('Location: ../');
		exit;
	}


	function qa_has_gd_image()
	{
		return extension_loaded('gd') && function_exists('imagecreatefromstring') && function_exists('imagejpeg');
	}
	
	function qa_image_constrain_data($imagedata, &$width, &$height, $size)
	{
		$inimage=@imagecreatefromstring($imagedata);
		
		if (is_resource($inimage)) {
			$width=imagesx($inimage);
			$height=imagesy($inimage);
			
			if (qa_image_constrain($width, $height, $size))
				qa_gd_image_resize($inimage, $width, $height);
		}
		
		if (is_resource($inimage)) {
			$imagedata=qa_gd_image_jpeg($inimage);
			imagedestroy($inimage);
			return $imagedata;
		}
		
		return null;	
	}
	
	function qa_image_constrain(&$width, &$height, $size)
/*

*/
	{
		if (($width>$size) || ($height>$size)) {
			$multiplier=min($size/$width, $size/$height);
			$width=floor($width*$multiplier);
			$height=floor($height*$multiplier);

			return true;
		}
		
		return false;
	}
	
	function qa_gd_image_resize(&$image, $width, $height)
/*

*/
	{
		$oldimage=$image;
		$image=null;

		$newimage=imagecreatetruecolor($width, $height);
		
		if (is_resource($newimage)) {
			if (imagecopyresampled($newimage, $oldimage, 0, 0, 0, 0, $width, $height, imagesx($oldimage), imagesy($oldimage)))
				$image=$newimage;
			else
				imagedestroy($newimage);
		}	

		imagedestroy($oldimage);
	}
	
	
	function qa_gd_image_jpeg($image, $output=false)
/*

*/
	{
		ob_start();
		imagejpeg($image, null, 90);
		return $output ? ob_get_flush() : ob_get_clean();
	}
	
	
	function qa_gd_image_formats()
	{
		$imagetypebits=imagetypes();
		
		$bitstrings=array(
			IMG_GIF => 'GIF',
			IMG_JPG => 'JPG',
			IMG_PNG => 'PNG',
		);
		
		foreach (array_keys($bitstrings) as $bit)
			if (!($imagetypebits&$bit))
				unset($bitstrings[$bit]);
				
		return $bitstrings;
	}


/*
	Omit PHP closing tag to help avoid accidental output
*/