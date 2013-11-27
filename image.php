<?php

//I will totally improve the performance of this, later

$image = new Imagick('image.png');

// If 0 is provided as a width or height parameter,
// aspect ratio is maintained
//$image->thumbnailImage(100, 0);



//calculate size
$sliceWidth = 32;
$imageHeight = $image->getImageHeight();
$numSlices= $image->getImageWidth() / $sliceWidth;


//decide on num of test points
$testPoints = $imageHeight;

//grab points for each slice
$point = $image->getImagePixelColor(1,1);

//print_r($point->getColor());

$pixelArray = array();
for($x=0;$x<$numSlices;$x++){
    for($y=0;$y<=$testPoints;$y++){
        $left = $image->getImagePixelColor((0+$sliceWidth*$x),(0+(floor($imageHeight/$testPoints))*$y));
        $right = $image->getImagePixelColor(($sliceWidth+$sliceWidth*$x-1),(0+(floor($imageHeight/$testPoints))*$y));
        $pixelArray[$x]['left'][$y] = array($left->getColor(),'x'=>0+$sliceWidth*$x,'y'=>0+floor($imageHeight/$testPoints)*$y);
        $pixelArray[$x]['right'][$y] = array($right->getColor(),'x'=>$sliceWidth+$sliceWidth*$x-1,'y'=>0+floor($imageHeight/$testPoints)*$y);
    }
}
//print_r($pixelArray);
//compare points and create matrix
$scores = array();
$c=0;
foreach($pixelArray as $p){
    //do something
    $r=0;
    $g=0;
    $b=0;
    $r1=0;
    $g1=0;
    $b1=0;
    foreach($p['left'] as $left){
        //do
        $r=$r+$left[0]['r'];
        $g=$g+$left[0]['g'];
        $b=$b+$left[0]['b'];
    }

    foreach($p['right'] as $right){
        //do
        $r1=$r1+$right[0]['r'];
        $g1=$g1+$right[0]['g'];
        $b1=$b1+$right[0]['b'];
    }
    $scores[$c]=array($r=>$r1,$g=>$g1,$b=>$b1);
    //scores[slice] = left => right
    $c++;
}
//print_r($scores);

/*
DiffMatrix
 [right][left]=score
*/
$diffMatrix2 = array();
for($counter1=0;$counter1<$numSlices;$counter1++){
    for($counter2=0;$counter2<$numSlices;$counter2++){

        if($counter1!=$counter2){
            //get each pixel to pixel difference
            $totalSliceDiff = 0;

            for($x=0;$x<$imageHeight;$x++){
                //get right side
                $rightPixel = $pixelArray[$counter1]['right'][$x];

            
                //get left side
                $leftPixel = $pixelArray[$counter2]['left'][$x];
                //if($x==1)
                    //echo '\n'.$rightPixel[0]['r'] .' ' . $leftPixel[0]['r'];
            
                //compare and add to total
                $totalSliceDiff = $totalSliceDiff + abs($leftPixel[0]['r']-$rightPixel[0]['r']);
                $totalSliceDiff = $totalSliceDiff + abs($leftPixel[0]['g']-$rightPixel[0]['g']);
                $totalSliceDiff = $totalSliceDiff + abs($leftPixel[0]['b']-$rightPixel[0]['b']);



            }
            $diffMatrix2[$counter1][$counter2]=$totalSliceDiff;
            //echo "\nCounter1 = $counter1 and Counter2 = $counter2 and totalDiff = $totalSliceDiff";
        }
    }
}


$diffMatrix = array();
$count = count($scores);
for($counter1=0;$counter1<$count;$counter1++){
    for($counter2=0;$counter2<$count;$counter2++){
        //calc differences
        if($counter1!=$counter2){
            $diffMatrix[$counter1][$counter2]=calcDiff($scores[$counter1],$scores[$counter2]);
        }
    }
}
//print_r($diffMatrix);
$d2sum=array();
$d2count=0;
foreach($diffMatrix2 as $d2){
    $d2sum[$d2count]=0;
    $sum=0;
    foreach($d2 as $k => $v){
         $sum = $sum+ $d2sum[$d2count] + $v;
    }
    $d2sum[$d2count]=$sum/19;
    $d2count++;
}
//print_r($d2sum);
//print_r($diffMatrix2);
$diffMatrix = $diffMatrix2;


//calc lowest diff for each
$lowestDiff = array();
$count = 0;
foreach($diffMatrix as $d){
    //find lowest
    $lowLoc = getLowest($d,"loc");
    $lowVal = getLowest($d);
    $lowestDiff[$count]['loc']=$lowLoc;
    $lowestDiff[$count]['val']=$lowVal;
    $count++;
}
//print_r($lowestDiff);

//test distance of lowest from average
$c=0;
foreach($lowestDiff as $ld){
    //subtract average from LD
    //echo "<br>\nAverage " . $d2sum[$c] . ' from Lowest' . $ld['val'] . ' =  ' . ($d2sum[$c] - $ld['val']);
    $c++;
}

//find highest right match (probably right end of image)
$highest=0;
$highestLoc=0;
$count=0;
foreach($lowestDiff as $d){
    //find highest
    if($highest==0){
        $highest=$d['val'];
    }
    elseif($d['val']>$highest){
        $highest=$d['val'];
        $highestLoc=$count;
    }
    $count++;
}
//CHEATING! $highestLoc=9;

