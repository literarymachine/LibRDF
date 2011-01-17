<?php
/* $Id: Storage.php 171 2006-06-15 23:24:18Z das-svn $ */
/**
 * LibRDF_Storage, an abstraction of an RDF graph as a set of statements.
 *
 * PHP version 5
 *
 * Copyright (C) 2006, David Shea <david@gophernet.org>
 *
 * LICENSE: This package is Free Software and a derivative work of Redland
 * http://librdf.org/.  This package is not endorsed by Dave Beckett or the 
 * University of Bristol. It is licensed under the following three licenses as 
 * alternatives:
 *   1. GNU Lesser General Public License (LGPL) V2.1 or any newer version
 *   2. GNU General Public License (GPL) V2 or any newer version
 *   3. Apache License, V2.0 or any newer version
 *
 * You may not use this file except in compliance with at least one of the
 * above three licenses.
 *
 * See LICENSE.txt at the top of this package for the complete terms and futher
 * detail along with the license tests for the licenses in COPYING.LIB, COPYING
 * and LICENSE-2.0.txt repectively.
 *
 * @package     LibRDF
 * @author      David Shea <david@gophernet.org>
 * @copyright   2006 David Shea
 * @license     LGPL/GPL/APACHE
 * @version     Release: 1.0.0
 * @link        http://www.gophernet.org/projects/redland-php/
 */

/**
 */
require_once(dirname(__FILE__) . '/Error.php');

/**
 * A wrapper around the librdf_storage datatype.
 *
 * @package     LibRDF
 * @author      David Shea <david@gophernet.org>
 * @copyright   2006 David Shea
 * @license     LGPL/GPL/APACHE
 * @version     Release: 1.0.0
 * @link        http://www.gophernet.org/projects/redland-php/
 */
class LibRDF_Storage
{
    /**
     * The underlying librdf_storage object.
     * 
     * @var     resource
     * @access  private
     */
    private $storage;

    /**
     * Creates a new storage backend.
     *
     * The storage methods available depends on the librdf configuration.
     * Methods always available are `memory', `hashes', `file' and `uri'.
     * Optional methods are `bdb', `mysql' and `sqllite'.  The default is
     * `memory'.
     *
     * The name argument is mandatory for storage methods that required a named
     * handle, such as file and URI.
     *
     *   <code>$stor = new LibRDF_Storage(storage_name="file", name="/tmp/filename");</code>
     *
     * The options string passes storage_name specific options to the chosen
     * backend and uses the following form:
     *
     *   <code>$stor = new LibRDF_Storage("storage_name", "name",
     *              "key1='value1', key2='value2', ...");</code>
     * 
     * Options values must be surrounded by single quotes for multiple
     * key/option pairs.
     *
     * The options common to all storage methods are:
     *    new - optional boolean (default false)
     *       If true, delete any existing store and create a new one, otherwise
     *       open an existing store.
     *    
     *    write - optional boolean (default true)
     *       If true, open the store in read-write mode.
     * 
     * For hashes:
     *    hash-type - the name of any supported hash type (default 'memory')
     *       'memory' and 'file' hash types are always present, and 'bdb'
     *       may be available depending on compile-time configuration of
     *       librdf.
     *    
     *    dir - (default '.') the directory in which to create files
     *
     *    mode - (default 0644) the octal file mode with which to create files
     *
     * @param   string  $storage_name   The type of storage to use
     * @param   string  $name           A name for the storage handle
     * @param   string  $options        Options for the storage backend
     * @return  void
     * @throws  LibRDF_Error            If unable to create a new storage
     * @access  public
     */
    public function __construct($storage_name="memory", $name=NULL,
        $options=NULL)
    {
        $this->storage = librdf_new_storage(librdf_php_get_world(),
            $storage_name, $name, $options);

        if (!$this->storage) {
            throw new LibRDF_Error("Unable to create storage");
        }
    }

    /**
     * Free the storage's resources.
     *
     * @return  void
     * @access  public
     */
    public function __destruct()
    {
        if ($this->storage) {
            librdf_free_storage($this->storage);
        }
    }

    /**
     * Create a new storage in the same context as an existing storage.
     *
     * When cloning a storage object, a new storage will be opened using
     * the same options as the existing one.  This may mean generating
     * new identifiers for files based on the existing identifier.
     *
     * @return  void
     * @throws  LibRDF_Error            If unable to copy the storage
     * @access  public
     */
    public function __clone()
    {
        $this->storage = librdf_new_storage_from_storage($this->storage);

        if (!$this->storage) {
            throw new LibRDF_Error("Unable to copy storage");
        }
    }

    /**
     * Return the underlying storage resource.
     *
     * This function is intended for other LibRDF classes and should not
     * be called.
     *
     * @return  resource    The storage resource
     * @access  public
     */
    public function getStorage()
    {
        return $this->storage;
    }
}

?>
