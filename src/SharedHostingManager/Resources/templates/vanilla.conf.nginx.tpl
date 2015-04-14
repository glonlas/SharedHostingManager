##
# Symfony2 preset for %url%
##
server {
    #listen 80 is default
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

    index index.php index.html index.htm;
    location ~ ^/(.+\.php)$ {
        try_files $uri =404;
        fastcgi_pass unix:/var/run/%url%.sock;

        include fastcgi_params;
        fastcgi_index index.php;
        fastcgi_split_path_info ^(.+\.php)(/.+)$;
        fastcgi_param PATH_INFO $fastcgi_path_info;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;

        send_timeout 1800;
        fastcgi_read_timeout 1800;

        internal;
    }

    # Caching
    location ~* \.(ico|jpg|webp|jpeg|gif|css|png|js|ico|bmp|zip|woff)$ {
        access_log off;
        log_not_found off;
        add_header Pragma public;
        add_header Cache-Control "public";
        expires 5d;
    }

    location ~* \.(php|html)$ {
        access_log on;
        log_not_found on;
        add_header Pragma public;
        add_header Cache-Control "public";
        expires 5d;
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
    location ~ /\.ht {
        deny all;
    }

    include preset/h5bp/basic.conf;
}