//echo "\nHighest: $highest at loc $highestLoc";

//work backwards from highest right match

$buildSumArray = array();
for($seed=0;$seed<$numSlices;$seed++){
    $order = array($seed);
    $buildSum = 0;
    for($x=0;$x<($numSlices-1);$x++){
        $left = buildOrder($order[0],$order,$diffMatrix);
        /*$left = findLeft($lowestDiff,$order[0]);
        echo '\nOrder: '.$order[0];*/
        array_unshift($order,$left[0]);
        $buildSum = $buildSum + $left[1];
    }
    $buildSumArray[$seed]=$buildSum;
}
//print_r($buildSumArray);
//print_r($order);

$order=array(findLowestNum($buildSumArray));
//print_r($order);
    for($x=0;$x<($numSlices-1);$x++){
        $left = buildOrder($order[0],$order,$diffMatrix);
        /*$left = findLeft($lowestDiff,$order[0]);
        echo '\nOrder: '.$order[0];*/
        array_unshift($order,$left[0]);
    }


//cut image into slices
$imageSlices = array();
for($x=0;$x<$numSlices;$x++){
    $xloc=0+$sliceWidth*$x;
    $yloc=0;
    $temp = $image->clone();
    $imageSlices[$x] = $temp->cropImage($sliceWidth,$imageHeight,$xloc,$yloc);
}

//create new image
$newImage = new Imagick();
$newImage->newImage($sliceWidth * $numSlices, $imageHeight, new ImagickPixel('red'));
$newImage->setImageFormat('png');

//put together most likely pieces and reassemble image

$newIM = new Imagick();
foreach($order as $o){
    //echo '\nO = '.$o;
    $xloc=0+$sliceWidth*$o;
    $yloc=0;
    $temp = new Imagick();
    $temp = $image->clone();
    $temp->cropImage($sliceWidth,$imageHeight,$xloc,$yloc);
    $newIM->newImage($sliceWidth,$imageHeight, "red");
    $newIM->setImage($temp);
    //$newImage->compositeImage($imageSlices[$o],Imagick::COMPOSITE_DEFAULT,$xloc,$yloc);
}
$newIM->resetIterator();
$combined = new Imagick();
$combined = $newIM->appendImages(false);
$combined->newImage($sliceWidth*$numSlices,$imageHeight,"blue");
$combined->setImage($image);
$combined->resetIterator();
$combined2=$combined->appendImages(true);
/* Output the image */
$combined2->setImageFormat("png");

header('Content-type: image/png');
echo $combined2;


function findLowestNum($arr){
    //find lowest number in array and returns location
    $count=0;
    $lowest = 0;
    $lowestLoc=0;
    foreach($arr as $a){
        if($count==0){
            $lowest=$a;
            //echo "\n<br>Init";
        }
        if($a<$lowest){
            //echo "\n<br>New Lowest: $a is lower than $lowest";
            $lowest=$a;
            $lowestLoc=$count;
        }
        $count++;

    }
    return $lowestLoc;
}

function buildOrder($rightMatch,$alreadyFound,$searchArray){
    //find left match of $rightMatch, excluding locations in already found, in $searchArray (diffMatrix)
    $bestLoc=0;
    $bestVal=9999999999999;
    foreach($searchArray as $k =>$v){
        //make sure not found
        $checkExists = array_search($k,$alreadyFound);
        if($checkExists===0||$checkExists>0){
            //do nothing, already exists
        }else{
            //do search
            foreach($v as $k2 => $v2){
                //search for match
                if($k2==$rightMatch&&$v2<$bestVal){
                    //update best find
                    $bestLoc=$k;
                    $bestVal=$v2;
                }

            }
        
        }
    }
    return array($bestLoc,$bestVal);
}



function findLeft($arr,$loc){
    //find best left match
    $bestLoc=-1;
    $bestVal=999999999999;
    foreach($arr as $k => $a){
        if($a['loc']==$loc){
            //echo "\nMATCH " . $a['val'] . ' ' . $a['loc'];
            if($a['val']<$bestVal){
                //echo "\nNEW BEST $bestVal > " . $a['val'];
                $bestLoc = $k;
                $bestVal=$a['val'];
            }
        }
    }
    return $bestLoc;
}

function getLowest($arr,$type = "val"){
    //print_r($arr);
    $lowest=9999999999;
    $lowestLoc=0;
    $x=0;
    foreach($arr as $k => $v){
        //
        if($v<$lowest){
            $lowest=$v;
            $lowestLoc=$x;
        }
        $x++;
    }
    if($type=="loc"){
        return $lowestLoc;
    }
    else{
        return $lowest;
    }

}



function calcDiff($arr1,$arr2){
    $rgb1 = array();
    $rbg2 = array();

    //calc diff between arr1 (right) and arr2 (left)
    foreach($arr1 as $a1){
        //get right side
        $rgb1[]=$a1;
    }
    foreach($arr2 as $a2 => $val){
        //get left side
        $rgb2[]=$a2;
    }
    $diff = 0;
    $diff = $diff+ abs($rgb1[0]-$rgb2[0]);
    $diff = $diff+ abs($rgb1[1]-$rgb2[1]);
    $diff = $diff+ abs($rgb1[2]-$rgb2[2]);
    return $diff;

}




?>
