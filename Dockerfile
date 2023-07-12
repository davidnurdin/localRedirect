FROM php:8.2-cli-alpine
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY ./src/ .
RUN export COMPOSER_ALLOW_SUPERUSER=1 ; ./usr/bin/composer install ; rm /usr/bin/composer ; rm -rf /root/.composer ;
# clean
RUN rm /usr/local/bin/php-cgi ;
RUN rm /usr/local/bin/phpdbg ;
RUN rm /usr/src/* ;

CMD ["/localRedirect.php"]

