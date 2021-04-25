FROM nginx:alpine

ARG DEVELOPMENT

# Expose the arguments as environment variables, in case their value might be useful inside the container
ENV DEVELOPMENT=${DEVELOPMENT}

RUN mkdir -p /var/www/html/src
RUN mkdir -p /var/www/html/public

COPY docker/web/nginx.conf /etc/nginx/nginx.conf