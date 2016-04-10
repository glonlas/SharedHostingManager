# SharedHostingManager
A tool to add user on a shared hosting LEMP server
This tool will help you to setup a new PHP website account on your server.
It will optimise your Nginx depending of the PHP framework/CMS your website/app
uses:
* Symfony2
* Wordpress
* Vanilla

## Pre-requisite
* PHP7
* [Composer](https://getcomposer.org/)
* Nginx
* MySQL

# Installation
1. `git clone git@github.com:glonlas/SharedHostingManager.git`
2. `cd SharedHostingManager`
3. `php composer.phar install`


## Commands
### Add a new website/app (domain)
`php app/console website:add`

### Delete a website/app (domain)
`php app/console website:remove`

### Change MySQL and FTP password for an existing website/app (domain)
`php app/console website:password`

