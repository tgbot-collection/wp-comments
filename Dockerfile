FROM golang:alpine as builder

RUN apk update && apk add git make ca-certificates && \
git clone https://github.com/tgbot-collection/wp-comments-tgbot /build && \
cd /build && make static


FROM scratch

COPY --from=builder /build/comments /comments
COPY --from=builder /etc/ssl/certs/ca-certificates.crt /etc/ssl/certs/
WORKDIR /

ENTRYPOINT ["/comments","-c","/config.json"]

# docker run -d --restart=always-v ./config.json:/config.json bennythink/archiver
