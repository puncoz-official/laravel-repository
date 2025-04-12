<?php

namespace JoBins\LaravelRepository\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class MakeTransformerCommand extends Command
{
    protected $signature = 'make:transformer {name : The transformer name, with optional subdirectory (e.g., Common/Book)}';
    protected $description = 'Generate a Transformer class with a proper namespace and folder structure.';
    protected Filesystem $files;

    public function __construct()
    {
        parent::__construct();
        $this->files = new Filesystem;
    }

    public function handle()
    {
        $name = $this->argument('name');

        // Get transformer base path from config (default to 'app/Transformers' if not set)
        $basePath = config('config.transformer_path', base_path('app/Transformers'));

        // Convert base path to namespace format (app/Transformers → App\Http\Transformers)
        $baseNamespace = str_replace('/', '\\', trim(str_replace(app_path(), 'App', $basePath), '/'));

        // Ensure uniform case handling (ucfirst)
        $parts     = explode('/', str_replace('\\', '/', $name));
        $className = ucfirst(array_pop($parts)) . 'Transformer';

        $subNamespace = implode('\\', array_map('ucfirst', $parts));

        // Construct full namespace dynamically
        $namespace = str_replace('/', '\\', trim(str_replace(app_path(), 'App', $basePath), '/'));
        $namespace = $subNamespace ? $namespace . '\\' . $subNamespace : $namespace;
        $namespace = ucfirst($namespace);


        // Construct target directory path
        $targetDirectory = $basePath . ($subNamespace ? '/' . str_replace('\\', '/', $subNamespace) : '');

        // Ensure directory exists
        if (!$this->files->exists($targetDirectory)) {
            $this->files->makeDirectory($targetDirectory, 0755, true);
        }

        // Transformer file path
        $transformerFile = $targetDirectory . '/' . $className . '.php';

        // Model namespace handling (assuming models are stored under 'App\Models')
        $modelNamespace = 'App\Models' . ($subNamespace ? '\\' . $subNamespace : '');
        $modelName      = str_replace('Transformer', '', $className);
        $modelVar       = Str::camel($modelName);

        // Prepare stub replacements
        $stubReplacements = [
            '{{ namespace }}'      => $namespace,
            '{{ modelNamespace }}' => $modelNamespace,
            '{{ modelName }}'      => $modelName,
            '{{ className }}'      => $className,
            '{{ modelVar }}'       => $modelVar,
        ];

        // Load and process stub
        $transformerStub = $this->fillStub($this->getStubPath('transformer.stub'), $stubReplacements);

        // Write to file
        $this->files->put($transformerFile, $transformerStub);

        $this->info("Transformer created: {$transformerFile}");
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
