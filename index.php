<?php
error_reporting(E_ALL | E_ERROR | E_WARNING | E_PARSE | E_NOTICE);

spl_autoload_register(function ($class_name) {
    require_once $class_name . '.php';
});

require "../library/php/dbconnect.php";
require "../library/php/library.php";

function logAccess($number, $fromBase, $toBase) {
    try {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME;
        $pdo = new PDO($dsn, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);
        $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $pdo->beginTransaction();
        $sql = 'INSERT INTO baseconverter(number, fromBase, toBase, ipaddr) ' .
            'VALUES(:number, :fromBase, :toBase, :ipaddr)';
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['number' => $number, 'fromBase' => $fromBase, 'toBase' => $toBase,
            'ipaddr' => getUserIP()]);
        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
        echo 'Connection failed: ' . $e->getMessage();
    }
}


function commonBaseOptions($selectBase) {
    $retval = "";
    foreach (COMMON_BASES as $base) {
        $retval .= "<option value=\"$base\"";
        if ($base === $selectBase) {
            $retval .= " selected";
        }
        $retval .= ">$base</option>\n";
     }
    return $retval;
}

function getDigitValue($digit) {
  if ($digit >= "0" && $digit <= "9") {
     return ord($digit) - ord("0");
     }
  $digit = strtoupper($digit);
  if (ctype_alpha($digit)) {
     return ord($digit) - ord("A") + 10;
     }
  return -1;
}

function convertToDecimal($str, $base, &$error) {
    $re = '/([0-9A-Z])|\((\d+)\)|([^0-9A-Z(]+)|(\()/i';
    preg_match_all($re, $str, $matches, PREG_SET_ORDER, 0);
    $retval = 0;
    foreach ($matches as $digit) {
       if (count($digit) === 2) {
         $digitVal = getDigitValue($digit[1]);
         }
       else if (count($digit) === 3) {
         $digitVal = (int)$digit[2];
         }
	   else {
         $error = "Illegal Digit Character Specified";
         return -1;
         }
       if ($digitVal >= $base) {
         $error = "Digit Character Not Valid for Base";
         return -1;
         }
       $retval = gmp_add(gmp_mul($retval, $base), $digitVal);
    }
    return $retval;
}

function convertDecimalToBase($decimalValue, $toBase) {
    $retval = "";
    while ($decimalValue > 0) {
		$qr = gmp_div_qr($decimalValue, $toBase);
		//echo "$decimalValue {$qr[0]}  {$qr[1]}<br>";  
		if ($qr[1] < 10)
			$retval = $qr[1] . $retval;
		else if ($qr[1] < 16)
			$retval = chr((int)(ord("A") + $qr[1] - 10)) . $retval;
		else
			$retval = "(" . $qr[1] . ")" . $retval;
		$decimalValue = $qr[0];
	}
    return $retval;
}

$title = "Number Base Converter!";
$current = "baseconverter";
$number = "";
$fromBase = 10;
$toBase = 16;
$solution = "";
$errMsg = "";
$success = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST'):
    $number = trim($_POST["number"]);
    $tmpnumber = test_input($number);
    $fromBase = trim($_POST["fromBase"]);
	$tmpfromBase = test_input($fromBase);
    $toBase = $_POST["toBase"];
	$tmptoBase = test_input($toBase);
	if ($number !== $tmpnumber || $fromBase != $tmpfromBase || $toBase != $tmptoBase) {
        $errMsg = "Hacking Attempt Detected";
		}
	else if (!is_numeric($fromBase) || (int)$fromBase <= 0 ||
		 !is_numeric($toBase) || (int)$toBase <= 0) {
        $errMsg = "Please enter integers greater than zero for all fields";
	    }
	else {
		logAccess($number, $fromBase, $toBase); 
		$decimalValue = convertToDecimal($number, $fromBase, $errMsg);
		if ($decimalValue < 0)
			$solution = $errMsg;
		else {
			$solution = convertDecimalToBase($decimalValue, $toBase);
			$success = true;
		}
	}
endif;
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title><?php echo $title; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="author" content="Antonio C. Silvestri">
    <meta name="description" content="Number Base Converter! Converts a number from one base to another. This app accepts arbitrarily large numbers!">
    <link rel="stylesheet" href="//stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
    <link rel="stylesheet" href="//use.fontawesome.com/releases/v5.7.2/css/all.css" integrity="sha384-fnmOCqbTlWIlj8LyTjo7mOUStjsKC4pOpQbqyi7RrhN7udi9RwhKkMHpvLbHG9Sr" crossorigin="anonymous">	
    <link rel="stylesheet" href="css/styles.css">

    <meta name="twitter:card" content="summary">
    <meta name="twitter:site" content="@bytecodeman">
    <meta name="twitter:title" content="<?php echo $title; ?>">
    <meta name="twitter:description" content="Number Base Converter! Converts a number from one base to another. This app accepts arbitrarily large numbers!">
    <meta name="twitter:image" content="https://cs.stcc.edu/specialapps/baseconverter/img/logo.png">

    <meta property="og:url" content="https://cs.stcc.edu/specialapps/baseconverter/" />
    <meta property="og:type" content="article" />
    <meta property="og:title" content=<?php echo $title; ?>" />
    <meta property="og:description" content="Number Base Converter! Converts a number from one base to another. This app accepts arbitrarily large numbers!" />
    <meta property="og:image" content="https://cs.stcc.edu/specialapps/baseconverter/img/logo.png" />

	<script async src="//pagead2.googlesyndication.com/pagead/js/adsbygoogle.js"></script>
	<script>
		 (adsbygoogle = window.adsbygoogle || []).push({
			  google_ad_client: "ca-pub-9626577709396562",
			  enable_page_level_ads: true
		 });
	</script>
