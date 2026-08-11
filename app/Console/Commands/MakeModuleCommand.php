<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

#[Signature('make:module {name : The module name, e.g. GameDesign} {--force : Create missing layers inside an existing module}')]
#[Description('Scaffold a new modular monolith module with its Domain, Application, Infrastructure, and Presentation layers')]
class MakeModuleCommand extends Command
{
    /**
     * The layers every module is composed of.
     *
     * @var list<string>
     */
    private const LAYERS = [
        'Domain',
        'Application',
        'Infrastructure',
        'Presentation',
    ];

    public function __construct(private readonly Filesystem $files)
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $name = $this->moduleName();

        if ($name === null) {
            $this->components->error('The module name may only contain letters and numbers, and must start with a letter.');

            return self::FAILURE;
        }

        $modulePath = $this->modulePath($name);

        if ($this->files->isDirectory($modulePath) && ! $this->option('force')) {
            $this->components->error("Module [{$name}] already exists. Use --force to add any missing layers.");

            return self::FAILURE;
        }

        foreach (self::LAYERS as $layer) {
            $this->createLayer($modulePath.DIRECTORY_SEPARATOR.$layer);
        }

        $this->components->info("Module [{$name}] is ready at ".$this->relativePath($modulePath).'.');

        return self::SUCCESS;
    }

    /**
     * Normalise the given name, returning null when it is not a valid module name.
     */
    private function moduleName(): ?string
    {
        $name = Str::studly(trim((string) $this->argument('name')));

        return preg_match('/^[A-Za-z][A-Za-z0-9]*$/', $name) === 1 ? $name : null;
    }

    /**
     * Create a single layer directory, keeping it tracked by git while empty.
     */
    private function createLayer(string $path): void
    {
        if ($this->files->isDirectory($path)) {
            $this->components->twoColumnDetail($this->relativePath($path), '<fg=yellow>exists</>');

            return;
        }

        $this->files->makeDirectory($path, recursive: true);
        $this->files->put($path.DIRECTORY_SEPARATOR.'.gitkeep', '');

        $this->components->twoColumnDetail($this->relativePath($path), '<fg=green>created</>');
    }

    /**
     * Resolve the absolute path of the given module.
     */
    private function modulePath(string $name): string
    {
        return base_path('modules'.DIRECTORY_SEPARATOR.$name);
    }

    /**
     * Present a path relative to the project root for readable output.
     */
    private function relativePath(string $path): string
    {
        return str_replace(base_path().DIRECTORY_SEPARATOR, '', $path);
    }
}
