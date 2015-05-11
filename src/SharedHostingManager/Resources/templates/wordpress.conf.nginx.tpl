##
# Wordpress preset for %url%
##
server {
	server_name %hostname%;
	return 301 $scheme://%url%$request_uri;
}

server {
	server_name %url%;
	root %htdocs%;

	error_log /var/log/nginx/%url%_error.log;
	#access_log /var/log/nginx/%url%_access.log;
	access_log off;

	client_max_body_size 4M;

	# Default location
	location / {
		index index.php index.html index.htm;
		try_files $uri/ $uri /index.php?q=$uri&$args;

		# Cache header
		add_header X-Cache-Status $upstream_cache_status;

		# Parse all .php file in the %htdocs% directory
		location ~ \.php$ {

			# Setup caching 
			set $no_cache "";

			# Don't cache POSTs
			if ($request_method = POST)
			{
			    set $no_cache 1;
			}

			# Don't cache the following URLs
			if ($request_uri ~* "/(wp-admin/|wp-login.php)")
			{
			    set $no_cache 1;
			}

			# Bypass if WordPress admin cookie is set
			if ($http_cookie ~* "wordpress_logged_in_")
			{
			    set $no_cache 1;
			}

			# Bypass cache if flag is set
			fastcgi_no_cache $no_cache;
			fastcgi_cache_bypass $no_cache;
			fastcgi_cache drm_custom_cache;
			fastcgi_cache_key $server_name|$request_uri;

			# Cache times
			fastcgi_cache_valid 404 60m;
			fastcgi_cache_valid 200 60m;
			fastcgi_max_temp_file_size 4m;
			fastcgi_cache_use_stale updating;
			fastcgi_pass unix:/var/run/%url%.sock;

			# Additional configs
			fastcgi_pass_header Set-Cookie;
			fastcgi_pass_header Cookie;
			fastcgi_ignore_headers Cache-Control Expires Set-Cookie;
			fastcgi_index index.php;
			fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
			fastcgi_split_path_info ^(.+\.php)(/.+)$;
			fastcgi_param  PATH_INFO $fastcgi_path_info;
			fastcgi_param  PATH_TRANSLATED $document_root$fastcgi_path_info;
			fastcgi_intercept_errors on;
			include fastcgi_params;
		}

		# Caching
		location ~* \.(ico|jpg|webp|jpeg|gif|css|png|js|ico|bmp|zip|woff)$ {
			access_log off;
			log_not_found off;
			add_header Pragma public;
			add_header Cache-Control "public";
			expires 14d;
		}

		# SECURITY
		# Ignore other host headers
		if ($host !~* ^(%url%|%hostname%)$ ) {
			return 444;
		}

		# Only allow GET , POST, and HEAD 
		if ($request_method !~ ^(GET|POST|HEAD)$ ) {
			return 444;
		}

		# Disable viewing of hidden files (files starting with a dot)
		location ~ /\. {
			deny  all;
		}
	} 

	include preset/wordpress/wpsecure.conf;
	include preset/wordpress/wpnocache.conf;
}
