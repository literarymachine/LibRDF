<?php
/* $Id: Node.php 171 2006-06-15 23:24:18Z das-svn $ */
/**
 * LibRDF_Node, a node or arc in an RDF graph.
 *
 * A LibRDF_Node is the type of the {@link LibRDF_Statement} triples.
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
 * A wrapper around the librdf_node datatype.
 *
 * The values of nodes come from three potential, disjoint sets: URIs,
 * literal strings and blank identifiers.  These types are represented by
 * {@link LibRDF_URINode}, {@link LibRDF_LiteralNode} and 
 * {@link LibRDF_BlankNode}, respectively.
 *
 * @package     LibRDF
 * @author      David Shea <david@gophernet.org>
 * @copyright   2006 David Shea
 * @license     LGPL/GPL/APACHE
 * @version     Release: 1.0.0
 * @link        http://www.gophernet.org/projects/redland-php/
 */
abstract class LibRDF_Node
{
    /**
     * The underlying librdf_node resource.
     *
     * This value must be set by the constructors for the concrete node types.
     *
     * @var     resource
     * @access  protected
     */
    protected $node;

    /**
     * Destroy the Node object.
     *
     * @return  void
     * @access  public
     */
    public function __destruct()
    {
        if ($this->node) {
            librdf_free_node($this->node);
        }
    }

    /**
     * Create a new node object from an existing node.
     *
     * @return  void
     * @throws  LibRDF_Error    If unable to copy the node
     * @access  public
     */
    public function __clone()
    {
        $this->node = librdf_new_node_from_node($this->node);

        if (!$this->node) {
            throw new LibRDF_Error("Unable to create new Node from Node");
        }
    }

    /**
     * Return a string representation of the node.
     *
     * @return  string  A string representation of the node
     * @access  public
     */
    public function __toString()
    {
        $rs = librdf_node_to_string($this->node);
        if ("1.0.11" > librdf_version_string_get()) {
            if (librdf_node_is_resource($this->node)) {
                $rs = '<' . substr($rs, 1, -1) . '>';
            } elseif (librdf_node_is_literal($this->node)) {
                $rs = '"' . $rs . '"';
            } elseif (librdf_node_is_blank($this->node)) {
                $rs = '_:' . substr($rs, 1, -1);
            }
        }
        return $rs;
    }

    /**
     * Compare this node with another node for equality.
     *
     * Nodes of different types are not equal; thus, a URI of
     * http://example.org/ and a literal string of http://example.org are not
     * equal, even though they contain the same string.  Similarly, literal
     * nodes must match in both type and language to be considered equal.
     *
     * @param   LibRDF_Node $node   The node against which to compare
     * @return  boolean             Whether the nodes are equal
     * @access  public
     */
    public function isEqual(LibRDF_Node $node)
    {
        return (boolean) librdf_node_equals($this->node, $node->node);
    }

    /**
     * Return the underlying librdf_node resource.
     *
     * This function is intended for other LibRDF classes and should not
     * be called.
     *
     * @return  resource    The wrapped node
     * @access  public
     */
    public function getNode()
    {
        return $this->node;
    }

    /**
     * Wrap a librdf_node resource in the correct Node object.
     *
     * This function is intended for use by LibRDF classes, allowing them
     * to easily convert a librdf_node resource into the correct type of
     * LibRDF_Node object.
     *
     * @param   resource    $node   The librdf_node to convert
     * @return  LibRDF_Node         A concrete object implementing Node
     * @throws  LibRDF_Error        If unable to create a new node
     * @access  public
     * @static
     */
    public static function makeNode($node)
    {
        if (!is_resource($node)) {
            throw new LibRDF_Error("Argument must be a librdf_node resource");
        }

        if (librdf_node_is_resource($node)) {
            return new LibRDF_URINode($node);
        } elseif (librdf_node_is_literal($node)) {
            return new LibRDF_LiteralNode($node);
        } elseif (librdf_node_is_blank($node)) {
            return new LibRDF_BlankNode($node);
        } else {
            throw new LibRDF_Error("Unknown query results type");
        }
    }
}

