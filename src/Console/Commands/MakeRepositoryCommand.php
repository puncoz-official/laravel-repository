<?php

namespace JoBins\LaravelRepository\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Illuminate\Filesystem\Filesystem;

class MakeRepositoryCommand extends Command
{
    protected $signature = 'make:repository {name : The repository name, with optional subdirectory (e.g., Common/Item)} {--model= : The model name to use (defaults to same as repository name)}';
    protected $description = 'Generate a repository and its interface with a proper namespace and folder structure.';

    protected Filesystem $files;

    public function __construct()
    {
        parent::__construct();
        $this->files = new Filesystem;
    }

    public function handle()
    {
        // Retrieve the input
        $name      = $this->argument('name');
        $modelName = $this->option('model') ?: class_basename($name);

        // Create folder paths and namespaces
        // Example: name = Common/Item translates to namespace "\Common" and className "ItemRepository"

        // Split directory and class name:
        $parts        = explode('/', $name);
        $className    = array_pop($parts) . 'Repository';
        $subNamespace = count($parts) > 0 ? '\\' . implode('\\', $parts) : '';

        // Determine base paths in the project (targeting app/Repositories)
        $basePath        = base_path('app/Repositories');
        $targetDirectory = $basePath . ($subNamespace ? '/' . str_replace('\\', '/', $subNamespace) : '');

        // Ensure directories exist
        if (!$this->files->exists($targetDirectory))
        {
            $this->files->makeDirectory($targetDirectory, 0755, true);
        }

        // Repository file destination
        $repositoryFile = $targetDirectory . '/' . $className . '.php';

        // Determine interface destination (create Interfaces folder under same subdirectory)
        $interfaceDirectory = $targetDirectory . '/Interfaces';
        if (!$this->files->exists($interfaceDirectory))
        {
            $this->files->makeDirectory($interfaceDirectory, 0755, true);
        }
        $interfaceFile = $interfaceDirectory . '/' . $className . 'Interface.php';

        // Build the replacement arrays for stubs
        $stubReplacements = [
            '{{ namespace }}'      => $subNamespace,
            '{{ className }}'      => $className,
            '{{ modelName }}'      => $modelName,
            // You might customize these further. For now, assume models are in App\Models with matching sub-namespace
            '{{ modelNamespace }}' => $subNamespace,
        ];

        // Load and fill stubs
        $repositoryStub = $this->fillStub($this->getStubPath('repository.stub'), $stubReplacements);
        $interfaceStub  = $this->fillStub($this->getStubPath('repository.interface.stub'), $stubReplacements);

        // Write files
        $this->files->put($repositoryFile, $repositoryStub);
        $this->files->put($interfaceFile, $interfaceStub);

        $this->info("Repository created: {$repositoryFile}");
        $this->info("Interface created: {$interfaceFile}");
    }

    protected function getStubPath($stub)
    {
        // You can modify this path if needed
        return __DIR__ . '/stubs/' . $stub;
    }

    protected function fillStub($stubPath, array $replacements): string
    {
        $content = $this->files->get($stubPath);
        foreach ($replacements as $search => $replace)
        {
            $content = str_replace($search, $replace, $content);
        }
        return $content;
    }
}