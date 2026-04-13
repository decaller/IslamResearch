<?php $exitCode = isset($exitCode) ? $exitCode : null; ?>
<?php $app_dir = isset($app_dir) ? $app_dir : null; ?>
<?php $repository = isset($repository) ? $repository : null; ?>
<?php $__container->servers(['web' => [env('ENVOY_DEPLOY_USER') . '@' . env('ENVOY_DEPLOY_HOST')]]); ?>

<?php
    $repository = 'https://github.com/decaller/IslamResearch.git';
    $app_dir = env('ENVOY_DEPLOY_PATH');
?>

<?php $__container->startMacro('deploy'); ?>
    initial_setup
    pull_repo
    build_containers
    migrate_database
    optimize_app
<?php $__container->endMacro(); ?>

<?php $__container->startTask('initial_setup'); ?>
    echo "Checking deployment directory..."
    [ -d <?php echo $app_dir; ?> ] || mkdir -p <?php echo $app_dir; ?>

    cd <?php echo $app_dir; ?>

    if [ ! -d .git ]; then
        echo "Cloning repository..."
        git clone <?php echo $repository; ?> .
    fi
    if [ ! -d vendor ]; then
        echo "Bootstrapping composer via Docker..."
        docker run --rm -v $(pwd):/var/www/html -w /var/www/html laravelsail/php85-composer:latest composer install --ignore-platform-reqs
    fi
<?php $__container->endTask(); ?>

<?php $__container->startTask('pull_repo'); ?>
    echo "Pulling latest changes..."
    cd <?php echo $app_dir; ?>

    git pull origin main
<?php $__container->endTask(); ?>

<?php $__container->startTask('build_containers'); ?>
    echo "Building and starting containers..."
    cd <?php echo $app_dir; ?>

    # Assuming we use a production-ready docker-compose file or Sail
    ./vendor/bin/sail build
    ./vendor/bin/sail up -d
<?php $__container->endTask(); ?>

<?php $__container->startTask('migrate_database'); ?>
    echo "Running migrations..."
    cd <?php echo $app_dir; ?>

    ./vendor/bin/sail artisan migrate --force
<?php $__container->endTask(); ?>

<?php $__container->startTask('optimize_app'); ?>
    echo "Optimizing Laravel..."
    cd <?php echo $app_dir; ?>

    ./vendor/bin/sail artisan optimize
<?php $__container->endTask(); ?>

<?php $_vars = get_defined_vars(); $__container->finished(function($exitCode = null) use ($_vars) { extract($_vars); 
    <?php if ($exitCode === 0): ?>
         if (! isset($task)) $task = null; Laravel\Envoy\Slack::make(env('ENVOY_SLACK_WEBHOOK'), '#deployments', "Deployment to " . env('ENVOY_DEPLOY_HOST') . " successful!")->task($task)->send();
    <?php else: ?>
         if (! isset($task)) $task = null; Laravel\Envoy\Slack::make(env('ENVOY_SLACK_WEBHOOK'), '#deployments', "Deployment to " . env('ENVOY_DEPLOY_HOST') . " failed!")->task($task)->send();
    <?php endif; ?>
}); ?>
