<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CreateUserCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'create:user';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'A command to create a user with prompts';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $name = $this->ask('What is the user\'s name?');
        $email = $this->ask('What is the user\'s email?');

        do {
            $password = $this->secret('What is the user\'s password?');
            $passwordConfirmation = $this->secret('Confirm the password');

            if ($password !== $passwordConfirmation) {
                $this->error('Passwords do not match. Please try again.');
            }
        } while ($password !== $passwordConfirmation);

        \App\Models\User::create([
            'name' => $name,
            'email' => $email,
            'password' => \Hash::make($password),
        ]);

        $this->info('User created successfully!');
    }
}
