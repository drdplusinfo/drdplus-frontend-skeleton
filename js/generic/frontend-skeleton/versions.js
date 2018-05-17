document.addEventListener('DOMContentLoaded', function () {
    var currentVersion = document.getElementsByClassName('current-version')[0];
    var otherVersions = document.getElementsByClassName('other-versions')[0];
    currentVersion.addEventListener('click', function () {
        console.log(otherVersions.style.display);
        if (otherVersions.style.display === 'none' || !otherVersions.style.display) {
            otherVersions.style.display = 'block';
        } else {
            otherVersions.style.display = 'none';
        }
    });
});