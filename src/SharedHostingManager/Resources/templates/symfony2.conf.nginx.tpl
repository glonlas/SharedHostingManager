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

    index app.php index.html index.htm;

    location ~ \.php(/|$) {
        fastcgi_index app.php;

        #fastcgi_pass 127.0.0.1:9001;
        fastcgi_pass unix:/var/run/%url%.sock;

        include fastcgi_params;
        fastcgi_split_path_info ^(.+\.php)(/.+)$;
        fastcgi_param PATH_INFO $fastcgi_path_info;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;

        send_timeout 1800;
        fastcgi_read_timeout 1800;

        internal;
    }

    include preset/symfony2.conf;
}

