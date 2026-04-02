
FROM php:7.4-apache AS apache

COPY apache/000-default.conf /etc/apache2/sites-enabled/000-default.conf
RUN a2enmod rewrite

# Copy application source
COPY . /var/www/html/


# Install Composer 2
RUN curl -sS https://getcomposer.org/installer | php -- --2 && \
	mv composer.phar /usr/local/bin/composer && \
	chmod a+rx /usr/local/bin/composer
RUN chmod -R 777 /var/www

RUN apt-get update
RUN apt-get install -y git zip unzip netcat-openbsd
RUN docker-php-ext-install pdo_mysql bcmath sockets
RUN composer install --no-plugins --no-scripts --optimize-autoloader --no-dev --no-progress
RUN apt-get install -y python3.9 && apt-get install -y python3-pip
RUN pip3 install numpy soundfile Pillow
RUN pip3 install scikit-maad matplotlib librosa
RUN pip3 install tensorflow
RUN pip3 install batdetect2
RUN pip3 install autrainer==0.7.1
RUN apt-get install -y libsndfile1-dev libasound2-dev imagemagick montage sox lame ffmpeg libsox-fmt-mp3
RUN chown -R www-data:www-data /var/www/html/sounds /var/www/html/tmp /var/www/html/cache
RUN chmod +x /var/www/html/bin/svt.py
RUN chmod +x /var/www/html/bin/getMaad.py


FROM rabbitmq:3.8.4-rc.3-alpine AS queue
ENV RABBITMQ_PID_FILE /var/lib/rabbitmq/mnesia/rabbitmq
RUN apk add python3
ADD rabbitmq/init.sh /init.sh
RUN chmod +x /init.sh

# Define default command
CMD ["/init.sh"]
