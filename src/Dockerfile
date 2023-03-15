FROM php:7.4-apache AS apache

COPY apache/000-default.conf /etc/apache2/sites-enabled/000-default.conf
RUN a2enmod rewrite

# Copy application source
COPY . /var/www/html/

ADD https://getcomposer.org/download/1.7.3/composer.phar /usr/local/bin/composer
RUN chmod a+rx /usr/local/bin/composer

RUN sed -i "s@http://deb.debian.org@http://mirrors.aliyun.com@g" /etc/apt/sources.list
RUN cat /etc/apt/sources.list
RUN rm -Rf /var/lib/apt/lists/*

RUN apt-get update
RUN apt-get install -y git zip unzip netcat
RUN docker-php-ext-install pdo_mysql bcmath sockets
RUN composer install --no-plugins --no-scripts --optimize-autoloader --no-dev --no-progress
RUN apt-get install -y python && curl https://bootstrap.pypa.io/pip/2.7/get-pip.py --output get-pip.py && python get-pip.py -i http://pypi.douban.com/simple/ --trusted-host pypi.douban.com && pip install numpy -i http://pypi.douban.com/simple/ --trusted-host pypi.douban.com
RUN apt-get install -y python3.9 && apt-get install -y python3-pip
RUN pip3 install scikit-maad -i http://pypi.douban.com/simple/ --trusted-host pypi.douban.com && pip3 install matplotlib -i http://pypi.douban.com/simple/ --trusted-host pypi.douban.com && pip3 install tensorflow -i http://pypi.douban.com/simple/ --trusted-host pypi.douban.com && pip3 install librosa -i http://pypi.douban.com/simple/ --trusted-host pypi.douban.com
RUN apt-get install -y python-dev python-setuptools libsndfile1-dev libasound2-dev imagemagick montage sox lame ffmpeg
RUN chown -R www-data:www-data /var/www/html/sounds /var/www/html/tmp /var/www/html/cache
RUN chmod +x /var/www/html/bin/svt.py
RUN chmod +x /var/www/html/bin/getMaad.py
RUN chmod -R 777 /usr/local/lib/python3.9
RUN pip install scikits.audiolab==0.8 Pillow -i http://pypi.douban.com/simple/ --trusted-host pypi.douban.com


FROM rabbitmq:3.8.4-rc.3-alpine AS queue
ENV RABBITMQ_PID_FILE /var/lib/rabbitmq/mnesia/rabbitmq

ADD rabbitmq/init.sh /init.sh
RUN chmod +x /init.sh

# Define default command
CMD ["/init.sh"]
