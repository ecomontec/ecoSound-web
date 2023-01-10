FROM php:7.4-apache AS apache

COPY apache/000-default.conf /etc/apache2/sites-enabled/000-default.conf
RUN a2enmod rewrite

# Copy application source
COPY . /var/www/html/

RUN curl https://getcomposer.org/download/1.7.3/composer.phar --output /usr/local/bin/composer
RUN chmod a+rx /usr/local/bin/composer

RUN apt-get update
RUN apt-get install -y git zip unzip netcat
RUN docker-php-ext-install pdo_mysql bcmath sockets
RUN apt-get install -y --no-install-recommends ffmpeg

RUN composer install --no-plugins --no-scripts --optimize-autoloader --no-dev --no-progress
RUN apt-get install -y python && curl https://bootstrap.pypa.io/pip/2.7/get-pip.py --output get-pip.py && python get-pip.py && pip install numpy
RUN apt-get install -y python3 && apt-get install -y python3-pip && pip3 install scikit-maad && pip3 install matplotlib scipy librosa bottle
RUN apt-get install -y python-dev python-setuptools libsndfile1-dev libasound2-dev imagemagick montage sox lame
RUN chown -R www-data:www-data /var/www/html/sounds /var/www/html/tmp /var/www/html/cache
RUN chmod +x /var/www/html/bin/svt.py
RUN chmod +x /var/www/html/bin/getMaad.py
RUN pip install scikits.audiolab==0.8 Pillow
RUN pip3 install tensorflow 
RUN pip3 install torch torchvision torchaudio --extra-index-url https://download.pytorch.org/whl/cpu
RUN cd /var/www/html/ && git clone https://github.com/kahst/BirdNET-Analyzer.git

FROM rabbitmq:3.8.4-rc.3-alpine AS queue
ENV RABBITMQ_PID_FILE /var/lib/rabbitmq/mnesia/rabbitmq

ADD rabbitmq/init.sh /init.sh
RUN chmod +x /init.sh

# Define default command
CMD ["/init.sh"]
