var admin = {
	// (A) SHOW/HIDE "NOW LOADING" BLOCK
	loading : (show) => {
    var block = document.getElementById("page-loader");
    if (show) { block.classList.add("active"); }
    else { block.classList.remove("active"); }
	},

  // (B) TOGGLE SIDE BAR
	sidebar : () => {
    document.getElementById("page-sidebar").aList.toggle("active");
	},

  // (C) SIGN OFF
	bye : () => {
    swal("Are you sure you want to logout?", {
      buttons: ["NO!", "YES!"],
    })
    .then((willDelete) => {
      if (willDelete) {
        admin.ajax({
          url : url.host + "ajax-session.php",
          data : { req : "out" },
          ok : () => { location.reload(); }
        });
      } else {
        swal("Opps!...... Sorry");
      }
    })
  },

  // (D) AJAX FETCH
  //  url : target url
  //  data : data to send
  //  ok : function to run on server "OK"
  ajax : (opt) => {
    // (D1) FORM DATA
    let data = new FormData();
    for (let [k, v] of Object.entries(opt.data)) { data.append(k, v); }

    // (D2) FETCH
    fetch(opt.url, { method:"POST", body:data })
    .then((res) => {
      if (res.status != 200) {
        alert(`Server ${res.status} error`);
        console.error(res);
      } else { return res.text(); }
    })
    .then((txt) => {
      if (txt != "OK") { swal({
        title: txt,
        text: " ",
        icon: "error",
        buttons: false,
        timer: 1000
      }); }
      else { opt.ok(); }
    })
    .catch((err) => { console.error(err); });
  }
}

