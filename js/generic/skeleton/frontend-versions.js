document.addEventListener('DOMContentLoaded', function () {
    var currentVersion = document.getElementsByClassName('current-version')[0];
    if (typeof currentVersion === 'undefined') {
        return;
    }
    var otherVersions = document.getElementsByClassName('other-versions')[0];
    if (typeof otherVersions === 'undefined') {
        return;
    }
    currentVersion.addEventListener('click', function () {
        if (otherVersions.style.display === 'none' || !otherVersions.style.display) {
            otherVersions.style.display = 'block';
        } else {
            otherVersions.style.display = 'none';
        }
    });
});