</head>
<body>
	<?php include "../library/php/navbar.php"; ?>
    <div class="container">
        <div class="jumbotron">
			<div class="row">
				<div class="col-lg-8">
					<h1><?php echo $title; ?></h1>
					<div class="clearfix">
						<img src="img/logo.png" alt="" class="rounded mb-2 mr-4 float-left d-block img-fluid">
						<p>Convert a number from one base to another.</p>
                    </div>
				<p class="d-print-none">This app accepts arbitrarily large numbers and uses the standard digits
                                     [0-9A-Z].  For digit values > 15, surround the base-10 digit value with parenthesis.</p>

 				<p class="d-print-none"><a href="#" data-toggle="modal" data-target="#myModal">About <?php echo $title; ?></a></p>
				</div>
				<div class="col-lg-4 d-print-none">
					<ins class="adsbygoogle"
						 style="display:block"
						 data-ad-client="ca-pub-9626577709396562"
						 data-ad-slot="7064413444"
						 data-ad-format="auto"></ins>
					<script>
					(adsbygoogle = window.adsbygoogle || []).push({});
					</script>
				</div>
			</div>
		</div>
		<div class="row">
            <div class="col">
               <form id="baseConverterForm" method="post" action="<?php echo htmlspecialchars(extractPath($_SERVER["PHP_SELF"])); ?>">
                    <?php if (!empty($errMsg)): ?>
                        <div id="errMsg" class="font-weight-bold h4 text-danger">
                            <?php echo $errMsg; ?>
                        </div>
                    <?php endif; ?>
					<?php if ($success): ?>
                        <div id="succMsg" class="form-group font-weight-bold h3 text-success">
                            <fieldset class="baseConversion">
                                <legend class="text-success">Results 
                                <div id="copyToClipboard">
                                    <a tabindex="0" id="copytoclip" data-trigger="focus" data-clipboard-target="#conversionResults" data-container="body" data-toggle="popover" data-placement="bottom" data-content="Copied!">
                                        <img src="img/clippy.png" alt="Copy to Clipboard" title="Copy to Clipboard">
                                    </a>
                                </div>
                                </legend>
                                <div id="conversionResults"><?php echo $solution; ?></div>
                            </fieldset>
                        </div>                           
                    <?php endif;?>
                    <div class="form-row">
                    	<div class="form-group col-md-4">
                            <label for="number">Number to Convert</label>
                            <input type="text" id="number" name="number" class="form-control form-control-lg" placeholder="Enter Number to Convert" value="<?php echo $number; ?>" required>
                    	</div>
                        <div class="form-group col-md-4">
                            <label for="fromBase">Number Base</label>
                            <input type="number" min="2" id="fromBase" name="fromBase" class="form-control" value="<?php echo $fromBase; ?>" placeholder="Enter Number's Base" required>
                        </div>
                        <div class="form-group col-md-4">
                            <label for="toBase">Convert To Base</label>
                            <input type="number" min="2" id="toBase" name="toBase" class="form-control" value="<?php echo $toBase; ?>" placeholder="Convert To Base" required>
                        </div>
                    </div>
                    <button type="submit" id="submit" name="submit" class="btn btn-primary btn-lg d-print-none">Submit</button>
                </form>
            </div>
        </div>
    </div>

    <?php
		require "../library/php/about.php";
	?>

    <script src="//code.jquery.com/jquery-3.3.1.min.js" integrity="sha256-FgpCb/KJQlLNfOu91ta32o/NMZxltwRo8QtmkMRdAu8=" crossorigin="anonymous"></script>
    <script src="//cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js" integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1" crossorigin="anonymous"></script>
    <script src="//stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js" integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM" crossorigin="anonymous"></script>
    <script src="//s7.addthis.com/js/300/addthis_widget.js#pubid=ra-5a576c39d176f4a6"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/clipboard.js/1.7.1/clipboard.min.js"></script>
    <script>
    $(function() {
        $('[data-toggle="popover"]').popover();
	    new Clipboard("#copytoclip");
    });
    </script>
</body>
</html>

