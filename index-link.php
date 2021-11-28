<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    <form name="form1" method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>" >
        <textarea name="urlx" id="" cols="30" rows="10">
https://shopee.co.id/-MK16-Mahkota-Tiara-Pengantin-Batu-Zircon-Kilap-Berlian-i.14320723.2043990684?sp_atk=a5f0c754-89a5-4545-9d64-85f7257d1c9d&xptdk=a5f0c754-89a5-4545-9d64-85f7257d1c9d
https://shopee.co.id/Mocha-Sepatu-Pesta-Wanita-Tali-Sepatu-Stiletto-Heels-9cm-AVEDA-FA6171-i.173501527.8715803630?sp_atk=85c32766-5a52-4915-acf2-78bae6494551&xptdk=85c32766-5a52-4915-acf2-78bae6494551
https://shopee.co.id/Ivory-White-Selop-Sandal-Sepatu-Pesta-Wanita-Block-Heels-5cm-AVEDA-HK6241-i.173501527.6941439891
        </textarea>
        <button type="submit">Test</button>
    </form>
</body>
</html>

<?php

function extract_url($collect) {

    $reqUri = "https://shopee.co.id/api/v4/";
    $imgUri = "https://cf.shopee.co.id/file/";

    // Excel Modules
    require 'vendor/autoload.php';
    $wExcel = new Ellumilel\ExcelWriter();
    $wExcel->setAuthor('Reno Fizaldy');
    $wExcel->writeSheetHeader('Shopee', [
        "SKU"         => "string",
        "CHILD"       => "string",
        "NAMA PRODUK" => "string",
        "HARGA"       => "integer",
        "DISKON"      => "integer",
        "LINK"        => "string",
        "DESKRIPSI"   => "string"
    ]);

    // Buffer Status
    if (ob_get_level() == 0) {
        ob_start();
    }

    foreach($collect as $item) {

        $shopId = $item['shop'];
        $itemId = $item['item'];

        $key      = 'SKU-' . $shopId . '-' . $itemId;

        $reqCall  = $reqUri . "item/get?itemid={$itemId}&shopid={$shopId}";
        $decode   = json_decode(file_get_contents($reqCall), true);
        $data     = $decode['data'];
        $video    = (isset($data['video_info_list'][0]['formats'][0])) ? $data['video_info_list'][0]['formats'][0]['url'] : $data['video_info_list'][0]['default_format']['url'];
        $pathSave = './result/'.$key;
        $varian   = $data['tier_variations'][0]['options'];

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
                $wExcel->writeSheetRow('Shopee', [
                    $key,
                    $key."-".$sku_child,
                    $data['name'] . ' - ' . $varian[$i-1],
                    ($data['models'][$i-1]['price_before_discount'] == 0) ? substr($data['price_before_discount'], 0, -5) : substr($data['models'][$i-1]['price_before_discount'], 0, -5),
                    substr($data['models'][$i-1]['price'], 0, -5),
                    $item['link'],
                    $data['description']
                ]);
            }
        } else {
            $wExcel->writeSheetRow('Shopee', [
                $key,
                null,
                $data['name'],
                ($data['price_before_discount'] !== 0) ? substr($data['price_before_discount'], 0, -5) : substr($data['price'], 0, -5),
                ($data['price_before_discount'] !== 0) ? substr($data['price'], 0, -5) : substr($data['price_before_discount'], 0, -5),
                $item['link'],
                $data['description']
            ]);
        }

        echo "DONE!! {$key} - {$data['name']}<br>";
        // Get Buffering Status
        ob_flush();
        flush();

    }

    $wExcel->writeToFile("./result/result.xlsx");

    // Set to End Buffering Process
    echo "\n\nDownload Complete!";
    ob_end_flush();
}


if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $post_text = preg_split("/\r\n|\n|\r/", $_POST['urlx']);

    $arr = [];
    foreach($post_text as $str) {
        $s_1 = str_replace("https://shopee.co.id/", "", $str);
        $s_2 = substr($s_1, strpos($s_1, ".")+1);
        $s_3 = substr($s_2, strpos($s_2, ".")+1);

        $shopId = strtok($s_2, '.');
        $itemId = strtok($s_3, '?');

        $arr[] = [
            "shop" => $shopId,
            "item" => $itemId,
            "link" => $str
        ];
    }
    // echo "<pre>";
    //     print_r($arr);
    // echo "</pre>";

    extract_url($arr);

}