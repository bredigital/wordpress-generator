window.onload = function () {
	$newmsg = 'WARNING: All sites generated begin with a 60 day expiry. It is your responsibility to ensure the site remains active as long as it is needed, otherwise it will be automatically pruned.';
	document.getElementById("btnCreateNew").onclick = function (e) {
		return confirm($newmsg);
	}
	
	document.getElementById("btnImportNew").onclick = function (e) {
		return confirm($newmsg);
	}

	document.getElementById('uplBackup').addEventListener('change', function (e) {
		filename = e.target.files[0].name;
		document.getElementsByClassName('custom-file-label')[0].innerHTML = filename;
	}, false);
}