FROM python:3.14-slim

WORKDIR /app

# Install system dependencies for Pillow/lxml
RUN apt-get update && apt-get install -y --no-install-recommends \
	libxml2 \
	libxslt1.1 \
	libjpeg62-turbo \
	libpng16-16 \
	&& rm -rf /var/lib/apt/lists/*

COPY pyproject.toml ./
COPY ./app ./app
COPY ./cli ./cli
COPY ./templates ./templates
COPY ./tests ./tests

RUN pip install --no-cache-dir .

#RUN mkdir -p data/log data/thumbnail

# Expose port 8000
EXPOSE 8000

CMD ["uvicorn", "app.main:app", "--host", "0.0.0.0", "--port", "8000", "--proxy-headers"]