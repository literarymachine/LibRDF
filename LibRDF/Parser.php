<?php
/* $Id: Parser.php 171 2006-06-15 23:24:18Z das-svn $ */
/**
 * LibRDF_Parser, a wrapper around librdf_parser.
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
require_once(dirname(__FILE__) . '/StreamIterator.php');
require_once(dirname(__FILE__) . '/URI.php');
require_once(dirname(__FILE__) . '/LibRDF.php');

/**
 * A wrapper around the librdf_parser datatype.
 *
 * Parsers can be used to particular type of serialized RDF into a
 * {@link LibRDF_Model} or to simply convert the serialization into an
 * iteration of {@link LibRDF_Statement} objects.
 *
 * @package     LibRDF
 * @author      David Shea <david@gophernet.org>
 * @copyright   2006 David Shea
 * @license     LGPL/GPL/APACHE
 * @version     Release: 1.0.0
 * @link        http://www.gophernet.org/projects/redland-php/
 */
class LibRDF_Parser
{
    /**
     * The underlying librdf_uri resource.
     *
     * @var     resource
     * @access  private
     */
    private $parser;

    /**
     * Create a new parser.
     *
     * Name is the type of parser.  Common parsers are "rdfxml", "ntriples" and
     * "turtle".  If all arguments are NULL, any available parser for
     * application/rdf+xml will be used.
     *
     * @param   string      $name       The name of the parser to use
     * @param   string      $mime_type  The MIME type of the values to parse
     * @param   string      $type_uri   The URI of the RDF syntax to parse
     * @return  void
     * @throws  LibRDF_Error            If unable to create a new parser
     * @access  public
     */
    public function __construct($name=NULL, $mime_type=NULL, 
        $type_uri=NULL)
    {
        if ($type_uri) {
            $type_uri = new LibRDF_URI($type_uri);
        }

        $this->parser = librdf_new_parser(librdf_php_get_world(),
            $name, $mime_type, ($type_uri ? $type_uri->getURI() : $type_uri));

        if (!$this->parser) {
            throw new LibRDF_Error("Unable to create new parser");
        }
    }

    /**
     * Free the parser's resources.
     *
     * @return  void
     * @access  public
     */
    public function __destruct()
    {
        if ($this->parser) {
            librdf_free_parser($this->parser);
        }
    }

    /**
     * Return the underlying parser resource.
     *
     * This function is intended for other LibRDF classes and shoult not
     * be called.
     *
     * @return  resource    The wrapper librdf_parser resource
     * @access  public
     */
    public function getParser()
    {
        return $this->parser;
    }

    /**
     * Parse a string and return an iterable object over the statements.
     *
     * The object returned can be used in PHP foreach statements.  It is not
     * rewindable.
     *
     * @param   string      $string     The data to parse
     * @param   string      $base_uri   The value to use for xml:base abbreviations
     * @return  LibRDF_StreamIterator   An iterator over the LibRDF_Statements parsed from the string
     * @throws  LibRDF_Error            If unable to parse the string
     * @access  public
     */
    public function parseString($string, $base_uri=NULL)
    {
        if ($base_uri) {
            $base_uri = new LibRDF_URI($base_uri);
        } else {
            $base_uri = new LibRDF_URI(RDF_BASE_URI);
        } 
        $stream = librdf_parser_parse_string_as_stream($this->parser,
            $string, $base_uri->getURI());

        if (!$stream) {
            throw new LibRDF_Error("Unable to parse string");
        }
        return new LibRDF_StreamIterator($stream, $this);
    } 

    /**
     * Parse a URI and return an iterable object over the statements.
     *
     * The object returned can be used in PHP foreach statements.  It is not
     * rewindable.
     *
     * @param   string      $uri        The URI to parse
     * @param   string      $base_uri   The value to use for the base URI if different from $uri
     * @return  LibRDF_StreamIterator   An iterator over the LibRDF_Statements parsed from $uri
     * @throws  LibRDF_Error            If unable to parse the URI
     * @access  public
     */
    public function parseURI($uri, $base_uri=NULL)
    {
        $uri = new LibRDF_URI($uri);
        if ($base_uri) {
            $base_uri = new LibRDF_URI($base_uri);
        }

        $stream = librdf_parser_parse_as_stream($this->parser, 
            $uri->getURI(), ($base_uri ? $base_uri->getURI() : $base_uri));

        if (!$stream) {
            throw new LibRDF_Error("Unable to parse URI");
        }
        return new LibRDF_StreamIterator($stream, $this);
    }
}
?>
