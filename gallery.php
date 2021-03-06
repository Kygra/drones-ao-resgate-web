<?php

if( ini_get('allow_url_fopen') ) {
	//die('allow_url_fopen is enabled. file_get_contents should work well');
	function get_url_content($request)
		{
			return file_get_contents($request);
		}
} else {
	//die('allow_url_fopen is disabled. file_get_contents would not work');
	function get_url_content($request)
		{
		$ch = curl_init($request);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		return curl_exec($ch);
		}
	}

// global consts
$apiKey = 'AIzaSyDy3-H6gDfiq4zEq10JuRoJZUQISVWFR2s'; // googleApi key
$defaultFolderId = '1KujX9HlGo-gvHJcpNB9RKBkt5wN44MA-'; // id of photos root dir
$defaultPath = 'http://drones-ao-resgate.appspot.com/gallery.php'; // default path of this gdrive_nanogallery.php file
$defaultFolderImageUrl = ''; // url of png or jpg for default thumbnail
$showThumbnail = false;
$thumbnailHeight = 300;
$imageHeight = 700;
$imageHeightXS = 400;
$imageHeightSM = 600;
$imageHeightME = 750;
$imageHeightLA = 1000;
$imageHeightXL = 1400;

function retrieveFilesArray($folderId, $apiKey){
  // returns all files in GDrive folder
  $request = 'https://www.googleapis.com/drive/v3/files?pageSize=999&orderBy=name&q=%27'.$folderId.'%27+in+parents&fields=files(id%2CimageMediaMetadata%2Ftime%2CimageMediaMetadata%2Flocation%2CmimeType%2Cname%2CmodifiedTime%2Cdescription)&key='.$apiKey;
  $response = get_url_content($request);
  //echo $response;
  $response = json_decode($response, $assoc = true);
  $fileArray = $response['files'];
  return $fileArray;
}

function retrieveOneFileArray($folderId, $apiKey){ 
  // returns one file in GDrive folder
  $request = 'https://www.googleapis.com/drive/v3/files?pageSize=1&q=%27'.$folderId.'%27+in+parents&fields=files(id%2CimageMediaMetadata%2Ftime%2CimageMediaMetadata%2Flocation%2CmimeType%2Cname%2CmodifiedTime)&key='.$apiKey;
  $response = get_url_content($request);
  $response = json_decode($response, $assoc = true);
  $fileArray = $response['files'];
  return $fileArray;
}

function filterByMimeType($fileArray, $mimeType){ 
  // returns array of files only with mimeType
  $filteredFileArray = [];
  foreach($fileArray as $file){
    if (strpos($file['mimeType'], $mimeType) !== false){
      $filteredFileArray[] = $file;
    }
  }
  return $filteredFileArray;
}

function getfileIds($fileArray){ 
  // returns array of all IDs from input array
  $imageIdsArray = [];
  foreach($fileArray as $file){
    $imageIdsArray[] = $file['id'];
  }
  return $imageIdsArray;
}

//ADAPTAÇÕES
function getfileNames($fileArray){ 
  // returns array of all NAMES from input array
  $imageNamesArray = [];
  foreach($fileArray as $file){
    $imageNamesArray[] = $file['name'];
  }
  return $imageNamesArray;
}
function getfileCreateDates($fileArray){ 
  // returns array of all CREATE DATES from input array
  $imageDatesArray = [];
  foreach($fileArray as $file){
    $imageDatesArray[] = $file["imageMediaMetadata"]["time"];
  }
  return $imageDatesArray;
}
function getfileModifiedDates($fileArray){ 
  // returns array of all MODIFIED DATES from input array
  $imageDatesArray = [];
  date_default_timezone_set('America/Sao_Paulo');
  foreach($fileArray as $file){
    $imageDatesArray[] = date("d/m/Y H:i:s", strtotime($file["modifiedTime"]));
  }
  return $imageDatesArray;
}
function getfileLocationsLat($fileArray){ 
  // returns array of all LOCATIONS (LAT) from input array
  $locationsArray = [];
  foreach($fileArray as $file){
    $locationsArray[] = $file["imageMediaMetadata"]["location"]["latitude"];
  }
  return $locationsArray;
}
function getfileLocationsLong($fileArray){ 
  // returns array of all LOCATIONS (LONG) from input array
  $locationsArray = [];
  foreach($fileArray as $file){
    $locationsArray[] = $file["imageMediaMetadata"]["location"]["longitude"];
  }
  return $locationsArray;
}
function getfileDescriptions($fileArray){ 
  // returns array of all DESCRIPTIONS from input array
  $descriptionsArray = [];
  foreach($fileArray as $file){
    $descriptionsArray[] = $file["description"];
  }
  return $descriptionsArray;
}
//

