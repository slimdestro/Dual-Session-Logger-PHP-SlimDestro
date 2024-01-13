# Dual Session Logger - PHP
#### This class logs session data($_SESSION["key"] = $val) into both database and File ###

## Setup 

```sh
require_once('./DualSessionLogger.php'); 
$db = new PDO('your_db_details_here');
$DualSessionLogger = new DualSessionLogger($db);
```
## Now bind this class with to session handler of native PHP

```sh
session_set_save_handler($DualSessionLogger, true);
```

## Now the data you save in session using $_SESSION[] will save in both Database and File

```sh
session_start();
$_SESSION["db_file_session_key"] = "db+file_value";
```

## To verify if this class is really working, you can try like this: 
```sh
if (isset($_SESSION["db_file_session_key"])){
    echo "<b>Value=> </b>" . $_SESSION["db_file_session_key"];
} else {
    echo "Didnt work";
}
```

## Author

[Checkout my Website](https://modcode.dev)
