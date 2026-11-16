<?php

namespace App\Console\Commands;

use App\Models\Setting;
use App\Support\VapidGenerationException;
use App\Support\VapidKeyGenerator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class GenerateVapidKeysCommand extends Command
{
    protected $signature = 'vapid:generate';

    protected $description = 'Generate a VAPID keypair for web push. Private key goes to .env (env-only), public key to the settings table.';

    public function handle(VapidKeyGenerator $generator): int
    {
        try {
            $keys = $generator->generate();
        } catch (VapidGenerationException $e) {
            $this->reportFailure($e);

            return self::FAILURE;
        }

        // Verify before persisting: a keypair the push service rejects is worse
        // than no keypair, because it looks configured.
        if (! $this->isWellFormed($keys['publicKey'], $keys['privateKey'])) {
            $this->error('Kunci VAPID yang dihasilkan tidak valid (panjang encoding salah).');
            $this->line('Public key  : '.strlen($keys['publicKey']).' karakter (harus 87)');
            $this->line('Private key : '.strlen($keys['privateKey']).' karakter (harus 43)');
            $this->newLine();
            $this->showOfflineInstructions();

            return self::FAILURE;
        }

        // 1. Write private key to .env (env-only; never in the repo/settings).
        $written = $this->writeEnv([
            'VAPID_PUBLIC_KEY' => $keys['publicKey'],
            'VAPID_PRIVATE_KEY' => $keys['privateKey'],
        ]);

        if (! $written) {
            return self::FAILURE;
        }

        // 2. Store public key in settings table for client-side subscription.
        Setting::set('vapaid_public_key', $keys['publicKey']);

        $this->info('Kunci VAPID berhasil dibuat.');
        $this->line('Public key : '.$keys['publicKey']);
        $this->line('Private key: '.$keys['privateKey'].' (ditulis ke .env, JANGAN commit)');
        $this->newLine();
        $this->line('Langkah berikutnya: buka /admin/push-settings dan aktifkan notifikasi browser.');

        return self::SUCCESS;
    }

    /**
     * Fail loudly, with the real OpenSSL reason and a documented alternative.
     *
     * The audit's core complaint about C1 was not that generation failed — it
     * was that the command reported a generic "gagal" while leaving push dead
     * with only a Log::warning. This prints the per-strategy diagnostic and the
     * offline path an operator on shared cPanel hosting can actually use.
     */
    protected function reportFailure(VapidGenerationException $e): void
    {
        $this->error('Gagal membuat kunci VAPID: tidak ada strategi pembuatan kunci yang berhasil.');
        $this->newLine();
        $this->line('Diagnostik per strategi:');
        $this->line('  '.$e->diagnosticReport());
        $this->newLine();
        $this->line('Kemungkinan penyebab: ekstensi OpenSSL PHP tidak dapat memuat openssl.cnf,');
        $this->line('atau kurva prime256v1 (P-256) tidak tersedia di build OpenSSL host ini.');
        $this->newLine();
        $this->showOfflineInstructions();
    }

    /**
     * The documented, host-independent alternative.
     */
    protected function showOfflineInstructions(): void
    {
        $this->warn('Pembuatan kunci VAPID native TIDAK tersedia di host ini.');
        $this->newLine();
        $this->line('Buat kunci secara offline di mesin Anda sendiri:');
        $this->line('  1. npx web-push generate-vapid-keys');
        $this->line('  2. Salin kedua nilai ke .env di server:');
        $this->line('       VAPID_PUBLIC_KEY=<publicKey>');
        $this->line('       VAPID_PRIVATE_KEY=<privateKey>');
        $this->line('  3. Simpan public key ke tabel settings:');
        $this->line('       php artisan tinker --execute="App\\Models\\Setting::set(\'vapaid_public_key\', \'<publicKey>\');"');
        $this->line('  4. php artisan config:clear');
        $this->newLine();
        $this->line('Selama kunci belum dikonfigurasi, notifikasi browser tidak akan terkirim');
        $this->line('dan SendWhatsAppNotificationJob akan mencatat peringatan pada setiap klik.');
    }

    /**
     * Base64url length checks for a 65-byte public point / 32-byte scalar.
     */
    protected function isWellFormed(string $publicKey, string $privateKey): bool
    {
        return strlen($publicKey) === 87
            && strlen($privateKey) === 43
            && strlen($this->b64urlDecode($publicKey) ?? '') === 65
            && strlen($this->b64urlDecode($privateKey) ?? '') === 32;
    }

    protected function b64urlDecode(string $value): ?string
    {
        $decoded = base64_decode(strtr($value, '-_', '+/'), true);

        return $decoded === false ? null : $decoded;
    }

    /**
     * Append or update keys in .env.
     *
     * @param  array<string, string>  $values
     * @return bool false when .env is not writable (reported to the operator)
     */
    protected function writeEnv(array $values): bool
    {
        $path = $this->environmentFilePath();

        if (! File::exists($path)) {
            $this->error('File .env tidak ditemukan di '.$path.'.');
            $this->line('Buat .env dari .env.example terlebih dahulu (cp .env.example .env).');

            return false;
        }

        if (! File::isWritable($path)) {
            $this->error('File .env tidak dapat ditulis: '.$path.'.');
            $this->line('Periksa permission file (biasanya 644 dengan owner yang sama seperti PHP-FPM).');

            return false;
        }

        $contents = File::get($path);

        foreach ($values as $key => $value) {
            $line = $key.'='.str_replace("\n", '', $value);

            if (preg_match('/^'.preg_quote($key, '/').'=.*/m', $contents)) {
                $contents = preg_replace('/^'.preg_quote($key, '/').'=.*/m', $line, $contents);
            } else {
                $contents = rtrim($contents, "\n")."\n".$line."\n";
            }
        }

        File::put($path, $contents);

        return true;
    }

    /**
     * Resolve the real .env path, honouring APP_ENV-specific files in testing.
     */
    protected function environmentFilePath(): string
    {
        return app()->environmentFilePath();
    }
}
