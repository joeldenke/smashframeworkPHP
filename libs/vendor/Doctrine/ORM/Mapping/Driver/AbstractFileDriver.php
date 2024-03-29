<?php
/*
 *  $Id$
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the LGPL. For more information, see
 * <http://www.doctrine-project.org>.
 */

namespace Doctrine\ORM\Mapping\Driver;

use Doctrine\ORM\Mapping\MappingException;

/**
 * Base driver for file-based metadata drivers.
 * 
 * A file driver operates in a mode where it loads the mapping files of individual
 * classes on demand. This requires the user to adhere to the convention of 1 mapping
 * file per class and the file names of the mapping files must correspond to the full
 * class name, including namespace, with the namespace delimiters '\', replaced by dots '.'.
 *
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.doctrine-project.com
 * @since       2.0
 * @version     $Revision: 1393 $
 * @author      Jonathan H. Wage <jonwage@gmail.com>
 * @author      Roman Borschel <roman@code-factory.org>
 */
abstract class AbstractFileDriver implements Driver
{
    /**
     * The paths where to look for mapping files.
     *
     * @var array
     */
    protected $_paths;

    /**
     * The file extension of mapping documents.
     *
     * @var string
     */
    protected $_fileExtension;

    /**
     * Initializes a new FileDriver that looks in the given path(s) for mapping
     * documents and operates in the specified operating mode.
     * 
     * @param string|array $paths One or multiple paths where mapping documents can be found.
     */
    public function __construct($paths)
    {
        $this->_paths = (array) $paths;
    }

    /**
     * Get the file extension used to look for mapping files under
     *
     * @return void
     */
    public function getFileExtension()
    {
        return $this->_fileExtension;
    }

    /**
     * Set the file extension used to look for mapping files under
     *
     * @param string $fileExtension The file extension to set
     * @return void
     */
    public function setFileExtension($fileExtension)
    {
        $this->_fileExtension = $fileExtension;
    }

    /**
     * Get the element of schema meta data for the class from the mapping file.
     * This will lazily load the mapping file if it is not loaded yet
     *
     * @return array $element  The element of schema meta data
     */
    public function getElement($className)
    {
        $result = $this->_loadMappingFile($this->_findMappingFile($className));
        return $result[$className];
    }

    /**
     * Whether the class with the specified name should have its metadata loaded.
     * This is only the case if it is either mapped as an Entity or a
     * MappedSuperclass.
     *
     * @param string $className
     * @return boolean
     */
    public function isTransient($className)
    {
        $isTransient = true;
        // check whether file exists
        foreach ((array)$this->_paths as $path) {
            if (file_exists($path . DIRECTORY_SEPARATOR . str_replace('\\', '.', $className) . $this->_fileExtension)) {
                $isTransient = false;
                break;
            }
        }

        return $isTransient;
    }
    
    /**
     * Gets the names of all mapped classes known to this driver.
     * 
     * @return array The names of all mapped classes known to this driver.
     */
    public function getAllClassNames()
    {
        $clasNames = array();
        foreach ((array)$this->_paths as $path) {
            if (is_dir($path)) {
                $files = glob($path . '/*');
                foreach ($files as $file) {
                    $classNames[] = str_replace(array($this->_fileExtension, '.'), array('', '\\'), basename($file));
                }
            } else if (is_file($path)) {
                $classNames[] = str_replace(array($this->_fileExtension, '.'), array('', '\\'), basename($file));
            }
        }

        return $classNames;
    }

    /**
     * Finds the mapping file for the class with the given name by searching
     * through the configured paths.
     *
     * @param $className
     * @return string The (absolute) file name.
     * @throws MappingException
     */
    protected function _findMappingFile($className)
    {
        $fileName = null;
        foreach ((array)$this->_paths as $path) {
            $fileName = $path . DIRECTORY_SEPARATOR . str_replace('\\', '.', $className) . $this->_fileExtension;
            if (file_exists($fileName)) {
                break;
            }
        }

        if ($fileName === null) {
            throw MappingException::mappingFileNotFound($className);
        }

        return $fileName;
    }

    /**
     * Loads a mapping file with the given name and returns a map
     * from class/entity names to their corresponding elements.
     * 
     * @param string $file The mapping file to load.
     * @return array
     */
    abstract protected function _loadMappingFile($file);
}