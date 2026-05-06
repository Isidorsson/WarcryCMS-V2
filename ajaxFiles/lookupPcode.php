<?PHP
if (!defined('init_ajax'))
{	
	header('HTTP/1.0 404 not found');
	exit;
}

//Load the Tokens Module
$CORE->load_CoreModule('promo.codes');

header('Content-Type: application/json; charset=utf-8');

$code = ((isset($_GET['code'])) ? $_GET['code'] : false);

if (!$code)
{
	echo json_encode(array('error' => 'The promo code is missing.'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
	die;
}

//Setup new promo code
$PCode = new PromoCode($code);

//Verify promo code
if ($PCode->Verify())
{
	echo json_encode($PCode->getInfo(), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
	exit;
}

//If we're here then something is wrong
echo json_encode(array('error' => (string)$PCode->getLastError()), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);

unset($PCode);

exit;