/**
 * A specialized version of {@link LibRDF_Node} representing a URI.
 *
 * @package     LibRDF
 * @author      David Shea <david@gophernet.org>
 * @copyright   2006 David Shea
 * @license     LGPL/GPL/APACHE
 * @version     Release: 1.0.0
 * @link        http://www.gophernet.org/projects/redland-php/
 */
class LibRDF_URINode extends LibRDF_Node
{
    /**
     * Create a new URINode from a URI object.
     *
     * @param   mixed       $uri    The URI string or librdf_node value to use
     * @return  void
     * @throws  LibRDF_Error        If unable to create a new URI
     * @access  public
     */
    public function __construct($uri)
    {
        if (is_string($uri)) {
            $uri = new LibRDF_URI($uri);
            $this->node = librdf_new_node_from_uri(librdf_php_get_world(),
                $uri->getURI());
        } elseif ((is_resource($uri)) and librdf_node_is_resource($uri)) {
            $this->node = $uri;
        } else {
            throw new LibRDF_Error("Argument is not a string or 
                librdf_node resource");
        }

        if (!$this->node) {
            throw new LibRDF_Error("Unable to create new URI node");
        }
    }

    /**
     * Get the namespace of a URI
     *
     * @return LibRDF_NS The namespace
     */
    public function getNamespace() {
      $split = strrpos($this, '#');
      if (!$split) {
        $split = strrpos($this, '/');
      }
      return new LibRDF_NS(substr($this, 1, $split));
    }

    /**
     * Get the local part of a URI
     *
     * @return string The local part
     */
    public function getLocalPart() {
      $split = strrpos($this, '#');
      if (!$split) {
        $split = strrpos($this, '/');
      }
      return substr($this, $split + 1, -1);
    }

    /**
     * Return the plain string of this URI
     *
     * @return string  The URI's value
     */
    public function getValue()
    {
        return substr($this, 1, -1);
    }

}

class LibRDF_NS extends LibRDF_URINode
{
    /**
     * Return a new URINode based on this one
     *
     * @return  LibRDF_URINode
     * @access  public
     */
    public function __get($localPart) {
        $str = $this->__toString();
        return new LibRDF_URINode(
            substr($str, 1, -1) . $localPart
        );
    }
}

/**
 * A representation of a blank node.
 *
 * @package     LibRDF
 * @author      David Shea <david@gophernet.org>
 * @copyright   2006 David Shea
 * @license     LGPL/GPL/APACHE
 * @version     Release: 1.0.0
 * @link        http://www.gophernet.org/projects/redland-php/
 */
class LibRDF_BlankNode extends LibRDF_Node
{
    /**
     * Create a new blank node with an optional identifier.
     *
     * @param   mixed   $nodeId     The nodeId value or librdf_node resource
     * @return  void
     * @throws  LibRDF_Error        If unable to create a new node
     * @access  public
     */
    public function __construct($nodeId=NULL)
    {
        if ($nodeId !== NULL) {
            if (is_resource($nodeId)) {
                if (librdf_node_is_blank($nodeId)) {
                    $this->node = $nodeId;
                } else {
                    throw new LibRDF_Error("Resource argument not a valid" .
                        " librdf_node blank node");
                }
            } else {
                $this->node = librdf_new_node_from_blank_identifier(librdf_php_get_world(),
                    $nodeId);
            }
        } else {
            $this->node = librdf_new_node(librdf_php_get_world());
        }

        if (!$this->node) {
            throw new LibRDF_Error("Unable to create new blank node");
        }
    }

    /**
     * Return the plain string of this bnode
     *
     * @return string  The bnode's value
     */
    public function getValue()
    {
        return "$this";
    }

}

/**
 * A representation of a literal node.
 *
 * Literal nodes can have a type and a language, but not both.
 *
 * @package     LibRDF
 * @author      David Shea <david@gophernet.org>
 * @copyright   2006 David Shea
 * @license     LGPL/GPL/APACHE
 * @version     Release: 1.0.0
 * @link        http://www.gophernet.org/projects/redland-php/
 */
class LibRDF_LiteralNode extends LibRDF_Node
{
    
