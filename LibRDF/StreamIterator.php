<?php
/**
 * Wrap a librdf_stream as a PHP iterator using SPL.
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
require_once(dirname(__FILE__) . '/Statement.php');

/**
 * Wrap a librdf_stream resource as an iterable object.
 *
 * This class should not be created directly, nor are its methods of interest
 * to the casual user.  Its only intent is to provide a return type for
 * LibRDF_Model::find_statements, as well as the underlying iterator for both
 * LibRDF_Model and LibRDF_GraphQueryResults, that can then be used in a PHP
 * foreach statement without any direct function calls.
 *
 * Objects of this type may only be used for iteration once.  Once iteration
 * has begun, a call to rewind will render the object invalid.
 *
 * @package     LibRDF
 * @author      David Shea <david@gophernet.org>
 * @copyright   2006 David Shea
 * @license     LGPL/GPL/APACHE
 * @version     Release: 1.0.0
 * @link        http://www.gophernet.org/projects/redland-php/
 */
class LibRDF_StreamIterator implements Iterator
{
    /**
     * A cache of whether the iterator is still valid.
     *
     * @var     boolean
     * @access  private
     */
    private $isvalid;

    /**
     * The underlying librdf_stream resource.
     *
     * @var     resource
     * @access  private
     */
    private $stream;

    /**
     * An integer used to provide keys over the iteration.
     *
     * There are no keys created by the librdf_stream data, so iteration
     * keys are created as an integer with an initial value of 0 increasing
     * by one for each call of {@link next}.
     *
     * @var     integer
     * @access  private
     */
    private $key;

    /**
     * A reference to the stream's source object to prevent it from being
     * garbage collected before the stream.
     *
     * @var     mixed
     * @access  private
     */
    private $source;

    /**
     * A flag for whether the stream is rewindable.
     *
     * A stream may be rewound before {@link next} is called, after which 
     * rewinding invalidates the stream.
     *
     * @var     boolean
     * @access  private
     */
    private $rewindable;

    /**
     * Create a new iterable object from a librdf_stream resource.
     *
     * User functions should not create librdf_stream resources directly,
     * so this constructor is intended only to provide an interface into the
     * streams returned by librdf functions and called by LibRDF classes.
     *
     * @param   resource    $stream     The librdf_stream object to wrap
     * @param   mixed       $source     The object that created the stream
     * @return  void
     * @access  public
     */
    public function __construct($stream, $source=NULL)
    {
        $this->stream = $stream;
        $this->isvalid = true;
        $this->key = 0;
        $this->source = $source;
        $this->rewindable = true;
    }

    /**
     * Free the stream's resources.
     *
     * @return  void
     * @access  public
     */
    public function __destruct()
    {
        if ($this->stream) {
            librdf_free_stream($this->stream);
        }
    }

    /**
     * Clone a LibRDF_StreamIterator object.
     *
     * Cloning a stream is unsupported, so using the clone operator on a
     * LibRDF_StreamIterator object will produce an empty iterator.
     *
     * @return  void
     * @access  public
     */
    public function __clone()
    {
        $this->stream = NULL;
        $this->isvalid = false;
    }

    /**
     * Rewind the stream.
     *
     * Rewinding is not supported, so this call invalidates the stream unless
     * the stream is still at the starting position.
     *
     * @return  void
     * @access  public
     */
    public function rewind()
    {
        if (!($this->rewindable)) {
            $this->isvalid = false;
        }
    }

    /**
     * Return the current statement or NULL if the stream is no longer valid.
     *
     * @return  LibRDF_Statement    The current statement on the iterator
     * @access  public
     */
    public function current()
    {
        if (($this->isvalid) and (!librdf_stream_end($this->stream))) {
            // the pointer returned is overwritten when the stream is
            // advanced or closed, so make a copy of the statement
            $ret = librdf_stream_get_object($this->stream);
            if ($ret) {
                return new LibRDF_Statement(librdf_new_statement_from_statement($ret));
            } else {
                throw new LibRDF_Error("Unable to get current statement");
            }
        } else {
            return NULL;
        }
    }

    /**
     * Return the key of the current element on the stream.
     *
     * @return  integer     The current key
     * @access  public
     */
    public function key()
    {
        return $this->key;
    }

    /**
     * Advance the stream's position.
     *
     * @return  void
     * @access  public
     */
    public function next()
    {
        if ($this->isvalid) {
            $this->rewindable = false;
            $ret = librdf_stream_next($this->stream);
            if ($ret) {
                $this->isvalid = false;
            } else {
                $this->key++;
            }
        }
    }

    /**
     * Return whether the stream is still valid.
     *
     * @return  boolean     Whether the stream is still valid
     * @access  public
     */
    public function valid()
    {
        if (($this->isvalid) and (!librdf_stream_end($this->stream))) {
            return true;
        } else {
            return false;
        }
    }
}

?>
