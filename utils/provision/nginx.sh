echo "Configuring NGINX..."

if [[ -f "$DOCUMENT_ROOT/index.nginx-debian.html" ]]; then
	rm "$DOCUMENT_ROOT/index.nginx-debian.html"
	fi
	
if [[ -f "/etc/nginx/sites-enabled/default" ]]; then
	rm "/etc/nginx/sites-enabled/default"
	fi

cat << 'EOF' > "/etc/nginx/sites-available/tarallo-server.conf"
server {
	listen 80 default_server;
	root /var/www/html/server;
	index index.php;
	server_name _;
	sendfile off; # broken in virtualbox
	
	location ~ \.(css|js)$ {
	root /var/www/html/server/SSRv1/static;
	}
	
	location / {
	include fastcgi_params;
	try_files $uri /index.php;
	
			fastcgi_param PATH_INFO $uri;
			fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
			fastcgi_pass 127.0.0.1:9000;
			}
			

EOF

cat << 'EOF' > "/etc/nginx/sites-available/tarallo-admin.conf"
server {
	listen 81 default_server;
	root /var/www/html/admin;
	server_name _;
	sendfile off; # broken in virtualbox
	
	location / {
	autoindex on;
	try_files $uri $uri/ =404;
	}

	location ~ \.php$ {
	include fastcgi_params;
	fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
	fastcgi_pass unix:/run/php/php7.0-fpm.sock;
	}
	}

EOF

ln -sf /etc/nginx/sites-available/tarallo-admin.conf /etc/nginx/sites-enabled/
ln -sf /etc/nginx/sites-available/tarallo-server.conf /etc/nginx/sites-enabled/

echo "Restarting NGINX..."

systemctl restart nginx
