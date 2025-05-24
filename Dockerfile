FROM php:8.2-apache

# 安装 GD 库及其依赖
RUN apt-get update && apt-get install -y \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libpng-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# 设置 Apache 使用 Railway 分配的端口（默认会传入环境变量 $PORT）
# Railway 会自动设置 PORT 环境变量，这里可以作为备用
ENV PORT ${PORT:-8080}
RUN sed -i "s/Listen 80/Listen \${PORT}/g" /etc/apache2/ports.conf \
    && sed -i "s/:80/:${PORT}/g" /etc/apache2/sites-available/000-default.conf

# 开启 mod_rewrite（如果你需要 URL 重写功能）
RUN a2enmod rewrite

# 把代码复制到 Apache 的 web 根目录
COPY . /var/www/html

# （可选）确保 Apache 和 PHP 错误日志输出到 stdout/stderr，方便 Railway 捕获
# 通常 php:apache 镜像会处理好 Apache 日志，PHP 错误可以通过 php.ini 配置
# RUN echo "error_log = /dev/stderr" >> /usr/local/etc/php/conf.d/docker-php-ext- Merror-logging.ini
# RUN echo "log_errors = On" >> /usr/local/etc/php/conf.d/docker-php-ext-error-logging.ini
# RUN echo "display_errors = Off" >> /usr/local/etc/php/conf.d/docker-php-ext-error-logging.ini