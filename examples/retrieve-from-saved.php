<?php

// Adapted from http://blog.literarymachine.net/?p=5

require_once('LibRDF/LibRDF.php');
 
// Create a new PostGreSQL storage. The second parameter is NOT the
// name of the PostGreSQL database to use, but the name of the
// triplestore. This makes it possible to create several
// triplestores within one database. The third parameter is
// a string containing the options for the actual PostGreSQL database.
// They should speak for themselves, except for "new='no'". It will
// reuse an existing saved model (see fetch-and-save.php for the script
// which initializes the DB and populates the model)

$store = new LibRDF_Storage("postgresql", "richard.cyganiak.de",
        "new='no',
        host='localhost',
        database='tests',
        user='postgres',
        password='whatever'");

$model = new LibRDF_Model($store);
 
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
