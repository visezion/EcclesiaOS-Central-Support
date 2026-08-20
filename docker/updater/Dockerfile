FROM alpine:3.21

RUN apk add --no-cache docker-cli docker-cli-compose git python3
COPY docker/updater/server.py /usr/local/bin/update-agent.py

WORKDIR /workspace
EXPOSE 8080
CMD ["python3", "/usr/local/bin/update-agent.py"]
