/**
 * sends a request to the specified url from a form. this will change the window location.
 * @param {string} path the path to send the post request to
 * @param {object} params the paramiters to add to the url
 * @param {string} [method=post] the method to use on the form
 */
function postToUpload(path, params, method='post') {
    // The rest of this code assumes you are not using a library.
    // It can be made less wordy if you use one.
    const form = document.createElement('form');
    form.method = method;
    form.action = path;
    for (const key in params) {
        if (params.hasOwnProperty(key)) {
        const hiddenField = document.createElement('input');
        hiddenField.type = 'hidden';
        hiddenField.name = key;
        hiddenField.value = params[key];
        form.appendChild(hiddenField);
        }
    }
    document.body.appendChild(form);
    form.submit();
}

function uploadFile(file, imageName) {
    $("#loadMe").modal({
        backdrop: "static", //remove ability to close modal with click
        keyboard: false, //remove option to close with keyboard
        show: true //Display loader!
    });   

    var destino = "https://script.google.com/macros/s/AKfycbwJ2FCx6osLIolP_3V6_1s01D5XJN3J3sw6_phml12qHjyYa7M/exec";
    var retorno = "http://" + window.location.hostname + "/gallery.php";

    var fr = new FileReader();

    fr.fileName = imageName;
    fr.onload = function(e) {
        e.target.result
        var parametros = {};

        console.log(e.target);

        parametros.data = e.target.result.replace(/^.*,/, '');
        parametros.mimetype = e.target.result.match(/^.*(?=;)/)[0];
        parametros.filename = e.target.fileName;
        parametros.returnpage = retorno;
        //Pego a descrição do campo atual, e limpo todos os pulos de linha que quebram o gallery.php depois na montagem
        parametros.description = document.getElementById("message-text").value.replace(/(\r\n|\n|\r)/gm," s"); 
        console.log("parametros.description: " + parametros.description);


        postToUpload(destino, parametros);           
    }
    fr.readAsDataURL(file);
}

function insertMetadata(dataURL, imageMetadata) {
    try{
        var mediadatafromdrive = imageMetadata;

        var zeroth = {};
        var exif = {};
        var gps = {};
        zeroth[piexif.ImageIFD.Make] = "Make";
        zeroth[piexif.ImageIFD.XResolution] = [777, 1];
        zeroth[piexif.ImageIFD.YResolution] = [777, 1];
        zeroth[piexif.ImageIFD.Software] = "Piexifjs";

        console.log(mediadatafromdrive.time);
        console.log(mediadatafromdrive.location.latitude);
        console.log(mediadatafromdrive.location.longitude);

        exif[piexif.ExifIFD.DateTimeOriginal] = mediadatafromdrive.time;
        exif[piexif.ExifIFD.LensMake] = "LensMake";
        exif[piexif.ExifIFD.Sharpness] = 777;
        exif[piexif.ExifIFD.LensSpecification] = [[1, 1], [1, 1], [1, 1], [1, 1]];
        gps[piexif.GPSIFD.GPSVersionID] = [7, 7, 7, 7];
        gps[piexif.GPSIFD.GPSDateStamp] = mediadatafromdrive.time;

        var lat = mediadatafromdrive.location.latitude;
        var lng = mediadatafromdrive.location.longitude;
        gps[piexif.GPSIFD.GPSLatitudeRef] = lat < 0 ? 'S' : 'N';
        gps[piexif.GPSIFD.GPSLongitudeRef] = lng < 0 ? 'W' : 'E';
        lat = Math.abs(lat);
        lng = Math.abs(lng);
        gps[piexif.GPSIFD.GPSLatitude] = piexif.GPSHelper.degToDmsRational(lat);
        gps[piexif.GPSIFD.GPSLongitude] = piexif.GPSHelper.degToDmsRational(lng);

        var exifObj = {"0th":zeroth, "Exif":exif, "GPS":gps};

        // get exif binary as "string" type
        var exifbytes = piexif.dump(exifObj);

        // insert exif binary into JPEG binary(DataURL)
        var inserted = piexif.insert(exifbytes, dataURL);

        var exifObj = piexif.load(inserted);            
        console.log("exif: " + exifObj);

        //RETORNA IMAGEM COM METADADO: returns JPEG as binary as string.
        return inserted;
    } catch(err){
        console.log("ERRO! :" + err);
        return "ERROR";
    }
}