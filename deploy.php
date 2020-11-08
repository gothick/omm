<?php
namespace Deployer;

require 'recipe/symfony.php';

// Project name
set('application', 'omm2.gothick.org.uk');

// Project repository
set('repository', 'git@github.com:gothick/omm.git');

// [Optional] Allocate tty for git clone. Default value is false.
set('git_tty', true); 

// Shared files/dirs between deploys 
add('shared_files', []);
add('shared_dirs', []);

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

// Migrate database before symlink new release.

// before('deploy:symlink', 'database:migrate');

