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
    var destino = "https://script.google.com/macros/s/AKfycbwJ2FCx6osLIolP_3V6_1s01D5XJN3J3sw6_phml12qHjyYa7M/exec";
    var retorno = window.location.hostname + "/gallery.php";

    var fr = new FileReader();

    fr.fileName = imageName;
    fr.onload = function(e) {
        e.target.result
        var parametros = {};
        parametros.data = e.target.result.replace(/^.*,/, '');
        parametros.mimetype = e.target.result.match(/^.*(?=;)/)[0];
        parametros.filename = e.target.fileName;
        parametros.returnpage = retorno;

        postToUpload(destino, parametros);           
    }
    fr.readAsDataURL(file);
}