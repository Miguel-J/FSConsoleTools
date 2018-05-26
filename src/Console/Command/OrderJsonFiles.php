<?php
/**
 * This file is part of FSConsoleTools
 * Copyright (C) 2018 Francesc Pineda Segarra <francesc.pineda.segarra@gmail.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace FacturaScriptsUtils\Console\Command;

use FacturaScriptsUtils\Console\ConsoleAbstract;

/**
 * Class OrderJsonFiles
 *
 * @author Francesc Pineda Segarra <francesc.pineda.segarra@gmail.com>
 */
class OrderJsonFiles extends ConsoleAbstract
{
    /**
     * Constant values for return, to easy know how execution ends.
     */
    const RETURN_SUCCESS = 0;
    const RETURN_SRC_FOLDER_NOT_SET = 1;
    const RETURN_DST_FOLDER_NOT_SET = 2;
    const RETURN_CANT_CREATE_FOLDER = 3;
    const RETURN_FAIL_SAVING_FILE = 4;
    const RETURN_NO_FILES = 5;
    const RETURN_SRC_FOLDER_NOT_EXISTS = 6;

    /**
     * Folder source path.
     *
     * @var string
     */
    private $folderSrcPath;

    /**
     * Folder destiny path.
     *
     * @var string
     */
    private $folderDstPath;

    /**
     * Set default source folder.
     *
     * @param string $folderSrcPath
     *
     * @return $this
     */
    public function setFolderSrcPath(string $folderSrcPath): self
    {
        $this->folderSrcPath = $folderSrcPath;
        return $this;
    }

    /**
     * Set default destiny folder.
     *
     * @param string $folderDstPath
     *
     * @return $this
     */
    public function setFolderDstPath(string $folderDstPath): self
    {
        $this->folderDstPath = $folderDstPath;
        return $this;
    }

    /**
     * Start point to run the command.
     *
     * @param array $params
     *
     * @return int
     */
    public function run(...$params): int
    {
        $this->checkOptions($params);

        $this->setFolderSrcPath($params[0] ?? \FS_FOLDER . 'Core/Translation/');
        $this->setFolderDstPath($params[1] ?? \FS_FOLDER . 'Core/Translation/');

        echo 'Options setted:' . \PHP_EOL;
        echo '   Source path: ' . $this->folderSrcPath . \PHP_EOL;
        echo '   Destiny path: ' . $this->folderDstPath . \PHP_EOL;
        if (!$this->areYouSure()) {
            echo '   Options [SRC] [DST] [TAG]' . \PHP_EOL;
            return self::RETURN_SUCCESS;
        }

        $status = $this->check();
        if ($status > 0) {
            return $status;
        }

        $files = array_diff(scandir($this->folderSrcPath, SCANDIR_SORT_ASCENDING), ['.', '..']);

        if (\count($files) === 0) {
            echo 'ERROR: No files on folder' . \PHP_EOL;
            return self::RETURN_NO_FILES;
        }

        return $this->orderJson($files);
    }

    /**
     * Return description about this class.
     *
     * @return string
     */
    public function getDescription(): string
    {
        return 'Order JSON content files by key.';
    }

    /**
     * Print help information to the user.
     */
    public function showHelp()
    {
        $array = \explode('\\', __CLASS__);
        $class = array_pop($array);
        echo 'Use as: php vendor/bin/console ' . $class . ' [OPTIONS]' . \PHP_EOL;
        echo 'Available options:' . \PHP_EOL;
        echo '   -h, --help        Show this help.' . \PHP_EOL;
        echo \PHP_EOL;
        echo '   OPTION1           Source path' . \PHP_EOL;
        echo '   OPTION2           Destiny path' . \PHP_EOL;
        echo \PHP_EOL;
    }

    /**
     * @param array $params
     */
    private function checkOptions(array $params = [])
    {
        if (isset($params[0])) {
            switch ($params[0]) {
                case '-h':
                case '--help':
                    $this->showHelp();
                    break;
            }
            die();
        }
    }

    /**
     * Launch basic checks.
     *
     * @return int
     */
    private function check(): int
    {
        if ($this->folderSrcPath === null) {
            echo 'ERROR: Source folder not setted.' . \PHP_EOL;
            return self::RETURN_SRC_FOLDER_NOT_SET;
        }
        if ($this->folderDstPath === null) {
            echo 'ERROR: Destiny folder not setted.' . \PHP_EOL;
            return self::RETURN_DST_FOLDER_NOT_SET;
        }
        if (!is_dir($this->folderSrcPath)) {
            echo 'ERROR: Source folder ' . $this->folderSrcPath . ' not exists.' . \PHP_EOL;
            return self::RETURN_SRC_FOLDER_NOT_EXISTS;
        }
        if (!is_file($this->folderDstPath) && !@mkdir($this->folderDstPath) && !is_dir($this->folderDstPath)) {
            echo "ERROR: Can't create folder " . $this->folderDstPath;
            return self::RETURN_CANT_CREATE_FOLDER;
        }
        return self::RETURN_SUCCESS;
    }

    /**
     * Order JSON files
     *
     * @param array $files
     *
     * @return int
     */
    private function orderJson(array $files): int
    {
        foreach ($files as $fileName) {
            $arrayContent = $this->readJSON($this->folderSrcPath . $fileName);
            \ksort($arrayContent);
            if (!$this->saveJSON($arrayContent, $this->folderDstPath . $fileName)) {
                echo "ERROR: Can't save file " . $fileName . \PHP_EOL;
            }
        }

        echo 'Finished! Look at "' . $this->folderDstPath . '"' . \PHP_EOL;
        return self::RETURN_SUCCESS;
    }

    /**
     * Reads a JSON from disc and return it content as array.
     *
     * @param string $pathName
     *
     * @return array
     */
    private function readJSON(string $pathName)
    {
        $data = json_decode(file_get_contents($pathName), true);
        return \is_array($data) ? (array) $data : [];
    }

    /**
     * Write a JSON from an array to disc and return its result.
     *
     * @param array  $data
     * @param string $pathName
     *
     * @return int
     */
    private function saveJSON(array $data, string $pathName): int
    {
        $jsonData = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        return (int) file_put_contents($pathName, $jsonData);
    }
}
