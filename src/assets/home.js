window.onload = function () {
	$newmsg = 'WARNING: All sites generated begin with a 60 day expiry. It is your responsibility to ensure the site remains active as long as it is needed, otherwise it will be automatically pruned.';
	document.getElementById("btnCreateNew").onclick = function (e) {
		return confirm($newmsg);
	}
	
	document.getElementById("btnImportNew").onclick = function (e) {
		return confirm($newmsg);
	}
	
	objCreate = [ document.getElementById("frmCreate"), document.getElementById("btnCreate") ];
	objImport = [ document.getElementById("frmImport"), document.getElementById("btnImport") ];
	document.getElementById("btnCreate").onclick = function (e) {
		e.preventDefault();
		objCreate[0].style.display = "";
		objImport[0].style.display = "none";
		objCreate[1].classList.add("active");
		objImport[1].classList.remove("active");
	}

	document.getElementById("btnImport").onclick = function (e) {
		e.preventDefault();
		objCreate[0].style.display = "none";
		objImport[0].style.display = "";
		objCreate[1].classList.remove("active");
		objImport[1].classList.add("active");
	}

	document.getElementById('uplBackup').addEventListener('change', function (e) {
		filename = e.target.files[0].name;
		document.getElementsByClassName('custom-file-label')[0].innerHTML = filename;
	}, false);
}