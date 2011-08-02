<?php
/* $Id: Serializer.php 171 2006-06-15 23:24:18Z das-svn $ */
/**
 * LibRDF_Serializer, a wrapper around librdf_serializer.
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
require_once(dirname(__FILE__) . '/URI.php');

/**
 * A wrapper around the librdf_serializer datatype.
 *
 * This class is used in conjunction with {@link LibRDF_Model} to produce
 * a serialization of a set of statements.
 *
 * @package     LibRDF
 * @author      David Shea <david@gophernet.org>
 * @copyright   2006 David Shea
 * @license     LGPL/GPL/APACHE
 * @version     Release: 1.0.0
 * @link        http://www.gophernet.org/projects/redland-php/
 */
class LibRDF_Serializer
{
    /**
     * The underlying librdf_serializer resource.
     *
     * @var     resource
     * @access  private
     */
    private $serializer;

    /**
     * Namespace mappings shared accross instances.
     *
     * @var array
     */
    private static $namespaces = array();

    /**
     * Create a new LibRDF_Serializer.
     *
     * Name is the name of the serializer to use.  Common choices are
     * "rdfxml", "ntriples" and "turtle".
     *
     * The "rdfxml" serializer is not pretty, outputing a flat list of
     * one XML element per statement.  "rdfxml-abbrev" is a bit nicer, but
     * slower.
     *
     * @param   string      $name       The name of the serializer to use
     * @param   string      $mime_type  The MIME type of the syntax
     * @param   string      $type_uri   The URI of the syntax
     * @return  void
     * @throws  LibRDF_Error            If unable to create a new serializer
     * @access  public
     */
    public function __construct($name, $mime_type=NULL, 
        $type_uri=NULL)
    {
        if ($type_uri) {
            $type_uri = new LibRDF_URI($type_uri);
        }

        $this->serializer = librdf_new_serializer(librdf_php_get_world(),
            $name, $mime_type, ($type_uri ? $type_uri->getURI : $type_uri));

        if (!$this->serializer) {
            throw new LibRDF_Error("Unable to create new serializer");
        }
        foreach (self::$namespaces as $uri => $prefix) {
            $this->setNamespace($uri, $prefix);
        }
    }

    /**
     * Free the serializer's resources.
     *
     * @return  void
     * @access  public
     */
    public function __destruct()
    {
        if ($this->serializer) {
            librdf_free_serializer($this->serializer);
        }
    }

    /**
     * Return the wrapped librdf_serializer resource.
     *
     * This function is intended for other LibRDF classes and should not
     * be called.
     *
     * @return  resource    The wrapper resource
     * @access  public
     */
    public function getSerializer()
    {
        return $this->serializer;
    }

    /**
     * Set a prefix to URI mapping.
     *
     * @param   string      $uri    The namespace URI
     * @param   string      $prefix The string prefix to use
     * @return  void
     * @throws  LibRDF_Error        If unable to set the prefix
     * @access  public
     */
    public function setNamespace($uri, $prefix)
    {
        $uri = new LibRDF_URI($uri);
        $ret = librdf_serializer_set_namespace($this->serializer,
            $uri->getURI(), $prefix);

        if ($ret) {
            throw new LibRDF_Error("Unable to set namespace prefix");
        }
    }

    /**
     * Set namespace mappings on class level.
     *
     * @param  array  $map
     * @return void
     */
    public static function setNamespaces($map)
    {
        self::$namespaces = $map;
    }
}
?>
