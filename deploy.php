<?php
namespace Deployer;

require 'recipe/symfony.php';

require 'contrib/cachetool.php';
require 'contrib/webpack_encore.php';

// Project name
set('application', 'omm.gothick.org.uk');

// Project repository
set('repository', 'git@github.com:gothick/omm.git');

// [Optional] Allocate tty for git clone. Default value is false.
set('git_tty', true);

// The default of ten was a bit much.
set('keep_releases', 5);

// Shared files/dirs between deploys
add('shared_files', [
    'google-cloud-service-account.json'
]);
add('shared_dirs', [
    // 'var/cache',
    'public/uploads/gpx',
    'public/uploads/images',
    'public/uploads/incoming',
    'public/media',
    'php_external_tools_bin'
]);

// Writable dirs by web server
add('writable_dirs', []);

// Hosts

// TODO: Try to set the shell to bash, or see if updated versions of deployer
// start working with zsh again. I'm on a beta at the moment because Symfony 5
host('ssh.gothick.org.uk')
    ->set('labels', ['stage' => 'prod'])
    ->setRemoteUser('omm')
    ->set('webpack_encore/env', 'production')
    ->set('webpack_encore/package_manager', 'yarn')
    ->set('cachetool_args', '--fcgi=/run/php/chef-managed-fpm-omm.sock --tmp-dir=/tmp')
    ->set('console_options', '-vvv')
    ->set('deploy_path', '/var/www/sites/gothick.org.uk/{{application}}');

host('omm.gothick.org.uk.localhost')
    ->set('labels', ['stage' => 'staging'])
    ->setRemoteUser('omm')
    ->set('webpack_encore/env', 'production')
    ->set('webpack_encore/package_manager', 'yarn')
    ->set('cachetool_args', '--fcgi=/run/php/chef-managed-fpm-omm.sock --tmp-dir=/tmp')
    ->set('console_options', '-vvv')
    ->set('deploy_path', '/var/www/sites/gothick.org.uk/{{application}}');


// Tasks

task('build', function () {
    run('cd {{release_path}} && build');
});

desc('Stop any existing messenger consumers; Supervisor will restart them.');
task('deploy:stop-workers', function () {
    run('{{bin/console}} messenger:stop-workers {{console_options}}');
});

// Testing

task('pwd', function () {
    $result = run('pwd');
    writeln("Current dir: $result");
});

task('test', function () {
    writeln('Hello world');
});

// [Optional] if deploy fails automatically unlock.
after('deploy:failed', 'deploy:unlock');

// Clear opcache on successful deployment
after('deploy:symlink', 'cachetool:clear:opcache');

// Restart messenger consuers on successful deployment
// I don't think this is currently working, but it's probably
// because of this bug:
// https://github.com/symfony/symfony/issues/40477
// So for now I'm probably going to have to manually restart
// stuff with Supervisor :(
after('cachetool:clear:opcache', 'deploy:stop-workers');

// Migrate database before symlink new release.

before('deploy:symlink', 'database:migrate');

// Webpack Encore
after('deploy:update_code', 'yarn:install');
after('deploy:update_code', 'webpack_encore:build');

