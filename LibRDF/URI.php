<?php
/* $Id: URI.php 171 2006-06-15 23:24:18Z das-svn $ */
/**
 * LibRDF_URI, a representation of a resource in a world.
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
 * A wrapper around the librdf_uri datatype.
 *
 * There is no need to use this class directly, as all LibRDF classes infer
 * whether or not a librdf_uri is needed from context; all the functions that
 * use librdf_uri internally take strings as arguments for the sake of
 * making things easier for the user.  This class exists mainly to make
 * error handling and garbage collection of librdf_uri resources more
 * convenient internally for the LibRDF classes.
 *
 * @package     LibRDF
 * @author      David Shea <david@gophernet.org>
 * @copyright   2006 David Shea
 * @license     LGPL/GPL/APACHE
 * @version     Release: 1.0.0
 * @link        http://www.gophernet.org/projects/redland-php/
 */
class LibRDF_URI
{
    /**
     * The underlying librdf_uri resource.
     *
     * @var     resource
     * @access  private
     */
    private $uri;

    /**
     * Create a new URI object from a string.
     *
     * @param   string          $uri_string The string to use for the URI
     * @return  void
     * @throws  LibRDF_Error    If unable to create a new URI
     * @access  public
     */
    public function __construct($uri_string)
    {
        $this->uri = librdf_new_uri(librdf_php_get_world(), $uri_string);

        if (!$this->uri) {
            throw new LibRDF_Error("Unable to create new URI from string");
        }
    }

    /**
     * Free the URI's resources.
     *
     * @return  void
     * @access  public
     */
    public function __destruct()
    {
        if ($this->uri) {
            librdf_free_uri($this->uri);
        }
    }

    /**
     * Return the string representation of the URI.
     *
     * @return  string      The URI string
     * @access  public
     */
    public function __toString()
    {
        return librdf_uri_to_string($this->uri);
    }

    /**
     * Create a new URI object from an existing URI.
     *
     * @return  void
     * @throws  LibRDF_Error        If unable to copy the URI
     * @access  public
     */
    public function __clone()
    {
        $this->uri = librdf_new_uri_from_uri($this->uri);

        if (!$this->uri) {
            throw new LibRDF_Error("Unable to create new URI from URI");
        }
    }

    /**
     * Return the underlying URI resource.
     *
     * This function is intended for other LibRDF classes and should not
     * be called.
     *
     * @return  resource    The URI resource
     * @access  public
     */
    public function getURI()
    {
        return $this->uri;
    }

    /**
     * Compare this URI against another URI for equality.
     *
     * @param   LibRDF_URI  $uri    The URI against which to compare
     * @return  boolean     Whether the two URIs are equal
     * @access  public
     */
    public function isEqual(LibRDF_URI $uri)
    {
        if (librdf_uri_equals($this->uri, $uri->getURI())) {
            return true;
        } else {
            return false;
        }
    }
}

?>
