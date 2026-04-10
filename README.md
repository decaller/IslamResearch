# IslamResearch

A Laravel-based platform for Islamic research, featuring Livewire, Filament v5, Prefect-orchestrated AI pipelines, and vector search capabilities.

## 🚀 Installation & Setup

Follow these steps to get the project running locally using **Laravel Sail**:

For more detailed documentation, please refer to the [docs folder](./docs/):
- [Architecture](./docs/architecture.md)
- [AI Pipeline Blueprint](./docs/pipeline.md)
- [Data Sources](./docs/data-sources.md)
- [Implementation Details](./docs/details.md)

1.  **Clone the repository**:
    ```bash
    git clone <repository-url>
    cd IslamResearch
    ```

2.  **Environment Setup**:
    Copy the example environment file:
    ```bash
    cp .env.example .env
    ```

3.  **Install Dependencies**:
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

4.  **Start Laravel Sail**:
    ```bash
    ./vendor/bin/sail up -d
    ```

5.  **Generate Application Key & Migrate**:
    ```bash
    ./vendor/bin/sail artisan key:generate
    ./vendor/bin/sail artisan migrate
    ```

6.  **Install Frontend Assets**:
    ```bash
    ./vendor/bin/sail npm install
    ./vendor/bin/sail npm run build
    ```

---

## 🔗 Accessible URLs (Local Environment)

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

## 🤖 AI Pipeline Setup (Prefect)

The project includes an automated AI factory orchestrated by Prefect.

### 1. Prefect UI
Access the Prefect dashboard at [http://localhost:4200](http://localhost:4200) to monitor and manage your Islamic text processing flows.

### 2. AI Worker Container
The `ai-pipeline` container runs the Prefect worker, which:
- Executes **Fast/CPU** tasks (Classification, Segmentation).
- Orchestrates **Slow/GPU** tasks (Ollama Translation/Enrichment).

### 3. Triggering Flows
Flows are triggered from the Laravel Filament dashboard via the Prefect REST API.

---

---

## 🛠 Tech Stack

- **Framework**: Laravel 13
- **Frontend**: Livewire 4, Tailwind CSS 4
- **Admin Panel**: Filament v5
- **AI Orchestration**: Prefect
- **Database**: PostgreSQL with pgvector
- **Search**: Meilisearch
- **Dev Environment**: Laravel Sail
