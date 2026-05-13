FROM phpmyadmin:latest
RUN echo 'ServerName localhost' > /etc/apache2/conf-available/servername.conf \
    && a2enconf servername
RUN printf '%s\n' \
    '<Directory "/var/www/html">' \
    '  <FilesMatch "^\\.">' \
    '    Require all denied' \
    '  </FilesMatch>' \
    '</Directory>' > /etc/apache2/conf-available/hide-dotfiles.conf \
    && a2enconf hide-dotfiles
