@servers(['web' => 'root@100.92.183.79'])

@setup
    $repository = 'https://github.com/decaller/IslamResearch.git';
    $app_dir = '/opt/islamresearch';
@endsetup

@story('deploy')
    initial_setup
    sync_env
    pull_repo
    build_containers
    migrate_database
    optimize_app
@endstory

@task('initial_setup')
    echo "Checking deployment directory..."
    [ -d {{ $app_dir }} ] || mkdir -p {{ $app_dir }}
    cd {{ $app_dir }}
    if [ ! -d .git ]; then
        echo "Cloning repository..."
        git clone {{ $repository }} .
    fi
    if [ ! -d vendor ]; then
        echo "Bootstrapping composer via Docker..."
        docker run --rm -v $(pwd):/app -w /app composer:2 install --ignore-platform-reqs
    fi
@endtask

@task('sync_env')
    echo "Syncing .env file..."
    cd {{ $app_dir }}
    if [ ! -f .env ]; then
        cp .env.example .env
        echo ".env created from .env.example. Please update it manually."
    fi
@endtask

@task('pull_repo')
    echo "Pulling latest changes..."
    cd {{ $app_dir }}
    git pull origin main
@endtask

@task('build_containers')
    echo "Building and starting containers..."
    cd {{ $app_dir }}
    ./vendor/bin/sail build
    ./vendor/bin/sail up -d
@endtask

@task('migrate_database')
    echo "Running migrations..."
    cd {{ $app_dir }}
    ./vendor/bin/sail artisan migrate --force
@endtask

@task('optimize_app')
    echo "Optimizing Laravel..."
    cd {{ $app_dir }}
    ./vendor/bin/sail artisan optimize
@endtask

@finished
    @if ($exitCode === 0)
        @slack(env('ENVOY_SLACK_WEBHOOK'), '#deployments', "Deployment to " . env('ENVOY_DEPLOY_HOST') . " successful!")
    @else
        @slack(env('ENVOY_SLACK_WEBHOOK'), '#deployments', "Deployment to " . env('ENVOY_DEPLOY_HOST') . " failed!")
    @endif
@endfinished
