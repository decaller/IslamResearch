@servers(['web' => [env('ENVOY_DEPLOY_USER') . '@' . env('ENVOY_DEPLOY_HOST')]])

@setup
    $repository = 'git@github.com:your-repo/islam-research.git';
    $releases_dir = env('ENVOY_DEPLOY_PATH') . '/releases';
    $app_dir = env('ENVOY_DEPLOY_PATH');
    $release = date('YmdHis');
    $new_release_dir = "{$releases_dir}/{$release}";
@endsetup

@story('deploy')
    clone_repository
    run_composer
    update_symlinks
@endstory

@task('clone_repository')
    echo 'Cloning repository'
    [ -d {{ $releases_dir }} ] || mkdir {{ $releases_dir }}
    git clone --depth 1 {{ $repository }} {{ $new_release_dir }}
    cd {{ $new_release_dir }}
    git sdist
@endtask

@task('run_composer')
    echo "Starting deployment ({{ $release }})"
    cd {{ $new_release_dir }}
    composer install --prefer-dist --no-scripts -q -o
@endtask

@task('update_symlinks')
    echo "Updating symlinks"
    ln -nfs {{ $new_release_dir }} {{ $app_dir }}/current
@endtask

@finished
    @if ($exitCode === 0)
        @slack(env('ENVOY_SLACK_WEBHOOK'), '#deployments', "Deployment to " . env('ENVOY_DEPLOY_HOST') . " successful!")
    @else
        @slack(env('ENVOY_SLACK_WEBHOOK'), '#deployments', "Deployment to " . env('ENVOY_DEPLOY_HOST') . " failed!")
    @endif
@endfinished
