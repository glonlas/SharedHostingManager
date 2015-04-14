<?php

namespace SharedHostingManager\Classes;

class Website
{
	private $email 		= null;
	private $url 		= null;
	private $ftp 		= null;
	private $mysql 		= null;
	private $framework 	= null;

	private $hostname 	= null;
	private $username 	= null;
	private $homePath 	= null;
	private $htdocsPath = null;

    const PRESET_WORDPRESS = 'wordpress';
    const PRESET_SYMFONY = 'symfony2';
    const PRESET_VANILLA = 'vanilla';

	public static function newPassword()
	{
		$chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        $length = 10;
        return substr(str_shuffle($chars), 0, $length);
	}

	/**
     * Get Email
     *
     * @return string
     */
    public function getEmail()
    {
    	return $this->email;
    }

    /**
     * Set email
     *
     * @param string $email
     * @return bool
     */
    public function setEmail($email)
    {
    	if (filter_var($email, FILTER_VALIDATE_EMAIL))
		{
    		$this->email = $email;
    		return true;
    	}

    	return false;
    }

	/**
     * Get Website Url
     *
     * @return string
     */
    public function getUrl()
    {
    	return $this->url;
    }

    /**
     * Set Website Url
     *
     * @param string $url
     * @return bool
     */
    public function setUrl($url)
    {
    	if ($url != "" && !is_null($url))
		{
    		$this->url = $url;
    		return true;
    	}

    	return false;
    }

	/**
     * Get FTP password
     *
     * @return string
     */
    public function getFTPPassword()
    {
    	return $this->ftp;
    }

    /**
     * Set FTP password
     *
     * @param string $ftp
     * @return bool
     */
    public function setFTPPassword($ftp = null)
    {
    	// Empty? generate a random password
        if(is_null($ftp))
        {
			$this->ftp = $this->newPassword();
			return false;
        }

    	$this->ftp = $ftp;
    	return true;
    }

	/**
     * Get MySQL password
     *
     * @return string
     */
    public function getMySQLPassword()
    {
    	return $this->mysql;
    }

    /**
     * Set MySQL password
     *
     * @param string $mysql
     * @return bool
     */
    public function setMySQLPassword($mysql = null)
    {
    	// Empty? generate a random password
        if(is_null($mysql))
        {
			$this->mysql = $this->newPassword();
			return false;
        }

    	$this->mysql = $mysql;
    	return true;
    }

	/**
     * Get Framework
     *
     * @return string
     */
    public function getFramework()
    {
        switch($this->framework)
        {
            case 'Wordpress preset':
                return self::PRESET_WORDPRESS;
                break;
            case 'Symfony2 preset':
                return self::PRESET_SYMFONY;
                break;
            default:
                return self::PRESET_VANILLA;
                break;
        }
    }

    /**
     * Set Framework
     *
     * @param string $framework
     * @return void
     */
    public function setFramework($framework)
    {
    	$this->framework = $framework;
    }

    public function getHostname()
    {
    	if(is_null($this->hostname))
    	{
    		$this->hostname = str_replace('www.', '', $this->getUrl());
    	}

    	return $this->hostname;
    }

    public function getUsername()
    {
    	if(is_null($this->username))
    	{
    		$this->username = explode('.', $this->getHostname())[0];
    	}

    	return $this->username;
    }

    public function getHome()
    {
    	if(is_null($this->homePath))
    	{
    		$this->homePath = '/home/'.$this->getHostname();
    	}

    	return $this->homePath;
    }

    public function getHtdocs()
    {
    	if(is_null($this->htdocsPath))
    	{
    		$this->htdocsPath = $this->getHome().'/htdocs';
    	}

    	return $this->htdocsPath;
    }
}