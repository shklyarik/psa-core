<?php

namespace Psa\Core\Cli\Commands\Generate;

use Psa\Core\Cli\App;

class GenerateCommand
{
    public function run(App $app)
    {
        $name = trim(readline("Enter command name: "));
        $name = strtr($name, [' ' => '_']);

        $baseDir = $app->getAlias('@app/src/Commands');
        if (!file_exists($baseDir)) {
            mkdir($baseDir, 0777, true);
        }

        // === Разбор имени ===
        $group = null;
        $sub = null;

        if (str_contains($name, ':')) {
            [$group, $sub] = explode(':', $name, 2);
        } else {
            $group = $name;
        }

        $toPascal = fn($str) => implode('', array_map('ucfirst', explode('-', $str)));

        if ($sub) {
            // пример grab-news:school-site
            $groupClass = $toPascal($group);
            $className = $toPascal($sub);
            $namespace = "App\\Commands\\{$groupClass}";
            $dir = "{$baseDir}/{$groupClass}";
            $fileName = "{$dir}/{$className}.php";
        } else {
            // пример grab-news
            $className = $toPascal($group) . 'Command';
            $namespace = 'App\\Commands';
            $dir = $baseDir;
            $fileName = "{$dir}/{$className}.php";
        }

        echo PHP_EOL . "File to create: {$fileName}" . PHP_EOL;
        echo "Namespace: {$namespace}" . PHP_EOL;
        echo "Class: {$className}" . PHP_EOL;

        $confirm = strtolower(trim(readline("Create file? (y/n): ")));
        if ($confirm !== 'y') {
            echo "Aborted." . PHP_EOL;
            return;
        }

        if (!file_exists(dirname($fileName))) {
            mkdir(dirname($fileName), 0777, true);
        }

        $content = <<<PHP
<?php

namespace {$namespace};

class {$className}
{
    public function run()
    {
        echo "{$name}" . PHP_EOL;
    }
}

PHP;

        file_put_contents($fileName, $content);
        echo "✅ File created: {$fileName}" . PHP_EOL;

        // === Добавляем в config/commands.php ===
        $commandListFile = $app->getAlias('@app/config/commands.php');
        if (!file_exists($commandListFile)) {
            echo "⚠️ Config file not found: {$commandListFile}" . PHP_EOL;
            return;
        }

        $alias = $name;
        $fullClass = "{$namespace}\\{$className}::class";

        $content = file_get_contents($commandListFile);

        // Проверим, есть ли уже команда
        if (strpos($content, $fullClass) !== false) {
            echo "ℹ️ Command already registered in config." . PHP_EOL;
            return;
        }

        // Вставляем перед последней закрывающей скобкой ]
        $pattern = '/return\s*\[(.*?)\];/s';
        if (preg_match($pattern, $content, $matches)) {
            $inner = trim($matches[1]);
            $newEntry = "    '{$alias}' => {$namespace}\\{$className}::class," . PHP_EOL;
            $updated = str_replace(
                $matches[0],
                "return [\n{$inner}\n{$newEntry}];",
                $content
            );
            file_put_contents($commandListFile, $updated);
            echo "✅ Command registered in config: {$alias}" . PHP_EOL;
        } else {
            echo "⚠️ Could not update commands.php — check format." . PHP_EOL;
        }
    }
}
