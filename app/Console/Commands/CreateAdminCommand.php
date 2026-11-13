<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use RuntimeException;

class CreateAdminCommand extends Command
{
    protected $signature = 'admin:create';

    protected $description = 'Create the first admin account once from explicit environment credentials';

    public function handle(): int
    {
        $name = getenv('ADMIN_BOOTSTRAP_NAME') ?: null;
        $email = getenv('ADMIN_BOOTSTRAP_EMAIL') ?: null;
        $password = getenv('ADMIN_BOOTSTRAP_PASSWORD') ?: null;

        $validator = Validator::make([
            'name' => $name,
            'email' => $email,
            'password' => $password,
        ], [
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:12', 'max:4096', 'regex:/[a-z]/', 'regex:/[A-Z]/', 'regex:/[0-9]/', 'regex:/[^A-Za-z0-9]/'],
        ]);

        if ($validator->fails()) {
            $this->error('Bootstrap credentials missing or invalid. Provide a name, valid email, and password with at least 12 characters including lowercase, uppercase, number, and symbol.');

            return self::FAILURE;
        }

        $lockAcquired = false;

        try {
            if (in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true)) {
                $lockAcquired = (int) DB::selectOne("SELECT GET_LOCK('wpcctv_admin_bootstrap', 10) AS acquired")->acquired === 1;

                if (! $lockAcquired) {
                    $this->error('Could not obtain bootstrap lock. No account was created.');

                    return self::FAILURE;
                }
            }

            DB::transaction(function () use ($name, $email, $password): void {
                if (User::query()->exists()) {
                    throw new RuntimeException('An account already exists. Bootstrap refused.');
                }

                User::query()->create([
                    'name' => $name,
                    'email' => mb_strtolower($email),
                    'password' => $password,
                ]);
            });
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        } finally {
            if ($lockAcquired) {
                DB::selectOne("SELECT RELEASE_LOCK('wpcctv_admin_bootstrap') AS released");
            }
        }

        $this->info('Initial admin account created. Remove bootstrap credentials from the environment now.');

        return self::SUCCESS;
    }
}
