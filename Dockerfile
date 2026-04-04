# Build stage
FROM golang:1.25-alpine AS builder

WORKDIR /app

COPY go.mod go.sum ./
RUN go mod download

COPY . .

RUN CGO_ENABLED=0 GOOS=linux go build -o /server ./cmd/server

# Runtime stage
FROM alpine:3.21

RUN apk add --no-cache ca-certificates tzdata

WORKDIR /app

COPY --from=builder /server /app/server
COPY public/css /app/public/css
COPY public/js /app/public/js
COPY public/img /app/public/img

EXPOSE 8080

ENV GOMAXPROCS=8

CMD ["/app/server"]
