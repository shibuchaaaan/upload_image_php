<?php

namespace MyApp;

class ImageUploader {

    //プロパティ宣言
    private $_imageFileName;
    private $_imageType;

    public function upload() {
        try {
            //エラーチェック
            $this->_validateUpload();
            //画像タイプチェック
            $extention = $this->_validateImageType();
            //拡張子を渡して保存(保存フォルダへのパスが返ってくる)
            $savePath = $this->_save($extention);
            //サムネの作成
            $this->_createThumbnail($savePath);

            $_SESSION['success'] = 'Upload Done!';

        } catch (\Exception $e) {
            $_SESSION['error'] = $e->getMessage();
        }
        //アップし終わったらindexへのリダイレクト
        header('Location: http://' . $_SERVER['HTTP_HOST']);
        exit;
    }

    //結果表示
    public function getResults() {
        $success = null;
        $error = null;
        if (isset($_SESSION['success'])) {
            $success = $_SESSION['success'];
            //$successに入れたのでこっちは消しておく
            unset($_SESSION['success']);
        }
        if (isset($_SESSION['error'])) {
            $error = $_SESSION['error'];
            unset($_SESSION['error']);
        }
        return [$success, $error];
    }

    private function _validateUpload() {
        //imageのファイル・またはデフォで入っているはずのimage,errorが定義されていない場合は例外を投げる
        if (!isset($_FILES['image']) || !isset($_FILES['image']['error'])) {
            throw new \Exception('Upload Error!');
        }
        //image,errorの中身が入っていた場合、中身で場合分け
        switch($_FILES['image']['error']) {
            case UPLOAD_ERR_OK:
                return true;
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                throw new \Exception('File too large!');
            default:
                throw new \Exception('Error: ' . $_FILES['image']['error']);
        }
    }

    private function _validateImageType() {
        //画像の種類を判定
        $this->_imageType = exif_imagetype($_FILES['image']['tmp_name']);
        switch($this->_imageType) {
            case IMAGETYPE_GIF:
                return 'gif';
            case IMAGETYPE_JPEG:
                return 'jpg';
            case IMAGETYPE_PNG:
                return 'png';
            default:
                throw new \Exception('PNG/JPEG/GIF only!');
        }
    }

    private function _save($extention) {
        //画像のファイル名を決める
        $this->_imageFileName = sprintf(
            //現在までの経過秒数_重複しないID.拡張子
            '%s_%s.%s',
            time(),
            sha1(uniqid(mt_rand(),true)), //乱数のuniqidをハッシュ化
            $extention
        );
        //一時保存フォルダ(tmp)に入っている画像を指定の画像保存フォルダに移動させてあげる
        $savePath = IMAGES_DIR . '/' . $this->_imageFileName;
        //move_upload_file(移動前,移動後(ファイル名までの指定が必要))
        $res = move_uploaded_file($_FILES['image']['tmp_name'], $savePath);
        if ($res === false) {
            throw new \Exception('Could not upload!');
        }
        return $savePath;
    }

    private function _createThumbnail($savePath) {
        $imageSize = getimagesize($savePath);
        $width = $imageSize[0];
        $height = $imageSize[1];
        if ($width > THUMBNAIL_WIDTH) {
            $this->_createThumbnailMain($savePath, $width, $height);
        }
    }

    private function _createThumbnailMain($savePath, $width, $height) {
        //拡張子ごとにソースイメージを作る
        switch($this->_imageType) {
            case IMAGETYPE_GIF:
                //新しい画像をパスから作成する
                $srcImage = imagecreatefromgif($savePath);
                break;
            case IMAGETYPE_JPEG:
                $srcImage = imagecreatefromjpeg($savePath);
                break;
            case IMAGETYPE_PNG:
                $srcImage = imagecreatefrompng($savePath);
                break;
        }
        //サムネ幅から固定比で高さを計算
        $thumbHeight = round($height * THUMBNAIL_WIDTH / $width);
        //GDの関数でサムネの枠作成
        $thumbImage = imagecreatetruecolor(THUMBNAIL_WIDTH, $thumbHeight );
        //画像をサンプリングしてイメージの一部をコピー＆伸縮
        imagecopyresampled($thumbImage, $srcImage, 0, 0, 0, 0, THUMBNAIL_WIDTH, $thumbHeight, $width, $height);

        //元の画像ファイルに出力、保存
        switch($this->_imageType) {
            case IMAGETYPE_GIF:
                imagegif($thumbImage, THUMBNAIL_DIR . '/' . $this->_imageFileName);
                break;
            case IMAGETYPE_JPEG:
                imagejpeg($thumbImage, THUMBNAIL_DIR . '/' . $this->_imageFileName);                
                break;
            case IMAGETYPE_PNG:
                imagepng($thumbImage, THUMBNAIL_DIR . '/' . $this->_imageFileName);
                break;
        }
    }

    public function getImages() {
        //実際に表示する画像ディレクトリが集まった配列
        $images = [];
        //imgフォルダから取った画像ファイルのディレクトリの配列(imgに入れられた順に全ての画像が配置されている)
        $files = [];

        $imageDir = opendir(IMAGES_DIR);
        //imgフォルダのディレクトリから1行ずつ読み込んで$fileに代入して、それがfalseでなければ以下の処理
        while (false !== ($file = readdir($imageDir))) {
            //カレントディレクトリを表す'.'や'..'の場合は処理スキップ
            if ($file === '.' || $file === '..') {
                continue;
            }

            $files[] = $file;
            
            //サムネがある場合はサムネのファイルを、なければimg内のファイルを$imagesに入れる
            if (file_exists(THUMBNAIL_DIR . '/' .$file)) {
                $images[] = basename(THUMBNAIL_DIR) . '/' .$file;
            }else {
                $images[] = basename(IMAGES_DIR) . '/' .$file;
            }
        }
        //$filesの順に逆向き(=新しくアップされた順)に$imagesをソートする
        array_multisort($files, SORT_DESC, $images);
        return $images;
    }
}