    /**
     * Create a new Literal node.
     *
     * Both the $language and $datatype parameters are optional.
     *
     * The value of the literal node can either be a string or an XML literal
     * in the form of a DOMNodeList object.  If using XML, a datatype of
     * http://www.w3.org/1999/02/22-rdf-syntax-ns#XMLLiteral is implied, so
     * the datatype parameter cannot be used with XML.  A literal cannot have
     * both a language and a datatype.
     *
     * @param   mixed       $value      The literal value, either a string, a DOMNodeList or a librdf_node resource
     * @param   string      $datatype   An optional datatype URI for the literal value
     * @param   string      $language   An option language for the literal value
     * @return  void
     * @throws  LibRDF_Error            If unabel to create a new node
     * @access  public
     */
    public function __construct()
    {
        $valuestr = "";
        $is_xml = 0;

        // possible parameter lists are either LibRDF_Node $resource or
        // string $value, $datatype=NULL, string $language=NULL
        $num_args = func_num_args();
        if (($num_args < 1) or ($num_args > 3)) {
            throw new LibRDF_Error("Invalid number of arguments");
        }
        $value = func_get_arg(0);
        if ($num_args >= 2) {
            $datatype = func_get_arg(1);
            if ($datatype) {
                $datatype = new LibRDF_URI($datatype);
            }
        } else {
            $datatype = NULL;
        }
        if ($num_args >= 3) {
            $language = func_get_arg(2);
        } else {
            $language = NULL;
        }

        if (($num_args == 1) and (is_resource($value))) {
            if (!librdf_node_is_literal($value)) {
                throw new LibRDF_Error("Argument 1 not a valid librdf_node " .
                    " literal node");
            } else {
                $this->node = $value;
            }
        } else {

            // value is XML, convert to a string and set the datatype
            if ($value instanceof DOMNodeList) {
                // XML values imply a datatype of
                // http://www.w3.org/1999/02/22-rdf-syntax-ns#XMLLiteral, so
                // specifying a different datatype is an error
                if (($datatype !== NULL) and
                    ($datatype->__toString() !== "http://www.w3.org/1999/02/22-rdf-syntax-ns#XMLLiteral")) {
                    throw new LibRDF_Error("Cannot override datatype for XML literal");
                } else {
                    $datatype = NULL;
                }

                $valuestr = "";
                foreach ($value as $item) {
                    $valuestr .= $item->ownerDocument->saveXML($item);
                }
                $is_xml = 1;
            } else {
                $valuestr = (string) $value;
                $is_xml = 0;
            }

            if ($datatype !== NULL) {
                $datatype_uri = $datatype->getURI();
            } else {
                $datatype_uri = NULL;
            }

            if (($is_xml) or (($datatype === NULL) and ($language === NULL))) {
                $this->node = librdf_new_node_from_literal(librdf_php_get_world(),
                    $valuestr, $language, $is_xml);
            } else {
                $this->node = librdf_new_node_from_typed_literal(librdf_php_get_world(),
                    $valuestr, $language, $datatype_uri);
            }
        }

        if (!$this->node) {
            throw new LibRDF_Error("Unable to create new literal node");
        }
    }

    /**
     * Return the datattype URI or NULL if this literal has no datatype.
     *
     * @return  string      The datatype URI
     * @access  public
     */
    public function getDatatype()
    {
        $uri = librdf_node_get_literal_value_datatype_uri($this->node);
        if ($uri !== NULL) {
            return librdf_uri_to_string($uri);
        } else {
            return NULL;
        }
    }

    /**
     * Return the language of this literal or NULL if the literal has no
     * language.
     *
     * @return  string  The literal's language
     * @access  public
     */
    public function getLanguage()
    {
        return librdf_node_get_literal_value_language($this->node);
    }

    /**
     * Return the plain string of this literal
     *
     * @return string  The literal's value
     */
    public function getValue()
    {
        if ($postfix = $this->getLanguage()) {
            $output = substr($this, 1, 0 - strlen($postfix) - 2);
        } else if ($postfix = $this->getDatatype()) {
            $output = substr($this, 1, 0 - strlen($postfix) - 5);
        } else {
            $output = substr($this, 1, -1);
        }
        return $output;
    }

}

?>
