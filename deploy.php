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

// Cachetool needs to be a lower version than default,
// as the latest only works with php 8.
// https://github.com/deployphp/deployer/issues/2344
// https://gordalina.github.io/cachetool/
// https://github.com/deployphp/deployer/blob/master/contrib/cachetool.php#L55
// https://github.com/gordalina/cachetool/releases/download/7.0.0/cachetool.phar
set('bin/cachetool', function () {
    if (!test('[ -f {{release_or_current_path}}/cachetool.phar ]')) {
        run("cd {{release_or_current_path}} && curl -sLO https://github.com/gordalina/cachetool/releases/download/7.0.0/cachetool.phar");
    }
    return '{{release_or_current_path}}/cachetool.phar';
});


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
host('production')
    ->setHostname('ssh.gothick.org.uk')
    ->set('labels', ['stage' => 'production'])
    ->setRemoteUser('omm')
    ->set('webpack_encore/env', 'production')
    ->set('webpack_encore/package_manager', 'yarn')
    ->set('cachetool_args', '--fcgi=/run/php/chef-managed-fpm-omm.sock --tmp-dir=/tmp')
    ->set('console_options', '-vvv')
    ->set('deploy_path', '/var/www/sites/gothick.org.uk/{{application}}');

host('staging')
    ->setHostname('omm.gothick.org.uk.localhost')
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

task('deploy:stop-workers', function () {
    // Hack alert: https://stackoverflow.com/a/63652279/300836
    // We've just move the previous release out of the way, but it's
    // the previous release's cache that has the details of the
    // workers we need to kill.
    if (has('previous_release')) {
        run('{{bin/php}} {{previous_release}}/bin/console messenger:stop-workers');
    }
})->desc('Stop any existing messenger consumers; Supervisor will restart them.');

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

// Yarn and Webpack Encore. Note that Yarn has to install _after_
// Composer, as some of the Symfony JS stuff introduces a Yarn
// dependency on a file in a Composer vendor directory. (it's for
// Charts.js:
//         "@symfony/ux-chartjs": "file:vendor/symfony/ux-chartjs/Resources/assets",
// )
after('deploy:vendors', 'yarn:install');
after('yarn:install', 'webpack_encore:build');
