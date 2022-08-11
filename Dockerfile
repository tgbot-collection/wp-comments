FROM golang:1.19.0-alpine as builder

RUN apk update && apk add  --no-cache make ca-certificates && mkdir /build
COPY go.mod /build
RUN cd /build && go mod download
COPY . /build
RUN cd /build && make static


FROM scratch

COPY --from=builder /build/comments /comments
COPY --from=builder /etc/ssl/certs/ca-certificates.crt /etc/ssl/certs/
WORKDIR /

ENTRYPOINT ["/comments","-c","/config.json"]

# docker run -d --restart=always-v ./config.json:/config.json bennythink/archiver
