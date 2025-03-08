<?php

namespace JoBins\LaravelRepository\Console\Commands;

use Illuminate\Console\Command;
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
        $name = $this->argument('name');

        // Split the path and class name
        $parts = explode('/', $name);
        $className = ucfirst(array_pop($parts)) . 'Repository';

        // Capitalize subdirectories correctly
        $subNamespace = implode('\\', array_map('ucfirst', $parts));

        // Base paths for repositories, can be modified from config
        $basePath = config('config.repository_path', base_path('app/Repositories'));
        $targetDirectory = $basePath . ($subNamespace ? '/' . str_replace('\\', '/', $subNamespace) : '');

        // Construct full namespace dynamically
        $namespace = str_replace('/', '\\', trim(str_replace(app_path(), 'App', $basePath), '/'));
        $namespace = $subNamespace ? $namespace . '\\' . $subNamespace : $namespace;
        $namespace = ucfirst($namespace);

        // Ensure the directory exists
        if (!$this->files->exists($targetDirectory)) {
            $this->files->makeDirectory($targetDirectory, 0755, true);
        }

        // Repository file destination
        $repositoryFile = $targetDirectory . '/' . $className . '.php';

        // Determine whether interface should be in a subfolder or not from config
        $interfaceSubFolder = config('config.interface_subfolder', false);
        $interfaceDirectory = $interfaceSubFolder ? $targetDirectory . '/Interfaces' : $targetDirectory;

        // Ensure interface directory exists (if required by configuration)
        if ($interfaceSubFolder && !$this->files->exists($interfaceDirectory)) {
            $this->files->makeDirectory($interfaceDirectory, 0755, true);
        }

        // Interface file destination
        $interfaceFile = $interfaceDirectory . '/' . $className . 'Interface.php';

        // Model namespace handling (assuming models are stored under 'App\Models')
        $modelNamespace = 'App\Models' . ($subNamespace ? '\\' . $subNamespace : '');
        $modelName = str_replace('Repository', '', $className);

        // Prepare the replacement values for stubs
        $stubReplacements = [
            '{{ namespace }}' => $namespace,
            '{{ className }}' => $className,
            '{{ modelName }}' => $modelName,
            '{{ modelNamespace }}' => $modelNamespace,
            '{{ interfaceNamespace }}' => $interfaceSubFolder ? ucfirst($namespace) . '\\Interfaces' : ucfirst($namespace),
        ];

        // Load and fill the repository and interface stubs
        $repositoryStub = $this->fillStub($this->getStubPath('repository.stub'), $stubReplacements);
        $interfaceStub = $this->fillStub($this->getStubPath('repository.interface.stub'), $stubReplacements);

        // Write the generated repository and interface to disk
        $this->files->put($repositoryFile, $repositoryStub);
        $this->files->put($interfaceFile, $interfaceStub);

        $this->info("Repository created: {$repositoryFile}");
        $this->info("Interface created: {$interfaceFile}");
    }

    protected function getStubPath($stub)
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
