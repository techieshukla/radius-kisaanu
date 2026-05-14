FROM php:8.3-apache

RUN echo 'ServerName localhost' > /etc/apache2/conf-available/servername.conf \
    && a2enconf servername

RUN printf '%s\n' \
    '<Directory "/var/www/html">' \
    '  <FilesMatch "^\\.">' \
    '    Require all denied' \
    '  </FilesMatch>' \
    '</Directory>' > /etc/apache2/conf-available/hide-dotfiles.conf \
    && a2enconf hide-dotfiles

RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip libpq-dev libzip-dev \
    && docker-php-ext-install mysqli pdo pdo_mysql \
    && pear install DB \
    && rm -rf /var/lib/apt/lists/*

RUN printf '%s\n' \
    'display_errors=Off' \
    'log_errors=On' \
    'error_log=/proc/self/fd/2' \
    'error_reporting=E_ALL & ~E_DEPRECATED & ~E_STRICT' \
    > /usr/local/etc/php/conf.d/zz-daloradius-production.ini

RUN git clone --depth=1 https://github.com/lirantal/daloradius.git /var/www/html/daloradius \
    && perl -i -pe 's/function getNextBillingDate\(\$planRecurringBillingSchedule = "Fixed", \$planRecurringPeriod, \$billDates = null\)/function getNextBillingDate(\$planRecurringBillingSchedule, \$planRecurringPeriod, \$billDates = null)/g' /var/www/html/daloradius/app/operators/include/management/pages_common.php \
    && perl -i -pe 's/function getPrevBillingDate\(\$planRecurringBillingSchedule = "Fixed", \$planRecurringPeriod, \$billDates = null\)/function getPrevBillingDate(\$planRecurringBillingSchedule, \$planRecurringPeriod, \$billDates = null)/g' /var/www/html/daloradius/app/operators/include/management/pages_common.php \
    && perl -i -pe 's/return htmlspecialchars\(trim\(\$value\), ENT_QUOTES, '\''UTF-8'\''\);/return htmlspecialchars(trim((string)(\$value ?? '\'''\'')), ENT_QUOTES, '\''UTF-8'\'');/g' /var/www/html/daloradius/app/operators/home-main.php \
    && perl -i -pe 's/\$configValues\['\''CONFIG_NODE_STATUS_MONITOR_EMAIL_TO'\''\]/(\$configValues['\''CONFIG_NODE_STATUS_MONITOR_EMAIL_TO'\''] ?? '\'''\'' )/g' /var/www/html/daloradius/app/operators/config-crontab.php \
    && perl -i -pe 's/trim\(\$input_descriptor\['\''(value|min|max|step)'\''\]\)/trim((string)(\$input_descriptor['\''$1'\''] ?? '\'''\''))/g' /var/www/html/daloradius/app/common/includes/layout.php \
    && perl -i -pe 's/htmlspecialchars\(\$row0\[\$i\], ENT_QUOTES, '\''UTF-8'\''\)/htmlspecialchars((string)(\$row0[\$i] ?? '\'''\''), ENT_QUOTES, '\''UTF-8'\'')/g' /var/www/html/daloradius/app/operators/mng-rad-usergroup-list.php \
    && perl -i -pe 's/htmlspecialchars\(\$row1\[\$i\], ENT_QUOTES, '\''UTF-8'\''\)/htmlspecialchars((string)(\$row1[\$i] ?? '\'''\''), ENT_QUOTES, '\''UTF-8'\'')/g' /var/www/html/daloradius/app/operators/mng-rad-usergroup-list.php \
    && chown -R www-data:www-data /var/www/html/daloradius

COPY daloradius/root-index.php /var/www/html/index.php
COPY daloradius/entrypoint.sh /usr/local/bin/daloradius-entrypoint.sh
RUN chmod +x /usr/local/bin/daloradius-entrypoint.sh

EXPOSE 80
ENTRYPOINT ["/usr/local/bin/daloradius-entrypoint.sh"]
