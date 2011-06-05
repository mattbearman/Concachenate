<?php

/**
 * Concachenate Library
 * 
 */
class Concachenate
{
	private $ci;
	private $useCaching; // set to false to not use cacheing (debug mode) 
	private $allFiles;
	private $cacheDir;
	private $cachePath;
	private $carriageRreturn;
	private $cacheLife; // seconds
	
	/**
	 * Constructor, load file helper, set up folder if requried
	 */
	public function __construct()
	{
		// get CodeIgniter instance
		$this->ci = &get_instance();
		
		// load file helper
		$this->ci->load->helper('file');
		
		// set cache directory
		$this->cacheDir = 'concacheinate/';
		$this->cachePath = FCPATH.$this->cacheDir;

		// make cachedir if it doesn't exist
		if(!is_dir($this->cachePath))
		{
			if(!mkdir($this->cachePath))
			{
				show_error('Could not create cache directory "'.$this->cachePath.'", pleaes check folder permissions');
			}
		} 
		
		// set carriage return
		$this->carriageRreturn = "\r\n";
		
		// cache file life span
		$this->cacheLife = 24 * 60 * 60; // default 1 day
		
		// debug mode?
		$this->useCaching = true;
	}
	
	/**
	 * Load Scripts, scipts can be requested as seperate argumetns or one comma seperated list
	 * 
	 * @param $scripts
	 */
	public function get()
	{
		// get the parameters
		$params = func_get_args();
		
		// if there's only one arg, go CSL style
		if(count($params) == 1)
		{
			$params = explode(',', $params[0]);
		}
		
		// split out the css and js files
		$this->splitFileTypes($params);
		
		// output file tags
		$this->outputCacheFiles();
	}
	
	/**
	 * Split up parameters in CSS and JS arrays
	 */
	private function splitFileTypes($files)
	{		
		// loop thorugh requested script files, decide if their js or css and add to appropriate array
		foreach($files as $file)
		{
			// make sure there's no white space around the file name
			$file = trim($file);
			
			// attempt to load the file
			if(file_exists(FCPATH.$file))
			{
				// work out file type
				$ext = strtolower(array_pop(explode('.', $file)));
				
				if($ext == 'css') $this->allFiles['css'][] = $file;
				else if($ext == 'js') $this->allFiles['js'][] = $file;
				else show_error('File "'.$file.'" does not seem to be a CSS or JavaScript file');
			}
			else show_error('Failed to load file "'.FCPATH.$file.'", files must be specified from root, eg: "/css/style.css", NOT "http://msysite.com/css/style.css" or "../css/style.css"');
		}
	}
	
	/**
	 * Attempt to get file from cache, save it to cache if its not there
	 */
	private function getFromCache($fileArray, $ext)
	{		
		// calculate hash of each the requests
		$fileName = sha1(implode('|', $fileArray)).'.'.$ext;
		
		$cacheFilePath = $this->cachePath.$fileName;
		
		// is it aready in cache
		if(!$fromCache = file_exists($cacheFilePath))
		{
			$this->createCacheFile($cacheFilePath, $fileArray);
		}
		
		// file exists, see if it's expired
		else
		{
			// find out when file was last modified
			$dateModified = get_file_info($cacheFilePath, array('date'));
			
			// see if date modified + life span is less than now, if so, re cache
			if($dateModified['date'] + $this->cacheLife < time())
			{
				$this->createCacheFile($cacheFilePath, $fileArray);
			}
		}
		
		return $fileName;
	}
	
	/**
	 * Create cachefile
	 */
	private function createCacheFile($name, $fileArray)
	{
		// not in cache, so save it to chache
		$data = $this->conCatFiles($fileArray);
			
		if(!write_file($name, $data))
		{
			show_error('Could not write cache file to "'.$name.'"');
		}
	}
	
	/**
	 * Concatinate array of files
	 * @param (array) $files
	 * @return (string) $concat
	 */
	private function conCatFiles($files)
	{
		// concatinated data to be returned
		$concatinated = '';
		
		// we'll be adding 2 carriage returns between files
		$cr = $this->carriageRreturn.$this->carriageRreturn;
		
		// load each file and join with a return char seperateor
		foreach($files as $file)
		{
			$concatinated.=(strlen($concatinated) ? $cr :'').read_file(FCPATH.$file);
		}
		
		return $concatinated;
	}
	
	/**
	 * Out put cache files
	 */
	private function outputCacheFiles()
	{
		foreach($this->allFiles as $ext=>$files)
		{
			if(count($files))
			{
				// if in debug mode, just output the files
				if($this->useCaching)
				{
					$cacheFile = $this->getFromCache($files, $ext);
					$this->fileOut('/'.$this->cacheDir.$cacheFile, $ext);
				}
				else 
				{
					foreach($files as $file)
					{
						$this->fileOut($file, $ext);
					}
				}
			}
		}
	}
	
	/**
	 * Output file tag
	 */
	private function fileOut($fileName, $type)
	{		
		switch($type)
		{
			case 'css':
				echo '<link rel="stylesheet" type="text/css" media="all" href="'.$fileName.'" />';
				break;
				
			case 'js':
				echo '<script type="text/javascript" src="'.$fileName.'"></script>';
				break;
		}
	}	
}