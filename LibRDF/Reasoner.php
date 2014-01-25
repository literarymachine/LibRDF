<?php
/**
 * LibRDF_Reasoner, TODO
 *
 * PHP version 5
 *
 * Copyright (C) 2014, Felix Ostrowski <felix.ostrowski@gmail.com>
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
 * @author      Felix Ostrowski <felix.ostrowski@googlemail.com>
 * @copyright   2014 Felix Ostrowski
 * @license     LGPL/GPL/APACHE
 * @version     Release: 1.0.0
 */

/**
 */
require_once(dirname(__FILE__) . '/Model.php');
require_once(dirname(__FILE__) . '/Query.php');

/**
 * TODO: short description.
 * 
 * TODO: long description.
 * 
 */
class LibRDF_Reasoner {

  protected $_inferenceQueries = array();

  /**
   * TODO: short description.
   * 
   * @param  string  $type 
   */
  public function __construct($type) {
    $this->_inferenceQueries = $this->_rdfsQueries();
  }

  /**
   * TODO: short description.
   * 
   * @param  LibRDF_Model  $tbox 
   * @param  LibRDF_Model  $abox 
   * @return TODO
   */
  public function inferStatements(LibRDF_Model $model) {
    $result = new LibRDF_Model(new LibRDF_Storage());
    $this->_inferStatements($model, $result);
    return $result;
  }

  protected function _inferStatements(LibRDF_Model $model, LibRDF_Model $result) {
    $add_count = 0;
    foreach ($this->_inferenceQueries as $query) {
      $inferred = $query->execute($model);
      foreach ($inferred as $triple) {
        if (!$model->hasStatement($triple)) {
          $model->addStatement($triple);
          $result->addStatement($triple);
          $add_count++;
        }
      }
    }
    if ($add_count > 0) $this->_inferStatements($model, $result);
  }

  /**
   * TODO: short description.
   * 
   * @return TODO
   */
  protected function _rdfsQueries() {

    $RDFS = new LibRDF_NS('http://www.w3.org/2000/01/rdf-schema#');

    $rdfsSubpropertyOfQuery = new LibRDF_Query("
      CONSTRUCT { ?subject ?property ?object } WHERE {
        ?subProperty {$RDFS->subPropertyOf} ?property .
        ?subject ?subProperty ?object.
      } ", null, 'sparql');

    $rdfsDomainQuery = new LibRDF_Query("
      CONSTRUCT { ?subject a ?class } WHERE {
        ?property {$RDFS->domain} ?class .
        ?subject ?property ?object.
      } ", null, 'sparql');

    $rdfsRangeQuery = new LibRDF_Query("
      CONSTRUCT { ?object a ?class } WHERE {
        ?property {$RDFS->range} ?class .
        ?subject ?property ?object.
      } ", null, 'sparql');

    $rdfsSubclassOfQuery = new LibRDF_Query("
      CONSTRUCT { ?subject a ?class } WHERE {
        ?subClass {$RDFS->subClassOf} ?class .
        ?subject a ?subClass .
      } ", null, 'sparql');

    return array(
      $rdfsSubpropertyOfQuery,
      $rdfsDomainQuery,
      $rdfsRangeQuery,
      $rdfsSubclassOfQuery,
    );

  }

}
