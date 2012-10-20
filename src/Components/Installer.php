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
            if (isset($extra['components']['path'])) {
                $base = $extra['components']['path'];
            }
        }

        return $base.DIRECTORY_SEPARATOR.$name;
    }

    /**
     * {@inheritDoc}
     */
    public function supports($packageType)
    {
        return $packageType == 'component';
    }
}
