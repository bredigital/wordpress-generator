window.onload = function () {
	$newmsg = 'WARNING: All sites generated begin with a 60 day expiry. It is your responsibility to ensure the site remains active as long as it is needed, otherwise it will be automatically pruned.';
	document.getElementById('btnCreateNew').onclick = function (e) {
		var r = confirm($newmsg);
		if (r == true) {
			document.getElementById('btnCreateNew').style.display = 'none';
			document.getElementById('btnCreateNewDisabled').style.display = '';
			document.getElementById('btnImportNew').disabled = true;
		}

		return r;
	}
	
	document.getElementById('btnImportNew').onclick = function (e) {
		var r = confirm($newmsg);
		if (r == true) {
			document.getElementById('btnImportNew').style.display = 'none';
			document.getElementById('btnImportNewDisabled').style.display = '';
			document.getElementById('btnCreateNew').disabled = true;
		}

		return r;
	}

	document.getElementById('uplBackup').addEventListener('change', function (e) {
		filename = e.target.files[0].name;
		document.getElementsByClassName('custom-file-label')[0].innerHTML = filename;
	}, false);
}