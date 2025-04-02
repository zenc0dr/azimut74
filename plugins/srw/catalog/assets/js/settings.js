$(window).load(function(){
    checkNewVersion();
});

function checkNewVersion(){
    var xhr = new XMLHttpRequest();
    xhr.open('GET', 'http://october-studio.ru/api/v1/version/get');
    xhr.onload = function (e) {
        if (xhr.readyState == 4 && xhr.status == 200) {
            showNewVersion(xhr.responseText);
        }
    };
    xhr.send(null);
}

function showNewVersion(version){
    $('#VersionPreloader').html(version);
}
