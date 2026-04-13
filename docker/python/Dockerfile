FROM python:3.11-slim

# Install system dependencies
RUN apt-get update && apt-get install -y \
    gcc \
    g++ \
    curl \
    libpq-dev \
    git \
    && rm -rf /var/lib/apt/lists/*

# Install core Python dependencies first
RUN pip install --no-cache-dir prefect transformers torch requests "spacy>=3.7.0,<3.8.0" psycopg2-binary

# Install heavier camel-tools separately
RUN pip install --no-cache-dir camel-tools

# The script already falls back to spacy.blank('ar') if ar_core_news_sm is not found
# and ar_core_news_sm is not an official model in current spaCy releases.

WORKDIR /app
CMD ["tail", "-f", "/dev/null"]
