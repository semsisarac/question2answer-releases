<?php

/*
	Question2Answer 1.3.1 (c) 2011, Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-util-image.php
	Version: 1.3.1
	Date: 2011-02-01 12:56:28 GMT
	Description: Some useful image-related functions (using GD)


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
/*
	Return true if PHP has the GD extension installed and it appears to be usable
*/
	{
		return extension_loaded('gd') && function_exists('imagecreatefromstring') && function_exists('imagejpeg');
	}

	
	function qa_image_constrain_data($imagedata, &$width, &$height, $size)
/*
	Given $imagedata containing JPEG/GIF/PNG data, constrain it proportionally to fit in $size (square)
	Return the new image data (will be a JPEG), and set the $width and $height variables
*/
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
	Given and $width and $height, return true if those need to be contrained to fit in $size (square)
	If so, also set $width and $height to the new proportionally constrained values
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
	Resize the GD $image to $width and $height, setting it to null if the resize failed
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
	Return the JPEG data for GD $image, also echoing it to browser if $output is true
*/
	{
		ob_start();
		imagejpeg($image, null, 90);
		return $output ? ob_get_flush() : ob_get_clean();
	}
	
	
	function qa_gd_image_formats()
/*
	Return an array of strings listing the image formats that are supported
*/
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