<?php

namespace FluentSupport\App;

class Vite
{
    private array $moduleScripts = [];
    private bool $isScriptFilterAdded = false;
    private string $viteHostProtocol = 'http://';
    private string $viteHost = 'localhost';
    private string $vitePort = '5175';
    private string $resourceDirectory = 'resources/';

    protected static ?Vite $instance = null;
    public ?string $lastJsHandle = null;
    private ?array $manifestData = null;
    private array $enqueuedChunkCss = [];

    public function __construct()
    {
        $serverConfigPath = FLUENT_SUPPORT_PLUGIN_PATH . 'config' . DIRECTORY_SEPARATOR . 'vite.json';
        if (file_exists($serverConfigPath)) {
            $serverConfig = json_decode(file_get_contents($serverConfigPath));
            if ($serverConfig) {
                $this->viteHost = $serverConfig->host ?: $this->viteHost;
                $this->viteHostProtocol = $serverConfig->protocol ?: $this->viteHostProtocol;
                $this->vitePort = $serverConfig->port ?: $this->vitePort;
            }
        }

        add_filter('script_loader_tag', [$this, 'maybeConvertToModule'], 999, 3);
    }

    /**
     * Convert scripts from Vite dev server or built assets to ES modules
     */
    public function maybeConvertToModule($tag, $handle, $src): string
    {
        $isViteScript = false;
        $assetBase = FLUENT_SUPPORT_PLUGIN_URL . 'assets/';

        if (strpos($src, 'localhost:' . $this->vitePort) !== false || strpos($src, '@vite/client') !== false) {
            $isViteScript = true;
        }

        if (in_array($handle, $this->moduleScripts)) {
            $isViteScript = true;
        }

        if (!$this->shouldServeViaDevServer()) {
            $assetPatterns = [
                $assetBase . 'admin/',
                $assetBase . 'portal/js/',
            ];
            foreach ($assetPatterns as $pattern) {
                if (strpos($src, $pattern) !== false && strpos($src, '.js') !== false) {
                    $excludePatterns = [
                        '/libs/',
                        '/vendor/',
                        'purify.min.js',
                    ];
                    $isExcluded = false;
                    foreach ($excludePatterns as $exclude) {
                        if (strpos($src, $exclude) !== false) {
                            $isExcluded = true;
                            break;
                        }
                    }
                    if (!$isExcluded) {
                        $isViteScript = true;
                        break;
                    }
                }
            }
        }

        if ($isViteScript) {
            if (strpos($tag, 'type="module"') !== false || strpos($tag, "type='module'") !== false) {
                return $tag;
            }
            $tag = preg_replace('/<script\s+/', '<script type="module" crossorigin ', $tag);
        }

        return $tag;
    }

    private static function getInstance(): Vite
    {
        if (static::$instance === null) {
            static::$instance = new static();
            if (!static::$instance->usingDevMode() || !static::$instance->isViteServerRunning()) {
                (static::$instance)->loadViteManifest();
            }
        }

        return static::$instance;
    }

    private function loadViteManifest()
    {
        if (!empty($this->manifestData)) {
            return;
        }

        $manifestPath = FLUENT_SUPPORT_PLUGIN_PATH . 'config' . DIRECTORY_SEPARATOR . 'vite_config.php';

        if (file_exists($manifestPath)) {
            $this->manifestData = require $manifestPath;
        }

        if (empty($this->manifestData)) {
            $this->manifestData = [];
        }
    }

    public static function enqueueScript($handle, $src, $dependency = [], $version = null, $inFooter = false): Vite
    {
        return static::getInstance()->enqueue_script(
            $handle, $src, $dependency, $version, $inFooter
        );
    }

