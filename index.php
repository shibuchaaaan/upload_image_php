<?php

ini_set('display_errors', 1);
session_start();
//MAX 1MB(1024KB * 1024なので)
define('MAX_FILE_SIZE', 1 * 1024 * 1024);
//サムネの幅400px指定
define('THUMBNAIL_WIDTH', 400);
//ディレクトリ定義
define('IMAGES_DIR', __DIR__ . '/img');
define('THUMBNAIL_DIR', __DIR__ . '/thumbnails');

//GDがインストールされているかチェック
if (!function_exists('imagecreatetruecolor')) {
    echo 'GD not installed';
    exit;
}

function h($s) {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

//アップロード
require 'ImageUploader.php';
$uploader = new \MyApp\ImageUploader();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $uploader->upload();
}

list($success, $error) = $uploader->getResults();

//表示
$images = $uploader->getImages();

?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Image Uploader</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="btn">
        Upload!
        <form action="" method="POST" enctype="multipart/form-data" id="my_form">
            <!-- 隠し要素でファイルサイズを制限 -->
            <input type="hidden" name="MAX_FILE_SIZE" value="<?php echo h(MAX_FILE_SIZE); ?>">
            <input type="file" name="image" id="my_file">
        </form>
    </div>

    <?php if (isset($success)) : ?>
        <div class="msg success"><?php echo h($success); ?></div>
    <?php endif; ?>
    <?php if (isset($error)) : ?>
        <div class="msg error"><?php echo h($error); ?></div>
    <?php endif; ?>

    <ul>
        <?php foreach ($images as $image) : ?>
        <li>
            <a href="<?php echo h(basename(IMAGES_DIR)) . '/' . h(basename($image)); ?>">
            <img src="<?php echo h($image); ?>" alt="">
            </a>
        </li>
        <?php endforeach; ?>
    </ul>

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
    <script>
    $(function() {
        //メッセージのフェードアウト
        $('.msg').fadeOut(3000);
        //ファイル選択されたら送信する
        $('#my_file').on('change', function(){
            $('#my_form').submit();
        });
    });
    </script>

</body>
</html>