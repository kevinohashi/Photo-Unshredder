<?php
$newImage = new Imagick();
$newImage->newImage(50, 50, new ImagickPixel('red'));
$newImage->setImageFormat('png');
header('Content-type: image/png');
//echo $newImage;
$image = new Imagick('image.png');
$temp = new Imagick();
$temp = $image->clone();
$temp->cropImage(32,359,0,0);
$temp->setImageFormat('png');
    $newIM = new Imagick();
    $newIM->newImage(32,359, "red");
    $newIM->setImage($temp);
    $newIM->setImageFormat('png');
    $newIM->newImage(32,359, "blue");
    //$newIM->setImage($temp);
    $newIM->setImageFormat('png');
    $newIM->resetIterator();
$combined = $newIM->appendImages(false);
$combined->setImageFormat("png");
echo $combined;

?>
