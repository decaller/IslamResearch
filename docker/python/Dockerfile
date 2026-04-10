FROM python:3.11-slim

# Install system dependencies
RUN apt-get update && apt-get install -y gcc g++ curl libpq-dev

# Install Python ML and Pipeline dependencies
RUN pip install prefect transformers torch requests spacy psycopg2-binary camel-tools
RUN python -m spacy download ar_core_news_sm

WORKDIR /app
CMD ["tail", "-f", "/dev/null"] # Keeps container alive for background tasks
