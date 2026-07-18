<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

#[Signature('admin:create-user')]
#[Description('Create a new admin user')]
class CreateAdminUser extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $name = text(
            label: 'Name',
            required: true,
        );

        $email = text(
            label: 'Email address',
            required: true,
            validate: fn (string $value) => match (true) {
                ! filter_var($value, FILTER_VALIDATE_EMAIL) => 'The email must be a valid email address.',
                User::where('email', $value)->exists() => 'A user with this email already exists.',
                default => null,
            },
        );

        $password = password(
            label: 'Password',
            required: true,
            validate: fn (string $value) => match (true) {
                strlen($value) < 8 => 'The password must be at least 8 characters.',
                default => null,
            },
        );

        User::create([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'is_admin' => true,
        ]);

        $this->components->info("Admin user [{$name}] created successfully.");
    }
}
