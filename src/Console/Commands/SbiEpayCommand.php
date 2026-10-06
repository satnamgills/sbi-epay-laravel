<?php

declare(strict_types=1);

namespace SbiEpay\SbiEpay\Console\Commands;

use Illuminate\Console\Command;

class SbiEpayCommand extends Command
{
    /**
     * The command signature.
     */
    protected $signature = 'sbi-epay:placeholder';

    /**
     * The command description.
     */
    protected $description = 'Placeholder Artisan command shipped by the package sbi-epay.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->line('SbiEpay placeholder command executed.');

        return self::SUCCESS;
    }
}
