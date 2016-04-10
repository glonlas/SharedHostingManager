[%url%]
listen = /var/run/php/%url%.sock
listen.owner = www-data
listen.group = www-data
user = %username%
group = www-data
pm = dynamic
pm.max_children = 5
pm.start_servers = 2
pm.min_spare_servers = 1
pm.max_spare_servers = 3
chdir = /
php_admin_value[open_basedir] = %home%:/usr/share/php/7.0:/tmp
php_admin_value[disable_functions] = dl,exec,passthru,shell_exec,system,popen,curl_exec,curl_multi_exec,parse_ini_file,show_source