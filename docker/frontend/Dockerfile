FROM node:latest

ARG DEVELOPMENT

ENV DEVELOPMENT=${DEVELOPMENT}

WORKDIR /usr/src

COPY frontend/package.json ./

RUN npm i --prefix /usr/src/

WORKDIR /usr/src/app

COPY ./frontend .

ENV NODE_PATH=/usr/src/node_modules

# https://github.com/vuejs/devtools/issues/977
ENV NODE_OPTIONS='--no-webstorage'

CMD  if [ "$DEVELOPMENT" = "true" ]; then \
	npm run dev; \
else \
	npm run prod; \
fi