<?php namespace App\Console\Commands\Generators;

use ICanBoogie\Inflector;

class AdminCRUDMakeCommand extends GeneratorCommandBase
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'make:admin-crud';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new Admin CRUD related files';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'AdminCRUD';

    protected function generate($name)
    {

        $modelName = $this->getModelName($name);

        if (!$this->generateController($modelName)) {
            return false;
        }
        if (!$this->generateRequest($modelName)) {
            return false;
        }
        if (!$this->generateViews($modelName)) {
            return false;
        }

        if (!$this->generateUnitTest($modelName)) {

        }

        $this->generateLanguageFile($modelName);


        return $this->addRoute($modelName);

    }

    /**
     * @param  string $name
     * @return bool
     */
    protected function generateController($name)
    {
        $path = $this->getControllerPath($name);
        if ($this->alreadyExists($path)) {
            $this->error($path . ' already exists.');

            return false;
        }

        $this->makeDirectory($path);

        $stub = $this->files->get($this->getStubForController());
        $this->replaceTemplateVariables($stub, $name);
        $this->files->put($path, $stub);

        return true;
    }

    /**
     * @param  string $name
     * @return string
     */
    protected function getModelName($name)
    {
        if (preg_match('/([A-Za-z0-9]+)(Controller)?$/', $name, $matches)) {
            $name = $matches[1];
        }
        $inflector = Inflector::get('en');

        return $inflector->singularize(ucwords($name));
    }

    protected function getColumns($name)
    {
        $modelFullName = '\\App\\Models\\' . $name;
        /** @var \App\Models\Base $model */
        $model = new $modelFullName;

        return $model->getFillableColumns();
    }

    /**
     * @param string $stub
     * @param string $modelName
     */
    protected function replaceTemplateVariables(&$stub, $modelName)
    {
        $this->replaceTemplateVariable($stub, 'CLASS', $modelName);
        $this->replaceTemplateVariable($stub, 'CLASSES', \StringHelper::pluralize($modelName));
        $this->replaceTemplateVariable($stub, 'class', strtolower(substr($modelName, 0, 1)) . substr($modelName, 1));
        $this->replaceTemplateVariable($stub, 'classes', \StringHelper::pluralize(strtolower($modelName)));
        $this->replaceTemplateVariable($stub, 'classes-spinal',
            \StringHelper::camel2spinal(\StringHelper::pluralize($modelName)));

        $columns = $this->getColumns($modelName);
        $list = join(',', array_map(function ($name) {
            return "'" . $name . "'";
        }, $columns));
        $this->replaceTemplateVariable($stub, 'COLUMNS', $list);
    }

    /**
     * @param  string $name
     * @return string
     */
    protected function getControllerPath($name)
    {
        return $this->laravel['path'] . '/Http/Controllers/Admin/' . $name . 'Controller.php';
    }

    /**
     * @return string
     */
    protected function getStubForController()
    {
        return __DIR__ . '/stubs/admin-crud-controller.stub';
    }

    /**
     * @param  string $name
     * @return bool
     */
    protected function generateRequest($name)
    {
        $path = $this->getRequestPath($name);
        if ($this->alreadyExists($path)) {
            $this->error($path . ' already exists.');

            return false;
        }

        $this->makeDirectory($path);

        $stub = $this->files->get($this->getStubForRequest());
        $this->replaceTemplateVariables($stub, $name);
        $this->files->put($path, $stub);

        return true;
    }

    /**
     * @param  string $name
     * @return string
     */
    protected function getRequestPath($name)
    {
        return $this->laravel['path'] . '/Http/Requests/Admin/' . $name . 'Request.php';
    }

    /**
     * @return string
     */
    protected function getStubForRequest()
    {
        return __DIR__ . '/stubs/admin-crud-request.stub';
    }

    /**
     * @param  string $name
     * @return bool
     */
    protected function generateViews($name)
    {

        foreach (['index', 'edit'] as $type) {
            $path = $this->getViewPath($name, $type);
            if ($this->alreadyExists($path)) {
                $this->error($path . ' already exists.');

                return false;
            }

            $this->makeDirectory($path);

            $stub = $this->files->get($this->getStubForView($type));
            $this->replaceTemplateVariables($stub, $name);
            $this->files->put($path, $stub);
        }

        return true;
    }

    /**
     * @param  string $name
     * @param  string $type
     * @return string
     */
    protected function getViewPath($name, $type)
    {
        $inflector = Inflector::get('en');
        $directoryName = \StringHelper::camel2spinal($inflector->pluralize($name));

        return $this->laravel['path'] . '/../resources/views/pages/admin/' . $directoryName . '/' . $type . '.blade.php';
    }

    /**
     * @param  string
     * @return string
     */
    protected function getStubForView($type)
    {
        return __DIR__ . '/stubs/admin-crud-view-' . $type . '.stub';
    }

    /**
     * @param  string $name
     * @return bool
     */
    protected function addRoute($name)
    {
        $inflector = Inflector::get('en');
        $directoryName = \StringHelper::camel2Spinal($inflector->pluralize($name));

        $routes = $this->files->get($this->getRoutesPath());
        $key = '/* NEW ADMIN RESOURCE ROUTE */';
        $route = 'Route::resource(\'' . $directoryName . '\', \'Admin\\' . $name . 'Controller\');' . PHP_EOL . '        ' . $key;
        $routes = str_replace($key, $route, $routes);
        $this->files->put($this->getRoutesPath(), $routes);

        return true;
    }

    protected function getRoutesPath()
    {
        return $this->laravel['path'] . '/Http/routes.php';
    }

    protected function generateLanguageFile($name)
    {
        $inflector = Inflector::get('en');
        $directoryName = \StringHelper::camel2Spinal($inflector->pluralize($name));

        $languages = $this->files->get($this->getLanguageFilePath());
        $key = '/* NEW PAGE STRINGS */';

        $columns = $this->getColumns($name);
        $data = "'" . $directoryName . "'   => [" . PHP_EOL . "            'columns'  => [" . PHP_EOL;
        foreach ($columns as $column) {
            $data .= "                '" . $column . "' => ''," . PHP_EOL;
        }
        $data .= "            ]," . PHP_EOL . "        ]," . PHP_EOL . '        ' . $key;


        $languages = str_replace($key, $data, $languages);
        $this->files->put($this->getLanguageFilePath(), $languages);

        return true;

    }

    protected function getLanguageFilePath()
    {
        return $this->laravel['path'] . '/../resources/lang/en/admin.php';
    }

    protected function generateUnitTest($name)
    {
        $path = $this->getUnitTestPath($name);
        if ($this->alreadyExists($path)) {
            $this->error($path . ' already exists.');

            return false;
        }

        $this->makeDirectory($path);

        $stub = $this->files->get($this->getStubForUnitTest());
        $this->replaceTemplateVariables($stub, $name);
        $this->files->put($path, $stub);

        return true;
    }

    /**
     * @param  string $name
     * @return string
     */
    protected function getUnitTestPath($name)
    {
        return $this->laravel['path'] . '/../tests/Controllers/Admin/' . $name . 'ControllerTest.php';
    }

    /**
     * @return string
     */
    protected function getStubForUnitTest()
    {
        return __DIR__ . '/stubs/admin-crud-controller-unittest.stub';
    }

}
