<?php
/**
 * LastImage - PHP Wrapper for the Last.fm API for images
 * 
 * Copyright (c) 2012  Andrew Lim.
 *
 * <pre>
 *   Permission is hereby granted, free of charge, to any person obtaining 
 *   a copy of this software and associated documentation files (the 'Software'), 
 *   to deal in the Software without restriction, including without limitation 
 *   the rights to use, copy, modify, merge, publish, distribute, sublicense, 
 *   and/or sell copies of the Software, and to permit persons to whom the 
 *   Software is furnished to do so, subject to the following conditions:
 *   
 *   The above copyright notice and this permission notice shall be included in 
 *   all copies or substantial portions of the Software.
 *   
 *   THE SOFTWARE IS PROVIDED 'AS IS', WITHOUT WARRANTY OF ANY KIND, EXPRESS OR 
 *   IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, 
 *   FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE 
 *   AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER 
 *   LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, 
 *   OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN 
 *   THE SOFTWARE.
 * </pre>
 * 
 * With an object of this class you may lookup images from Last.fm and cache the images locally. 
 * 
 * Example:
 * <code>
 * $lastfm = LastImage::getInstance();
 * $lastfm->setApiKey("your_api_key");
 * $image = $lastfm->grab("Cher", "", "Believe");
 * </code>
 * 
 * By default caching is not enabled, to enable it you wil have to set the cache
 * directory.
 * 
 * Example:
 * <code>
 * $lastfm->setCacheDir("lib/cache/");
 * </code>
 * 
 * @todo set expiry date, set SSL, handle 404, clear cache 
 * @copyright Andrew Lim 2012
 * @author Andrew Lim <hiya@andrew-lim.net>
 * @version 1.0
 */

 
/**
 * Class LastImage
 * Provides methods to fetch Last.fm artist images and cache them
 */
class LastImage
{
	const CACHE_PREFIX = "LASTFM_CACHE_";
	
	const SERVICE_BASE_URL = "http://ws.audioscrobbler.com/";
	
	private $_cache_dir = null;
	private $_use_cache = false;
	
	private $_api_key;
	
	private $_default_image;
	
	# Holds instance
    private static $_instance;
	
	# Singelton-patterned class. No need to make an instance of this object 
    # outside it self. 
    private function __construct()
    {
        
    }

    /**
     * Get new instance of this object.
     */
    public static function getInstance()
    {
        if (!isset(self::$_instance))
        {
            $class = __CLASS__;
            self::$_instance = new $class;
        }

        return self::$_instance;
    }
	
    /**
     * Sets the cache directory and sets use_cache to true
     * 
     * @param string $dir
     */
    public function setCacheDir($dir)
    {
    	$this->_cache_dir = $dir;
    	$this->_use_cache = true;
    }
    
    /**
     * 
     * Sets the API Key
     * @param string $key
     */
    public function setApiKey($key)
    {
    	$this->_api_key = $key;
    }
	
    /**
     * 
     * Sets the path to an image to be used in the case that no image is returned from Last.fm
     * @param string $path
     */
    public function setDefaultImage($path)
    {
    	$this->default_image = $path;
    }
    
	/**
	 * Returns the path for an image which can be used directly in an <img> tag
	 * 
	 * @param string $artist
	 * @param string $track
	 * @param string $album
	 * @param string $size - small, medium, large, extra large, full size
	 * @return string
	 */
	public function grab($artist, $track = NULL, $album = NULL, $size = "small")
	{
		# get remote path
		$RemotePath = $this->getRemotePath($artist, $track, $album);
		$strRemotePath = $RemotePath["path"];
		$strRemoteMethod = $RemotePath["method"];
		
		# get local path
		$strLocalPath = $this->getLocalPath($artist, $track, $album, $size);
		
		# check if cache expired
		
		# check if the cached file exists
		if (file_exists($strLocalPath) && $this->_use_cache)
		{
			$imagePath =  $strLocalPath;
		}
		else
		{
			# access last.fm for image
			$imagePath = $this->get_image($strRemotePath, $strRemoteMethod, $size);
			
			if($this->_use_cache)
			{
				# cache file
				$this->cache($imagePath, $strLocalPath);
			}
		}
		
		return $imagePath;
	}
    
