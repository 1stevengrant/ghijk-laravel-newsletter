<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class DatabaseSync extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:sync {direction : pull or push}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync SQLite database with remote server';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (app()->environment('production')) {
            $this->error('This command cannot be run in production environment');

            return 1;
        }

        $direction = $this->argument('direction');

        if (! in_array($direction, ['pull', 'push'])) {
            $this->error('Direction must be either "pull" or "push"');

            return 1;
        }

        $remoteUser = config('database.remote.user');
        $remoteHost = config('database.remote.host');
        $remotePath = config('database.remote.path');
        $localPath = config('database.connections.sqlite.database');

        if (! $remoteUser || ! $remoteHost || ! $remotePath) {
            $this->error('Remote database configuration not found. Please set DATABASE_REMOTE_USER, DATABASE_REMOTE_HOST, and DATABASE_REMOTE_PATH in your .env file');

            return 1;
        }

        if ($direction === 'pull') {
            $this->info('Pulling database from remote server...');
            $command = "rsync -avzP {$remoteUser}@{$remoteHost}:{$remotePath} " . dirname($localPath) . '/';
        } else {
            $this->info('Pushing database to remote server...');
            $command = "rsync -avzP {$localPath} {$remoteUser}@{$remoteHost}:" . dirname($remotePath) . '/';
        }

        $this->info("Running: {$command}");

        $result = shell_exec($command . ' 2>&1');
        $exitCode = 0;

        if ($result === null) {
            $this->error('Command failed to execute');

            return 1;
        }

        $this->info($result);

        if ($direction === 'pull') {
            $this->info('Database pulled successfully!');
        } else {
            $this->info('Database pushed successfully!');
        }

        return 0;
    }
}
