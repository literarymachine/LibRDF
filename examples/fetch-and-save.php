<?php

// copied from http://blog.literarymachine.net/?p=5

require_once('LibRDF/LibRDF.php');
 
// Create a new PostGreSQL storage. The second parameter is NOT the
// name of the PostGreSQL database to use, but the name of the
// triplestore. This makes it possible to create several
// triplestores within one database. The third parameter is
// a string containing the options for the actual PostGreSQL database.
// They should speak for themselves, except for "new='yes'". If
// this option is given, the necessary table structure is created and
// any existing triples are dropped. You probably only want to use
// it in some kind of setup or installation procedure.

$store = new LibRDF_Storage("postgresql", "richard.cyganiak.de",
        "new='yes',
        host='localhost',
        database='tests',
        user='postgres',
        password='whatever'");

$model = new LibRDF_Model($store);
 
// Load some data into the model. The format must explicitly be
// declared for the parser, but using e.g. ARC's format detector
// should be easy to implement. Anyways, in this case we're
// dealing with an RDF/XML document:
$model->loadStatementsFromURI(
        new LibRDF_Parser('rdfxml'),
        'http://richard.cyganiak.de/foaf.rdf');
 
// Create a SPARQL query
$query = new LibRDF_Query("
PREFIX foaf:   <http://xmlns.com/foaf/0.1/>
SELECT ?name1 ?name2
WHERE
  {
    ?person1 foaf:knows ?person2 .
    ?person1 foaf:name ?name1 .
    ?person2 foaf:name ?name2 .
  }
", null, 'sparql');
 
// Execute the query. The results of a SPARQL SELECT provide
// array access by using the variables used in the query as keys:
$results = $query->execute($model);
foreach ($results as $result) {
    echo $result['name1'] . " knows " . $result['name2'] . "\n";
}