    private function enqueue_script($handle, $src, $dependency = [], $version = null, $inFooter = false): Vite
    {
        $this->moduleScripts[] = $handle;
        $this->lastJsHandle = $handle;

        if (!$this->isScriptFilterAdded) {
            add_filter('script_loader_tag', function ($tag, $handle, $src) {
                return $this->addModuleToScript($tag, $handle, $src);
            }, 10, 3);
            $this->isScriptFilterAdded = true;
        }

        if ($this->shouldServeViaDevServer()) {
            $srcPath = $this->getVitePath() . $src;
        } else {
            $assetFile = $this->getFileFromManifest($src);
            $srcPath = $this->getProductionFilePath($assetFile);
        }

        if (empty($srcPath)) {
            return $this;
        }

        $version = empty($version) ? FLUENT_SUPPORT_VERSION : $version;

        wp_enqueue_script($handle, $srcPath, $dependency, $version, $inFooter);

        return $this;
    }

    private function addModuleToScript($tag, $handle, $src): string
    {
        if (in_array($handle, $this->moduleScripts)) {
            if ($this->usingDevMode() || strpos($src, 'assets/') !== false) {
                $tag = '<script type="module" crossorigin src="' . esc_url($src) . '" id="' . esc_attr($handle) . '-js"></script>' . "\n";
            } else {
                $tag = '<script type="module" src="' . esc_url($src) . '" id="' . esc_attr($handle) . '-js"></script>' . "\n";
            }
        }
        return $tag;
    }

    private function getFileFromManifest($src)
    {
        if (isset($this->manifestData[$this->resourceDirectory . $src])) {
            return $this->manifestData[$this->resourceDirectory . $src];
        }

        return '';
    }

    private function getProductionFilePath($file): string
    {
        if (!isset($file['file'])) {
            return '';
        }

        $assetPath = static::getAssetPath();
        $this->ensureChunkCssIsLoaded($file);

        return ($assetPath . $file['file']);
    }

    // CSS files that are standalone entry outputs (NOT merged into style.css).
    // Everything else from Vite chunk splitting is merged into admin/css/alpha-admin.css.
    private array $standaloneCssPaths = [
        'admin/css/alpha-admin.css',
        'portal/css/app.css',
        'admin/css/all_public.css',
    ];

    private function ensureChunkCssIsLoaded($file)
    {
        $assetPath = static::getAssetPath();
        $cssFiles = $this->collectChunkCssFiles($file);

        foreach ($cssFiles as $cssPath) {
            if (isset($this->enqueuedChunkCss[$cssPath])) {
                continue;
            }

            // Skip chunk CSS that was merged into style.css by mergeCssChunksPlugin.
            // Only standalone SCSS entry outputs are NOT merged.
            if (!in_array($cssPath, $this->standaloneCssPaths)) {
                $this->enqueuedChunkCss[$cssPath] = true;
                continue;
            }

            wp_enqueue_style(
                'fluent_support_vite_css_' . md5($cssPath),
                $assetPath . $cssPath,
                [],
                FLUENT_SUPPORT_VERSION
            );

            $this->enqueuedChunkCss[$cssPath] = true;
        }
    }

    private function collectChunkCssFiles($file, &$visited = []): array
    {
        $cssFiles = [];

        if (!is_array($file)) {
            return $cssFiles;
        }

        $fileId = isset($file['file']) ? $file['file'] : md5(wp_json_encode($file));
        if (isset($visited[$fileId])) {
            return $cssFiles;
        }
        $visited[$fileId] = true;

        if (isset($file['css']) && is_array($file['css'])) {
            foreach ($file['css'] as $path) {
                if (is_string($path) && $path !== '') {
                    $cssFiles[] = $path;
                }
            }
        }

        if (isset($file['imports']) && is_array($file['imports'])) {
            foreach ($file['imports'] as $importKey) {
                if (!isset($this->manifestData[$importKey]) || !is_array($this->manifestData[$importKey])) {
                    continue;
                }
                $cssFiles = array_merge($cssFiles, $this->collectChunkCssFiles($this->manifestData[$importKey], $visited));
            }
        }

        return array_values(array_unique($cssFiles));
    }

