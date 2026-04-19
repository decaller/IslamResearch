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
RUN pip install --no-cache-dir "prefect>=3.0.0" transformers torch sentence-transformers requests "spacy>=3.7.0,<3.8.0" psycopg2-binary fastapi uvicorn numpy wikipedia-api python-dotenv

# Install heavier camel-tools
RUN pip install --no-cache-dir camel-tools

# Download CAMeL Tools default datasets (required for Analyzer)
# Note: This is around 500MB
RUN camel_data -i defaults

# Download SpaCy English model
RUN python -m spacy download en_core_web_sm

WORKDIR /app
# Default command (will be overridden by compose)
CMD ["python", "serve_pipeline.py"]
