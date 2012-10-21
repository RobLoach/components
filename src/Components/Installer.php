<?php
namespace Components;

use Composer\Package\PackageInterface;
use Composer\Installer\LibraryInstaller;

class Installer extends LibraryInstaller
{
    /**
     * {@inheritDoc}
     */
    public function getInstallPath(PackageInterface $package)
    {
        // Retrieve the $name and $vendor names for the package.
        $prettyName = $package->getPrettyName();
        if (strpos($prettyName, '/') !== false) {
            list($vendor, $name) = explode('/', $prettyName);
        } else {
            $vendor = '';
            $name = $prettyName;
        }

        // Allow switching the base components installation directory.
        $base = 'components';
        if ($this->composer->getPackage()) {
            $extra = $this->composer->getPackage()->getExtra();
            if (isset($extra['components']['install-path'])) {
                $base = $extra['components']['install-path'];
            }
        }

        // Copy require.js to the base directory.
        $source = __DIR__.DIRECTORY_SEPARATOR.'Resources'.DIRECTORY_SEPARATOR.'require.js';
        copy($source, $base.DIRECTORY_SEPARATOR.'autoload.js');

        // Write the new autoload.config.js with the new package information.
        $config = $this->composer->getConfig();
        $packages = $config->has('component-packages') ? $config->get('component-packages') : array();
        $packages[] = $this->requireJsPackage($package, $name);
        $config = $this->requireJsConfig($packages);
        file_put_contents($base.DIRECTORY_SEPARATOR.'autoload.config.js', $config);

        return $base.DIRECTORY_SEPARATOR.$name;
    }

    /**
     * {@inheritDoc}
     */
    public function supports($packageType)
    {
        return $packageType == 'component';
    }

    /**
     * Build a RequireJS package definition from the Composer package.
     */
    protected function requireJsPackage(PackageInterface $package, $name) {
        // The components extra definition replicates what is passed to Require.
        $extra = $package->getExtra();
        $output = isset($extra['components']) ? $extra['components'] : array();

        // The location is needed to be passed in so that require knows what
        // folder to look in.
        $output['location'] = $name;

        return $output;
    }

    /**
     * From a set of RequireJS packages, construct the JavaScript config.
     */
    protected function requireJsConfig(array $packages) {
        $json = json_encode($packages);
        $output = 'var components = {"packages": [' . $json . ']};';
        $output .= <<<EOT
        if (typeof require !== "undefined" && require.config) {
            require.config({packages: components.packages});
        }
        else {
            var require = {packages: components.packages};
        }

        if (typeof exports !== "undefined" && typeof module !== "undefined") {
            module.exports = components;
        }
EOT;
        return $output;
    }
}