function orderImagesByTime($imageArray){ 
  // returns array of images sorted by the date of picture is taken and name
  $sortingArray = array();
  foreach ($imageArray as $key => $image) {
    //$sortingArray["time"][$key] = $image["imageMediaMetadata"]["time"];    
    $sortingArray["time"][$key] = strtotime($image["modifiedTime"]);
    $sortingArray["name"][$key] = $image["name"];
  }
  array_multisort($sortingArray["time"], SORT_DESC, $sortingArray["name"], SORT_ASC, $imageArray);

  return $imageArray;
}

function retrieveImageIds($folderId, $apiKey){
  // returns all images in folder
  $fileArray = retrieveFilesArray($folderId, $apiKey);
  $filteredFileArray = orderImagesByTime(filterByMimeType($fileArray, "image/"));
  return getfileIds($filteredFileArray);
}

function retrieveOneImageId($folderId, $apiKey){
  // returns only one image for the folder (used for thumbnails)
  $fileArray = retrieveOneFileArray($folderId, $apiKey);
  $filteredFileArray = filterByMimeType($fileArray, "image/");
  return getfileIds($filteredFileArray);
}

function retrieveSubfolderArray($folderId, $apiKey){ 
  // returns array of all subfolders in folder
  $fileArray = retrieveFilesArray($folderId, $apiKey);
  $subfolderArray = filterByMimeType($fileArray, "folder");
  return $subfolderArray;
}

function retrieveFileName($fileId, $apiKey){
  // returns filename or dirname for input ID
  $request = "https://www.googleapis.com/drive/v3/files/$fileId?key=$apiKey";
  $response = get_url_content($request);
  $response = json_decode($response, $assoc = true);
  $fileName = $response['name'];
  return $fileName;
}

function debug_to_console( $data ) {
  $output = $data;
  if ( is_array( $output ) ){
      echo '<pre>'; print_r($output); echo '</pre>'; //$output = implode( ',', $output);
  }
  else {
    echo "<script>console.log( 'Debug Objects: " . $output . "' );</script>";
  }
}

// process variables from GET
//if($_GET['folderId'] != ""){
//  $folderId = $_GET['folderId'];
//}
//else{
  $folderId = $defaultFolderId;
//}

//if($_GET['homeId'] != ""){
//  $homeId = $_GET['homeId'];
//}
//else{
  $homeId = $folderId;
//}

// retrive data
//$folderName = retrieveFileName($folderId, $apiKey);
$folderName = 'Drones ao Resgate - Galeria';
$subfolderArray = retrieveSubfolderArray($folderId, $apiKey);
$imageIds = retrieveImageIds($folderId, $apiKey);

// START OF HTML ----------------------------------------------------------------------------------------------
?> 

<html lang="en">
  <head>
    <title><?= $folderName ?></title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="http://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
    <script src="http://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
    <!-- Add jQuery library (MANDATORY) -->
    <script type="text/javascript" src="nanogallery/third.party/jquery-1.7.1.min.js"></script> 
    <!-- Add nanoGALLERY plugin files (MANDATORY) -->
    <link href="nanogallery/css/nanogallery.css" rel="stylesheet" type="text/css">
    <link href="nanogallery/css/themes/light/nanogallery_light.css" rel="stylesheet" type="text/css">
    <script type="text/javascript" src="nanogallery/jquery.nanogallery.js"></script>

    <link href="https://fonts.googleapis.com/css?family=Lato" rel="stylesheet">    

    <link href="styles/default.css" rel="stylesheet" type="text/css" media="screen">
  </head>

  <body>
    <div class="roundcontent" style="width: 96%; margin: 2%; margin-top: 10px;">
      <div class='divHome'>
        <h1 class="h1Home" onclick="window.location.href='index.html'">Drones ao Resgate</h1>
        <button class="btnHome" onclick="window.location.href='gallery.php'">Galeria</button>
        <button class="btnHome" onclick="window.location.href='maps.html'">Mapa</button>
      </div>
      <div class='home'>
        <h1>Galeria de imagens compartilhada</h1>
        <p>As imagens são apresentadas de acordo com a última modificação, da mais <b>recente</b> para a mais <b>antiga</b>.</p>
        <p id="instructionMobile">Instrução para usuários mobile:<br>Mantenha seu aparelho na horizontal para melhor visualização.</p>
      </div>
      <?php
      if($homeId !== $folderId){
        echo "<h4 class='title'>".$folderName."</h4>";
        echo "<a href='".$defaultPath."?folderId=".$homeId."'><div class='back'><< zpátky</div></a><br>";
      }
      foreach($subfolderArray as $subfolder){
        echo "<div class='ngal_album'>";
            echo "<a href='".$defaultPath."?folderId=".$subfolder['id']."&homeId=".$homeId."'>";
        $thumbnailId = retrieveOneImageId($subfolder['id'], $apiKey)[0];
        $thumbnailWidth = 200;
        if (empty($thumbnailId)) {
          $thumbnailSrc = $defaultFolderImageUrl;
        } else {
          $thumbnailSrc = "https://drive.google.com/thumbnail?authuser=0&sz=w".$thumbnailWidth."&id=".$thumbnailId;
        }
        if (showThumbnail){
          echo "<div class='ngal_foto'><img src='".$thumbnailSrc."' width='".$thumbnailWidth."'></div>";
        }
        echo "<div class='ngal_content'>";
        //echo "<a href='".$defaultPath."?folderId=".$subfolder['id']."&homeId=".$homeId."'>".$subfolder['name']."</a>";
        echo "<div class='album-name'>".$subfolder["name"]."</div>";
        echo "</div>";
        echo "</a>";
        echo "</div>";
      }
      ?>
      <div id="nanoGalleryWrapperDrive"></div>
    </div>
  </body>
