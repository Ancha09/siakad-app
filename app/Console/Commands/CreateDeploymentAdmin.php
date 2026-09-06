<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

class CreateDeploymentAdmin extends Command
{
    protected $signature = 'deploy:admin';

    protected $description = 'Buat admin baru secara interaktif tanpa mengganti akun yang sudah ada';

    public function handle(): int
    {
        if (! $this->input->isInteractive()) {
            $this->error('Jalankan command ini dari terminal interaktif.');

            return self::FAILURE;
        }

        $data = [
            'name' => $this->ask('Nama admin'),
            'login' => $this->ask('Login admin'),
            'email' => $this->ask('Email admin'),
            'password' => $this->secret('Password (minimal 12 karakter)'),
            'password_confirmation' => $this->secret('Ulangi password'),
        ];
        $validator = Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'login' => ['required', 'string', 'max:255', 'unique:users,login'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:12', 'confirmed'],
        ]);
        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                $this->error($message);
            }

            return self::FAILURE;
        }

        unset($data['password_confirmation']);
        User::create($data + ['role' => 'admin']);
        $this->info('Admin dibuat. Password disimpan melalui hashed cast model User.');

        return self::SUCCESS;
    }
}
