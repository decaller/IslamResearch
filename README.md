# IslamResearch

A Laravel-based platform for Islamic research, featuring Livewire, Filament v5, n8n, and vector search capabilities.

## 🚀 Installation & Setup

Follow these steps to get the project running locally using **Laravel Sail**:

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
| **Main Application** | [http://localhost:8000](http://localhost:8000) | The main user interface |
| **Filament Admin** | [http://localhost:8000/admin](http://localhost:8000/admin) | Admin dashboard for data management |
| **n8n Workflow** | [http://localhost:5678](http://localhost:5678) | Automation and workflow engine |
| **Laravel Horizon** | [http://localhost:8000/horizon](http://localhost:8000/horizon) | Queue and job monitoring |
| **Laravel Telescope** | [http://localhost:8000/telescope](http://localhost:8000/telescope) | Debugging and performance insights |
| **Meilisearch** | [http://localhost:7700](http://localhost:7700) | Search engine dashboard |

---

## 🤖 n8n Setup & Automation

The project includes an automated n8n setup with pre-loaded workflows and credentials.

### 1. Initial Setup
When you first access n8n at [http://localhost:5678](http://localhost:5678), it will ask you to register an owner account. 
- **Register manually** in the browser to set your own credentials.
- After login, you will find the **"Webhook Trigger Flow"** already imported and active.

### 2. Automated Imports
The project uses a sidecar container called `n8n-provision` to automatically import:
- **Ollama Credentials**: Pre-configured to connect to the local Ollama instance.
- **Example Workflows**: Located in the `./flow` directory.

### 3. Testing the Webhook
You can verify the n8n automation is working by triggering the example webhook from your host machine or via Sail:

**Via Curl (Sail):**
```bash
./vendor/bin/sail shell -c "curl -i http://n8n:5678/webhook/test-webhook"
```

**Expected Response:** `{"message":"Workflow was started"}`

---

---

## 🛠 Tech Stack

- **Framework**: Laravel 13
- **Frontend**: Livewire 4, Tailwind CSS 4
- **Admin Panel**: Filament v5
- **Automation**: n8n
- **Database**: PostgreSQL with TimescaleDB (pgvectorscale)
- **Search**: Meilisearch
- **Dev Environment**: Laravel Sail
