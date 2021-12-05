<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    <h3>Format: SKU, Link <small>"Separated by break line"</small></h3>
    <form name="form1" method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>" >
        <textarea name="urlx" id="" cols="30" rows="10"></textarea>
        <br>
        <button type="submit">Test</button>
    </form>
    <hr>
</body>
</html>

<?php

set_time_limit(300);

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $preg = preg_split("/\r\n|\n|\r/", trim($_POST['urlx']));

    $rest = [];

    foreach($preg as $arr) {

        list($key, $value) = explode(',', $arr);

        $rest[$key] = $value;

    }

    execution($rest);

}

function rrmdir($dir) { 
    if (is_dir($dir)) { 
        $objects = scandir($dir);
        foreach ($objects as $object) { 
            if ($object != "." && $object != "..") { 
                if (is_dir($dir. DIRECTORY_SEPARATOR .$object) && !is_link($dir."/".$object))
                rrmdir($dir. DIRECTORY_SEPARATOR .$object);
                else
                unlink($dir. DIRECTORY_SEPARATOR .$object); 
            } 
        }
        rmdir($dir); 
    }
}

function extract_link($str) {
    $s_1 = str_replace("https://shopee.co.id/", "", $str);
    $s_2 = substr($s_1, strpos($s_1, "i.")+2);
    $s_3 = substr($s_2, strpos($s_2, ".")+1);

    $shopId = strtok($s_2, '.');
    $itemId = strtok($s_3, '?');

    return [
        "shop" => $shopId,
        "item" => $itemId,
        "link" => $str
    ];
}

function get_models_price($search, $data) {
    $models = $data['models'];
    $return = [];
    foreach($models as $k=>$v) {
        if ($search == $v['name']) {
            $return = [
                'price'    => substr($v['price'], 0, -5),
                'price_bf' => substr($v['price_before_discount'], 0, -5),
                'stock'    => $v['stock']
            ];
            break;
        }
    }
    return $return;
}

function execution($itemId) {
    $reqUri = "https://shopee.co.id/api/v4/";
    $imgUri = "https://cf.shopee.co.id/file/";

    rrmdir("result");
    mkdir("result");

    // Excel Modules
    require 'vendor/autoload.php';
    $wExcel = new Ellumilel\ExcelWriter();
    $wExcel->setAuthor('Reno Fizaldy');
    $wExcel->writeSheetHeader('Shopee', [
        "SKU"         => "string",
        "CHILD"       => "string",
        "NAMA PRODUK" => "string",
        "VARIAN"      => "string",
        "HARGA"       => "integer",
        "DISKON"      => "integer",
        "LINK"        => "string",
        "SOLD"        => "integer",
        "DESKRIPSI"   => "string"
    ]);

    // Buffer Status
    if (ob_get_level() == 0) {
        ob_start();
    }

    $x = 0;
    foreach($itemId as $key=>$item) {

        $e_link   = extract_link($item);
        $reqCall  = $reqUri . "item/get?itemid={$e_link['item']}&shopid={$e_link['shop']}";
        $decode   = json_decode(file_get_contents($reqCall), true);
        $data     = $decode['data'];
        $video    = (isset($data['video_info_list'][0]['formats'][0])) ? $data['video_info_list'][0]['formats'][0]['url'] : $data['video_info_list'][0]['default_format']['url'];
        $pathSave = './result/'.$key;
        $varian   = $data['tier_variations'][0]['options'];

        // header('Content-Type: application/json');
        // $json2 = json_encode($data, JSON_PRETTY_PRINT);
        // echo $json2;

        // echo "<pre>";
        // print_r($data['models']);
        // echo "</pre>";
        // echo get_models_price('4 gold', $data);
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
                if ($i < 10) {
                    $var_img_path = $pathSave.'/00'.$i;
                } 
                else if ($i >= 10 && $i < 100) {
                    $var_img_path = $pathSave.'/0'.$i;
                } 
                else {
                    $var_img_path = $pathSave.'/'.$i;
                }
                $var_img = $data['tier_variations'][0]['images'][$i-1];
                mkdir($var_img_path, 0777, true);
                file_put_contents($var_img_path."/{$var_img}.jpg", file_get_contents($imgUri.$var_img));
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
                $get_models_price = get_models_price($varian[$i-1], $data);
                $wExcel->writeSheetRow("Shopee", [
                    $key,
                    $key."-".$sku_child,
                    $data['name'],
                    $varian[$i-1],
                    ($get_models_price['price_bf'] == 0) ? substr($data['price_before_discount'], 0, -5) : $get_models_price['price_bf'],
                    $get_models_price['price'],
                    $e_link['link'],
                    $data['historical_sold'],
                    $data['description']
                ]);
            }
        } else {
            $wExcel->writeSheetRow("Shopee", [
                $key,
                null,
                $data['name'],
                null,
                ($data['price_before_discount'] !== 0) ? substr($data['price_before_discount'], 0, -5) : substr($data['price'], 0, -5),
                ($data['price_before_discount'] !== 0) ? substr($data['price'], 0, -5) : substr($data['price_before_discount'], 0, -5),
                $e_link['link'],
                $data['historical_sold'],
                $data['description']
            ]);
        }

        echo "DONE!! {$key} - {$data['name']}<br>";
        $x++;
        // Get Buffering Status
        ob_flush();
        flush();

    }

    $wExcel->writeToFile("./result/result.xlsx");

    // Set to End Buffering Process
    echo "<br><br><b>{$x} Download Complete!</b>";
    ob_end_flush();
}