    public static function enqueueStyle($handle, $src, $dependency = [], $version = null, $media = 'all')
    {
        static::getInstance()->enqueue_style(
            $handle, $src, $dependency, $version, $media
        );
    }

    private function enqueue_style($handle, $src, $dependency = [], $version = null, $media = 'all')
    {
        if ($this->shouldServeViaDevServer()) {
            $srcPath = $this->getVitePath() . $src;
        } else {
            $assetFile = $this->getFileFromManifest($src);
            $srcPath = $this->getProductionFilePath($assetFile);
        }

        if (empty($srcPath)) {
            return;
        }

        $version = empty($version) ? FLUENT_SUPPORT_VERSION : $version;

        wp_enqueue_style($handle, $srcPath, $dependency, $version, $media);
    }

    public static function enqueueStaticScript($handle, $src, $dependency = [], $version = null, $inFooter = false): Vite
    {
        $version = empty($version) ? FLUENT_SUPPORT_VERSION : $version;

        return static::getInstance()->enqueue_static_script(
            $handle, $src, $dependency, $version, $inFooter
        );
    }

    private function enqueue_static_script($handle, $src, $dependency = [], $version = null, $inFooter = false): Vite
    {
        $version = empty($version) ? FLUENT_SUPPORT_VERSION : $version;

        wp_enqueue_script(
            $handle,
            $this->getStaticEnqueuePath($src),
            $dependency,
            $version,
            $inFooter
        );

        return $this;
    }

    private function getStaticEnqueuePath($path): string
    {
        if ($this->shouldServeViaDevServer()) {
            return $this->getVitePath() . $path;
        }

        return $this->get_asset_url($path);
    }

    public static function enqueueStaticStyle($handle, $src, $dependency = [], $version = null, $media = 'all')
    {
        $version = empty($version) ? FLUENT_SUPPORT_VERSION : $version;

        static::getInstance()->enqueue_static_style(
            $handle, $src, $dependency, $version, $media
        );
    }

    private function enqueue_static_style($handle, $src, $dependency = [], $version = null, $media = 'all')
    {
        $version = empty($version) ? FLUENT_SUPPORT_VERSION : $version;

        wp_enqueue_style(
            $handle,
            $this->getStaticEnqueuePath($src),
            $dependency,
            $version,
            $media
        );
    }

    public static function underDevelopment(): bool
    {
        return static::getInstance()->usingDevMode();
    }

    /**
     * True only when env=dev AND the Vite dev server is actually reachable.
     * Use this instead of underDevelopment() when deciding whether to skip built assets.
     */
    public static function isServingFromDevServer(): bool
    {
        return static::getInstance()->shouldServeViaDevServer();
    }

    public function usingDevMode(): bool
    {
        $app = App::getInstance();
        return $app->config->get('app.env') === 'dev';
    }

    /**
     * True only when env=dev AND the Vite dev server is reachable.
     */
    private function shouldServeViaDevServer(): bool
    {
        return $this->usingDevMode() && $this->isViteServerRunning();
    }

    private function isViteServerRunning(): bool
    {
        static $isRunning = null;

        if ($isRunning !== null) {
            return $isRunning;
        }

        $viteUrl = $this->viteHostProtocol . $this->viteHost . ':' . $this->vitePort . '/@vite/client';

        $response = wp_remote_get($viteUrl, [
            'timeout'   => 1,
            'sslverify' => false
        ]);

        $isRunning = !is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200;

        return $isRunning;
    }

    public function getVitePath(): string
    {
        $protocol = rtrim($this->viteHostProtocol, ':/');
        $host = rtrim($this->viteHost, '/');
        $port = $this->vitePort;
        $resource = ltrim($this->resourceDirectory, '/');

        return sprintf('%s://%s:%s/%s', $protocol, $host, $port, $resource);
    }

