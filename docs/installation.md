# Installation & Setup Guide

This document provides step-by-step instructions to get the **IslamResearch** project running locally using **Laravel Sail**.

---

## 🚀 Local Environment Setup

Follow these steps to initialize the project:

### 1. Clone the repository
```bash
git clone <repository-url>
cd IslamResearch
```

### 2. Environment Setup
Copy the example environment file:
```bash
cp .env.example .env
```

### 3. Install PHP Dependencies
If you have Composer installed locally, run:
```bash
composer install
```
Alternatively, run it via Docker:
```bash
docker run --rm \
    -u "$(id -u):$(id -g)" \
    -v "$(pwd):/var/www/html" \
    -w /var/www/html \
    laravelsail/php85-composer:latest \
    composer install --ignore-platform-reqs
```

### 4. Start Laravel Sail
Start the Docker containers in detached mode:
```bash
./vendor/bin/sail up -d
```

### 5. Application Initialization
Generate the application key and run database migrations:
```bash
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate
```

### 6. Install Frontend Assets
```bash
./vendor/bin/sail npm install
./vendor/bin/sail npm run build
```

---

## 🔗 Accessible Services

Once the Sail containers are up and running, you can access the following services:

| Service | URL | Description |
| :--- | :--- | :--- |
| **Main Application** | [http://localhost](http://localhost) | The main user interface |
| **Filament Admin** | [http://localhost/admin](http://localhost/admin) | Admin dashboard for data management |
| **Prefect UI** | [http://localhost:4200](http://localhost:4200) | AI Pipeline orchestration dashboard |
| **Laravel Horizon** | [http://localhost/horizon](http://localhost/horizon) | Queue and job monitoring |
| **Laravel Telescope** | [http://localhost/telescope](http://localhost/telescope) | Debugging and performance insights |
| **Meilisearch** | [http://localhost:7700](http://localhost:7700) | Search engine dashboard |

---

## 🤖 AI Pipeline Prerequisites

For the AI pipeline to function correctly, ensure that:
1. The **Ollama** service is accessible (usually via the `ollama` container).
2. The required models (e.g., `aya-23-8b`) are pulled or available on the host/GPU.
3. The `ai-pipeline` container is healthy and connected to the internal network.
