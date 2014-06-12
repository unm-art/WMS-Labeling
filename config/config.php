<?php

if (!defined('WSKEY')) {
    // define ("WSKEY", "XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX");
    define("WSKEY", "MTK9b14WyAlgZn2f3QCeTWR7sEpeKjnV83rC8O5svm8ocrmFHkRisqeF3Fpr8lfPy641mb1EakGeVW20");
    //define ("WSKEY", "psomBeyDZAvtxS5VaQHLwTYUtXam6LE1slugnpfSsnxjYYi99YVGLdqSfdOTBLf9a8wUxs0sjfdszEgn");
}

if (!defined('BIBKEY')) {
    // define ("BIBKEY", "XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX");
    define("BIBKEY", "MTK9b14WyAlgZn2f3QCeTWR7sEpeKjnV83rC8O5svm8ocrmFHkRisqeF3Fpr8lfPy641mb1EakGeVW20");
    //define ("BIBKEY", "psomBeyDZAvtxS5VaQHLwTYUtXam6LE1slugnpfSsnxjYYi99YVGLdqSfdOTBLf9a8wUxs0sjfdszEgn");
}

if (!defined('SECRET')) {
    // define ("SECRET", "XXXXXXXXXXXXXXXXXXXXXXXX");
    define("SECRET", "aqyK7qqhB04RHvH5o8yWZw==");
    //define ("SECRET", "Gsw5eHStUPKg47S0Fb2t7w==");
}

if (!isset($inst_id)) {
    // $inst_id = '999999';
    $inst_id = "1822";
    //$inst_id = "128807";
}

if (!defined('PRINCIPALID')) {
    // define ("PRINCIPALID", "XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX");
    define("PRINCIPALID", "f41b2eba-08f1-4bf5-8a1c-d0d47d9bff90");
    //define ("PRINCIPALID", "8eaa9f92-3951-431c-975a-d7df26b8d131");
}

if (!defined('PRINCIPALIDNS')) {
    define("PRINCIPALIDNS", "urn:oclc:wms:da");
}

if (!defined('URL')) {
    // define ("URL", "https://circ.sd04.worldcat.org/LHR");
    define("URL", "https://circ.sd00.worldcat.org/LHR");
}

if (!defined('BIBURL')) {
    define("BIBURL", "http://www.worldcat.org/webservices/catalog/content");
}

if (!defined('METHOD')) {
    define("METHOD", "GET");
}

if (!defined('BODYHASH')) {
    define("BODYHASH", "");
}