	/**
	 * 
	 * Accesses the Last.fm API that retreives an XML which is parsed to return the image.
	 * @param String $path
	 * @param String $method
	 * @param string $size - small, medium, large, extra large, full size
	 */
	function get_image($path, $method, $size)
	{
		# Get the feed
		$feed = simplexml_load_file($path);
		
		#
		if($method == "album.getinfo")
		{
			$tracks = $feed->album->image;
		}
		else if($method == "track.getinfo")
		{
			$tracks = $feed->track->album->image;
		}
		else
		{
			$tracks = $feed->artist->image;
		}
		
		#
		$i = 0;
		if($size == "small")
		{
			$i = 0;
		}
		else if($size == "medium")
		{
			$i = 1;
		}
		else if($size == "large")
		{
			$i = 2;
		}
		else if($size == "extra large")
		{
			$i = 3;
		}
		else if($size == "full size")
		{
			$i = 4;
		}
		
		$image = $tracks[$i];

		if(!$image)
		{
			$image = $this->default_image;
		}
		
		return $image;
	}
	
    /**
	 * Saves the file to the cache directory
	 * 
	 * @param string $strRemotePath
	 * @param string $strLocalPath
	 */
	private function cache($strRemotePath, $strLocalPath)
	{
		$contents = @file_get_contents($strRemotePath);
		file_put_contents($strLocalPath, $contents);
	}
    
	/**
	 * Returns last.fm path for an image which can be used directly in an <img> tag
	 * 
	 * @param string $artist
	 * @param string $track
	 * @param string $album
	 * @param string $version
	 * @return string
	 */
	private function getRemotePath($artist, $track = NULL, $album = NULL, $version = "2.0")
	{
		# Set base path
		$strPath = self::SERVICE_BASE_URL .$version . "/";
		
		# select api method
		if($artist && !$track && $album)
		{
			# album method
			$method = "album.getinfo";
		}
		else if($artist && $track && !$album)
		{
			# track method
			$method = "track.getinfo";
		}
		else if($artist && !$track && !$album)
		{
			# artist method
			$method = "artist.getinfo";
		}
		
		# set last.fm params
		$params['method'] = $method;
		$params['api_key'] = $this->_api_key;
		$params['artist'] = $artist;
		if($track)
			$params['track'] = $track;
		if($album)
			$params['album'] = $album;
        
		$strPath = $strPath . '?' . http_build_query($params, '', '&');
		
		$path_info["path"] = $strPath;
		$path_info["method"] = $method;
        
		return $path_info;
	}
	
	/**
	 * Returns local path for an image which can be used directly in an <img> tag.
	 * NB The cache directory needs to be writable for this to work. 
	 * 
	 * @param string $artist
	 * @param string $track
	 * @param string $album
	 * @param string $size
	 * @return string
	 */
	private function getLocalPath($artist, $track = NULL, $album = NULL, $size)
	{
		# Set base path
		$strPath = $this->_cache_dir . self::CACHE_PREFIX;
		
		# select api method
		if($artist && !$track && $album)
		{
			# album method
			$method = "album.getinfo";
		}
		else if($artist && $track && !$album)
		{
			# track method
			$method = "track.getinfo";
		}
		else if($artist && !$track && !$album)
		{
			# artist method
			$method = "artist.getinfo";
		}
		
		# set last.fm params
		$params['method'] = $method;
		$params['size'] = $size;
		$params['artist'] = $artist;
		if($track)
			$params['track'] = $track;
		if($album)
			$params['album'] = $album;
        
		$strPath = $strPath . md5(http_build_query($params, '', '&')) . ".jpg";
		
        return $strPath;
	}
}
 