</html>


<!--JAVASCRIPT CODE-->
<script type="text/javascript">
  
  jQuery(document).ready(function () {
    jQuery("#nanoGalleryWrapperDrive").nanoGallery({
      items: [
            <?php //PHP code -----------------------------------------------------------------------------------
            
            //
            $fileArrayToDisplay = retrieveFilesArray($folderId, $apiKey);
            $filteredFileArrayToDisplay = orderImagesByTime(filterByMimeType($fileArrayToDisplay, "image/"));
            $fileArrayNamesToDisplay = getfileNames($filteredFileArrayToDisplay);
            $fileArrayCreateDatesToDisplay = getfileCreateDates($filteredFileArrayToDisplay);
            $fileArrayModifiedDatesToDisplay = getfileModifiedDates($filteredFileArrayToDisplay);
            $fileArrayLocationLatitudeToDisplay = getfileLocationsLat($filteredFileArrayToDisplay);            
            $fileArrayLocationLongitudeToDisplay = getfileLocationsLong($filteredFileArrayToDisplay); 
            $fileArrayDescriptionsToDisplay = getfileDescriptions($filteredFileArrayToDisplay);            

            $counterLoop = 0;
            //

            foreach($imageIds as $id) {
              echo "{"."\r\n";
              echo "  src: 'https://drive.google.com/thumbnail?authuser=0&sz=h".$imageHeight."&id=".$id."',\r\n";
              echo "  srcXS: 'https://drive.google.com/thumbnail?authuser=0&sz=h".$imageHeightXS."&id=".$id."',\r\n";
              echo "  srcSM: 'https://drive.google.com/thumbnail?authuser=0&sz=h".$imageHeightSM."&id=".$id."',\r\n";
              echo "  srcME: 'https://drive.google.com/thumbnail?authuser=0&sz=h".$imageHeightME."&id=".$id."',\r\n";
              echo "  srcLA: 'https://drive.google.com/thumbnail?authuser=0&sz=h".$imageHeightLA."&id=".$id."',\r\n";
              echo "  srcXL: 'https://drive.google.com/thumbnail?authuser=0&sz=h".$imageHeightXL."&id=".$id."',\r\n";
              echo "  srct: 'https://drive.google.com/thumbnail?authuser=0&sz=h".$thumbnailHeight."&id=".$id."',\r\n";

              if($fileArrayDescriptionsToDisplay[$counterLoop]){
                echo "  title: '".$fileArrayNamesToDisplay[$counterLoop]." - Comentário: ".$fileArrayDescriptionsToDisplay[$counterLoop]."',\r\n";
              } else {
                echo "  title: '".$fileArrayNamesToDisplay[$counterLoop]."',\r\n";
              }
              //description é exibido no hover na galeria, e na imagem aberta
              //echo "  description : 'Criado em: ".$fileArrayCreateDatesToDisplay[$counterLoop]."',\r\n";
              echo "  description : 'Modificado em: ".$fileArrayModifiedDatesToDisplay[$counterLoop]."',\r\n"; 
              echo "  customData : { latitude: '".$fileArrayLocationLatitudeToDisplay[$counterLoop]."' , longitude : '".$fileArrayLocationLongitudeToDisplay[$counterLoop]."'},\r\n";
              echo "},\r\n";
              $counterLoop += 1; 
              //
            }

            //TESTE
            //debug_to_console($fileArrayToDisplay);

            // END OF PHP -----------------------------------------------------------------------------------------
            ?>
        ],
      thumbnailWidth: 350,
      thumbnailHeight: 'auto',
      theme: 'light',
      colorScheme: 'none',
      thumbnailHoverEffect: [{ name: 'labelAppear75', duration: 300 }],
      thumbnailGutterWidth : 0,
      thumbnailGutterHeight : 0,
      slideshowDelay: 5000,
      //i18n: { thumbnailImageDescription: 'Image', thumbnailAlbumDescription: 'Album' },
      thumbnailLabel: { display: true, position: 'overImageOnMiddle', align: 'center' }

    });
  });
</script>
