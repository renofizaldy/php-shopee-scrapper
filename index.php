<?php

$reqUri = "https://shopee.co.id/api/v4/";
$imgUri = "https://cf.shopee.co.id/file/";

$shopId = "201071840";
$itemId = [
    "SALL-WTLB-000001" => "2924946282",
    "SALL-WTLB-000002" => "3170161664",
    "SALL-WTLB-000003" => "4011685722",
    "SALL-WTLB-000004" => "10261317376",
    "SALL-WTLB-000005" => "8261379863",
    "SALL-WTLB-000006" => "9535238425",
    "SALL-WTLB-000007" => "5283761590",
    "SALL-WTLB-000008" => "8061378641",
    "SALL-WTLB-000009" => "3611788396",
    "SALL-WTLB-000010" => "4511700129",
    "SALL-WTLB-000011" => "6211693448",
    "SALL-WTLB-000012" => "8008923980",
    "SALL-WTLB-000013" => "11561318634",
    "SALL-WTLB-000014" => "3311796478",
    "SALL-WTLB-000015" => "13942854710",
    "SALL-WTLB-000016" => "9861380326",
    "SALL-WTLB-000017" => "5011692144",
    "SALL-WTLB-000018" => "13526034044",
    "SALL-WTLB-000019" => "5378779832",
    "SALL-WTLB-000020" => "7661397539",
    "SALL-WTLB-000021" => "5711696002",
    "SALL-WTLB-000022" => "3479331703",
];

// Excel Modules
require 'vendor/autoload.php';
$wExcel = new Ellumilel\ExcelWriter();
$wExcel->setAuthor('Reno Fizaldy');
$wExcel->writeSheetHeader($shopId, [
    "SKU"         => "string",
    "CHILD"       => "string",
    "NAMA PRODUK" => "string",
    "HARGA"       => "integer",
    "DISKON"      => "integer",
    "DESKRIPSI"   => "string"
]);

// Buffer Status
if (ob_get_level() == 0) {
    ob_start();
}

foreach($itemId as $key=>$item) {

    $reqCall  = $reqUri . "item/get?itemid={$item}&shopid={$shopId}";
    $decode   = json_decode(file_get_contents($reqCall), true);
    $data     = $decode['data'];
    $video    = (isset($data['video_info_list'][0]['formats'][0])) ? $data['video_info_list'][0]['formats'][0]['url'] : $data['video_info_list'][0]['default_format']['url'];
    $pathSave = './result/'.$key;
    $varian   = $data['tier_variations'][0]['options'];

    // header('Content-Type: application/json');
    // $json2 = json_encode($decode, JSON_PRETTY_PRINT);
    // echo $json2;
    // exit();

    // Create Dir
    mkdir($pathSave, 0777, true);
    // Get Images
    foreach($data['images'] as $images) {
        file_put_contents($pathSave."/{$images}.jpg", file_get_contents($imgUri.$images));
    }
    // Get Video
    if (strlen($video) > 0) {
        file_put_contents($pathSave."/video.mp4", file_get_contents($video));
    }

    // IF there any variant
    if (count($varian) > 1) {
        for ($i=1;$i<=count($varian);$i++) {
            if ($i<10) {
                $var_img_path = $pathSave.'/00'.$i;
                $var_img = $data['tier_variations'][0]['images'][$i-1];
                mkdir($var_img_path, 0777, true);
                file_put_contents($var_img_path."/{$var_img}.jpg", file_get_contents($imgUri.$var_img));
            } else {
                $var_img_path = $pathSave.'/0'.$i;
                mkdir($var_img_path, 0777, true);
                file_put_contents($var_img_path."/{$var_img}.jpg", file_get_contents($imgUri.$var_img));
            }
        }
    }

    // Write Excel
    if (count($varian) > 1) {
        for ($i=1;$i<=count($varian);$i++) {
            if ($i<10) {
                $sku_child = "00".$i;
            } else {
                $sku_child = "0".$i;
            }
            $wExcel->writeSheetRow($shopId, [
                $key,
                $key."-".$sku_child,
                $data['name'] . ' - ' . $varian[$i-1],
                ($data['price_before_discount'] !== 0) ? substr($data['price_before_discount'], 0, -5) : substr($data['price'], 0, -5),
                substr($data['models'][$i-1]['price'], 0, -5),
                $data['description']
            ]);
        }
    } else {
        $wExcel->writeSheetRow($shopId, [
            $key,
            null,
            $data['name'],
            ($data['price_before_discount'] !== 0) ? substr($data['price_before_discount'], 0, -5) : substr($data['price'], 0, -5),
            ($data['price_before_discount'] !== 0) ? substr($data['price'], 0, -5) : substr($data['price_before_discount'], 0, -5),
            $data['description']
        ]);
    }

    echo "DONE!! {$key} - {$data['name']}\n";
    // Get Buffering Status
    ob_flush();
    flush();

}

$wExcel->writeToFile("./result/{$shopId}.xlsx");

// Set to End Buffering Process
echo "\n\nDownload Complete!";
ob_end_flush();