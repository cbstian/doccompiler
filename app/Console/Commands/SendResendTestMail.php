<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Mail;
use Throwable;

#[Signature('app:send-resend-test-mail')]
#[Description('Envia un correo de prueba usando Resend a sebastian@procodigo.cl')]
class SendResendTestMail extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (! is_string(config('services.resend.key')) || config('services.resend.key') === '') {
            $this->error('RESEND_API_KEY no está configurada. Define esta variable en tu .env antes de ejecutar el comando.');

            return Command::FAILURE;
        }

        try {
            Mail::mailer('resend')
                ->raw('Este es un correo de prueba enviado con el driver Resend en Laravel.', function (Message $message): void {
                    $message->to('sebastian@procodigo.cl')
                        ->subject('Prueba de Resend desde Laravel');
                });

            $this->info('Correo de prueba enviado a sebastian@procodigo.cl usando Resend.');

            return Command::SUCCESS;
        } catch (Throwable $exception) {
            $this->error('No se pudo enviar el correo de prueba con Resend.');
            $this->line($exception->getMessage());

            return Command::FAILURE;
        }
    }
}
