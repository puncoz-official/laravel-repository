<?php

namespace JoBins\LaravelRepository\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class MakeFilterCommand extends Command
{
    protected $signature = 'make:filter {name : The filter name, with optional subdirectory (e.g., Admin/Book)}';
    protected $description = 'Generate a Filter class with a proper namespace and folder structure.';
    protected Filesystem $files;

    public function __construct()
    {
        parent::__construct();
        $this->files = new Filesystem;
    }

    public function handle()
    {
        $name = $this->argument('name');

        // Get filter base path from config (default to 'app/Filters' if not set)
        $basePath = config('repository.filter_path', base_path('app/Filters'));

        // Convert base path to namespace format (app/Filters → App\Filters)
        $baseNamespace = str_replace('/', '\\', trim(str_replace(app_path(), 'App', $basePath), '/'));

        // Ensure uniform case handling (ucfirst)
        $parts        = explode('/', str_replace('\\', '/', $name));
        $className    = ucfirst(array_pop($parts)) . 'ListFilter';
        $subNamespace = implode('\\', array_map('ucfirst', $parts));

        // Construct full namespace dynamically
        $namespace = $subNamespace ? $baseNamespace . '\\' . $subNamespace : $baseNamespace;
        $namespace = ucfirst($namespace); // Ensure namespace starts with an uppercase letter

        // Construct target directory path
        $targetDirectory = $basePath . ($subNamespace ? '/' . str_replace('\\', '/', $subNamespace) : '');

        // Ensure directory exists
        if (!$this->files->exists($targetDirectory)) {
            $this->files->makeDirectory($targetDirectory, 0755, true);
        }

        // Filter file path
        $filterFile = $targetDirectory . '/' . $className . '.php';

        // Prepare stub replacements
        $stubReplacements = [
            '{{ namespace }}' => $namespace,
            '{{ className }}' => $className,
        ];

        // Load and process stub
        $filterStub = $this->fillStub($this->getStubPath('filter.stub'), $stubReplacements);

        // Write to file
        $this->files->put($filterFile, $filterStub);

        $this->info("Filter created: {$filterFile}");
    }

    protected function getStubPath($stub): string
    {
        return __DIR__ . '/stubs/' . $stub;
    }

    protected function fillStub($stubPath, array $replacements): string
    {
        $content = $this->files->get($stubPath);
        foreach ($replacements as $search => $replace) {
            $content = str_replace($search, $replace, $content);
        }
        return $content;
    }
}
