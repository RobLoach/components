<?php
namespace Components;

use Composer\Package\PackageInterface;
use Composer\Installer\LibraryInstaller;

class Installer extends LibraryInstaller
{
    /**
     * The version of the Components Composer installer.
     */
    const VERSION = '@package_version@';

    /**
     * {@inheritDoc}
     */
    public function getInstallPath(PackageInterface $package)
    {
        // Make sure we're installing the right type of package.
        if ($package->getType() != 'component') {
            throw new \InvalidArgumentException('Unable to install packages other than "components".');
        }

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
        copy($source, $base.DIRECTORY_SEPARATOR.'require.js');

        // Write the new require.config.js with the new package information.
        $extra = $this->composer->getPackage()->getExtra();
        $packages = isset($extra['component-packages']) ? $extra['component-packages'] : array();
        $requirepackage = $this->requireJsPackage($package, $name);
        $packages[] = $requirepackage;
        $javascript = $this->requireJsConfig($packages);
        file_put_contents($base.DIRECTORY_SEPARATOR.'require.config.js', $javascript);

        // Re-save the array of Component packages for addition later on.
        $extra['component-packages'][] = $requirepackage;
        // @todo Save the package to the extra definition so we have a whole
        // list of needed packages.
        //$this->composer->getPackage()->setExtra($extra);

        // Instruct to install into the base directory.
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
        // Create the components RequireJS definition.
        $components['packages'] = $packages;
        $components['version'] = Installer::VERSION;
        $json = json_encode($components);

        // Construct the JavaScript output.
        $output = <<<EOT
var components = $json;
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
