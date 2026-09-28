<?php
declare(strict_types=1);
require_once "config.php";
$payload=json_decode(file_get_contents("php://input"),true)?:$_POST;
$invoice=trim((string)($payload["invoice"]??$payload["order_id"]??""));
$token=trim((string)($payload["token"]??$_SERVER["HTTP_X_TOKOFLOW_TOKEN"]??""));
if($invoice===""||$token===""){http_response_code(400);echo "missing";exit;}
$st=$conn->prepare("SELECT user_id FROM gateway_settings WHERE webhook_token=? LIMIT 1");
$st->bind_param("s",$token);$st->execute();$gw=$st->get_result()->fetch_assoc();$st->close();
if(!$gw){http_response_code(403);echo "invalid token";exit;}
$status=strtolower((string)($payload["status"]??""));
$success=in_array($status,["settlement","capture","paid","success","sukses"],true);
if($success){
 $ref=trim((string)($payload["reference"]??$payload["transaction_id"]??"Webhook"));
 $q=$conn->prepare("UPDATE sales SET payment_ref=? WHERE invoice=? AND user_id=?");
 $q->bind_param("ssi",$ref,$invoice,$gw["user_id"]);$q->execute();
}
header("Content-Type: application/json");
echo json_encode(["ok"=>true,"invoice"=>$invoice,"paid"=>$success]);
?>