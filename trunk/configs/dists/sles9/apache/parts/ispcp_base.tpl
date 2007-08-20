# httpd Data BEGIN.

#
# Web traffic accounting.
#

LogFormat "%B" traff

#
# GUI Location.
#

Alias /ispcp /srv/www/ispcp/gui
<Directory /srv/www/ispcp/gui>
    AllowOverride none
    Options MultiViews IncludesNoExec FollowSymLinks
    ErrorDocument 404 /ispcp/errordocs/index.php
    DirectoryIndex index.html index.php
</Directory>

<Directory /srv/www/ispcp/gui/tools/filemanager>
    php_flag register_globals On
    php_admin_value open_basedir "/srv/www/ispcp/gui/tools/filemanager/:/tmp/:/usr/share/php/"
</Directory>

Alias /ispcp_images /srv/www/ispcp/gui/images
<Directory /srv/www/ispcp/gui/images>
    AllowOverride none 
    Options MultiViews IncludesNoExec FollowSymLinks
</Directory>

#
# Default GUI.
#

<VirtualHost _default_:*> 

    DocumentRoot /srv/www/ispcp/gui

    <Directory /srv/www/ispcp/gui>
        Options Indexes Includes FollowSymLinks MultiViews
        AllowOverride None
        Order allow,deny
        Allow from all
    </Directory>

</VirtualHost>

# httpd [{IP}] virtual host entry BEGIN.
# httpd [{IP}] virtual host entry END.

# httpd Data END.
