FROM php:8.2-apache

# 设置 Apache 使用 Railway 分配的端口（默认会传入环境变量 $PORT）
ENV PORT=8080
RUN sed -i "s/80/\${PORT}/g" /etc/apache2/ports.conf /etc/apache2/sites-available/000-default.conf

# 开启 mod_rewrite（如果你需要 URL 重写功能）
RUN a2enmod rewrite

# 把代码复制到 Apache 的 web 根目录
COPY . /var/www/html
