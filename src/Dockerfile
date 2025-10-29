
FROM php:7.4-apache AS apache

# Ensure Transformers uses PyTorch only inside the container and reduce noisy logs
ENV TRANSFORMERS_NO_TF=1 \
	TRANSFORMERS_NO_JAX=1 \
	TRANSFORMERS_NO_TORCHVISION=1 \
	TOKENIZERS_PARALLELISM=false \
	HF_HUB_DISABLE_PROGRESS_BARS=1 \
	PYTHONUNBUFFERED=1

COPY apache/000-default.conf /etc/apache2/sites-enabled/000-default.conf
RUN a2enmod rewrite

# Copy application source
COPY . /var/www/html/

ADD https://getcomposer.org/download/1.7.3/composer.phar /usr/local/bin/composer
RUN chmod -R 777 /var/www
RUN chmod a+rx /usr/local/bin/composer

RUN apt-get update
RUN apt-get install -y git zip unzip netcat \
	python-dev python-setuptools libsndfile1-dev libasound2-dev \
	imagemagick montage sox lame ffmpeg libsox-fmt-mp3
RUN docker-php-ext-install pdo_mysql bcmath sockets
RUN composer install --no-plugins --no-scripts --optimize-autoloader --no-dev --no-progress
RUN apt-get install -y python && curl https://bootstrap.pypa.io/pip/2.7/get-pip.py --output get-pip.py && python get-pip.py && python -m pip install --no-cache-dir numpy
RUN apt-get install -y python3.9 && apt-get install -y python3-pip
# Upgrade pip first
RUN pip3 install --no-cache-dir --upgrade pip setuptools wheel
# Install PyTorch CPU first to avoid multiple versions (TensorFlow/batdetect2/autrainer may pull torch)
# Install PyTorch CPU first to avoid multiple versions (TensorFlow/batdetect2/autrainer may pull torch)
RUN pip3 install --no-cache-dir --index-url https://download.pytorch.org/whl/cpu torch==2.3.1 torchaudio==2.3.1 torchvision==0.18.1
# Install numpy version compatible with autrainer before other packages
RUN pip3 install --no-cache-dir "numpy>=1.24.1,<2.0.0"
# Install Transformers and soundfile for HuggingFace inference (force reinstall to ensure clean install)
RUN pip3 install --no-cache-dir --force-reinstall transformers>=4.30.0 soundfile>=0.12.0
# Install remaining packages (they'll use the already-installed PyTorch and numpy)
RUN pip3 install --no-cache-dir scikit-maad matplotlib tensorflow librosa batdetect2 autrainer
# (moved earlier) system libs already installed above
RUN chown -R www-data:www-data /var/www/html/sounds /var/www/html/tmp /var/www/html/cache
RUN chmod +x /var/www/html/bin/svt.py
RUN chmod +x /var/www/html/bin/getMaad.py
RUN chmod -R 777 /usr/local/lib/python3.9
# Install Python 2-only tools dependencies with pip for python2
RUN python -m pip install --no-cache-dir scikits.audiolab==0.8 Pillow


FROM rabbitmq:3.8.4-rc.3-alpine AS queue
ENV RABBITMQ_PID_FILE /var/lib/rabbitmq/mnesia/rabbitmq
RUN apk add python3
ADD rabbitmq/init.sh /init.sh
RUN chmod +x /init.sh

# Define default command
CMD ["/init.sh"]