    /**
     * Resolve asset path - works for both dev and production.
     * Accepts Mix-style paths (e.g. admin/js/start.js) and maps them to Vite source paths.
     */
    public static function getEnqueuePath($path = ''): string
    {
        $vite = static::getInstance();
        $path = ltrim($path, '/');

        if (!$vite->usingDevMode()) {
            $sourcePath = $vite->getSourcePathForManifest($path);
            $assetFile = $vite->getFileFromManifest($sourcePath);
            if ($assetFile) {
                $srcPath = $vite->getProductionFilePath($assetFile);
            } else {
                $srcPath = static::getAssetPath() . $path;
            }
        } else {
            if ($vite->isViteServerRunning()) {
                $srcPath = $vite->mapToSourcePath($path);
            } else {
                $sourcePath = $vite->getSourcePathForManifest($path);
                $assetFile = $vite->getFileFromManifest($sourcePath);
                if ($assetFile) {
                    $srcPath = $vite->getProductionFilePath($assetFile);
                } else {
                    $srcPath = static::getAssetPath() . $path;
                }
            }
        }

        return $srcPath;
    }

    /**
     * Map built asset paths to source paths for dev mode
     */
    private function mapToSourcePath($path): string
    {
        $cssMap = [
            'admin/css/alpha-admin.css' => 'scss/alpha-admin.scss',
            'portal/css/app.css'        => 'customer_portal/app.scss',
            'admin/css/all_public.css'  => 'scss/all_public.scss',
        ];

        $jsMap = [
            'admin/js/start.js'          => 'admin/start.js',
            'admin/js/global_admin.js'   => 'admin/global_admin.js',
            'admin/js/global_summary.js' => 'admin/global_summary.js',
            'portal/js/app.js'           => 'customer_portal/portal.js',
            'portal/js/login_helper.js'  => 'customer_portal/login_helper.js',
        ];

        $pathMap = array_merge($cssMap, $jsMap);

        if (isset($pathMap[$path])) {
            return $this->getVitePath() . $pathMap[$path];
        }

        if (strpos($path, 'resources/') === 0) {
            return $this->getVitePath() . substr($path, 10);
        }

        return $this->getVitePath() . $path;
    }

    /**
     * Map Mix-style paths to Vite source paths for manifest lookup
     */
    private function getSourcePathForManifest($path): string
    {
        $pathMap = [
            'admin/js/start.js'          => 'admin/start.js',
            'admin/js/global_admin.js'   => 'admin/global_admin.js',
            'admin/js/global_summary.js' => 'admin/global_summary.js',
            'portal/js/app.js'           => 'customer_portal/portal.js',
            'portal/js/login_helper.js'  => 'customer_portal/login_helper.js',

            'admin/css/alpha-admin.css' => 'scss/alpha-admin.scss',
            'portal/css/app.css'        => 'customer_portal/app.scss',
            'admin/css/all_public.css'  => 'scss/all_public.scss',
        ];

        if (isset($pathMap[$path])) {
            return $pathMap[$path];
        }

        return $path;
    }

    public static function getAssetUrl($path = ''): string
    {
        return esc_url(static::getInstance()->get_asset_url($path) ?? '');
    }

    private function get_asset_url($path = ''): string
    {
        if ($this->shouldServeViaDevServer()) {
            return $this->getVitePath() . $path;
        }

        return FLUENT_SUPPORT_PLUGIN_URL . 'assets/' . ltrim($path, '/');
    }

    public static function getAssetPath(): string
    {
        return FLUENT_SUPPORT_PLUGIN_URL . 'assets/';
    }

    /**
     * Inject Vite client for HMR in development mode
     */
    public static function injectViteClient()
    {
        $vite = static::getInstance();

        if ($vite->shouldServeViaDevServer()) {
            $protocol = rtrim($vite->viteHostProtocol, ':/');
            $host = rtrim($vite->viteHost, '/');
            $port = $vite->vitePort;

            $viteClientUrl = sprintf('%s://%s:%s/@vite/client', $protocol, $host, $port);
            echo '<script type="module" crossorigin src="' . esc_url($viteClientUrl) . '"></script>' . "\n";
        }
    }
}
