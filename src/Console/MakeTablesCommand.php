<?php

namespace PaymentSystem\Laravel\Console;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use PaymentSystem\Laravel\Contracts\MigrationTemplateInterface;

use function Illuminate\Filesystem\join_paths;
use function Laravel\Prompts\error;
use function Laravel\Prompts\info;

class MakeTablesCommand extends Command
{
    protected $signature = 'payments:make-tables {--append}';

    protected $description = 'Create migrations for payment system and it\'s modules';

    private array $templates;

    public function __construct(
        private readonly Filesystem $files,
        MigrationTemplateInterface ...$templates,
    ) {
        parent::__construct();

        $this->templates = $templates;
    }

    public function __invoke(): int
    {
        foreach ($this->templates as $template) {
            $table = $template->getTableName();

            if ($this->migrationExists($table)) {
                if (!$this->option('append')) {
                    error("Migration for '$table' table already exists.");

                    return self::FAILURE;
                }

                continue;
            }

            $this->replaceMigrationPlaceholders(
                $this->createBaseMigration($table),
                $table,
                $template->getStubPath()
            );
        }

        info('Migrations created successfully.');

        return self::SUCCESS;
    }

    protected function migrationExists($table): bool
    {
        return count(
                $this->files->glob(
                    join_paths($this->laravel->databasePath('migrations'), '*_*_*_*_create_' . $table . '_table.php')
                )
            ) !== 0;
    }

    protected function replaceMigrationPlaceholders(string $path, string $table, string $stub): void
    {
        $stub = str_replace(
            '{{table}}',
            $table,
            $this->files->get($stub)
        );

        $this->files->put($path, $stub);
    }

    protected function createBaseMigration($table): string
    {
        return $this->laravel['migration.creator']->create(
            'create_' . $table . '_table',
            $this->laravel->databasePath('/migrations')
        );
    }
}