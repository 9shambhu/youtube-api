FROM php:8.2-apache

# Install Python, FFmpeg (Crucial for YouTube 1080p), and Git
RUN apt-get update && apt-get install -y \
    python3 \
    python3-pip \
    ffmpeg \
    git \
    && rm -rf /var/lib/apt/lists/*

# Install yt-dlp (Using master branch for latest YouTube fixes)
RUN pip3 install https://github.com/yt-dlp/yt-dlp/archive/master.zip --break-system-packages

# Setup Folders and Permissions
RUN a2enmod rewrite
RUN mkdir -p /var/www/html/downloads \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

COPY . /var/www/html/

EXPOSE 80
