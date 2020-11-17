<?php
namespace Deployer;

require 'recipe/symfony.php';

require 'contrib/cachetool.php';

// Project name
set('application', 'omm.gothick.org.uk');

// Project repository
set('repository', 'git@github.com:gothick/omm.git');

// [Optional] Allocate tty for git clone. Default value is false.
set('git_tty', true); 

// Shared files/dirs between deploys 
add('shared_files', []);
add('shared_dirs', [
    'public/uploads/gpx',
    'public/uploads/images',
    'public/uploads/incoming'
]);

// Writable dirs by web server 
add('writable_dirs', []);

// Saves me typing it out every time
set('default_stage', 'production');


// Hosts

// TODO: Try to set the shell to bash, or see if updated versions of deployer
// start working with zsh again. I'm on a beta at the moment because Symfony 5
host('ssh.gothick.org.uk')
    ->set('stage', 'production')
    ->setRemoteUser('omm')
    ->set('cachetool_args', '--fcgi=/run/php/chef-managed-fpm-omm.sock --tmp-dir=/tmp')
    ->set('deploy_path', '/var/www/sites/gothick.org.uk/{{application}}');    
    
// Tasks

task('build', function () {
    run('cd {{release_path}} && build');
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

// Migrate database before symlink new release.

before('deploy:symlink', 'database:migrate');
