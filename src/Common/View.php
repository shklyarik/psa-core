<?php

namespace Psa\Core\Common;

use RuntimeException;

/**
 * View class for rendering templates with optional layouts.
 *
 * This class handles the rendering of view files and can optionally wrap the content
 * with a layout template. It supports passing parameters to the view and layout.
 */
class View
{
    /** @var string|null Path to the layout file */
    protected ?string $layoutFile = null;

    /** @var array Parameters to pass to the layout */
    protected array $layoutParams = [];

    /** @var string The rendered content */
    protected string $content = '';

    /**
     * Constructor for the View class.
     *
     * @param string $viewFile Path to the view file to render
     * @param array $params Parameters to pass to the view
     */
    public function __construct(
        protected string $viewFile,
        protected array $params = []
    ) {}

    /**
     * Renders the view file and optionally wraps it with a layout.
     *
     * @return string The rendered content
     */
    public function render(): string
    {
        $this->content = $this->renderFile($this->resolvePath($this->viewFile), $this->params);

        if ($this->layoutFile) {
            $layoutPath = $this->resolvePath($this->layoutFile);
            $layoutVars = array_merge($this->layoutParams, ['content' => $this->content]);
            $this->content = $this->renderFile($layoutPath, $layoutVars);
        }

        return $this->content;
    }

    /**
     * Sets the layout file and parameters for the view.
     *
     * @param string $layoutPath Path to the layout file
     * @param array $params Parameters to pass to the layout
     * @return void
     */
    public function layout(string $layoutPath, array $params = []): void
    {
        $this->layoutFile = $layoutPath;
        $this->layoutParams = $params;
    }

    /**
     * Renders a file with the given parameters.
     *
     * @param string $file Path to the file to render
     * @param array $params Parameters to extract for the file
     * @return string The rendered content
     * @throws RuntimeException If the view file does not exist
     */
    protected function renderFile(string $file, array $params): string
    {
        if (!file_exists($file)) {
            throw new RuntimeException("View file not found: $file");
        }

        extract($params, EXTR_OVERWRITE);

        ob_start();
        ob_implicit_flush(false);
        include $file;
        return ob_get_clean();
    }

    /**
     * Resolves the path for a view or layout file.
     *
     * @param string $path The path to resolve
     * @return string The resolved path
     */
    protected function resolvePath(string $path): string
    {
        return $path;
    }
}